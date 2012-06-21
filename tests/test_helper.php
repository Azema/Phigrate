<?php

//set up some preliminary defaults, this is so all of our
//framework includes work!
if (! defined('RUCKUSING_BASE')) {
    define('RUCKUSING_BASE', dirname(__FILE__) . '/..');
}

if (! defined('FIXTURES_PATH')) {
    define('FIXTURES_PATH', dirname(__FILE__) . '/fixtures');
}

if (! defined('MOCKS_PATH')) {
    define('MOCKS_PATH', dirname(__FILE__) . '/mocks');
}

//Parent of migrations directory.
if (!defined('RUCKUSING_DB_DIR')) {
    define('RUCKUSING_DB_DIR', RUCKUSING_BASE . '/tests/dummy/db');
}

// DB table where the version info is stored
if (!defined('RUCKUSING_SCHEMA_TBL_NAME')) {
	define('RUCKUSING_SCHEMA_TBL_NAME', 'schema_info');
}

if (!defined('RUCKUSING_TS_SCHEMA_TBL_NAME')) {
    define('RUCKUSING_TS_SCHEMA_TBL_NAME', 'schema_migrations');
}

//Where the dummy migrations reside
if (!defined('RUCKUSING_MIGRATION_DIR')) {
    define('RUCKUSING_MIGRATION_DIR', RUCKUSING_DB_DIR . '/migrate');
}

// User MySQL by default
if (!defined('USER_MYSQL_DEFAULT')) {
    define('USER_MYSQL_DEFAULT', 'root');
}

// User MySQL by default
if (!defined('PASSWORD_MYSQL_DEFAULT')) {
    define('PASSWORD_MYSQL_DEFAULT', '');
}

spl_autoload_register('loader', true, true);

set_include_path(
    implode(PATH_SEPARATOR, array(
        RUCKUSING_BASE . '/library',
        get_include_path(),
    ))
);
function loader($classname)
{
    //echo 'load: ' . $classname . PHP_EOL;
    $filename = str_replace('_', '/', $classname) . '.php';
    if (is_file(RUCKUSING_BASE . '/library/' . $filename)) {
        $filename = RUCKUSING_BASE . '/library/' . $filename;
        include_once $filename;
    }
}

// Mocks
$files = scandir(MOCKS_PATH);
foreach ($files as $file) {
    if ($file == '.' || $file == '..' || is_dir($file) || substr($file, -4) != '.php') continue;
    require_once MOCKS_PATH . '/' . $file;
}

// Clean file logs
if (is_file(RUCKUSING_BASE . '/tests/logs/tests.log')) {
    unlink(RUCKUSING_BASE . '/tests/logs/tests.log');
}
