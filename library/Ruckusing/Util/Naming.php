<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
 * @package    Ruckusing_Util
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */

/**
 * This utility class maps class names between their task names, back and forth.
 *
 * This framework relies on conventions which allow us to make certain
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
 * @package    Ruckusing_Util
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_Util_Naming
{
    /**
     * prefix of class name
     *
     * @var string 
     */
    const CLASS_NS_PREFIX = 'Task_';

    /**
     * Return the name of task from the namespace and the basename
     * 
     * @param string $namespace The namespace of a task
     * @param string $basename  The name of class
     *
     * @return string
     */
    public static function taskNameFromNamespaceAndBasename($namespace, $basename)
    {
        return strtolower($namespace . ':' . $basename);
	}

    /**
     * Return the name of task from the name of class 
     * 
     * @param string $klass The name of class
     *
     * @return string
     */
    public static function taskFromClassName($klass)
    {
        //strip namespace
        $klass = str_replace(self::CLASS_NS_PREFIX, '', $klass);
        $klass = strtolower($klass);
        $klass = str_replace('_', ':', $klass);
        return $klass;
	}

    /**
     * Return the name of class from the name of task 
     * 
     * @param string $task The task name
     *
     * @return string
     * @throws Exception
     */
    public static function taskToClassName($task)
    {
		$parts = explode('::', $task);
        if (count($parts) < 2) {
            require_once 'Ruckusing/Exception/Argument.php';
			throw new Ruckusing_Exception_Argument('Task name invalid: ' . $task);
		}
        return self::CLASS_NS_PREFIX . ucfirst($parts[0]) 
            . '_' . ucfirst($parts[1]);
	}

    /**
     * class from file name 
     * 
     * @param string $fileName The file name
     *
     * @return string
     */
    public static function classFromFileName($fileName)
    {
		//we could be given either a string or an absolute path
		//deal with it appropriately
        if (is_file($fileName)) {
            $parts = explode(DIRECTORY_SEPARATOR, $fileName);
            $namespace = $parts[count($parts)-2];
			$fileName = substr($parts[count($parts)-1], 0, -4);
        }
        return self::CLASS_NS_PREFIX . ucfirst($namespace) 
            . '_' . ucfirst($fileName);
	}
	
    /**
     * class from migration file 
     * 
     * @param string $fileName The file name
     *
     * @return string
     */
    public static function classFromMigrationFile($fileName)
    {
		if (preg_match('/^(\d+)_(.*)\.php$/', $fileName, $matches)) {
			if (count($matches) == 3) {
				return $matches[2];
			}
		}
	}
	
    /**
     * camelcase 
     * 
     * @param string $str String to camelcased
     *
     * @return string
     */
    public static function camelcase($str)
    {
        $str = preg_replace('/\s+/', '_', $str);
        $parts = explode("_", $str);
        //if there were no spaces in the input string
        //then assume its already camel-cased
        if (count($parts) == 0) return $str;
        $cleaned = '';
        foreach ($parts as $word) {
            $cleaned .= ucfirst($word);
        }
        return $cleaned;  
    }
  
    /**
     * underscore 
     * 
     * @param string $str String to change
     *
     * @return string
     */
    public static function underscore($str)
    {
		return preg_replace('/\W/', '_', $str);
	}

    /**
     * index name 
     * 
     * @param string       $tableName  The table name
     * @param string|array $columnName The column name
     *
     * @return string
     */
    public static function indexName($tableName, $columnName)
    {
		$name = sprintf("idx_%s", self::underscore($tableName));
        // if the column parameter is an array then the user wants 
        // to create a multi-column index
		if (is_array($columnName)) {
			$columnStr = join('_and_', $columnName);
		} else {
			$columnStr = $columnName;
		}
		$name .= sprintf('_%s', $columnStr);
		return $name;
	}
}
