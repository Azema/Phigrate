<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
 * @package    Ruckusing
 * @subpackage Ruckusing_Adapter
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */

/**
 * Adapter base
 *
 * @category   RuckusingMigrations
 * @package    Ruckusing
 * @subpackage Ruckusing_Adapter
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_Adapter_Base
{
    /**
     * dsn 
     * 
     * @var string
     */
	protected $_dsn;
    /**
     * db 
     * 
     * @var mixed
     */
    protected $_db;

    /**
     * connection to DB
     * 
     * @var mixed
     */
    protected $_conn;

    /**
     * logger 
     * 
     * @var Ruckusing_Logger
     */
    protected $_logger;
	
    /**
     * __construct 
     * 
     * @param array $dsn Config DB for connect it
     *
     * @return Ruckusing_Adapter_Base
     */
    function __construct($dsn)
    {
		$this->setDsn($dsn);
	}
	
    /**
     * set dsn 
     * 
     * @param array $dsn Config DB for connect it
     *
     * @return void
     */
    public function setDsn($dsn) 
    {
		$this->_dsn = $dsn;
    }

    /**
     * get dsn 
     * 
     * @return array
     */
    public function getDsn()
    {
		return $this->_dsn;
	}	

    /**
     * set db
     *
     * @param mixed $db The connection to DB
     *
     * @return void 
     */
    public function setDb($db) 
    {
		$this->_db = $db;
    }

    /**
     * get db 
     * 
     * @return mixed
     */
    public function getDb()
    {
		return $this->_db;
	}	
	
    /**
     * set logger 
     * 
     * @param Ruckusing_Logger $logger The logger
     *
     * @return void
     */
    public function setLogger($logger)
    {
		$this->_logger = $logger;
	}

    /**
     * get logger 
     * 
     * @return Ruckusing_Logger
     */
    public function getLogger()
    {
		return $this->_logger;
	}
	
	//alias
    /**
     * has table 
     * 
     * @param string $tbl Table name
     *
     * @return boolean
     */
    public function hasTable($tbl)
    {
		return $this->tableExists($tbl);
	}
}
