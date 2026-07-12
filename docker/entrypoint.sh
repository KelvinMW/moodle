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

# 5. Install schema on first boot, otherwise run pending upgrades
cd "$APP"
if run_as_www "php admin/cli/cfg.php --name=version" >/dev/null 2>&1; then
    echo "[entrypoint] existing install detected — running upgrade"
    run_as_www "php admin/cli/upgrade.php --non-interactive" || true
    run_as_www "php admin/cli/purge_caches.php" || true
else
    echo "[entrypoint] fresh database — installing Moodle"
    : "${MOODLE_ADMIN_PASS:?set MOODLE_ADMIN_PASS in Railway before first deploy}"
    run_as_www "php admin/cli/install_database.php \
        --lang='${MOODLE_LANG:-en}' \
        --adminuser='${MOODLE_ADMIN_USER:-admin}' \
        --adminpass='${MOODLE_ADMIN_PASS}' \
        --adminemail='${MOODLE_ADMIN_EMAIL:-admin@example.com}' \
        --fullname='${MOODLE_SITE_FULLNAME:-OneBoard}' \
        --shortname='${MOODLE_SITE_SHORTNAME:-OneBoard}' \
        --agree-license --non-interactive"
fi

# 6. Serve
echo "[entrypoint] starting Apache on port ${PORT}"
exec apache2-foreground
