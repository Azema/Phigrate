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
 * This task retrieves the current version of the schema.
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
class Task_Db_Version extends Task_Base implements Ruckusing_Task_ITask
{
    /**
     * Primary task entry point
     *
     * @param mixed $args Arguments to task
     *
     * @return string
     */
    public function execute($args)
    {
        $return = 'Started: ' . date('Y-m-d g:ia T') . "\n\n"
            . "[db:version]:\n";
        if (! $this->_adapter->tableExists(RUCKUSING_TS_SCHEMA_TBL_NAME)) {
            //it doesnt exist, create it
            $return .= "\tSchema version table (" . RUCKUSING_TS_SCHEMA_TBL_NAME
                . ") does not exist. Do you need to run 'db:setup'?";
        } else {
            //it exists, read the version from it
            // We only want one row but we cannot assume that we are using MySQL and use a LIMIT statement
            // as it is not part of the SQL standard. Thus we have to select all rows and use PHP to return
            // the record we need
            $versions_nested = $this->_adapter->selectAll(
                sprintf(
                    'SELECT version FROM %s',
                    $this->_adapter->identifier(RUCKUSING_TS_SCHEMA_TBL_NAME)
                )
            );
            $versions = array();
            foreach ($versions_nested as $v) {
                $versions[] = $v['version'];
            }
            $num_versions = count($versions);
            if ($num_versions > 0) {
                sort($versions); //sorts lowest-to-highest (ascending)
                $version = (string)$versions[$num_versions-1];
                $return .= sprintf("\tCurrent version: %s", $version);
            } else {
                $return .= "\tNo migrations have been executed.";
            }
        }
        $return .= "\n\nFinished: " . date('Y-m-d g:ia T') . "\n\n";
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
Task: \033[36mdb:version\033[0m

It is always possible to ask the framework (really the DB) what version it is
currently at.

This task not take arguments.

USAGE;
        return $output;
    }
}
