<?php

//set up some preliminary defaults, this is so all of our
//framework includes work!
if(!defined('RUCKUSING_BASE')) {
	define('RUCKUSING_BASE', dirname(__FILE__) . '/..');
}

//Parent of migrations directory.
if(!defined('RUCKUSING_DB_DIR')) {
	define('RUCKUSING_DB_DIR', RUCKUSING_BASE . '/tests/dummy/db');
}

if(!defined('RUCKUSING_TS_SCHEMA_TBL_NAME')) {
	define('RUCKUSING_TS_SCHEMA_TBL_NAME', 'schema_migrations');
}

//Where the dummy migrations reside
if(!defined('RUCKUSING_MIGRATION_DIR')) {
	define('RUCKUSING_MIGRATION_DIR', RUCKUSING_DB_DIR . '/migrate');
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
