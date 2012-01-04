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
 * Generator for migrations.
 * Usage: php generate.php <migration name>
 * Call with no arguments to see usage info.
 */

if (!defined('RUCKUSING_BASE')) define('RUCKUSING_BASE', dirname(__FILE__));
/**
 * Config file
 */
require_once RUCKUSING_BASE . '/config/config.inc.php';
/**
 * @see Ruckusing_NamingUtil
 */
require_once RUCKUSING_BASE  . '/lib/classes/util/class.Ruckusing_NamingUtil.php';
/**
 * @see Ruckusing_MigratorUtil
 */
require_once RUCKUSING_BASE  . '/lib/classes/util/class.Ruckusing_MigratorUtil.php';

if (!isset($argv))
    $argv = '';
$args = parseArgs($argv);
main($args);


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
    if (count($argv) < 2 || $argv[1] == 'help') printHelp(true);

    $migrationName = $argv[1];
    return array('name' => $migrationName);
}

/**
 * Print a usage scenario for this script.
 * Optionally take a boolean on whether to immediately die or not.
 * 
 * @param boolean $exit Flag to exit script generate
 *
 * @return void
 */
function printHelp($exit = false)
{
    echo "\nusage: php generate.php <migration name>\n\n"
        . "\tWhere <migration name> is a descriptive name of the migration, "
        . "joined with underscores.\n\tExamples: add_index_to_users | "
        . "create_users_table | remove_pending_users\n\n";
    if ($exit) exit;
}

/**
 * The main function of this script
 * 
 * @param array $args Arguments of command line
 *
 * @return void
 */
function main($args)
{
    //input sanity check
    if (! is_array($args) 
        || (is_array($args) && ! array_key_exists('name', $args))
    ) {
        printHelp(true);
    }
    $migrationName = $args['name'];
    
    //clear any filesystem stats cache
    clearstatcache();
    
    //check to make sure our migration directory exists
    if (! is_dir(RUCKUSING_MIGRATION_DIR)) {
        dieWithError(
            "ERROR: migration directory '" . RUCKUSING_MIGRATION_DIR 
            . "' does not exist. Specify MIGRATION_DIR in "
            . "config/config.inc.php and try again."
        );
    }
    
    //generate a complete migration file
    $timestamp   = Ruckusing_MigratorUtil::generateTimestamp();
    // @TODO Check the class name is not duplicated
    $klass       = Ruckusing_NamingUtil::camelcase($migrationName);
    $fileName    = $timestamp . '_' . $klass . '.php';
    $fullPath    = realpath(RUCKUSING_MIGRATION_DIR) . '/' . $fileName;
    $templateStr = getTemplate($klass);
    
    //check to make sure our destination directory is writable
    if (! is_writable(RUCKUSING_MIGRATION_DIR . '/')) {
        dieWithError(
            "ERROR: migration directory '" . RUCKUSING_MIGRATION_DIR 
            . "' is not writable by the current user. "
            . "Check permissions and try again."
        );
    }

    //write it out!
    $fileResult = file_put_contents($fullPath, $templateStr);
    if ($fileResult === false) {
        dieWithError(
            'Error writing to migrations directory/file. '
            . 'Do you have sufficient privileges?'
            . "\nOr the file is maybe double ({$fileName})?";
        );
    } else {
        echo "\nCreated migration: {$fileName}\n\n";
    }
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
/*
 * For documentation on the methods of migration
 *
 * @see https://github.com/Azema/ruckusing-migrations/wiki/Migration-Methods
 */
class $klass extends Ruckusing_BaseMigration
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

