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
 * @see Task_Base
 */
require_once 'Task/Base.php';

/**
 * @see Ruckusing_Task_ITask
 */
require_once 'Ruckusing/Task/ITask.php';

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
class Task_Db_Status extends Task_Base implements Ruckusing_Task_ITask
{
    /**
     * Primary task entry point
     * 
     * @param array $args Arguments to task
     *
     * @return string
     */
    public function execute($args)
    {
		$return = 'Started: ' . date('Y-m-d g:ia T') . "\n\n"
            . "[db:status]:\n";
        require_once 'Ruckusing/Util/Migrator.php';
		$util = new Ruckusing_Util_Migrator($this->_adapter);
		$migrations = $util->getExecutedMigrations();
        $files = $util->getMigrationFiles($this->_migrationDir, 'up');
		$applied = array();
		$notApplied = array();
		foreach ($files as $file) {
            if (in_array($file['version'], $migrations)) {
                $applied[] = $file['class'] . ' [ ' . $file['version'] . ' ]';
            } else {
                $notApplied[] = $file['class'] . ' [ ' . $file['version'] . ' ]';
            }
        }
        if (count($applied) > 0) {
            $return .= $this->_displayMigrations($applied, 'APPLIED');
        }
        if (count($notApplied) > 0) {
            $return .= $this->_displayMigrations($notApplied, 'NOT APPLIED');
        }
        $return .= "\n\nFinished: " . date('Y-m-d g:ia T') . "\n\n";		
        return $return;
    }

    /**
     * _displayMigrations 
     * 
     * @param array  $migrations The migrations
     * @param string $title      The title of section
     *
     * @return string
     */
    protected function _displayMigrations($migrations, $title)
    {
        $return = "\n\n===================== {$title} =======================\n";
        foreach ($migrations as $a) {
            $return .= "\t" . $a . "\n";
        }
        return $return;
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
