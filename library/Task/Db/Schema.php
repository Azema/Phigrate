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
 * This is a generic task which dumps the schema of the DB
 * as a text file.	
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
class Task_Db_Schema implements Ruckusing_Task_ITask
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
     * @return Ruckusing_DB_Schema
     */
    public function __construct($adapter)
    {
		$this->_adapter = $adapter;
    }

    /**
     * setDirectoryMigration : Define directory of migrations
     * 
     * @param string $migrationDir Directory of migrations
     *
     * @return Task_Db_Schema
     */
    public function setDirectoryOfMigrations($migrationDir)
    {
        $this->_migrationDir = $migrationDir;
        return $this;
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
            $schema_file = $this->_migrationDir . '/schema.txt';
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
