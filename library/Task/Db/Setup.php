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
 * This is a generic task which initializes a table
 * to hold migration version information.
 * This task is non-destructive and will only create the table
 * if it does not already exist, otherwise no other actions are performed.
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
class Task_Db_Setup extends Task_Base implements Ruckusing_Task_ITask
{
    /**
     * Primary task entry point
     *
     * @param mixed $args Arguments to task
     *
     * @return void
     */
    public function execute($args)
    {
        $return = 'Started: ' . date('Y-m-d g:ia T') . "\n\n"
            . "[db:setup]: \n";
        //it doesnt exist, create it
        if (! $this->_adapter->tableExists(RUCKUSING_TS_SCHEMA_TBL_NAME, true)) {
            $return .= sprintf("\tCreating table: '%s'", RUCKUSING_TS_SCHEMA_TBL_NAME);
            $this->_adapter->createSchemaVersionTable();
            $return .= "\n\tDone.";
        } else {
            $return .= sprintf(
                "\tNOTICE: table '%s' already exists. Nothing to do.",
                RUCKUSING_TS_SCHEMA_TBL_NAME
            );
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
Task: \033[36mdb:setup\033[0m

A basic task to initialize your DB for migrations is available. One should
always run this task when first starting out.

This task not take arguments.

USAGE;
        return $output;
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
