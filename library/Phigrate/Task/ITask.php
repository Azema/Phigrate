<?php

/**
 * Phigrate
 *
 * PHP Version 5.3
 *
 * @category   Phigrate
 * @package    Phigrate_Task
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */

/**
 * Interface that all tasks must implement.
 *
 * @category   Phigrate
 * @package    Phigrate_Task
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */
interface Phigrate_Task_ITask
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

    /**
     * setAdapter
     *
     * @param Phigrate_Adapter_IAdapter $adapter Adapter RDBMS
     *
     * @return Phigrate_Task_ITask
     */
    public function setAdapter(Phigrate_Adapter_IAdapter $adapter);
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
