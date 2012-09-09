<?php

/**
 * Phigrate
 *
 * PHP Version 5.3
 *
 * @category   Phigrate
 * @package    Phigrate_Adapter
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */

/**
 * Adapter base
 *
 * @category   Phigrate
 * @package    Phigrate_Adapter
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */
abstract class Phigrate_Adapter_Base
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
     * @var Phigrate_Logger
     */
    protected $_logger;
    
    /**
     * Export SQL
     *
     * @var boolean
     */
    protected $_export = false;

    /**
     * SQL to export
     *
     * @var string
     */
    protected $_sql;

    /**
     * __construct 
     * 
     * @param array            $dbConfig    Config DB for connect it
     * @param Phigrate_Logger $logger The logger
     *
     * @return Phigrate_Adapter_Base
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
     * @return Phigrate_Adapter_Base
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
     * @throws Phigrate_Exception_Argument
     */
    public function checkDbConfig($dbConfig)
    {
        if (! is_array($dbConfig) || empty($dbConfig)) {
            require_once 'Phigrate/Exception/Argument.php';
            throw new Phigrate_Exception_Argument(
                'The argument dbConfig must be a array!'
            );
        }
        if (! array_key_exists('uri', $dbConfig)) {
            if (! array_key_exists('database', $dbConfig)) {
                require_once 'Phigrate/Exception/Argument.php';
                throw new Phigrate_Exception_Argument(
                    'The argument dbConfig must be contains index "database"'
                );
            }
            if (! array_key_exists('socket', $dbConfig)
                && ! array_key_exists('host', $dbConfig)
            ) {
                require_once 'Phigrate/Exception/Argument.php';
                throw new Phigrate_Exception_Argument(
                    'The argument dbConfig must be contains '
                    . 'index "host" or index "socket"'
                );
            }
            if (! array_key_exists('user', $dbConfig)) {
                require_once 'Phigrate/Exception/Argument.php';
                throw new Phigrate_Exception_Argument(
                    'The argument dbConfig must be contains index "user"'
                );
            }
            if (! array_key_exists('password', $dbConfig)) {
                require_once 'Phigrate/Exception/Argument.php';
                throw new Phigrate_Exception_Argument(
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
     * @param Phigrate_Logger $logger The logger
     *
     * @return void
     */
    public function setLogger($logger)
    {
        if (! $logger instanceof Phigrate_Logger) {
            require_once 'Phigrate/Exception/Argument.php';
            throw new Phigrate_Exception_Argument(
                'Logger parameter must be instance of Phigrate_Logger'
            );
        }
        $this->_logger = $logger;
        return $this;
    }

    /**
     * get logger 
     * 
     * @return Phigrate_Logger
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
     * @throws Phigrate_Exception_AdapterConnexion
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
            if (PHP_VERSION_ID >= 50300) {
                throw new Phigrate_Exception_AdapterConnexion(
                    $e->getMessage(),
                    $e->getCode(),
                    $e->getPrevious()
                );
            } else {
                throw new Phigrate_Exception_AdapterConnexion(
                    $e->getMessage(),
                    $e->getCode()
                );
            }
        }

        return $pdo;
    }
    
    /**
     * Define flag export SQL
     * 
     * @param boolean $export The export flag
     * 
     * @return \Phigrate_Adapter_Base
     */
    public function setExport($export = false)
    {
        $this->_export = (boolean)$export;
        if ($this->hasExport()) {
            $this->initSql();
        }
        return $this;
    }
    
    /**
     * Return the flag export SQL
     * 
     * @return boolean
     */
    public function hasExport()
    {
        return $this->_export;
    }
    
    /**
     * Return the SQL
     * 
     * @return string
     */
    public function getSql()
    {
        return $this->_sql;
    }
    
    /**
     * Initialize the variable SQL
     * 
     * @return void
     */
    public function initSql()
    {
        $this->_sql = '';
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
