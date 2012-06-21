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
     * @var PDO
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
     * @param array            $dbConfig    Config DB for connect it
     * @param Ruckusing_Logger $logger The logger
     *
     * @return Ruckusing_Adapter_Base
     */
    function __construct($dbConfig, $logger)
    {
        $this->setDbConfig($dbConfig);
        $this->setLogger($logger);
    }

    /**
     * set dbConfig 
     * 
     * @param array $dbConfig Config DB for connect it
     *
     * @return Ruckusing_Adapter_Base
     */
    public function setDbConfig($dbConfig)
    {
        $this->checkDbConfig($dbConfig);
        $this->_dbConfig = $dbConfig;
        return $this;
    }

    /**
     * check DB infos 
     * 
     * @param array $dbConfig DB Infos
     *
     * @return boolean
     * @throws Ruckusing_Exception_Argument
     */
    public function checkDbConfig($dbConfig)
    {
        if (! is_array($dbConfig) || empty($dbConfig)) {
            require_once 'Ruckusing/Exception/Argument.php';
            throw new Ruckusing_Exception_Argument(
                'The argument dbConfig must be a array!'
            );
        }
        if (! array_key_exists('uri', $dbConfig)) {
            if (! array_key_exists('database', $dbConfig)) {
                require_once 'Ruckusing/Exception/Argument.php';
                throw new Ruckusing_Exception_Argument(
                    'The argument dbConfig must be contains index "database"'
                );
            }
            if (! array_key_exists('socket', $dbConfig)
                && ! array_key_exists('host', $dbConfig)
            ) {
                require_once 'Ruckusing/Exception/Argument.php';
                throw new Ruckusing_Exception_Argument(
                    'The argument dbConfig must be contains '
                    . 'index "host" or index "socket"'
                );
            }
            if (! array_key_exists('user', $dbConfig)) {
                require_once 'Ruckusing/Exception/Argument.php';
                throw new Ruckusing_Exception_Argument(
                    'The argument dbConfig must be contains index "user"'
                );
            }
            if (! array_key_exists('password', $dbConfig)) {
                require_once 'Ruckusing/Exception/Argument.php';
                throw new Ruckusing_Exception_Argument(
                    'The argument dbConfig must be contains index "password"'
                );
            }
        }
        return true;
    }

    /**
     * get dsn 
     * 
     * @return array
     */
    public function getDsn()
    {
        if (! isset($this->_dsn)) {
            $this->_dsn = $this->_initDsn();
        }
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

    /**
     * getConnexion 
     * 
     * @return PDO
     */
    public function getConnexion()
    {
        if (! isset($this->_conn)) {
            $this->_conn = $this->_createPdo();
        }
        return $this->_conn;
    }

    /**
     * Quote a raw string.
     * 
     * @param string|int|float|string[] $value Raw string
     *
     * @return string
     */
    public function quote($value)
    {
        if (is_int($value)) {
            return $value;
        } elseif (is_float($value)) {
            return sprintf('%F', $value);
        } elseif (is_array($value)) {
            foreach ($value as &$val) {
                $val = $this->quote($val);
            }
            return implode(', ', $value);
        }
        return "'" . addcslashes($value, "\000\n\r\\'\"\032") . "'";
    }

    /**
     * Creates a PDO instance to represent a connection
     * to the requested database.
     * 
     * @return PDO
     * @throws Ruckusing_Exception_AdapterConnexion
     */
    protected function _createPdo()
    {
        $user = '';
        if (array_key_exists('user', $this->_dbConfig)) {
            $user = $this->_dbConfig['user'];
        }
        $password = '';
        if (array_key_exists('password', $this->_dbConfig)) {
            $password = $this->_dbConfig['password'];
        }
        $options = array();
        if (array_key_exists('options', $this->_dbConfig)
            && is_array($this->_dbConfig['options'])
        ) {
            $options = $this->_dbConfig['options'];
        }
        try {
            $pdo = new PDO($this->getDsn(), $user, $password, $options);
        } catch (PDOException $e) {
            throw new Ruckusing_Exception_AdapterConnexion(
                $e->getMessage(),
                $e->getCode(),
                $e->getPrevious()
            );
        }

        return $pdo;
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
