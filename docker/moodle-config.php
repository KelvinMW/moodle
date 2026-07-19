<?php
// OneBoard — Moodle config, driven entirely by Railway environment variables.
// Copied to <dirroot>/config.php by docker/entrypoint.sh at container start.
// public/config.php loads this file via ../config.php.

unset($CFG);
global $CFG;
$CFG = new stdClass();

// ---------------------------------------------------------------------------
// Database — PostgreSQL (Railway Postgres plugin provides PG* vars)
// ---------------------------------------------------------------------------
$CFG->dbtype    = 'pgsql';
$CFG->dblibrary = 'native';
$CFG->dbhost    = getenv('PGHOST') ?: 'localhost';
$CFG->dbname    = getenv('PGDATABASE') ?: 'railway';
$CFG->dbuser    = getenv('PGUSER') ?: 'postgres';
$CFG->dbpass    = getenv('PGPASSWORD') ?: '';
$CFG->prefix    = getenv('MOODLE_DB_PREFIX') ?: 'mdl_';
$CFG->dboptions = [
    'dbpersist' => false,
    'dbport'    => getenv('PGPORT') ?: '5432',
    'dbsocket'  => '',
];

// ---------------------------------------------------------------------------
// Site URL and data directory
// ---------------------------------------------------------------------------
$railwaydomain = getenv('RAILWAY_PUBLIC_DOMAIN');
$CFG->wwwroot  = getenv('MOODLE_WWWROOT')
    ?: ($railwaydomain ? 'https://' . $railwaydomain : 'http://localhost');
$CFG->dataroot = getenv('MOODLE_DATAROOT') ?: '/var/moodledata';
$CFG->admin    = 'admin';
$CFG->directorypermissions = 02777;

// Default theme = stock Moodle Boost. Switch to boost_union (baked into the
// image) or oneboard via Site admin → Appearance, or override with MOODLE_THEME.
$CFG->theme = getenv('MOODLE_THEME') ?: 'boost';

// Enable mobile web services so the OneBoard app can connect / log in.
$CFG->enablemobilewebservice = 1;

// Railway terminates TLS at its edge proxy.
$CFG->sslproxy = !empty($railwaydomain) || getenv('MOODLE_SSLPROXY') === 'true';

// ---------------------------------------------------------------------------
// Sessions via Redis (Railway Redis plugin provides REDIS* vars)
// ---------------------------------------------------------------------------
if (getenv('REDISHOST')) {
    $CFG->session_handler_class = '\core\session\redis';
    $CFG->session_redis_host    = getenv('REDISHOST');
    $CFG->session_redis_port    = (int)(getenv('REDISPORT') ?: 6379);
    $CFG->session_redis_database = (int)(getenv('REDISDB') ?: 0);
    if (getenv('REDISPASSWORD')) {
        $CFG->session_redis_auth = getenv('REDISPASSWORD');
    }
    $CFG->session_redis_prefix = 'ob_sess_';
    $CFG->session_redis_acquire_lock_timeout = 120;
    $CFG->session_redis_lock_expire = 7200;
}

// ---------------------------------------------------------------------------
// Debug (off unless MOODLE_DEBUG=true)
// ---------------------------------------------------------------------------
if (getenv('MOODLE_DEBUG') === 'true') {
    $CFG->debug = (E_ALL | E_STRICT);
    $CFG->debugdisplay = 1;
}

require_once(__DIR__ . '/lib/setup.php');
