<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
 * @package    Task
 * @subpackage Db
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */

/**
 * Prints out a list of migrations that have and haven't been applied
 *
 * @category   RuckusingMigrations
 * @package    Task
 * @subpackage Db
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Task_Db_Status implements Ruckusing_Task_ITask
{
    /**
     * adapter 
     * 
     * @var Ruckusing_BaseAdapter
     */
    private $_adapter = null;

    /**
     * _migrationDir 
     * 
     * @var string
     */
    private $_migrationDir;

    /**
     * __construct 
     * 
     * @param Ruckusing_BaseAdapter $adapter Adapter RDBMS
     *
     * @return Ruckusing_DB_Status
     */
    function __construct($adapter)
    {
		$this->_adapter = $adapter;
	}
	
    /**
     * setDirectoryOfMigrations : Define directory of migrations
     * 
     * @param string $migrationDir Directory of migrations
     *
     * @return Migration_Db_Schema
     */
    public function setDirectoryOfMigrations($migrationDir)
    {
        $this->_migrationDir = $migrationDir;
        return $this;
    }
	
    /**
     * Primary task entry point
     * 
     * @param array $args Arguments to task
     *
     * @return void
     */
    public function execute($args)
    {
		echo 'Started: ' . date('Y-m-d g:ia T') . "\n\n";		
		echo "[db:status]: \n";
		$util = new Ruckusing_MigratorUtil($this->_adapter);
		$migrations = $util->getExecutedMigrations();
		$files = $util->getMigrationFiles(RUCKUSING_MIGRATION_DIR, 'up');
		$applied = array();
		$notApplied = array();
		foreach ($files as $file) {
            if (in_array($file['version'], $migrations)) {
                $applied[] = $file['class'] . ' [ ' . $file['version'] . ' ]';
            } else {
                $notApplied[] = $file['class'] . ' [ ' . $file['version'] . ' ]';
            }
        }
        echo "\n\n===================== APPLIED ======================= \n";
        foreach ($applied as $a) {
            echo "\t" . $a . "\n";
        }
        echo "\n\n===================== NOT APPLIED ======================= \n";
        foreach ($notApplied as $na) {
            echo "\t" . $na . "\n";
        }
		echo "\n\nFinished: " . date('Y-m-d g:ia T') . "\n\n";		
	}

    /**
     * Return the usage of the task
     * 
     * @return string
     */
    public function help()
    {
        $output =<<<USAGE
Task: \033[36mdb:status\033[0m

With this taks you'll get an overview of the already executed migrations and 
which will be executed when running db:migrate.

This task not take arguments.

USAGE;
        return $output;
    }
}
