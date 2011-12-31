<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category  RuckusingMigrations
 * @package   tasks
 * @author    Cody Caughlan <toolbag@gmail.com>
 * @copyright 2010-2011 Cody Caughlan
 * @license   
 * @link      https://github.com/ruckus/ruckusing-migrations
 */

/**
 * @see Ruckusing_iTask 
 */
require_once RUCKUSING_BASE . '/lib/classes/task/class.Ruckusing_iTask.php';
/**
 * get config 
 */
require_once RUCKUSING_BASE . '/config/config.inc.php';

/**
 * This is a generic task which dumps the schema of the DB
 * as a text file.	
 *
 * @category  RuckusingMigrations
 * @package   tasks
 * @author    Cody Caughlan <toolbag@gmail.com>
 * @copyright 2010-2011 Cody Caughlan
 * @license   
 * @link      https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_DB_Schema implements Ruckusing_iTask {
	
    /**
     * adapter 
     * 
     * @var Ruckusing_BaseAdapter
     */
	private $adapter = null;
	
    /**
     * __construct 
     * 
     * @param Ruckusing_BaseAdapter $adapter 
     *
     * @return Ruckusing_DB_Schema
     */
	function __construct($adapter) {
		$this->adapter = $adapter;
	}
	
    /**
     * Primary task entry point
     * @param mixed $args 
     * @return void
     */
    public function execute($args) {
        try {
            echo "Started: " . date('Y-m-d g:ia T') . "\n\n";		
            echo "[db:schema]: \n";
            $schema = $this->adapter->schema();
            //write to disk
            $schema_file = RUCKUSING_DB_DIR . '/schema.txt';
            file_put_contents($schema_file, $schema, LOCK_EX);
            echo "\tSchema written to: $schema_file\n\n";
            echo "\n\nFinished: " . date('Y-m-d g:ia T') . "\n\n";							
        }catch(Exception $ex) {
            throw $ex; //re-throw
        }
    }//execute
	
}//class

?>
