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
class Task_Db_Schema extends Task_Base implements Ruckusing_Task_ITask
{
    /**
     * Primary task entry point
     *
     * @param mixed $args Arguments to the task
     *
     * @return string
     */
    public function execute($args)
    {
        $return = 'Started: ' . date('Y-m-d g:ia T') . "\n\n"
            . "[db:schema]: \n";
        try {
            $schema = $this->_adapter->schema();
            //write to disk
            $schema_file = $this->_migrationDir . '/schema.txt';
            file_put_contents($schema_file, $schema, LOCK_EX);
        } catch (Exception $ex) {
            if (! $ex instanceof Ruckusing_Exception_Task) {
                $ex = new Ruckusing_Exception_Task(
                    $ex->getMessage(),
                    $ex->getCode(),
                    $ex
                );
            }
            throw $ex;
        }
        $return .= "\tSchema written to: $schema_file\n\n"
            . "\n\nFinished: " . date('Y-m-d g:ia T') . "\n\n";
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
