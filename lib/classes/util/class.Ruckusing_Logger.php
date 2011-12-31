<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
 * @package    classes
 * @subpackage util
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    
 * @link       https://github.com/ruckus/ruckusing-migrations
 */

/**
 * Logger of application
 *
 * @category   RuckusingMigrations
 * @package    classes
 * @subpackage util
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_Logger {

    /**
     * Instance of logger
     *
     * @var Ruckusing_Logger 
     */
    private static $instance;

    /**
     * file 
     * 
     * @var string
     */
    private $file = '';
  
    /**
     * __construct 
     * 
     * @param string $file Path of file to write logs
     *
     * @return Ruckusing_Logger
     */
    public function __construct($file) {
        $this->file = $file;
        $this->fp = fopen($file, "a+");
        //register_shutdown_function(array("Logger", "close_log"));
    }
  
    /**
     * Return an instance of logger
     * 
     * @param string $logfile Path of file to write logs
     *
     * @return Ruckusing_Logger
     */
    public static function instance($logfile) {
        if (self::$instance !== NULL) {
            return self::$instance;
        }
        self::$instance = new Ruckusing_Logger($logfile);
        return self::$instance; 
    }
  
    /**
     * log a message in file
     * 
     * @param string $msg 
     *
     * @return void
     * @throws Exception
     */
    public function log($msg) {
        if ($this->fp) {
            $ts = date('M d H:i:s', time());
            $line = sprintf("%s [info] %s\n", $ts, $msg); 
            fwrite($this->fp, $line);
        } else {
            throw new Exception(
                sprintf(
                    "Error: logfile '%s' not open for writing!", 
                    $this->file
                )
            );
        }
        
    }
  
    /**
     * close a file logs
     * 
     * @return void
     */
    public function close() {
        if ($this->fp) {
            fclose($this->fp);
        }
    }
  
}//class()

?>
