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
 * This utility class maps class names between their task names, back and forth.
 *
 *This framework relies on conventions which allow us to make certain
 * assumptions.
 *
 * Example valid task names are "db:version" which maps to a PHP class called DB_Version.
 * 
 * Namely, underscores are converted to colons, the first part of the task name is upper-cased
 * and the first character of the second part is capitalized.
 * 
 * Using this convention one can easily go back and forth between task names and PHP Class names.
 *
 * @category   RuckusingMigrations
 * @package    classes
 * @subpackage util
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_NamingUtil {

    /**
     * prefix of class name
     *
     * @var string 
     */
    const class_ns_prefix = 'Ruckusing_';

    /**
     * Return the name of task from the name of class 
     * 
     * @param string $klass The name of class
     *
     * @return string
     */
	public static function task_from_class_name($klass) {
        //strip namespace
        $klass = str_replace(self::class_ns_prefix, '', $klass);
        $klass = strtolower($klass);
        $klass = str_replace("_", ":", $klass);
        return $klass;
	}

    /**
     * Return the name of class from the name of task 
     * 
     * @param string $task 
     *
     * @return string
     * @throws Exception
     */
	public static function task_to_class_name($task) {
		$parts = explode(':', $task);
		if(count($parts) < 2) {
			throw new Exception("Task name invalid: $task");
		}
        return self::class_ns_prefix . strtoupper($parts[0]) 
            . '_' . ucfirst($parts[1]);
	}

    /**
     * class_from_file_name 
     * 
     * @param string $file_name 
     *
     * @return string
     */
	public static function class_from_file_name($file_name) {
		//we could be given either a string or an absolute path
		//deal with it appropriately
		if(is_file($file_name)) {
			$file_name = basename($file_name);
		}
		$regex = '/^class\.(\w+)\.php$/';	
		if(preg_match($regex, $file_name, $matches)) {
			if(count($matches) == 2) {
				return $matches[1];
			}
		}
		return "";		
	}
	
    /**
     * class_from_migration_file 
     * 
     * @param string $file_name 
     *
     * @return string
     */
	public static function class_from_migration_file($file_name) {
		if(preg_match('/^(\d+)_(.*)\.php$/', $file_name, $matches)) {
			if( count($matches) == 3) {
				return $matches[2];
			}
		}//if-preg-match
	}
	
    /**
     * camelcase 
     * 
     * @param string $str 
     *
     * @return string
     */
	public static function camelcase($str) {
        $str = preg_replace('/\s+/', '_', $str);
        $parts = explode("_", $str);
        //if there were no spaces in the input string
        //then assume its already camel-cased
        if(count($parts) == 0) { return $str; }
        $cleaned = "";
        foreach($parts as $word) {
            $cleaned .= ucfirst($word);
        }
        return $cleaned;  
    }

    /**
     * index_name 
     * 
     * @param string $table_name 
     * @param string $column_name 
     *
     * @return string
     */
	public static function index_name($table_name, $column_name) {
		$name = sprintf("idx_%s", self::underscore($table_name));
		//if the column parameter is an array then the user wants to create a multi-column
		//index
		if(is_array($column_name)) {
			$column_str = join("_and_", $column_name);
		} else {
			$column_str = $column_name;
		}
		$name .= sprintf("_%s", $column_str);
		return $name;
	}
  
    /**
     * underscore 
     * 
     * @param string $str 
     * @return string
     */
	public static function underscore($str) {
		return preg_replace('/\W/', '_', $str);
	}
}

?>
