<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
 * @package    Classes
 * @subpackage Adapters
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */

/**
 * Class of mysql table definition
 *
 * @category   RuckusingMigrations
 * @package    Classes
 * @subpackage Adapters
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_MySQLTableDefinition
{
    /**
     * adapter MySQL
     * 
     * @var Ruckusing_MySQLAdapter
     */
	private $_adapter;
    /**
     * name 
     * 
     * @var string
     */
	private $_name;
    /**
     * options 
     * 
     * @var mixed
     */
	private $_options;
    /**
     * sql 
     * 
     * @var string
     */
	private $_sql = '';
    /**
     * initialized 
     * 
     * @var boolean
     */
	private $_initialized = false;
    /**
     * columns 
     * 
     * @var array
     */
	private $_columns = array();
    /**
     * table def 
     * 
     * @var Ruckusing_TableDefinition
     */
	private $_tableDef;
    /**
     * primary keys 
     * 
     * @var array
     */
	private $_primaryKeys = array();
    /**
     * auto generate id 
     * 
     * @var boolean
     */
	private $_autoGenerateId = true;
	
    /**
     * __construct 
     * 
     * @param Ruckusing_BaseAdapter $adapter Adapter MySQL
     * @param string                $name    The table name
     * @param array                 $options The options definition
     *
     * @return Ruckusing_MySQLTableDefinition
     * @throws Ruckusing_MissingAdapterException
     * @throws Ruckusing_ArgumentException
     */
    function __construct($adapter, $name, $options = array())
    {
		//sanity check
		if (! $adapter instanceof Ruckusing_BaseAdapter) {
            throw new Ruckusing_MissingAdapterException(
                'Invalid MySQL Adapter instance.'
            );
		}
		if (! $name) {
			throw new Ruckusing_ArgumentException("Invalid 'name' parameter");
		}

		$this->_adapter = $adapter;
		$this->_name = $name;
		$this->_options = $options;		
		$this->_initSql($name, $options);
        $this->_tableDef = new Ruckusing_TableDefinition(
            $this->_adapter, 
            $this->_options
        );

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

        /*
		//Add a primary key field if necessary, defaulting to "id"
		$pk_name = null;
		if(array_key_exists('id', $options)) {
			if($options['id'] != false) {
				if(array_key_exists('primary_key', $options)) {
					$pk_name = $options['primary_key'];
				}
			}
		} else {
			// Auto add primary key of "id"
			$pk_name = 'id';
		}
		if($pk_name != null) {	
		    $auto_increment = true;
		    if(array_key_exists('auto_increment', $options)) {
		      $auto_increment = is_bool($options['auto_increment']) ? $options['auto_increment'] : true;
	      }
			$this->primary_key($pk_name, $auto_increment);
		}
		*/
	}
	
	/*
    public function primary_key($name, $auto_increment)
    {
        $options = array('auto_increment' => $auto_increment);
		$this->column($name, "primary_key", $options);
	}
	*/
	
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
		if ($this->_tableDef->included($column_name) == true) {
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
        $column = new Ruckusing_ColumnDefinition($this->_adapter, $column_name, $type, $column_options);
        
        $this->_columns[] = $column;
	}
	
    /**
     * keys 
     * 
     * @return void
     */
    private function _keys()
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
     * @throws Ruckusing_InvalidTableDefinitionException
     */
    public function finish($wants_sql = false)
    {
		if ($this->_initialized == false) {
            throw new Ruckusing_InvalidTableDefinitionException(
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
            $primary_id = new Ruckusing_ColumnDefinition(
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
    private function _columnsToStr()
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
    private function _initSql($name, $options = array())
    {
        if (! is_array($options)) $options = array();

		//are we forcing table creation? If so, drop it first
		if (array_key_exists('force', $options) && $options['force'] == true) {
			try {
				$this->_adapter->dropTable($name);
			} catch (Ruckusing_MissingTableException $e) {
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

/**
 * Class of table definition
 *
 * @category   RuckusingMigrations
 * @package    Classes
 * @subpackage Adapters
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_TableDefinition
{
    /**
     * columns 
     * 
     * @var array
     */
	private $_columns = array();
    /**
     * adapter 
     * 
     * @var Ruckusing_BaseAdapter
     */
	private $_adapter;
	
    /**
     * __construct 
     * 
     * @param Ruckusing_BaseAdapter $adapter Adapter MySQL
     *
     * @return Ruckusing_TableDefinition
     */
    function __construct($adapter)
    {
		$this->_adapter = $adapter;
	}
	
	/*
    public function column($name, $type, $options = array())
    {
	    die;
		$column = new Ruckusing_ColumnDefinition($this->_adapter, $name, $type);
		$native_types = $this->_adapter->nativeDatabaseTypes();
		echo "\n\nCOLUMN: " . print_r($options,true) . "\n\n";
		
		if($native_types && array_key_exists('limit', $native_types) && !array_key_exists('limit', $options)) {
			$limit = $native_types['limit'];
		} elseif(array_key_exists('limit', $options)) {
			$limit = $options['limit'];
		} else {
			$limit = null;
		}		
		$column->limit = $limit;
		
		if(array_key_exists('precision', $options)) {
			$precision = $options['precision'];
		} else {
			$precision = null;
		}
		$column->precision = $precision;

		if(array_key_exists('scale', $options)) {
			$scale = $options['scale'];
		} else {
			$scale = null;
		}
		$column->scale = $scale;

		if(array_key_exists('default', $options)) {
			$default = $options['default'];
		} else {
			$default = null;
		}
		$column->default = $default;

		if(array_key_exists('null', $options)) {
			$null = $options['null'];
		} else {
			$null = null;
		}
		$column->null = $null;

		if($this->included($column) == false) {
			$this->_columns[] = $column;
		}		
	}//column
	*/
	
    /**
     * included 
	 * Determine whether or not the given column already exists in our 
	 * table definition.
	 * 
	 * This method is lax enough that it can take either a string column name
	 * or a Ruckusing_ColumnDefinition object.
     * 
     * @param Ruckusing_ColumnDefinition|string $column The column to included
     *
     * @return boolean
     */
    public function included($column)
    {
		$k = count($this->_columns);
		for ($i = 0; $i < $k; $i++) {
			$col = $this->_columns[$i];
			if (is_string($column) && $col->name == $column) {
				return true;
			}
            if ($column instanceof Ruckusing_ColumnDefinition 
                && $col->name == $column->name
            ) {
				return true;
			}
		}
		return false;
	}	
	
    /**
     * toSql 
     * 
     * @return string
     */
    public function toSql()
    {
		return join(",", $this->_columns);
	}
}

/**
 * Class of column definition
 *
 * @category   RuckusingMigrations
 * @package    Classes
 * @subpackage Adapters
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_ColumnDefinition
{
    /**
     * adapter 
     * 
     * @var Ruckusing_BaseAdapter
     */
	private $_adapter;
    /**
     * name 
     * 
     * @var string
     */
	public $name;
    /**
     * type 
     * 
     * @var mixed
     */
	public $type;
    /**
     * properties 
     * 
     * @var mixed
     */
	public $properties;
    /**
     * options 
     * 
     * @var array
     */
	private $_options = array();
	
    /**
     * __construct 
     * 
     * @param Ruckusing_BaseAdapter $adapter Adapter of RDBMS
     * @param string                $name    Name column
     * @param string                $type    Type generic
     * @param array                 $options Options column
     * 
     * @return Ruckusing_ColumnDefinition
     */
    function __construct($adapter, $name, $type, $options = array())
    {
		$this->_adapter = $adapter;
		$this->name = $name;
		$this->type = $type;
	    $this->_options = $options;
	}

    /**
     * toSql 
     * 
     * @return string
     */
    public function toSql()
    {
        $column_sql = sprintf(
            '%s %s', 
            $this->_adapter->identifier($this->name), 
            $this->_sqlType()
        );
        $column_sql .= $this->_adapter->addColumnOptions(
            $this->type,
            $this->_options
        );
		return $column_sql;
	}

    /**
     * __toString 
     * 
     * @return string
     */
    public function __toString()
    {
        //Dont catch any exceptions here, let them bubble up
        return $this->toSql();
	}

    /**
     * sql type 
     * 
     * @return string
     */
    private function _sqlType()
    {
        return $this->_adapter->typeToSql($this->type, $this->_options);
	}
}
