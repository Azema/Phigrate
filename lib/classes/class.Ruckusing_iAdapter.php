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
 * Interface of adapters
 *
 * @category  RuckusingMigrations
 * @package   classes
 * @author    Cody Caughlan <toolbag@gmail.com>
 * @copyright 2010-2011 Cody Caughlan
 * @license   
 * @link      https://github.com/ruckus/ruckusing-migrations
 */
interface Ruckusing_iAdapter {

    /**
     * quote 
     * 
     * @param string $value 
     * @param string $column 
     * @return void
     */
	public function quote($value, $column);
    /**
     * supports_migrations 
     * 
     * @return void
     */
	public function supports_migrations();
    /**
     * native_database_types 
     * 
     * @return void
     */
	public function native_database_types();
    /**
     * schema 
     * 
     * @return void
     */
	public function schema();
	
    /**
     * execute 
     * 
     * @param string $query 
     * @return void
     */
	public function execute($query);
    /**
     * quote_string 
     * 
     * @param string $str 
     * @return void
     */
	public function quote_string($str);
	
	//database level operations
    /**
     * database_exists 
     * 
     * @param string $db 
     * @return void
     */
	public function database_exists($db);
    /**
     * create_table 
     * 
     * @param string $table_name 
     * @param array $options 
     * @return void
     */
	public function create_table($table_name, $options = array());
    /**
     * drop_database 
     * 
     * @param string $db 
     * @return void
     */
	public function drop_database($db);
	
    /*
     * table level operations
     */

    /**
     * show_fields_from 
     * 
     * @param string $tbl 
     * @return void
     */
	public function show_fields_from($tbl);
    /**
     * table_exists 
     * 
     * @param string $tbl 
     * @return void
     */
	public function table_exists($tbl);
    /**
     * drop_table 
     * 
     * @param string $tbl 
     * @return void
     */
	public function drop_table($tbl);
    /**
     * rename_table 
     * 
     * @param string $name 
     * @param string $new_name 
     * @return void
     */
	public function rename_table($name, $new_name);

    /*
     * column level operations
     */

    /**
     * rename_column 
     * 
     * @param string $table_name 
     * @param string $column_name 
     * @param string $new_column_name 
     * @return void
     */
	public function rename_column($table_name, $column_name, $new_column_name);
    /**
     * add_column 
     * 
     * @param string $table_name 
     * @param string $column_name 
     * @param string $type 
     * @param array $options 
     * @return void
     */
	public function add_column($table_name, $column_name, $type, $options = array());
    /**
     * remove_column 
     * 
     * @param string $table_name 
     * @param string $column_name 
     * @return void
     */
	public function remove_column($table_name, $column_name);
    /**
     * change_column 
     * 
     * @param string $table_name 
     * @param string $column_name 
     * @param string $type 
     * @param array $options 
     * @return void
     */
	public function change_column($table_name, $column_name, $type, $options = array());
    /**
     * remove_index 
     * 
     * @param string $table_name 
     * @param string $column_name 
     * @return void
     */
	public function remove_index($table_name, $column_name);
    /**
     * add_index 
     * 
     * @param string $table_name 
     * @param string $column_name 
     * @param array $options 
     * @return void
     */
	public function add_index($table_name, $column_name, $options = array());
}

?>
