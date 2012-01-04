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
 * @see Ruckusing_BaseAdapter 
 */
require_once RUCKUSING_BASE . '/lib/classes/class.Ruckusing_BaseAdapter.php';
/**
 * @see Ruckusing_IAdapter 
 */
require_once RUCKUSING_BASE . '/lib/classes/class.Ruckusing_IAdapter.php';
/**
 * @see Ruckusing_MySQLTableDefinition 
 */
require_once RUCKUSING_BASE . '/lib/classes/adapters/class.Ruckusing_MySQLTableDefinition.php';
/**
 * @see Ruckusing_NamingUtil 
 */
require_once RUCKUSING_BASE . '/lib/classes/util/class.Ruckusing_NamingUtil.php';	

/** @var integer Query type unknown */
define('SQL_UNKNOWN_QUERY_TYPE', 1);
/** @var integer Query type select */
define('SQL_SELECT', 2);
/** @var integer Query type insert */
define('SQL_INSERT', 4);
/** @var integer Query type update */
define('SQL_UPDATE', 8);
/** @var integer Query type delete */
define('SQL_DELETE', 16);
/** @var integer Query type alter */
define('SQL_ALTER', 32);
/** @var integer Query type drop */
define('SQL_DROP', 64);
/** @var integer Query type create */
define('SQL_CREATE', 128);
/** @var integer Query type show */
define('SQL_SHOW', 256);
/** @var integer Query type rename */
define('SQL_RENAME', 512);
/** @var integer Query type set */
define('SQL_SET', 1024);

/** @var integer max length of an identifier like a column or index name */
define('MAX_IDENTIFIER_LENGTH', 64);

/**
 * Class of mysql adapter
 *
 * @category   RuckusingMigrations
 * @package    Classes
 * @subpackage Adapters
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_MySQLAdapter extends Ruckusing_BaseAdapter 
    implements Ruckusing_IAdapter
{
    /**
     * Name of adapter 
     * 
     * @var string
     */
	private $_name = 'MySQL';
    /**
     * tables 
     * 
     * @var array
     */
	private $_tables = array();
    /**
     * tables_loaded 
     * 
     * @var boolean
     */
	private $_tablesLoaded = false;
    /**
     * version 
     * 
     * @var string
     */
	private $_version = '1.0';
    /**
     * Indicate if is in transaction
     * 
     * @var boolean
     */
	private $_inTrx = false;

    /**
     * __construct 
     * 
     * @param array            $dsn    Config DB for connect it
     * @param Ruckusing_Logger $logger Logger
     *
     * @return Ruckusing_MySQLAdapter
     */
    public function __construct($dsn, $logger)
    {
		parent::__construct($dsn);
		$this->_connect($dsn);
		$this->setLogger($logger);
	}
	
    /**
     * supports migrations ?
     * 
     * @return boolean
     */
    public function supportsMigrations()
    {
	    return true;
    }
	
    /**
     * native database types 
     * 
     * @return array
     */
    public function nativeDatabaseTypes()
    {
		$types = array(
            'primary_key'   => array(
                'name' => 'integer',
                'limit' => 11,
                'null' => false,
            ),
            'string'        => array(
                'name' => 'varchar',
                'limit' => 255,
            ),
            'text'          => array('name' => 'text'),
            'mediumtext'    => array('name' => 'mediumtext'),
            'integer'       => array(
                'name' => 'int',
                'limit' => 11,
            ),
            'smallinteger'  => array('name' => 'smallint'),
            'biginteger'    => array('name' => 'bigint'),
            'float'         => array('name' => 'float'),
            'decimal'       => array('name' => 'decimal'),
            'datetime'      => array('name' => 'datetime'),
            'timestamp'     => array('name' => 'timestamp'),
            'time'          => array('name' => 'time'),
            'date'          => array('name' => 'date'),
            'binary'        => array('name' => 'blob'),
            'boolean'       => array(
                'name' => 'tinyint',
                'limit' => 1,
            ),
		);
		return $types;
	}
	
	//-----------------------------------
	// PUBLIC METHODS
	//-----------------------------------
	
	/*  */
    /**
     * Create the schema table, if necessary
     * 
     * @return void
     */
    public function createSchemaVersionTable()
    {
        if (! $this->hasTable(RUCKUSING_TS_SCHEMA_TBL_NAME)) {
            $t = $this->createTable(
                RUCKUSING_TS_SCHEMA_TBL_NAME, 
                array('id' => false)
            );
            $t->column('version', 'string');
            $t->finish();
            $this->addIndex(
                RUCKUSING_TS_SCHEMA_TBL_NAME, 
                'version', 
                array('unique' => true)
            );
        }
    }
	
    /**
     * start a transaction if not started
     * 
     * @return void
     */
    public function startTransaction()
    {
		try {
            $this->_beginTransaction();
		} catch (Exception $e) {
			trigger_error($e->getMessage());
		}
    }

    /**
     * commit the transaction if started
     * 
     * @return void
     */
    public function commitTransaction()
    {
		try {
            $this->_commit();
		} catch (Exception $e) {
			trigger_error($e->getMessage());
		}
    }

    /**
     * rollback the transaction if started
     * 
     * @return void
     */
    public function rollbackTransaction()
    {
		try {
            $this->_rollback();
		} catch (Exception $e) {
			trigger_error($e->getMessage());
		}
	}
	
    /**
     * quote table 
     * 
     * @param string $tableName The table name to quote
     *
     * @return string
     */
    public function quoteTable($tableName)
    {
        return $this->identifier($tableName);
    }
	
    /**
     * column definition 
     * 
     * @param string $columnName Column name
     * @param string $type       Type generic
     * @param array  $options    Options
     *
     * @return string
     */
    public function columnDefinition($columnName, $type, $options = null)
    {
        $col = new Ruckusing_ColumnDefinition(
            $this, 
            $columnName, 
            $type, 
            $options
        );
		return $col->__toString();
	}

	//-------- DATABASE LEVEL OPERATIONS
    /**
     * database exists ?
     * 
     * @param string $db Database name
     *
     * @return boolean
     */
    public function databaseExists($db)
    {
		$ddl = 'SHOW DATABASES';
		$result = $this->selectAll($ddl);
        foreach ($result as $dbrow) {
            if ($dbrow['Database'] == $db) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * create database 
     * 
     * @param string $db Database name
     *
     * @return boolean
     */
    public function createDatabase($db)
    {
		if ($this->databaseExists($db)) {
			return false;
		}
		$ddl = sprintf('CREATE DATABASE %s', $this->identifier($db));
		return $this->query($ddl);
	}
	
    /**
     * drop database 
     * 
     * @param string $db Database name
     *
     * @return boolean
     */
    public function dropDatabase($db)
    {
		if (!$this->databaseExists($db)) {
			return false;
		}
		$ddl = sprintf('DROP DATABASE IF EXISTS %s', $this->identifier($db));
		return $this->query($ddl);
	}

    /**
	 * Dump the complete schema of the DB. This is really just all of the 
	 * CREATE TABLE statements for all of the tables in the DB.
	 * 
	 * NOTE: this does NOT include any INSERT statements or the actual data
	 * (that is, this method is NOT a replacement for mysqldump)
     * 
     * @return string
     */
    public function schema()
    {
		$final = '';
        $views = '';
		$this->_loadTables(true);
		foreach ($this->_tables as $tbl => $idx) {
			if ($tbl == 'schema_info') continue;

			$stmt = sprintf('SHOW CREATE TABLE %s', $this->identifier($tbl));
			$result = $this->query($stmt);

            if (is_array($result) && count($result) == 1) {
                $row = $result[0];
                if (count($row) == 2) {
                    if (isset($row['Create Table'])) {
                        $final .= $row['Create Table'] . ";\n\n";
                    } else if (isset($row['Create View'])) {
                        $views .= $row['Create View'] . ";\n\n";
                    }
                }
            }
		}
		return $final.$views;
	}
	
    /**
     * Verify if table exists 
     * 
     * @param string  $tbl           Table name
     * @param boolean $reload_tables Flag for reload all tables
     *
     * @return boolean
     */
    public function tableExists($tbl, $reload_tables = false)
    {
		$this->_loadTables($reload_tables);
		return array_key_exists($tbl, $this->_tables);
	}
		
    /**
     * show fields from 
     * 
     * @param string $tbl Table name
     *
     * @return string
     */
    public function showFieldsFrom($tbl)
    {
		return '';
	}

    /**
     * execute 
     * 
     * @param string $query Query SQL
     *
     * @return mixed
     */
    public function execute($query)
    {
		return $this->query($query);
	}

    /**
     * query 
     * 
     * @param string $query Query SQL
     *
     * @return mixed
     */
    public function query($query)
    {
		$this->getLogger()->log($query);
		$query_type = $this->_determineQueryType($query);
		$data = array();
        $res = mysql_query($query, $this->_conn);
        // Check error
        if ($this->_isError($res)) { 
            trigger_error(
                sprintf(
                    "Error executing 'query' with:\n%s\n\nReason: %s\n\n",
                    $query, 
                    mysql_error($this->_conn)
                )
            );
        }
		if ($query_type == SQL_SELECT || $query_type == SQL_SHOW) {		  
            while ($row = mysql_fetch_assoc($res)) {
                $data[] = $row; 
            }
			return $data;
		}
        return true;
	}
	
    /**
     * select one for query type SELECT or SHOW
     * 
     * @param string $query Query SQL
     *
     * @return array 
     */
    public function selectOne($query)
    {
		$query_type = $this->_determineQueryType($query);
        if ($query_type != SQL_SELECT && $query_type != SQL_SHOW) {
            trigger_error(
                'Query for selectOne() is not one of SELECT or SHOW: ' 
                . $query
            );
        }
        $result = $this->query($query);
        return array_shift($result);			
	}

    /**
     * select all 
     * 
     * @param string $query Query SQL
     *
     * @return array
     */
    public function selectAll($query)
    {
        $query_type = $this->_determineQueryType($query);
        if ($query_type != SQL_SELECT && $query_type != SQL_SHOW) {
            trigger_error(
                'Query for selectAll() is not one of SELECT or SHOW: ' 
                . $query
            );
        }
        $results = $this->query($query);
        return $results;			
	}
	
    /**
	 * Use this method for non-SELECT queries
     * Or anything where you dont necessarily expect a result string, 
     * e.g. DROPs, CREATEs, etc.
     * 
     * @param string $ddl Query SQL
     *
     * @return boolean
     */
    public function executeDdl($ddl)
    {
        $result = $this->query($ddl);
        // TODO : Check result
		return true;
	}
	
    /**
     * drop table 
     * 
     * @param string $tbl Table name
     *
     * @return boolean
     */
    public function dropTable($tbl)
    {
		$ddl = sprintf('DROP TABLE IF EXISTS %s', $this->identifier($tbl));
        $result = $this->query($ddl);
        // TODO : Check result
		return true;
	}
	
    /**
     * create table 
     * 
     * @param string $tableName Table name
     * @param array  $options   Options definition table
     *
     * @return Ruckusing_MySQLTableDefinition
     */
    public function createTable($tableName, $options = array())
    {
		return new Ruckusing_MySQLTableDefinition($this, $tableName, $options);
	}
	
    /**
     * quote string 
     * 
     * @param string $str String to escape
     *
     * @return string
     */
    public function quoteString($str)
    {
        return mysql_real_escape_string($str); 
    }
  
    /**
     * identifier 
     * 
     * @param string $str Identifier to quote
     *
     * @return string
     */
    public function identifier($str)
    {
        return('`' . $str . '`');
    }
	
    /**
     * quote a value
     * 
     * @param string $value  String to quote
     * @param string $column Column name
     *
     * @return string
     */
    public function quote($value, $column)
    {
        return $this->quoteString($value);
	}
	
    /**
     * rename table 
     * 
     * @param string $name    The old table name
     * @param string $newName The new table name
     *
     * @return boolean
     * @throws Ruckusing_ArgumentException
     */
    public function renameTable($name, $newName)
    {
		if (empty($name)) {
            throw new Ruckusing_ArgumentException(
                'Missing original table name parameter'
            );
		}
		if (empty($newName)) {
            throw new Ruckusing_ArgumentException(
                'Missing new table name parameter'
            );
		}
        $sql = sprintf(
            'RENAME TABLE %s TO %s',
            $this->identifier($name), 
            $this->identifier($newName)
        );
		return $this->executeDdl($sql);
	}
	
    /**
     * add column 
     * 
     * @param string $tableName  Table name
     * @param string $columnName Column name
     * @param string $type       Type generic of column
     * @param array  $options    Options of column
     *
     * @return boolean
     * @throws Ruckusing_ArgumentException
     */
    public function addColumn($tableName, $columnName, $type, $options = array())
    {
		if (empty($tableName)) {
            throw new Ruckusing_ArgumentException(
                'Missing table name parameter'
            );
		}
		if (empty($columnName)) {
            throw new Ruckusing_ArgumentException(
                'Missing column name parameter'
            );
		}
		if (empty($type)) {
			throw new Ruckusing_ArgumentException('Missing type parameter');
		}
		//default types
		if (! array_key_exists('limit', $options)) {
			$options['limit'] = null;
		}
		if (! array_key_exists('precision', $options)) {
			$options['precision'] = null;
		}
		if (! array_key_exists('scale', $options)) {
			$options['scale'] = null;
		}
        $sql = sprintf(
            'ALTER TABLE %s ADD %s %s',
            $this->identifier($tableName),
            $this->identifier($columnName),
            $this->typeToSql($type, $options)
        );
        $sql .= $this->addColumnOptions($type, $options);

		return $this->executeDdl($sql);
	}
	
    /**
     * remove column 
     * 
     * @param string $tableName  Table name
     * @param string $columnName Column name
     *
     * @return boolean
     */
    public function removeColumn($tableName, $columnName)
    {
		if (empty($tableName)) {
            throw new Ruckusing_ArgumentException(
                'Missing table name parameter'
            );
		}
		if (empty($columnName)) {
            throw new Ruckusing_ArgumentException(
                'Missing column name parameter'
            );
		}
        $sql = sprintf(
            'ALTER TABLE %s DROP COLUMN %s', 
            $this->identifier($tableName), 
            $this->identifier($columnName)
        );

		return $this->executeDdl($sql);
	}
	
    /**
     * rename column 
     * 
     * @param string $tableName     Table name
     * @param string $columnName    Old column name
     * @param string $newColumnName New column name
     *
     * @return boolean
     * @throws Ruckusing_ArgumentException
     */
    public function renameColumn($tableName, $columnName, $newColumnName)
    {
		if (empty($tableName)) {
            throw new Ruckusing_ArgumentException(
                'Missing table name parameter'
            );
		}
		if (empty($columnName)) {
            throw new Ruckusing_ArgumentException(
                'Missing original column name parameter'
            );
		}
		if (empty($newColumnName)) {
            throw new Ruckusing_ArgumentException(
                'Missing new column name parameter'
            );
		}
		$columnInfo = $this->columnInfo($tableName, $columnName);
		$current_type = $columnInfo['type'];
        $sql = sprintf(
            'ALTER TABLE %s CHANGE %s %s %s', 
		    $this->identifier($tableName), 
		    $this->identifier($columnName), 
            $this->identifier($newColumnName),
            $current_type
        );

		return $this->executeDdl($sql);
	}


    /**
     * change column 
     * 
     * @param string $tableName  Table name
     * @param string $columnName Column name
     * @param string $type       Type generic of column
     * @param array  $options    Options definition column
     *
     * @return boolean
     * @throws Ruckusing_ArgumentException
     */
    public function changeColumn($tableName, $columnName, $type, $options = array())
    {
		if (empty($tableName)) {
            throw new Ruckusing_ArgumentException(
                'Missing table name parameter'
            );
		}
		if (empty($columnName)) {
            throw new Ruckusing_ArgumentException(
                'Missing column name parameter'
            );
		}
		if (empty($type)) {
			throw new Ruckusing_ArgumentException('Missing type parameter');
		}
		$columnInfo = $this->columnInfo($tableName, $columnName);
		//default types
		if (! array_key_exists('limit', $options)) {
			$options['limit'] = null;
		}
		if (! array_key_exists('precision', $options)) {
			$options['precision'] = null;
		}
		if (! array_key_exists('scale', $options)) {
			$options['scale'] = null;
		}
        $sql = sprintf(
            'ALTER TABLE %s CHANGE %s %s %s',
            $this->identifier($tableName),
            $this->identifier($columnName),
            $this->identifier($columnName),
            $this->typeToSql($type, $options)
        );
        $sql .= $this->addColumnOptions($type, $options);

		return $this->executeDdl($sql);
	}

    /**
     * column info 
     * 
     * @param string $table  Table name
     * @param string $column Column name
     *
     * @return mixed
     * @throws Ruckusing_ArgumentException
     */
    public function columnInfo($table, $column)
    {
		if (empty($table)) {
            throw new Ruckusing_ArgumentException(
                'Missing table name parameter'
            );
		}
		if (empty($column)) {
            throw new Ruckusing_ArgumentException(
                'Missing column name parameter'
            );
		}
        $sql = sprintf(
            'SHOW COLUMNS FROM %s LIKE \'%s\'',
            $this->identifier($table),
            $column
        );
        $result = $this->selectOne($sql);
        if (is_array($result)) {
            //lowercase key names
            $result = array_change_key_case($result, CASE_LOWER);			
        }
        return $result;
	}
	
    /**
     * add index 
     * 
     * @param string       $tableName  Table name
     * @param string|array $columnName Column name
     * @param array        $options    Options definition of index
     *
     * @return boolean
     * @throws Ruckusing_ArgumentException
     * @throws Ruckusing_InvalidIndexNameException
     */
    public function addIndex($tableName, $columnName, $options = array())
    {
		if (empty($tableName)) {
            throw new Ruckusing_ArgumentException(
                'Missing table name parameter'
            );
		}
		if (empty($columnName)) {
            throw new Ruckusing_ArgumentException(
                'Missing column name parameter'
            );
		}
		//unique index?
        $unique = false;
        if (is_array($options) && array_key_exists('unique', $options)
            && $options['unique'] === true
        ) {
			$unique = true;
		}
        $indexName = $this->_getIndexName($tableName, $columnName, $options);
		// Check length index name
		if (strlen($indexName) > MAX_IDENTIFIER_LENGTH) {
            $msg = 'The auto-generated index name is too long for '
                . 'MySQL (max is 64 chars). Considering using \'name\' option '
                . 'parameter to specify a custom name for this index. '
                . 'Note: you will also need to specify this custom name '
                . 'in a drop_index() - if you have one.';
		    throw new Ruckusing_InvalidIndexNameException($msg);
	    }
        $columnNames = $columnName;
		if (! is_array($columnNames)) {
			$columnNames = array($columnNames);
	    }
		$cols = array();
		foreach ($columnNames as $name) {
		    $cols[] = $this->identifier($name);
	    }
        $sql = sprintf(
            'CREATE %sINDEX %s ON %s(%s)',
            ($unique === true) ? 'UNIQUE ' : '',
            $indexName, 
            $this->identifier($tableName),
            join(', ', $cols)
        );

		return $this->executeDdl($sql);		
	}
	
    /**
     * remove index 
     * 
     * @param string $tableName  The table name
     * @param string $columnName The column name
     * @param array  $options    The options definition of the index
     *
     * @return boolean
     * @throws Ruckusing_ArgumentException
     */
    public function removeIndex($tableName, $columnName, $options = array())
    {
		if (empty($tableName)) {
            throw new Ruckusing_ArgumentException(
                'Missing table name parameter'
            );
		}
		if (empty($columnName)) {
            throw new Ruckusing_ArgumentException(
                'Missing column name parameter'
            );
		}
        $indexName = $this->_getIndexName($tableName, $columnName, $options);
        $sql = sprintf(
            'DROP INDEX %s ON %s', 
            $this->identifier($indexName), 
            $this->identifier($tableName)
        );

		return $this->executeDdl($sql);
	}

    /**
     * has index 
     * 
     * @param string $tableName  The table name
     * @param string $columnName The column name
     * @param array  $options    The option definition of the index
     *
     * @return boolean
     * @throws Ruckusing_ArgumentException
     */
    public function hasIndex($tableName, $columnName, $options = array())
    {
		if (empty($tableName)) {
            throw new Ruckusing_ArgumentException(
                'Missing table name parameter'
            );
		}
		if (empty($columnName)) {
            throw new Ruckusing_ArgumentException(
                'Missing column name parameter'
            );
		}
        $indexName = $this->_getIndexName($tableName, $columnName, $options);
		$indexes = $this->indexes($tableName);
		foreach ($indexes as $idx) {
			if ($idx['name'] == $indexName) {
				return true;
			}
		}
		return false;
    }

    /**
     * indexes 
     * 
     * @param string $tableName The table name
     *
     * @return array
     */
    public function indexes($tableName)
    {
		$sql = sprintf('SHOW KEYS FROM %s', $this->identifier($tableName));
		$result = $this->selectAll($sql);
		$indexes = array();
		foreach ($result as $row) {
            //skip primary
            if ($row['Key_name'] == 'PRIMARY') continue;
            $indexes[] = array(
                'name' => $row['Key_name'], 
                'unique' => (int)$row['Non_unique'] == 0 ? true : false,
            );
        }
		return $indexes;
	}

    /**
     * type to sql : Return the type SQL of type generic
     * 
     * @param string $type    The type generic
     * @param array  $options The options definition
     *
     * @return string
     * @throws Ruckusing_ArgumentException
     */
    public function typeToSql($type, $options = array())
    {
        $natives = $this->nativeDatabaseTypes();
        if (! is_array($options)) {
            $options = array();
        }
		
		if (! array_key_exists($type, $natives)) {
            $error = sprintf(
                "Error: I dont know what column type "
                . "of '%s' maps to for MySQL.",
                $type
            );
            $error .= "\nYou provided: {$type}\n"
                . "Valid types are: \n";
            $types = array_keys($natives);
            foreach ($types as $t) {
                if ($t == 'primary_key') continue;
                $error .= "\t{$t}\n";
            }
            throw new Ruckusing_ArgumentException($error);
        }
	  
        $scale = null;
        $precision = null;
        $limit = null;
	  
        if (array_key_exists('precision', $options)) {
            $precision = $options['precision'];
        }
        if (array_key_exists('scale', $options)) {
            $scale = $options['scale'];
        }
        if (array_key_exists('limit', $options)) {
            $limit = $options['limit'];
        }
		
		$native_type = $natives[$type];
		if (is_array($native_type) && array_key_exists('name', $native_type)) {
			$column_type_sql = $native_type['name'];
		} else {
			return $native_type;
		}
		if ($type == 'decimal') {
			//ignore limit, use precison and scale
			if (!isset($precision) || array_key_exists('precision', $native_type)) {
				$precision = $native_type['precision'];
			}
			if (!isset($scale) || array_key_exists('scale', $native_type)) {
				$scale = $native_type['scale'];
			}
			if (isset($precision)) {
				if (is_int($scale)) {
					$column_type_sql .= sprintf('(%d, %d)', $precision, $scale);
				} else {
					$column_type_sql .= sprintf('(%d)', $precision);						
				}//scale
			} else {
				if ($scale) {
                    throw new Ruckusing_ArgumentException(
                        'Error adding decimal column: precision cannot '
                        . 'be empty if scale is specified'
                    );
				}
			}//precision
		} else {
			//not a decimal column
			if (!isset($limit) && array_key_exists('limit', $native_type)) {
				$limit = $native_type['limit'];
			}
			if ($limit) {
				$column_type_sql .= sprintf('(%d)', $limit);
			}
        }

		return $column_type_sql;
	}
	
    /**
     * add column options 
     * 
     * @param string $type    The type generic
     * @param array  $options The options definition
     *
     * @return string
     */
    public function addColumnOptions($type, $options)
    {
		$sql = '';

        if (!is_array($options) || empty($options)) {
            return $sql;
        }

        // unsigned
        if (array_key_exists('unsigned', $options) 
            && $options['unsigned'] === true
        ) {
			$sql .= ' UNSIGNED';
		}
        /*
        if($type === 'primary_key') {
      		if(is_array($options) && array_key_exists('auto_increment', $options) && $options['auto_increment'] === true) {
      			$sql .= ' auto_increment';
      		}
      		$sql .= ' PRIMARY KEY';
        }
        */

        // auto_increment
        if (array_key_exists('auto_increment', $options) 
            && $options['auto_increment'] === true
        ) {
			$sql .= ' auto_increment';
		}

        // default value
        if (array_key_exists('default', $options) 
            && $options['default'] !== null
        ) {
			if ($this->_isSqlMethodCall($options['default'])) {
				//$default_value = $options['default'];
                throw new Exception(
                    'MySQL does not support function calls '
                    . 'as default values, constants only.'
                );
			} else {
                if (is_int($options['default'])) {			    
                    $default_format = '%d';
                } elseif (is_bool($options['default'])) {
                    $default_format = "'%d'";
                } else {
                    $default_format = "'%s'";
                }
                $default_value = sprintf($default_format, $options['default']);			
            }
			$sql .= sprintf(' DEFAULT %s', $default_value);
		}

        // default null
        if (array_key_exists('null', $options) && $options['null'] === false) {
			$sql .= ' NOT NULL';
        }

        // position of column
		if (array_key_exists('after', $options)) {
            $sql .= sprintf(' AFTER %s', $this->identifier($options['after']));
        }

		return $sql;
	}
	
    /**
     * set current version 
     * 
     * @param string $version The current version
     *
     * @return boolean
     */
    public function setCurrentVersion($version)
    {
        $sql = sprintf(
            "INSERT INTO %s (version) VALUES ('%s')", 
            RUCKUSING_TS_SCHEMA_TBL_NAME, 
            $version
        );
		return $this->executeDdl($sql);
	}
	
    /**
     * remove version 
     * 
     * @param string $version The version to remove
     *
     * @return boolean
     */
    public function removeVersion($version)
    {
        $sql = sprintf(
            "DELETE FROM %s WHERE version = '%s'", 
            RUCKUSING_TS_SCHEMA_TBL_NAME, 
            $version
        );
		return $this->executeDdl($sql);
    }
	
    /**
     * __toString 
     * 
     * @return string
     */
    public function __toString()
    {
		return 'Ruckusing_MySQLAdapter, version ' . $this->_version;
	}

	
	//-----------------------------------
	// PRIVATE METHODS
	//-----------------------------------	
    /**
     * connect 
     * 
     * @param array $dsn DSN config to connect at DB
     *
     * @return void
     */
    private function _connect($dsn)
    {
		$this->_dbConnect($dsn);
	}
	
    /**
     * db connect 
     * 
     * @param array $dsn DSN config to connect at DB
     *
     * @return boolean
     */
    private function _dbConnect($dsn)
    {
        $dbInfo = $this->getDsn();
        if ($dbInfo) {
            //we might have a port
            $host = $dbInfo['host'];
            if (! empty($dbInfo['port'])) {
                $host .= ':' . $dbInfo['port'];
            }
            $this->_conn = mysql_connect(
                $host, 
                $dbInfo['user'], 
                $dbInfo['password']
            );
            if (! $this->_conn) {
                die(
                    "\n\nCould not connect to the DB, "
                    . "check host / user / password\n\n"
                );
            }
            if (! mysql_select_db($dbInfo['database'], $this->_conn)) {
                die(
                    "\n\nCould not select the DB, "
                    . "check permissions on host\n\n"
                );
            }
            return true;
        } else {
            die(
                "\n\nCould not extract DB connection "
                . "information from: " . var_export($dsn, true) ."\n\n"
            );
        }
    }
	
	//Delegate to PEAR
    /**
     * isError 
     * 
     * @param mixed $o Return to mysql_query function 
     *
     * @return boolean
     */
    private function _isError($o)
    {
		return $o === false;
	}
	
    /**
     * load tables 
	 * Initialize an array of table names
     * 
     * @param boolean $reload Flag to reload tables
     *
     * @return void
     */
    private function _loadTables($reload = true)
    {
		if ($this->_tablesLoaded == false || $reload) {
			$this->_tables = array(); //clear existing structure
			$qry = 'SHOW TABLES';
			$res = mysql_query($qry, $this->_conn);
			while ($row = mysql_fetch_row($res)) {
                $table = $row[0];
                $this->_tables[$table] = true;
            }
		}
    }

    /**
     * determine query type 
     * 
     * @param string $query Query SQL
     *
     * @return integer
     */
    private function _determineQueryType($query)
    {
        $query = strtolower(trim($query));
        $match = array();
        preg_match('/^(\w)*/i', $query, $match);
        if (empty($match)) {
            return SQL_UNKNOWN_QUERY_TYPE;
        }
        $type = $match[0];
        switch (strtolower($type)) {
        case 'select':
                return SQL_SELECT;
        case 'update':
                return SQL_UPDATE;
        case 'delete':
                return SQL_DELETE;
        case 'insert':
                return SQL_INSERT;
        case 'alter':
                return SQL_ALTER;
        case 'drop':
                return SQL_DROP;
        case 'create':
                return SQL_CREATE;
        case 'show':
                return SQL_SHOW;
        case 'rename':
                return SQL_RENAME;
        case 'set':
                return SQL_SET;
        default:
                return SQL_UNKNOWN_QUERY_TYPE;
        }
	}
	
    /**
     * is select 
     * 
     * @param integer $query_type Type of query SQL
     *
     * @return boolean
     */
    private function _isSelect($query_type)
    {
		if ($query_type == SQL_SELECT) {
			return true;
		}
		return false;
	}
	
    /**
     * _getIndexName 
     * 
     * @param string       $tableName  The table name
     * @param string|array $columnName The column name(s)
     * @param array        $options    The options definition of the index
     *
     * @return string
     */
    private function _getIndexName($tableName, $columnName, $options = array())
    {
		//did the user specify an index name?
		if (is_array($options) && array_key_exists('name', $options)) {
			$indexName = $options['name'];
		} else {
            $indexName = Ruckusing_NamingUtil::indexName(
                $tableName, 
                $columnName
            );
        }
        return $indexName;
    }
	
    /**
     * is sql method call 
	 * Detect whether or not the string represents a function call and if so
	 * do not wrap it in single-quotes, otherwise do wrap in single quotes.
     * 
     * @param string $str String to detect method call
     *
     * @return boolean
     */
    private function _isSqlMethodCall($str)
    {
		$str = trim($str);
		if (substr($str, -2, 2) == '()') {
			return true;
		}
        return false;
	}
	
    /**
     * beginTransaction 
     * 
     * @return void
     */
    private function _beginTransaction()
    {
        if ($this->_inTrx === true) {
            throw new Exception('Transaction already started');
        }
        mysql_query('BEGIN', $this->_conn);
        $this->_inTrx = true;
    }
  
    /**
     * commit 
     * 
     * @return void
     */
    private function _commit()
    {
        if ($this->_inTrx === false) {
            throw new Exception('Transaction not started');
        }
        mysql_query('COMMIT', $this->_conn);
        $this->_inTrx = false; 
    }
  
    /**
     * rollback 
     * 
     * @return void
     */
    private function _rollback()
    {
        if ($this->_inTrx === false) {
            throw new Exception('Transaction not started');
        }
        mysql_query('ROLLBACK', $this->_conn);
        $this->_inTrx = false; 
    }
}
