<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
 * @package    Task
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */

/**
 * This is the abstract class of Tasks
 *
 * @category   RuckusingMigrations
 * @package    Task
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
abstract class Task_Base
{
    /**
     * adapter 
     * 
     * @var Ruckusing_Adapter_Base
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
     * @var Ruckusing_Logger
     */
    protected $_logger;

    /**
     * _migrationDir 
     * 
     * @var string
     */
    protected $_migrationDir;
	
    /**
     * __construct 
     * 
     * @param Ruckusing_Adapter_Base $adapter Adapter RDBMS
     *
     * @return Task_Db_Migrate
     */
    function __construct($adapter)
    {
        $this->_adapter = $adapter;
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
}
