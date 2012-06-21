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
 * @copyright 2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license   GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/ruckus/ruckusing-migrations
 *
 * Generator for migrations.
 * Ruckusing Migrations v{$version} at {$dateVersion}
 *
 * Usage: php generate.php [options] [help] [ENV=environment] <migration name>
 *
 * Options:
 *     -c, --configuration  Path to the configuration file (INI) of application.
 *
 *     -m, --migrationdir   Path of the directory of the migrations.
 *
 * ###########
 *
 *     help: Display this message
 *
 *     ENV: The ENV command line parameter can be used to specify a different
 * database to run against, as specific in the configuration file (config/database.inc.php).
 * By default, ENV is "development"
 *
 *     <migration_name> is a descriptive name of the migration, joined with undescores.
 *         Examples: add_index_to_users | create_users_table | remove_pending_users
 *
 */

if (strpos('@pear_directory@', '@pear_directory') === 0) {  // not a pear install
    define('RUCKUSING_BASE', realpath(dirname(__FILE__) . '/..'));
} else {
    define('RUCKUSING_BASE', '@pear_directory@/Ruckusing');
}
set_include_path(
    implode(PATH_SEPARATOR, array(
        RUCKUSING_BASE . '/library',
        get_include_path(),
    ))
);

set_error_handler('scrErrorHandler', E_ALL);
set_exception_handler('scrExceptionHandler');
spl_autoload_register('loader', true, true);

/**
 * Permet d'itérer à reculons sur un répertoire
 * à la recherche d'un fichier de configuration caché
 *
 * @param string $dir Chemin du répertoire
 *
 * @return string
 */
function iterateDir($dir) {
    $fp = opendir($dir);
    while(false !== ($entry = readdir($fp))) {
        if ($entry == '.rucku') {
            closedir($fp);
            return $dir . '/.rucku';
        }
    }
    if (is_dir($dir . '/..') && $dir != '/') {
        closedir($fp);
        return iterateDir(realpath($dir . '/..'));
    }
    closedir($fp);
    return null;
}

if (!isset($argv)) {
    $argv = '';
}

$args = parseArgs($argv);
if (! in_array('-c', $args)) {
    $config = iterateDir(getcwd());
    if (null !== $config) {
        echo 'Fichier de configuration trouvé: ', $config, "\n";
        $args[] = '-c';
        $args[] = $config;
    }
}
$env = getEnvironment($args);
$config = getConfig($args, $env);
main($args, $config);


//-------------------

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
    $options = array();
    if ($nbArgs < 2) {
        printHelp();
    } elseif ($nbArgs >= 2) {
        for ($i = 1; $i < $nbArgs; $i++) {
            switch ($argv[$i]) {
                // help for command line
                case '-h':
                case '--help':
                case '-?':
                    printHelp();
                    break;
                // configuration file path
                case '-c':
                case '--configuration':
                    $i++;
                    if (! array_key_exists($i, $argv)) {
                        require_once 'Ruckusing/Exception/Argument.php';
                        throw new Ruckusing_Exception_Argument(
                            'Please, specify the configuration file if you use'
                            . ' the argument -c or --configuration'
                        );
                    }
                    $options['configFile'] = $argv[$i];
                    break;
                // migration directory
                case '-m':
                case '--migrationdir':
                    $i++;
                    if (! array_key_exists($i, $argv)) {
                        require_once 'Ruckusing/Exception/Argument.php';
                        throw new Ruckusing_Exception_Argument(
                            'Please, specify the directory of migration files '
                            . ' if you use the argument -m or --migrationdir'
                        );
                    }
                    $options['migration.dir'] = $argv[$i];
                    break;
                // other
                default:
                    $arg = $argv[$i];
                    if ($arg == 'help') {
                        printHelp();
                    } elseif (strpos($arg, '=') !== false) {
                        list($key, $value) = explode('=', $arg);
                        $options[strtolower($key)] = $value;
                    } else {
                        $options['name'] = $arg;
                    }
                    break;
            }
        }
    }
    if (! array_key_exists('name', $options)) {
        printHelp();
    }
    return $options;
}

/**
 * getEnvironment
 *
 * @param array $options
 *
 * @return string
 */
function getEnvironment($options)
{
    $env = 'development';
    if (array_key_exists('env', $options)) {
        $env = $options['env'];
    }
    return $env;
}

/**
 * getConfig
 *
 * @param array  $options
 * @param string $env
 *
 * @return Ruckusing_Config
 */
function getConfig($options, $env)
{
    $configFile = realpath(dirname(__FILE__) . '../config/application.ini');
    if (array_key_exists('configFile', $options)) {
        $configFile = $options['configFile'];
    }
    if (! is_file($configFile)) {
        require_once 'Ruckusing/Exception/Config.php';
        throw new Ruckusing_Exception_Config(
            'The configuration file "' . $configFile 
            . '" does not exists or is not a file.'
        );
    }
    require_once 'Ruckusing/Config/Ini.php';
    return new Ruckusing_Config_Ini($configFile, $env);
}

/**
 * Print a usage scenario for this script.
 * Optionally take a boolean on whether to immediately die or not.
 *
 * @param boolean $exit Flag to exit script generate
 *
 * @return void
 */
function printHelp()
{
    $version = '0.9-experimental';
    $dateVersion = date('c', 1325926800);
    $usage =<<<USAGE
Ruckusing Migrations v{$version} at {$dateVersion}

Usage: php generate.php [options] [help] [ENV=environment] <migration name>

Options:
    -c, --configuration  Path to the configuration file (INI) of application.

    -m, --migrationdir   Path of the directory of the migrations.

###########

    help: Display this message

    ENV: The ENV command line parameter can be used to specify a different
database to run against, as specific in the configuration file (config/database.inc.php).
By default, ENV is "development"

    <migration_name> is a descriptive name of the migration, joined with undescores.
        Examples: add_index_to_users | create_users_table | remove_pending_users


USAGE;

    echo $usage;
    exit(0);
}

/**
 * The main function of this script
 *
 * @param array $args Arguments of command line
 *
 * @return void
 */
function main($args, $config)
{
    $migrationName = $args['name'];
    if (array_key_exists('migration.dir', $args)) {
        $migrationDir = $args['migration.dir'];
    } elseif (isset($config->migration) && isset($config->migration->dir)) {
        $migrationDir = $config->migration->dir;
    } else {
        require_once 'Ruckusing/Exception/MissingMigrationDir.php';
        throw new Ruckusing_Exception_MissingMigrationDir(
            'Error: Migration directory must be specified!'
        );
    }

    //clear any filesystem stats cache
    clearstatcache();

    //check to make sure our migration directory exists
    if (! is_dir($migrationDir)) {
        require_once 'Ruckusing/Exception/InvalidMigrationDir.php';
        throw new Ruckusing_Exception_InvalidMigrationDir(
            'ERROR: migration directory \'' . $migrationDir
            . '\' does not exist. Specify \'migration.dir\' in '
            . 'config/application.ini and try again.'
        );
    }

    $migrationDir = realpath($migrationDir);
    //generate a complete migration file
    require_once 'Ruckusing/Util/Migrator.php';
    $timestamp   = Ruckusing_Util_Migrator::generateTimestamp();
    $klass       = Ruckusing_Util_Naming::camelcase($migrationName);
    if (classNameIsDuplicated($klass, $migrationDir)) {
        require_once 'Ruckusing/Exception/Argument.php';
        throw new Ruckusing_Exception_Argument(
            'This class name is already used. Please, choose another name.'
        );
    }
    $fileName    = $timestamp . '_' . $klass . '.php';
    $fullPath    = $migrationDir . '/' . $fileName;
    $templateStr = getTemplate($klass);

    //check to make sure our destination directory is writable
    if (! is_writable($migrationDir . '/')) {
        require_once 'Ruckusing/Exception/InvalidMigrationDir.php';
        throw new Ruckusing_Exception_InvalidMigrationDir(
            'ERROR: migration directory (' . $migrationDir
            . ') is not writable by the current user. '
            . 'Check permissions and try again.'
        );
    }

    // write it out!
    $fileResult = file_put_contents($fullPath, $templateStr);
    // No three equals (check error and zero caracter writed)
    if ($fileResult == false) {
        require_once 'Ruckusing/Exception/InvalidMigrationDir.php';
        throw new Ruckusing_Exception_InvalidMigrationDir(
            'Error writing to migrations directory/file. '
            . 'Do you have sufficient privileges?'
            . "\nOr the file is maybe double ({$fileName})?"
        );
    }
    echo "\nCreated migration: {$fileName}\n\n";
}

/**
 * Indicate if a class name is already used
 *
 * @param string $classname    The class name to test
 * @param string $migrationDir The directory of migration files
 *
 * @return bool
 */
function classNameIsDuplicated($classname, $migrationDir)
{
    require_once 'Ruckusing/Util/Migrator.php';
    $migrationFiles = Ruckusing_Util_Migrator::getMigrationFiles($migrationDir);
    $classname = strtolower($classname);
    foreach ($migrationFiles as $file) {
        if (strtolower($file['class']) == $classname) {
            return true;
        }
    }
    return false;
}

/**
 * die with error
 *
 * @param string $str Message to display
 *
 * @return void
 */
function dieWithError($str)
{
    die("\n{$str}\n");
}

/**
 * get template
 *
 * @param string $klass The class name
 *
 * @return string
 */
function getTemplate($klass)
{
    $template = <<<TPL
<?php\n
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
 * @package    Migrations
 * @author
 * @copyright
 * @license
 * @link
 */

/**
 * Class migration DB $klass
 *
 * For documentation on the methods of migration
 *
 * @see https://github.com/Azema/ruckusing-migrations/wiki/Migration-Methods
 *
 * @category   RuckusingMigrations
 * @package    Migrations
 * @author
 * @copyright
 * @license
 * @link
 */
class $klass extends Ruckusing_Migration_Base
{
    /**
     * up
     *
     * @return void
     */
    public function up()
    {
        // Add your code here
    }

    /**
     * down
     *
     * @return void
     */
    public function down()
    {
        // Add your code here
    }
}
TPL;
    return $template;
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
    echo "\t\033[40m\033[1;31m " . $exception->getMessage() . " \033[0m\n\n";
    //. "\nbacktrace: \n" . $exception->getTraceAsString() . "\n";
    printHelp();
    exit(1); // exit with error
}

function loader($classname)
{
    $filename = str_replace('_', '/', $classname) . '.php';
    if (defined('RUCKUSING_BASE')
        && is_file(RUCKUSING_BASE . '/library/' . $filename)
    ) {
        $filename = RUCKUSING_BASE . '/library/' . $filename;
    }
    include_once $filename;
}
