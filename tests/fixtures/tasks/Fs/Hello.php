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
class Task_Fs_Hello implements Phigrate_Task_ITask
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
        return 'Hello ' . $args;
    }

    /**
     * Return the usage of the task
     *
     * @return string
     */
    public function help()
    {
        $output =<<<USAGE
Task: \033[36mfs:hello\033[0m

This task say hello

USAGE;
        return $output;
    }

    public function setDirectoryOfMigrations($dir)
    {
    }

    public function setAdapter(Phigrate_Adapter_IAdapter $adapter)
    {
    }

    public function setManager(Phigrate_Task_Manager $manager)
    {
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
