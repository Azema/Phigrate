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
 * @see Ruckusing_ITask 
 */
require_once RUCKUSING_BASE . '/lib/classes/task/class.Ruckusing_ITask.php';
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
 * @package   Tasks
 * @author    Cody Caughlan <toolbag@gmail.com>
 * @copyright 2010-2011 Cody Caughlan
 * @license   GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_DB_Status implements Ruckusing_ITask
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
     * @return Ruckusing_DB_Status
     */
    function __construct($adapter)
    {
		$this->_adapter = $adapter;
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
}
