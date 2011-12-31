<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category  RuckusingMigrations
 * @package   classes
 * @author    Cody Caughlan <toolbag@gmail.com>
 * @copyright 2010-2011 Cody Caughlan
 * @license   
 * @link      https://github.com/ruckus/ruckusing-migrations
 */

/**
 * Adapter base
 *
 * @category  RuckusingMigrations
 * @package   classes
 * @author    Cody Caughlan <toolbag@gmail.com>
 * @copyright 2010-2011 Cody Caughlan
 * @license   
 * @link      https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_BaseAdapter {
    /**
     * dsn 
     * 
     * @var string
     */
	private $dsn;
    /**
     * db 
     * 
     * @var mixed
     */
	private $db;
    /**
     * conn 
     * 
     * @var mixed
     */
	private $conn;
	
    /**
     * __construct 
     * 
     * @param string $dsn 
     * @return Ruckusing_BaseAdapter
     */
	function __construct($dsn) {
		$this->set_dsn($dsn);
	}
	
    /**
     * set_dsn 
     * 
     * @param string $dsn 
     *
     * @return void
     */
	public function set_dsn($dsn) { 
		$this->dsn = $dsn;
    }

    /**
     * get_dsn 
     * 
     * @return strinfg
     */
	public function get_dsn() {
		return $this->dsn;
	}	

    /**
     * set_db
     *
     * @param mixed $db
     *
     * @return void 
     */
	public function set_db($db) { 
		$this->db = $db;
    }

    /**
     * get_db 
     * 
     * @return mixed
     */
	public function get_db() {
		return $this->db;
	}	
	
    /**
     * set_logger 
     * 
     * @param Ruckusing_Logger $logger 
     *
     * @return void
     */
	public function set_logger($logger) {
		$this->logger = $logger;
	}

    /**
     * get_logger 
     * 
     * @return Ruckusing_Logger
     */
	public function get_logger() {
		return $this->logger;
	}
	
	//alias
    /**
     * has_table 
     * 
     * @param string $tbl 
     * @return boolean
     */
	public function has_table($tbl) {
		return $this->table_exists($tbl);
	}
	
}
?>
