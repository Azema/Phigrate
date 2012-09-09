<?php

/**
 * Phigrate
 *
 * PHP Version 5.3
 *
 * @category   Phigrate
 * @package    Phigrate
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */

/**
 * Logger of application
 *
 * @category   Phigrate
 * @package    Phigrate
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */
class Phigrate_Logger
{
    /**
     * @var string Log type Error
     */
    const ERROR = 1;
    /**
     * @var string Log type Warning
     */
    const WARNING = 2;
    /**
     * @var string Log type Info
     */
    const INFO = 3;
    /**
     * @var string Log type Debug
     */
    const DEBUG = 4;

    /**
     * Instance of logger
     *
     * @var Phigrate_Logger
     */
    private static $_instance;

    /**
     * file
     *
     * @var string
     */
    private $_file = '';

    /**
     * File descriptor
     *
     * @var resource
     */
    private $_fp;

    /**
     * _priority
     *
     * @var integer
     */
    private $_priority = 99;

    /**
     * __construct
     *
     * @param string $file Path of file to write logs
     *
     * @return Phigrate_Logger
     */
    protected function __construct($file)
    {
        $this->_file = $file;
        $this->_fp = fopen($this->_file, "a+");
    }

    /**
     * Close the file descriptor
     *
     * @return void
     */
    public function __destruct()
    {
        $this->debug(__METHOD__);
        $this->close();
    }

    /**
     * Return an instance of logger
     *
     * @param string $logfile Path of file to write logs
     *
     * @return Phigrate_Logger
     */
    public static function instance($logfile)
    {
        if (isset(self::$_instance)) {
            return self::$_instance;
        }
        self::$_instance = new Phigrate_Logger($logfile);
        return self::$_instance;
    }

    /**
     * set priority of log
     *
     * @param mixed $priority The priority of log
     *
     * @return Phigrate_Logger
     */
    public function setPriority($priority)
    {
        $this->_priority = $priority;
        return $this;
    }

    /**
     * getPriority
     *
     * @return integer
     */
    public function getPriority()
    {
        return $this->_priority;
    }

    /**
     * Log a message with debug type
     *
     * @param string $msg Message to log
     *
     * @return void
     */
    public function debug($msg)
    {
        $this->log($msg, 'debug');
    }

    /**
     * Log a message with info type
     *
     * @param string $msg Message to log
     *
     * @return void
     */
    public function info($msg)
    {
        $this->log($msg, 'info');
    }

    /**
     * Log a message with warn type
     *
     * @param string $msg Message to log
     *
     * @return void
     */
    public function warn($msg)
    {
        $this->log($msg, 'warn');
    }

    /**
     * Log a message with err type
     *
     * @param string $msg Message to log
     *
     * @return void
     */
    public function err($msg)
    {
        $this->log($msg, 'err');
    }

    /**
     * log a message in file
     *
     * @param string $msg  Message to log
     * @param string $type Type of log (default: info)
     *
     * @return void
     * @throws Exception
     */
    public function log($msg, $type = 'info')
    {
        $prioOfType = $this->_getPriorityFromType($type);
        if ($this->_fp && $prioOfType <= $this->_priority) {
            $ts = date('M d H:i:s');
            $line = sprintf("%s [%s] %s\n", $ts, strtoupper($type), $msg);
            fwrite($this->_fp, $line);
        }
    }

    /**
     * get priority from type
     *
     * @param string $type The type of log
     *
     * @return integer
     */
    private function _getPriorityFromType($type)
    {
        $priority = 0;
        switch (strtolower($type)) {
            case 'err':
                $priority = self::ERROR;
                break;
            case 'warn':
                $priority = self::WARNING;
                break;
            case 'debug':
                $priority = self::DEBUG;
                break;
            case 'info':
            default:
                $priority = self::INFO;
                break;
        }
        return $priority;
    }

    /**
     * close a file logs
     *
     * @return void
     */
    public function close()
    {
        $this->debug(__METHOD__);
        if ($this->_fp) {
            $closed = fclose($this->_fp);
            if ($closed) {
                $this->_fp = null;
                self::$_instance = null;
            } else {
                echo 'Error closing the log file';
            }
        }
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
