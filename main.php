#!/usr/bin/env php

<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category  RuckusingMigrations
 * @package   Main
 * @author    Cody Caughlan <toolbag@gmail.com>
 * @copyright 2010-2011 Cody Caughlan
 * @license   GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/ruckus/ruckusing-migrations
 *
 * Usage: php main.php [ENV=environment] <task> [task parameters]
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

set_error_handler('scrErrorHandler', E_ALL);
set_exception_handler('scrExceptionHandler');

if (!defined('RUCKUSING_BASE')) define('RUCKUSING_BASE', dirname(__FILE__));

//requirements
require_once RUCKUSING_BASE . '/lib/classes/util/class.Ruckusing_Logger.php';
require_once RUCKUSING_BASE . '/config/database.inc.php';
require_once RUCKUSING_BASE . '/lib/classes/class.Ruckusing_FrameworkRunner.php';
require_once RUCKUSING_BASE . '/lib/classes/Ruckusing_exceptions.php';

if (!isset($argv))
    $argv = '';
$args = parseArgs($argv);
$env = getEnvironment($args);
$logger = getLogger($env);
$main = new Ruckusing_FrameworkRunner($ruckusing_db_config, $args, $env, $logger);
$output = $main->execute();
echo "\n", $output, "\n";
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
 * get environment
 * 
 * @param array $args Arguments of command line
 *
 * @return string
 */
function getEnvironment($args)
{
    $env = 'development';
    $nbArgs = count($args);
    if ($nbArgs > 2) {
        for ($i = $nbArgs-1; $i >= 1; $i--) {
            if (preg_match('/^ENV=(\w+)$/',$args[$i], $match)) {
                $env = $match[1];
                break;
            }
        }
    }
    return $env;
}

/**
 * getLogger : Return an instance of logger
 * 
 * @param string $env Environment
 *
 * @return Ruckusing_Logger
 */
function getLogger($env)
{
    //initialize logger
    $log_dir = RUCKUSING_BASE . '/logs';
    if (is_dir($log_dir) && ! is_writable($log_dir)) {
        die(
            "\n\nCannot write to log directory: "
            . "{$log_dir}\n\nCheck permissions.\n\n"
        );
    } else if (! is_dir($log_dir)) {
        //try and create the log directory
        mkdir($log_dir);
    }
    $logger = Ruckusing_Logger::instance($log_dir . '/' . $env . '.log');

    if ($env == 'development') {
        $logger->setPriority(Ruckusing_Logger::DEBUG);
    } elseif ($env == 'production') {
        $logger->setPriority(Ruckusing_Logger::INFO);
    }

    return $logger;
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
    $version = '0.9';
    $dateVersion = date('c', 1325578455);
    $usage =<<<USAGE
Ruckusing Migrations v{$version} at {$dateVersion}

Usage: php main.php [help] [ENV=environment] <task> [task parameters]

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
    if ($exit) exit;
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
    echo 'Error: ' . $exception->getMessage() . "\n";
        //. "\nbacktrace: \n" . $exception->getTraceAsString();
    exit(1); // exit with error
}

?>
