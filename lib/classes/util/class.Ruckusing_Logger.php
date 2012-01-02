<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
 * @package    Classes
 * @subpackage Util
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */

/**
 * Logger of application
 *
 * @category   RuckusingMigrations
 * @package    Classes
 * @subpackage Util
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_Logger
{
    /**
     * Instance of logger
     *
     * @var Ruckusing_Logger 
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
     * __construct 
     * 
     * @param string $file Path of file to write logs
     *
     * @return Ruckusing_Logger
     */
    public function __construct($file)
    {
        $this->_file = $file;
        $this->_fp = fopen($this->_file, "a+");
        //register_shutdown_function(array("Logger", "close_log"));
    }
  
    /**
     * Return an instance of logger
     * 
     * @param string $logfile Path of file to write logs
     *
     * @return Ruckusing_Logger
     */
    public static function instance($logfile)
    {
        if (isset(self::$_instance)) {
            return self::$_instance;
        }
        self::$_instance = new Ruckusing_Logger($logfile);
        return self::$_instance; 
    }
  
    /**
     * log a message in file
     * 
     * @param string $msg Message to log
     *
     * @return void
     * @throws Exception
     */
    public function log($msg)
    {
        if ($this->_fp) {
            $ts = date('M d H:i:s', time());
            $line = sprintf("%s [info] %s\n", $ts, $msg); 
            fwrite($this->_fp, $line);
        } else {
            throw new Exception(
                sprintf(
                    "Error: logfile '%s' not open for writing!", 
                    $this->_file
                )
            );
        }
    }
  
    /**
     * close a file logs
     * 
     * @return void
     */
    public function close()
    {
        if ($this->_fp) {
            fclose($this->_fp);
        }
    }
  
}
