#!/usr/bin/env bash
# OneBoard — Moodle container entrypoint for Railway.
# Builds config.php from env, points Apache at public/ on $PORT,
# installs the DB schema on first boot and upgrades on later boots.
set -euo pipefail

APP=/var/www/html
DATAROOT="${MOODLE_DATAROOT:-/var/moodledata}"
PORT="${PORT:-8080}"

run_as_www() { su -s /bin/bash -c "$1" www-data; }

echo "[entrypoint] preparing OneBoard Moodle..."

# 0. Derive discrete DB/Redis vars from Railway connection URLs when the
#    individual PG*/REDIS* vars are not set. Exported here so both the CLI
#    install below and the Apache/PHP process (via exec) see them.
if [ -n "${DATABASE_URL:-}" ]; then
    _u="${DATABASE_URL#*://}"; _cred="${_u%%@*}"; _hp="${_u#*@}"
    export PGUSER="${_cred%%:*}"
    export PGPASSWORD="${_cred#*:}"
    _db="${_hp#*/}"; export PGDATABASE="${_db%%\?*}"
    _hostport="${_hp%%/*}"
    export PGHOST="${_hostport%%:*}"
    _port="${_hostport#*:}"; [ "$_port" = "$_hostport" ] && _port=5432
    export PGPORT="$_port"
    echo "[entrypoint] parsed DATABASE_URL -> host=$PGHOST db=$PGDATABASE user=$PGUSER"
fi
if [ -n "${REDIS_URL:-}" ]; then
    _u="${REDIS_URL#*://}"; _cred="${_u%%@*}"; _hp="${_u#*@}"
    export REDISPASSWORD="${_cred#*:}"
    export REDISHOST="${_hp%%:*}"
    _rp="${_hp#*:}"; export REDISPORT="${_rp%%/*}"
    echo "[entrypoint] parsed REDIS_URL -> host=$REDISHOST port=$REDISPORT"
fi

# 1. moodledata lives on the Railway volume (outside the web root)
mkdir -p "$DATAROOT"
chown -R www-data:www-data "$DATAROOT"
chmod -R 0770 "$DATAROOT"

# 2. Install the env-driven config.php (root config, loaded by public/config.php)
cp "$APP/docker/moodle-config.php" "$APP/config.php"
chown www-data:www-data "$APP/config.php"

# 3. Point Apache at public/ and listen on Railway's $PORT
cat > /etc/apache2/ports.conf <<EOF
Listen ${PORT}
EOF
cat > /etc/apache2/sites-available/000-default.conf <<EOF
<VirtualHost *:${PORT}>
    DocumentRoot ${APP}/public
    <Directory ${APP}/public>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# 4. Wait for PostgreSQL to accept connections
echo "[entrypoint] waiting for database ${PGHOST:-localhost}:${PGPORT:-5432}..."
for i in $(seq 1 30); do
    if php -r '$c=@pg_connect("host=".getenv("PGHOST")." port=".(getenv("PGPORT")?:5432)." dbname=".getenv("PGDATABASE")." user=".getenv("PGUSER")." password=".getenv("PGPASSWORD")); exit($c?0:1);' 2>/dev/null; then
        echo "[entrypoint] database is up"; break
    fi
    echo "  ...retry $i/30"; sleep 2
done

# 5. Install schema on first boot, otherwise run pending upgrades.
#    Non-fatal: if this fails we still start Apache so the error is visible
#    in the browser / logs instead of crashlooping the container.
cd "$APP"
set +e
if run_as_www "php admin/cli/cfg.php --name=version" >/dev/null 2>&1; then
    echo "[entrypoint] existing install detected — running upgrade"
    run_as_www "php admin/cli/upgrade.php --non-interactive"
    run_as_www "php admin/cli/purge_caches.php"
else
    echo "[entrypoint] fresh database — installing Moodle"
    if [ -z "${MOODLE_ADMIN_PASS:-}" ]; then
        echo "[entrypoint] WARNING: MOODLE_ADMIN_PASS not set — skipping install"
    else
        run_as_www "php admin/cli/install_database.php \
            --lang='${MOODLE_LANG:-en}' \
            --adminuser='${MOODLE_ADMIN_USER:-admin}' \
            --adminpass='${MOODLE_ADMIN_PASS}' \
            --adminemail='${MOODLE_ADMIN_EMAIL:-admin@example.com}' \
            --supportemail='${MOODLE_ADMIN_EMAIL:-admin@example.com}' \
            --fullname='${MOODLE_SITE_FULLNAME:-OneBoard}' \
            --shortname='${MOODLE_SITE_SHORTNAME:-OneBoard}' \
            --agree-license"
        rc=$?
        [ $rc -eq 0 ] && echo "[entrypoint] install complete" \
            || echo "[entrypoint] WARNING: install exited $rc — starting Apache anyway (check DB/Redis vars)"
    fi
fi
set -e

# 6. Serve
echo "[entrypoint] starting Apache on port ${PORT}"
exec apache2-foreground
