<?php


// PHP_VERSION_ID est disponible depuis PHP 5.2.7, 
// si votre version est antérieure, émulez-le.
if (!defined('PHP_VERSION_ID')) {
   $version = explode('.',PHP_VERSION);

   define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

//set up some preliminary defaults, this is so all of our
//framework includes work!
if (! defined('PHIGRATE_BASE')) {
    define('PHIGRATE_BASE', realpath(dirname(__FILE__) . '/..'));
}

if (! defined('FIXTURES_PATH')) {
    define('FIXTURES_PATH', dirname(__FILE__) . '/fixtures');
}

if (! defined('MOCKS_PATH')) {
    define('MOCKS_PATH', dirname(__FILE__) . '/mocks');
}

//Parent of migrations directory.
if (!defined('PHIGRATE_DB_DIR')) {
    define('PHIGRATE_DB_DIR', PHIGRATE_BASE . '/tests/dummy/db');
}

// DB table where the version info is stored
if (!defined('PHIGRATE_SCHEMA_TBL_NAME')) {
	define('PHIGRATE_SCHEMA_TBL_NAME', 'schema_info');
}

if (!defined('PHIGRATE_TS_SCHEMA_TBL_NAME')) {
    define('PHIGRATE_TS_SCHEMA_TBL_NAME', 'schema_migrations');
}

//Where the dummy migrations reside
if (!defined('PHIGRATE_MIGRATION_DIR')) {
    define('PHIGRATE_MIGRATION_DIR', PHIGRATE_DB_DIR . '/migrate');
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
        PHIGRATE_BASE . '/library',
        get_include_path(),
    ))
);

function loader($classname)
{
    if (! class_exists($classname, false)) {
        //echo 'load: ' . $classname . PHP_EOL;
        $filename = str_replace('_', '/', $classname) . '.php';
        if (is_file(PHIGRATE_BASE . '/library/' . $filename)) {
            $filename = PHIGRATE_BASE . '/library/' . $filename;
            include_once $filename;
        }
    }
}

// Mocks
$files = scandir(MOCKS_PATH);
foreach ($files as $file) {
    if ($file == '.' || $file == '..' || is_dir($file) || substr($file, -4) != '.php') continue;
    require_once MOCKS_PATH . '/' . $file;
}

// Clean file logs
if (is_file(PHIGRATE_BASE . '/tests/logs/tests.log')) {
    unlink(PHIGRATE_BASE . '/tests/logs/tests.log');
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
