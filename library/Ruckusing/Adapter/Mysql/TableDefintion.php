<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
 * @package    Ruckusing_Adapter
 * @subpackage Ruckusing_Adapter_Mysql
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */

/**
 * @see Ruckusing_Adapter_TableDefinition
 */
require_once 'Ruckusing/Adapter/TableDefinition.php';

/**
 * Class of mysql table definition
 *
 * @category   RuckusingMigrations
 * @package    Ruckusing_Adapter
 * @subpackage Ruckusing_Adapter_Mysql
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_Adapter_Mysql_TableDefinition extends Ruckusing_Adapter_TableDefinition
{
    /**
     * adapter MySQL
     * 
     * @var Ruckusing_Adapter_Mysql_Adapter
     */
	protected $_adapter;
    /**
     * sql 
     * 
     * @var string
     */
	protected $_sql = '';
    /**
     * initialized 
     * 
     * @var boolean
     */
	protected $_initialized = false;
    /**
     * primary keys 
     * 
     * @var array
     */
	protected $_primaryKeys = array();
    /**
     * auto generate id 
     * 
     * @var boolean
     */
	protected $_autoGenerateId = true;
	
    /**
     * __construct 
     * 
     * @param Ruckusing_Adapter_Base $adapter Adapter MySQL
     * @param string                 $name    The table name
     * @param array                  $options The options definition
     *
     * @return Ruckusing_Adapter_Mysql_TableDefinition
     * @throws Ruckusing_Exception_MissingAdapter
     * @throws Ruckusing_Exception_Argument
     */
    function __construct($adapter, $name, $options = array())
    {
		//sanity check
		if (! $adapter instanceof Ruckusing_Adapter_Base) {
            throw new Ruckusing_Exception_MissingAdapter(
                'Invalid MySQL Adapter instance.'
            );
		}
		if (!isset($name) || ! is_string($name) || empty($name)) {
			throw new Ruckusing_Exception_Argument("Invalid 'name' parameter");
		}

		$this->_adapter = $adapter;
		$this->_name = $name;
		$this->_options = $options;		
		$this->_initSql($name, $options);

		if (array_key_exists('id', $options)) {
			if (is_bool($options['id']) && $options['id'] == false) {
                $this->_autoGenerateId = false;
			}
			//if its a string then we want to auto-generate an integer-based
			//primary key with this name
			if (is_string($options['id'])) {
                $this->_autoGenerateId = true;
                $this->_primaryKeys[] = $options['id'];
            }
        }
	}
	
    /**
     * column
     * 
     * @param string $column_name The column name
     * @param string $type        The type generic of the column
     * @param array  $options     The options defintion of the column
     *
     * @return void
     */
    public function column($column_name, $type, $options = array())
    {
		//if there is already a column by the same name then silently fail 
		//and continue
		if ($this->included($column_name) == true) {
			return;
		}
		
		$column_options = array();
		
		if (array_key_exists('primary_key', $options)) {
            if ($options['primary_key'] == true) {
                $this->_primaryKeys[] = $column_name;
            }
        }
	  
		if (array_key_exists('auto_increment', $options)) {
            if ($options['auto_increment'] == true) {
                $column_options['auto_increment'] = true;
            }
        }
        $column_options = array_merge($column_options, $options);
        $column = new Ruckusing_Adpater_Mysql_ColumnDefinition($this->_adapter, $column_name, $type, $column_options);
        
        $this->_columns[] = $column;
	}
	
    /**
     * keys 
     * 
     * @return void
     */
    protected function _keys()
    {
        $keys = '';
        if (count($this->_primaryKeys) > 0) {
            $lead = ' PRIMARY KEY (';
            $quoted = array();
            foreach ($this->_primaryKeys as $key) {
                $quoted[] = sprintf("%s", $this->_adapter->identifier($key));
            }
            $keys = ",\n" . $lead . implode(",", $quoted) . ")";
        }
        return $keys;
    }
	
    /**
     * finish 
     * 
     * @param boolean $wants_sql Flag to get SQL generated
     *
     * @return mixed
     * @throws Ruckusing_Exception_InvalidTableDefinition
     */
    public function finish($wants_sql = false)
    {
		if ($this->_initialized == false) {
            throw new Ruckusing_Exception_InvalidTableDefinition(
                sprintf(
                    "Table Definition: '%s' has not been initialized", 
                    $this->_name
                )
            );
		}
        $opt_str = null;			
        if (is_array($this->_options) 
            && array_key_exists('options', $this->_options)
        ) {
			$opt_str = $this->_options['options'];
		}
		
		$close_sql = sprintf(') %s;', $opt_str);
		$createTableSql = $this->_sql;
		
		if ($this->_autoGenerateId === true) {
            $this->_primaryKeys[] = 'id';
            $primary_id = new Ruckusing_Adapter_Mysql_ColumnDefinition(
                $this->_adapter, 
                'id', 
                'integer', 
                array(
                    'unsigned' => true, 
                    'null' => false, 
                    'auto_increment' => true,
                )
            );
            $createTableSql .= $primary_id->toSql() . ",\n";
	    }
	    
	    $createTableSql .= $this->_columnsToStr();
	    $createTableSql .= $this->_keys() . $close_sql;
		
		if ($wants_sql) {
			return $createTableSql;
		} else {
			return $this->_adapter->executeDdl($createTableSql);			
		}
	}
	
    /**
     * columns to str 
     * 
     * @return string
     */
    protected function _columnsToStr()
    {
		$str = '';
		$fields = array();
		$len = count($this->_columns);
		for ($i = 0; $i < $len; $i++) {
			$c = $this->_columns[$i];
			$fields[] = $c->__toString();
		}
		return join(",\n", $fields);
	}
	
    /**
     * init sql 
     * 
     * @param string $name    The table name
     * @param array  $options The options definition of the table
     *
     * @return void
     */
    protected function _initSql($name, $options = array())
    {
        if (! is_array($options)) $options = array();

		//are we forcing table creation? If so, drop it first
		if (array_key_exists('force', $options) && $options['force'] == true) {
			try {
				$this->_adapter->dropTable($name);
			} catch (Ruckusing_Exception_MissingTable $e) {
				//do nothing
			}
		}
		$temp = "";
		if (array_key_exists('temporary', $options)) {
			$temp = " TEMPORARY";
		}
		$create_sql = sprintf('CREATE%s TABLE ', $temp);
        $create_sql .= sprintf("%s (\n", $this->_adapter->identifier($name));
		$this->_sql .= $create_sql;
		$this->_initialized = true;
	}
}
