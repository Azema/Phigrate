<?php

/**
 * Phigrate
 *
 * PHP Version 5.3
 *
 * @category   Phigrate
 * @package    Task
 * @subpackage Db
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */

/**
 * @see Task_Base
 */
require_once 'Task/Base.php';

/**
 * @see Phigrate_Task_ITask
 */
require_once 'Phigrate/Task/ITask.php';

/**
 * Prints out a list of migrations that have and haven't been applied
 *
 * @category   Phigrate
 * @package    Task
 * @subpackage Db
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */
class Task_Db_Status extends Task_Base implements Phigrate_Task_ITask
{
    const BLUE_COLOR = "\033[40m\033[1;34m";
    const ORANGE_COLOR = "\033[40m\033[1;33m";
    const RED_COLOR = "\033[40m\033[1;31m";
    const END_COLOR = "\033[0m";

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
        require_once 'Phigrate/Util/Migrator.php';
        $util = new Phigrate_Util_Migrator($this->_adapter);
        $migrations = $util->getExecutedMigrations();
        $this->_logger->debug('Migrations: ' . var_export($migrations, true));
        $files = $util->getMigrationFiles($this->_migrationDir, 'up');
        $this->_logger->debug('Files: ' . var_export($files, true));
        $applied = array();
        $notApplied = array();
        foreach ($files as $file) {
            $key = array_search($file['version'], $migrations);
            if (false !== $key) {
                $applied[] = self::BLUE_COLOR . $file['class'] . ' [ ' . $file['version'] . ' ]' . self::END_COLOR;
                unset($migrations[$key]);
            } else {
                $notApplied[] = self::ORANGE_COLOR . $file['class'] . ' [ ' . $file['version'] . ' ]' . self::END_COLOR;
            }
        }
        if (count($applied) > 0) {
            $return .= $this->_displayMigrations($applied, 'APPLIED');
        }
        if (count($migrations) > 0) {
            foreach ($migrations as $key => $migration) {
                $migrations[$key] = self::RED_COLOR . '??? [ ' . $migration . ' ]' . self::END_COLOR;
            }
            $return .= $this->_displayMigrations($migrations, 'APPLIED WITHOUT MIGRATION FILE');
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

/* vim: set expandtab tabstop=4 shiftwidth=4: */
