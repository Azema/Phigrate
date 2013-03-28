<?php

/**
 * Phigrate
 *
 * PHP Version 5.3
 *
 * @category   Phigrate
 * @package    Task
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */

/**
 * This is the abstract class of Tasks
 *
 * @category   Phigrate
 * @package    Task
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */
abstract class Task_Base
{
    /**
     * adapter
     *
     * @var Phigrate_Adapter_Base
     */
    protected $_adapter = null;

    /**
     * task args
     *
     * @var array
     */
    protected $_taskArgs = array();

    /**
     * debug
     *
     * @var boolean
     */
    protected $_debug = false;

    /**
     * _logger
     *
     * @var Phigrate_Logger
     */
    protected $_logger;

    /**
     * _migrationDir
     *
     * @var string
     */
    protected $_migrationDir;

    /**
     * Manager de taches
     *
     * @var Phigrate_Task_Manager
     */
    protected $_manager;

    /**
     * __construct
     *
     * @param Phigrate_Adapter_Base $adapter Adapter RDBMS
     *
     * @return Task_Db_Base
     */
    function __construct($adapter)
    {
        $this->setAdapter($adapter);
        $this->_logger = $adapter->getLogger();
    }

    /**
     * setDirectoryOfMigrations : Define directory of migrations
     *
     * @param string $migrationDir Directory of migrations
     *
     * @return Migration_Db_Schema
     */
    public function setDirectoryOfMigrations($migrationDir)
    {
        $this->_migrationDir = $migrationDir;
        return $this;
    }

    /**
     * setAdapter
     *
     * @param Phigrate_Adapter_IAdapter $adapter Adapter RDBMS
     *
     * @return Phigrate_Task_ITask
     */
    public function setAdapter(Phigrate_Adapter_IAdapter $adapter)
    {
        $this->_adapter = $adapter;
        return $this;
    }

    /**
     * setManager
     *
     * @param Phigrate_Task_Manager $manager Le manager des taches
     *
     * @return Phigrate_Task_ITask
     */
    public function setManager(Phigrate_Task_Manager $manager)
    {
        $this->_manager = $manager;
        return $this;
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
