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
 * @see Ruckusing_iAdapter 
 */
require_once RUCKUSING_BASE . '/lib/classes/class.Ruckusing_iAdapter.php';

/**
 * Migration base
 *
 * @category  RuckusingMigrations
 * @package   classes
 * @author    Cody Caughlan <toolbag@gmail.com>
 * @copyright 2010-2011 Cody Caughlan
 * @license   
 * @link      https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_BaseMigration {
	
    /**
     * adapter 
     * 
     * @var Ruckusing_BaseAdapter
     */
	private $adapter;
	
    /**
     * set_adapter 
     * 
     * @param Ruckusing_BaseAdapter $adapter 
     * @return void
     */
	public function set_adapter($adapter) {
		$this->adapter = $adapter;
	}
	
    /**
     * get_adapter 
     * 
     * @return Ruckusing_BaseAdapter
     */
	public function get_adapter() {
		return $this->adapter;
	}
	
    /**
     * create_database 
     * 
     * @param string $name 
     * @param array $options 
     *
     * @return boolean
     */
	public function create_database($name, $options = null) {
		return $this->adapter->create_database($name, $options);
	}
	
    /**
     * drop_database 
     * 
     * @param string $name 
     *
     * @return boolean
     */
	public function drop_database($name) {
		return $this->adapter->drop_database($name);		
	}
	
    /**
     * drop_table 
     * 
     * @param string $tbl 
     *
     * @return boolean
     */
	public function drop_table($tbl) {
		return $this->adapter->drop_table($tbl);				
	}
	
    /**
     * rename_table 
     * 
     * @param string $name 
     * @param string $new_name 
     *
     * @return boolean
     */
	public function rename_table($name, $new_name) {
		return $this->adapter->rename_table($name, $new_name);						
	}
		
    /**
     * rename_column 
     * 
     * @param string $tbl_name 
     * @param string $column_name 
     * @param string $new_column_name 
     *
     * @return boolean
     */
	public function rename_column($tbl_name, $column_name, $new_column_name) {
		return $this->adapter->rename_column($tbl_name, $column_name, $new_column_name);
	}

    /**
     * add_column 
     * 
     * @param string $table_name 
     * @param string $column_name 
     * @param string $type 
     * @param array $options 
     *
     * @return boolean
     */
	public function add_column($table_name, $column_name, $type, $options = array()) {
		return $this->adapter->add_column($table_name, $column_name, $type, $options);
	}
	
    /**
     * remove_column 
     * 
     * @param string $table_name 
     * @param string $column_name 
     * @return void
     */
	public function remove_column($table_name, $column_name) {
		return $this->adapter->remove_column($table_name, $column_name);
	}

    /**
     * change_column 
     * 
     * @param string $table_name 
     * @param string $column_name 
     * @param string $type 
     * @param array $options 
     *
     * @return boolean
     */
	public function change_column($table_name, $column_name, $type, $options = array()) {
		return $this->adapter->change_column($table_name, $column_name, $type, $options);	
	}
	
    /**
     * add_index 
     * 
     * @param string $table_name 
     * @param string $column_name 
     * @param array $options 
     *
     * @return boolean
     */
	public function add_index($table_name, $column_name, $options = array()) {
		return $this->adapter->add_index($table_name, $column_name, $options);			
	}
	
    /**
     * remove_index 
     * 
     * @param string $table_name 
     * @param string $column_name 
     * @param array $options 
     *
     * @return boolean
     */
	public function remove_index($table_name, $column_name, $options = array()) {
		return $this->adapter->remove_index($table_name, $column_name, $options);
	}
	
    /**
     * create_table 
     * 
     * @param string $table_name 
     * @param array $options 
     *
     * @return boolean
     */
	public function create_table($table_name, $options = array()) {
		return $this->adapter->create_table($table_name, $options);
	}
	
    /**
     * execute 
     * 
     * @param string $query 
     *
     * @return boolean
     */
	public function execute($query) {
		return $this->adapter->query($query);
	}
	
    /**
     * select_one 
     * 
     * @param string $sql 
     *
     * @return mixed
     */
	public function select_one($sql) {
		return $this->adapter->select_one($sql);
	}

    /**
     * select_all 
     * 
     * @param string $sql 
     *
     * @return mixed
     */
	public function select_all($sql) {
		return $this->adapter->select_all($sql);
    }

    /**
     * query 
     * 
     * @param string $sql 
     *
     * @return boolean
     */
	public function query($sql) {
		return $this->adapter->query($sql);		
	}
	
    /**
     * quote_string 
     * 
     * @param string $str 
     *
     * @return string
     */
	public function quote_string($str) {
        return $this->adapter->quote_string($str); 
    }
	
}//Ruckusing_BaseMigration

?>
