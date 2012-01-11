<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
 * @package    Ruckusing_Adapter
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */

/**
 * Adapter base
 *
 * @category   RuckusingMigrations
 * @package    Ruckusing_Adapter
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
abstract class Ruckusing_Adapter_Base
{
    /**
     * dsn 
     * 
     * @var array
     */
    protected $_dsn;

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
     * @param array            $dsn    Config DB for connect it
     * @param Ruckusing_Logger $logger The logger
     *
     * @return Ruckusing_Adapter_Base
     */
    function __construct($dsn, $logger)
    {
        $this->setDsn($dsn);
        $this->setLogger($logger);
	}
	
    /**
     * set dsn 
     * 
     * @param array $dsn Config DB for connect it
     *
     * @return Ruckusing_Adapter_Base
     */
    public function setDsn($dsn) 
    {
        if (! is_array($dsn)) {
            require_once 'Ruckusing/Exception/Argument.php';
            throw new Ruckusing_Exception_Argument(
                'The argument DSN must be a array!'
            );
        }
        $this->checkDsn($dsn);
        $this->_dsn = $dsn;
        return $this;
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
     * set logger 
     * 
     * @param Ruckusing_Logger $logger The logger
     *
     * @return void
     */
    public function setLogger($logger)
    {
        if (! $logger instanceof Ruckusing_Logger) {
            require_once 'Ruckusing/Exception/Argument.php';
            throw new Ruckusing_Exception_Argument(
                'Logger parameter must be instance of Ruckusing_Logger'
            );
        }
        $this->_logger = $logger;
        return $this;
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
		return $this->tableExists($tbl, true);
	}
}
