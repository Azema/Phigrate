<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category  RuckusingMigrations
 * @package   Tasks
 * @author    Cody Caughlan <toolbag@gmail.com>
 * @copyright 2010-2011 Cody Caughlan
 * @license   GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/ruckus/ruckusing-migrations
 */

/**
 * This is a generic task which initializes a table 
 * to hold migration version information. 
 * This task is non-destructive and will only create the table 
 * if it does not already exist, otherwise no other actions are performed.	
 *
 * @category  RuckusingMigrations
 * @package   Tasks
 * @author    Cody Caughlan <toolbag@gmail.com>
 * @copyright 2010-2011 Cody Caughlan
 * @license   GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/ruckus/ruckusing-migrations
 */
class Task_Db_Setup implements Ruckusing_Task_ITask
{
    /**
     * adapter 
     * 
     * @var Ruckusing_BaseAdapter
     */
	private $_adapter = null;
	
    /**
     * __construct 
     * 
     * @param Ruckusing_BaseAdapter $adapter Adapter RDBMS
     *
     * @return Ruckusing_DB_Setup
     */
    function __construct($adapter)
    {
		$this->_adapter = $adapter;
	}
	
    /**
     * Primary task entry point
     * 
     * @param mixed $args Arguments to task
     *
     * @return void
     */
    public function execute($args)
    {
		echo 'Started: ' . date('Y-m-d g:ia T') . "\n\n";		
		echo "[db:setup]: \n";
		//it doesnt exist, create it
		if (! $this->_adapter->tableExists(RUCKUSING_TS_SCHEMA_TBL_NAME)) {
			echo sprintf("\tCreating table: %s", RUCKUSING_TS_SCHEMA_TBL_NAME);
            $this->_adapter->createSchemaVersionTable();
			echo "\n\tDone.\n";
		} else {
            echo sprintf(
                "\tNOTICE: table '%s' already exists. Nothing to do.", 
                RUCKUSING_TS_SCHEMA_TBL_NAME
            );
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
Task: \033[36mdb:setup\033[0m

A basic task to initialize your DB for migrations is available. One should 
always run this task when first starting out.

This task not take arguments.

USAGE;
        return $output;
    }
}
