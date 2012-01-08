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
 * This is a generic task which dumps the schema of the DB
 * as a text file.	
 *
 * @category  RuckusingMigrations
 * @package   Tasks
 * @author    Cody Caughlan <toolbag@gmail.com>
 * @copyright 2010-2011 Cody Caughlan
 * @license   GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/ruckus/ruckusing-migrations
 */
class Task_Db_Schema implements Ruckusing_Task_ITask
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
     * @return Ruckusing_DB_Schema
     */
    function __construct($adapter)
    {
		$this->_adapter = $adapter;
	}
	
    /**
     * Primary task entry point
     *
     * @param mixed $args Arguments to the task
     *
     * @return void
     */
    public function execute($args)
    {
        try {
            echo 'Started: ' . date('Y-m-d g:ia T') . "\n\n";		
            echo "[db:schema]: \n";
            $schema = $this->_adapter->schema();
            //write to disk
            $schema_file = RUCKUSING_DB_DIR . '/schema.txt';
            file_put_contents($schema_file, $schema, LOCK_EX);
            echo "\tSchema written to: $schema_file\n\n";
            echo "\n\nFinished: " . date('Y-m-d g:ia T') . "\n\n";							
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Return the usage of the task
     * 
     * @return string
     */
    public function help()
    {
        $output =<<<USAGE
Task: \033[36mdb:schema\033[0m

It can be beneficial to get a dump of the DB in raw SQL format which represents 
the current version.

\033[31mNote\033[0m: This dump only contains the actual schema (e.g. the DML needed to 
reconstruct the DB), but not any actual data. 

In MySQL terms, this task would not be the same as running the mysqldump command 
(which by defaults does include any data in the tables).

USAGE;
        return $output;
    }
}
