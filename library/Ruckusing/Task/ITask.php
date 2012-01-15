<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
 * @package    Ruckusing_Task
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */

/**
 * Interface that all tasks must implement.
 *
 * @category   RuckusingMigrations
 * @package    Ruckusing_Task
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
interface Ruckusing_Task_ITask
{
    /**
     * execute the task
     * 
     * @param array $args Argument to the task
     *
     * @return string
     */
    public function execute($args);
    
    /**
     * Return the usage of the task
     * 
     * @return string
     */
    public function help();

    /**
     * setDirectoryOfMigrations 
     * 
     * @param string $migrationDir The migration directory path
     *
     * @return void
     */
    public function setDirectoryOfMigrations($migrationDir);
}
