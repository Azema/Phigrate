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

if (!defined('RUCKUSING_BASE')) define('RUCKUSING_BASE', dirname(__FILE__));

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
if (!isset($argv))
    $argv = '';
$args = parseArgs($argv);

// Get environment
$env = getEnvironment($args);

/*
 *  Config application
 */
$configFile = RUCKUSING_BASE . '/config/application.ini';
// Get config application file from command line
if (array_key_exists('config', $args)) {
    $configFile = $args['config'];
}
$config = getConfigFromFile($configFile, $env);

// Task directory
if (array_key_exists('taskdir', $args)) {
    $config['task.dir'] = $args['taskdir'];
}
// Migration directory
if (array_key_exists('migrationdir', $args)) {
    $config['migration.dir'] = $args['migrationdir'];
}
/*
 *  Config DB
 */
$configDbFile = RUCKUSING_BASE . '/config/database.ini';
// Get config DB file from command line
if (array_key_exists('configDb', $args)) {
    $configDbFile = $args['configDb'];
}
$configDb = getConfigFromFile($configDbFile, $env);

// Get logger of application
$logger = getLogger($config, $env);

$main = new Ruckusing_FrameworkRunner($config, $configDb, $args, $env, $logger);
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
    /*
    * Configuration des options passées dans la ligne de commande
    */
    $shortOptions = 'hc:t:m:';
    $longOptions = array(
        'help', // sans valeur
        'configuration:', // necéssite le chemin du fichier
        'taskdir:', // nécessite le chemin des tâches
        'migrationdir:', // necéssite le chemin des migrations
    );
    $nbArgs = count($argv);
    if ($nbArgs < 2) {
        printHelp(true);
    } elseif ($nbArgs == 2) {
        if ($argv[1] == 'help') {
            printHelp(true);
        }
    } else {
        $args = array();
        for ($i = 1; $i < $nbArgs; $i++) {
            switch ($argv[$i]) {
                // help for command line
                case '-h':
                case '--help':
                case '-?':
                    printHelp(true);
                    break;
                // configuration file path
                case '-c':
                case '--configuration':
                    $i++;
                    $args['config'] = $argv[$i];
                    break;
                // configuration db file path
                case '-d':
                case '--database':
                    $i++;
                    $args['configDb'] = $argv[$i];
                    break;
                // task directory
                case '-t':
                case '--taskdir':
                    $i++;
                    $args['taskdir'] = $argv[$i];
                    break;
                // migration directory
                case '-m':
                case '--migrationdir':
                    $i++;
                    $args['migrationdir'] = $argv[$i];
                    break;
                // other
                default:
                    $args[] = $argv[$i];
                    break;
            }
            $argv = $args;
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
    for ($i = $nbArgs-1; $i >= 1; $i--) {
        if (preg_match('/^ENV=(\w+)$/',$args[$i], $match)) {
            $env = $match[1];
            break;
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
function getLogger($config, $env)
{
    //initialize logger
    $log_dir = RUCKUSING_BASE . '/logs';
    if (array_key_exists('log.dir', $config)) {
        $log_dir = $config['log.dir'];
    }
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
 * getConfigFromFile : Return sectionName from filename 
 * 
 * @param string $filename    The config file name
 * @param string $sectionName The section name
 *
 * @return array
 */
function getConfigFromFile($filename, $sectionName)
{
    if (! is_file($filename)) {
        throw new Exception('Config file not found (' . $filename . ')');
    }
    $ini_array = parse_ini_file($filename, true);
    if (! array_key_exists($sectionName, $ini_array)) {
        $found = false;
        foreach ($ini_array as $name => $section) {
            if (preg_match('/^'.$sectionName.'\s?:\s?(\w+)$/', $name, $matches)) {
                $sectionExtended = getConfigFromFile($filename, $matches[1]);
                $config = array_merge($sectionExtended, $section);
                $found = true;
                break;
            }
        }
        if (! $found) {
            throw new Exception('Section "' . $sectionName . '" not found in config file : ' . $filename);
        }
    } else {
        $config = $ini_array[$sectionName];
    }
    return $config;
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
