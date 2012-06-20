#!/usr/bin/env php

<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category  RuckusingMigrations
 * @package   Main
 * @author    Cody Caughlan <codycaughlan % gmail . com>
 * @author    Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright 2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license   GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/ruckus/ruckusing-migrations
 *
 * Usage: php main.php [ENV=environment] <task> [task parameters]
 *
 * Options:
 *     -c, --configuration  Path to the configuration file of application.
 *
 *     -d, --database       Path to the configuration file of databases.
 *
 *     -t, --taskdir        Path of the directory of the tasks.
 *
 *     -m, --migrationdir   Path of the directory of the migrations.
 *
 * ###########
 *
 * ENV: The ENV command line parameter can be used to specify a different
 * database to run against, as specific in the configuration file (config/database.inc.php).
 * By default, ENV is "development"
 *
 * task: In a nutshell, task names are pseudo-namespaced. The tasks that come
 * with the framework are namespaced to "db" (e.g. the tasks are "db:migrate", "db:setup", etc).
 * All tasks available actually :
 *
 *      - db:setup : A basic task to initialize your DB for migrations is
 *      available. One should always run this task when first starting out.
 *
 *      - db:migrate : The primary purpose of the framework is to run migrations,
 *      and the execution of migrations is all handled by just a regular ol' task.
 *
 *      - db:version : It is always possible to ask the framework (really the DB)
 *      what version it is currently at.
 *
 *      - db:status : With this taks you'll get an overview of the already
 *      executed migrations and which will be executed when running db:migrate
 *
 *      - db:schema : It can be beneficial to get a dump of the DB in raw SQL
 *      format which represents the current version.
 *
 * For more documentation on the tasks
 * https://github.com/Azema/ruckusing-migrations/wiki/Available-Tasks
 *
 * Call with no arguments to see usage info.
 */

if (!defined('RUCKUSING_BASE')) {
    define('RUCKUSING_BASE', '@pear_directory@/Ruckusing');
}

// DB table where the version info is stored
if (!defined('RUCKUSING_SCHEMA_TBL_NAME')) {
	define('RUCKUSING_SCHEMA_TBL_NAME', 'schema_info');
}

if (!defined('RUCKUSING_TS_SCHEMA_TBL_NAME')) {
	define('RUCKUSING_TS_SCHEMA_TBL_NAME', 'schema_migrations');
}

set_error_handler('scrErrorHandler', E_ALL);
set_exception_handler('scrExceptionHandler');
spl_autoload_register('loader', true, true);

set_include_path(
    implode(PATH_SEPARATOR, array(
        RUCKUSING_BASE . '/library',
        get_include_path(),
    ))
);

// Parse args of command line
if (!isset($argv)) {
    $argv = '';
}
$args = parseArgs($argv);

$main = new Ruckusing_FrameworkRunner($args);
$output = $main->execute();
echo "\n", $output, "\n";
// It's good
exit(0);

/**
 * Parse command line arguments
 *
 * @param array $argv Arguments of command line
 *
 * @return array
 */
function parseArgs($argv)
{
    $nbArgs = count($argv);
    if ($nbArgs < 2) {
        printHelp(true);
    } elseif ($nbArgs == 2) {
        if ($argv[1] == 'help') {
            printHelp(true);
        }
    }
    return $argv;
}

/**
 * Print a usage scenario for this script.
 * Optionally take a boolean on whether to immediately die or not.
 *
 * @param boolean $exit Flag to die
 *
 * @return void
 */
function printHelp($exit = false)
{
    $version = '0.9-experimental';
    $dateVersion = date('c', 1325578455);
    $usage =<<<USAGE
Ruckusing Migrations v{$version} at {$dateVersion}

Usage: php main.php [options] [help] [ENV=environment] <task> [task parameters]

Options:
    -c, --configuration  Path to the configuration file (INI) of application.

    -d, --database       Path to the configuration file (INI) of databases.

    -t, --taskdir        Path of the directory of the tasks.

    -m, --migrationdir   Path of the directory of the migrations.

###########

    help: Display this message

    ENV: The ENV command line parameter can be used to specify a different
database to run against, as specific in the configuration file (config/database.inc.php).
By default, ENV is "development"

    task: In a nutshell, task names are pseudo-namespaced. The tasks that come
with the framework are namespaced to "db" (e.g. the tasks are "db:migrate", "db:setup", etc).
All tasks available actually :

     - db:setup : A basic task to initialize your DB for migrations is
     available. One should always run this task when first starting out.

     - db:migrate : The primary purpose of the framework is to run migrations,
     and the execution of migrations is all handled by just a regular ol' task.

     - db:version : It is always possible to ask the framework (really the DB)
     what version it is currently at.

     - db:status : With this taks you'll get an overview of the already
     executed migrations and which will be executed when running db:migrate

     - db:schema : It can be beneficial to get a dump of the DB in raw SQL
     format which represents the current version.
For more documentation on the tasks
@see https://github.com/Azema/ruckusing-migrations/wiki/Available-Tasks

Call with no arguments to see usage info.


USAGE;
    echo $usage;
    if ($exit) {
        exit;
    }
}

/**
 * error_handler
 * Global error handler to process all errors during script execution
 *
 * @param integer $errno   Error number
 * @param string  $errstr  Error message
 * @param string  $errfile Error file
 * @param string  $errline Error line
 *
 * @return void
 */
function scrErrorHandler($errno, $errstr, $errfile, $errline)
{
    echo sprintf(
        "\n\n(%s:%d) %s\n\n",
        basename($errfile),
        $errline,
        $errstr
    );
    exit(1); // exit with error
}

/**
 * exception handler
 * Global exception handler to process all exception during script execution
 *
 * @param Exception $exception Exception
 *
 * @return void
 */
function scrExceptionHandler($exception)
{
    echo 'Error: ' . $exception->getMessage() . "\n"
        . "\nbacktrace: \n" . $exception->getTraceAsString() . "\n";
    exit(1); // exit with error
}

function loader($classname)
{
    //echo 'load: ' . $classname . PHP_EOL;
    $filename = str_replace('_', '/', $classname) . '.php';
    if (is_file(RUCKUSING_BASE . '/library/' . $filename)) {
        $filename = RUCKUSING_BASE . '/library/' . $filename;
    }
    include_once $filename;
}
?>
