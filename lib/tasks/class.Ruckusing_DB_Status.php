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
 * @see Ruckusing_MigratorUtil 
 */
require_once RUCKUSING_BASE . '/lib/classes/util/class.Ruckusing_MigratorUtil.php';

/**
 * Prints out a list of migrations that have and haven't been applied
 *
 * @category  RuckusingMigrations
 * @package   tasks
 * @author    Cody Caughlan <toolbag@gmail.com>
 * @copyright 2010-2011 Cody Caughlan
 * @license   
 * @link      https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_DB_Status implements Ruckusing_iTask {
	
    /**
     * adapter 
     * 
     * @var Ruckusing_BaseAdapter
     */
	private $adapter = null;
    /**
     * create_ddl 
     * 
     * @var string
     */
	private $create_ddl = "";
	
    /**
     * __construct 
     * 
     * @param Ruckusing_BaseAdapter $adapter 
     *
     * @return Ruckusing_DB_Status
     */
	function __construct($adapter) {
		$this->adapter = $adapter;
	}
	
    /**
     * Primary task entry point
     * 
     * @param array $args 
     * @return void
     */
	public function execute($args) {
		echo "Started: " . date('Y-m-d g:ia T') . "\n\n";		
		echo "[db:status]: \n";
		$util = new Ruckusing_MigratorUtil($this->adapter);
		$migrations = $util->get_executed_migrations();
		$files = $util->get_migration_files(RUCKUSING_MIGRATION_DIR, 'up');
		$applied = array();
		$not_applied = array();
		foreach($files as $file) {
            if(in_array($file['version'], $migrations)) {
                $applied[] = $file['class'] . ' [ ' . $file['version'] . ' ]';
            } else {
                $not_applied[] = $file['class'] . ' [ ' . $file['version'] . ' ]';
            }
        }
        echo "\n\n===================== APPLIED ======================= \n";
        foreach($applied as $a) {
            echo "\t" . $a . "\n";
        }
        echo "\n\n===================== NOT APPLIED ======================= \n";
        foreach($not_applied as $na) {
            echo "\t" . $na . "\n";
        }
		echo "\n\nFinished: " . date('Y-m-d g:ia T') . "\n\n";		
	}
}

?>
