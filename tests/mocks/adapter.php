<?php

require_once 'Phigrate/Adapter/Mysql/Adapter.php';
/**
 * Mock class adapter RDBMS
 *
 * @category   Phigrate
 * @package    Mocks
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/azema/phigrate-migrations
 */
class adapterMock extends Phigrate_Adapter_Mysql_Adapter
{
    public function __construct($dbConfig, $logger)
    {
        $this->_conn = new pdoMock();
        $this->_logger = new logMock();
    }
}

/**
 * Mock class PDO
 *
 * @category   Phigrate
 * @package    Mocks
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/azema/phigrate-migrations
 */
class pdoMock
{
    protected $_queries = array();

    public function query($query)
    {
        $this->_queries[] = $query;
    }

    public function getQueries()
    {
        return $this->_queries;
    }
}

/**
 * Mock class log
 *
 * @category   Phigrate
 * @package    Mocks
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/azema/phigrate-migrations
 */
class logMock
{
    public $debug = array();
    public $info = array();
    public $warn = array();
    public $err = array();

    public function debug($msg)
    {
        $this->debug[] = $msg;
    }

    public function info($msg)
    {
        $this->info[] = $msg;
    }

    public function warn($msg)
    {
        $this->warn[] = $msg;
    }

    public function err($msg)
    {
        $this->err[] = $msg;
    }

    public function log($msg)
    {
        $this->info[] = $msg;
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
