<?php

/**
 * Phigrate
 *
 * PHP Version 5.3
 *
 * @category   Phigrate
 * @package    Phigrate_Adapter
 * @subpackage Mysql
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */

/**
 * @see Phigrate_Adapter_Base
 */
require_once 'Phigrate/Adapter/Base.php';

/**
 * @see Phigrate_Adapter_IAdapter
 */
require_once 'Phigrate/Adapter/IAdapter.php';

/**
 * @see Phigrate_Adapter_Mysql_TableDefinition
 */
require_once 'Phigrate/Adapter/Mysql/TableDefinition.php';

/**
 * @see Phigrate_Util_Naming
 */
require_once 'Phigrate/Util/Naming.php';

/**
 * Class of mysql adapter
 *
 * @category   Phigrate
 * @package    Phigrate_Adapter
 * @subpackage Mysql
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */
class Phigrate_Adapter_Mysql_Adapter extends Phigrate_Adapter_Base
    implements Phigrate_Adapter_IAdapter
{
    /** @var integer Query type unknown */
    const SQL_UNKNOWN_QUERY_TYPE = 1;
    /** @var integer Query type select */
    const SQL_SELECT = 2;
    /** @var integer Query type insert */
    const SQL_INSERT = 4;
    /** @var integer Query type update */
    const SQL_UPDATE = 8;
    /** @var integer Query type delete */
    const SQL_DELETE = 16;
    /** @var integer Query type alter */
    const SQL_ALTER = 32;
    /** @var integer Query type drop */
    const SQL_DROP = 64;
    /** @var integer Query type create */
    const SQL_CREATE = 128;
    /** @var integer Query type show */
    const SQL_SHOW = 256;
    /** @var integer Query type rename */
    const SQL_RENAME = 512;
    /** @var integer Query type set */
    const SQL_SET = 1024;

    /** @var integer max length of an identifier like a column or index name */
    const MAX_IDENTIFIER_LENGTH = 64;

    /**
     * Name of adapter
     *
     * @var string
     */
    protected $_name = 'MySQL';

    /**
     * tables
     *
     * @var array
     */
    protected $_tables = array();

    /**
     * tables_loaded
     *
     * @var boolean
     */
    protected $_tablesLoaded = false;

    /**
     * version
     *
     * @var string
     */
    protected $_version = '1.0';

    /**
     * Indicate if is in transaction
     *
     * @var boolean
     */
    protected $_inTrx = false;

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

    /**
     * Create the schema table, if necessary
     *
     * @return void
     */
    public function createSchemaVersionTable()
    {
        if (! $this->hasTable(PHIGRATE_TS_SCHEMA_TBL_NAME)) {
            $t = $this->createTable(
                PHIGRATE_TS_SCHEMA_TBL_NAME,
                array('id' => false))
                ->column('version', 'string')
                ->finish();
            $this->addIndex(
                PHIGRATE_TS_SCHEMA_TBL_NAME,
                'version',
                array('unique' => true)
            );
            return true;
        }
    }

    /**
     * start a transaction if not started
     *
     * @return void
     */
    public function startTransaction()
    {
        if ($this->hasExport()) {
            return 'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";' . "\nSET AUTOCOMMIT=0;\nSTART TRANSACTION;";
        }
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
        if ($this->hasExport()) {
            return "COMMIT;";
        }
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
        if ($this->hasExport()) {
            return "";
        }
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
    public function columnDefinition($columnName, $type, $options = array())
    {
        $col = new Phigrate_Adapter_Mysql_ColumnDefinition(
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
        $ddl = sprintf('CREATE DATABASE %s;', $this->identifier($db));
        return $this->executeDdl($ddl);
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
        return $this->executeDdl($ddl);
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
        foreach (array_keys($this->_tables) as $tbl) {
            if ($tbl == PHIGRATE_SCHEMA_TBL_NAME) {
                continue;
            }

            $stmt = sprintf('SHOW CREATE TABLE %s;', $this->identifier($tbl));
            $result = $this->execute($stmt);

            if (is_array($result) && count($result) == 1) {
                $row = $result[0];
                if (array_key_exists('Create Table', $row)) {
                    $final .= $row['Create Table'] . ";\n\n";
                } else if (array_key_exists('Create View', $row)) {
                    $views .= $row['Create View'] . ";\n\n";
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
        if ($this->hasExport()) {
            $this->_logger->debug('Query hasExport true');
            $this->_sql .= $query . "\n\n";
            return true;
        }
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
        $queryType = $this->_determineQueryType($query);
        if ($queryType == self::SQL_UNKNOWN_QUERY_TYPE) {
            return false;
        }
        $data = array();
        $pdoStmt = $this->getConnexion()->query($query, PDO::FETCH_ASSOC);
        // Check error
        if ($this->_isError($pdoStmt)) {
            $error = $this->getConnexion()->errorInfo();
            require_once 'Phigrate/Exception/AdapterQuery.php';
            throw new Phigrate_Exception_AdapterQuery($error[2]);
        }
        if ($queryType == self::SQL_SELECT || $queryType == self::SQL_SHOW) {
            foreach ($pdoStmt as $row) {
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
        if ($query_type != self::SQL_SELECT && $query_type != self::SQL_SHOW) {
            require_once 'Phigrate/Exception/AdapterQuery.php';
            throw new Phigrate_Exception_AdapterQuery(
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
        if ($query_type != self::SQL_SELECT && $query_type != self::SQL_SHOW) {
            require_once 'Phigrate/Exception/AdapterQuery.php';
            throw new Phigrate_Exception_AdapterQuery(
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
        if ($this->hasExport()) {
            $this->_logger->debug('Query hasExport true');
            $this->_sql .= $ddl . "\n\n";
            return true;
        }
        return $this->query($ddl);
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
        $ddl = sprintf('DROP TABLE IF EXISTS %s;', $this->identifier($tbl));
        $result = $this->executeDdl($ddl);
        return $result;
    }

    /**
     * create table
     *
     * @param string $tableName Table name
     * @param array  $options   Options definition table
     *
     * @return Phigrate_Adapter_Mysql_TableDefinition
     */
    public function createTable($tableName, $options = array())
    {
        return new Phigrate_Adapter_Mysql_TableDefinition($this, $tableName, $options);
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
        return '`' . $str . '`';
    }

    /**
     * rename table
     *
     * @param string $name    The old table name
     * @param string $newName The new table name
     *
     * @return boolean
     * @throws Phigrate_Exception_Argument
     */
    public function renameTable($name, $newName)
    {
        if (empty($name)) {
            throw new Phigrate_Exception_Argument(
                'Missing original table name parameter'
            );
        }
        if (empty($newName)) {
            throw new Phigrate_Exception_Argument(
                'Missing new table name parameter'
            );
        }
        $sql = sprintf(
            'RENAME TABLE %s TO %s;',
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
     * @throws Phigrate_Exception_Argument
     */
    public function addColumn($tableName, $columnName, $type, $options = array())
    {
        if (empty($tableName)) {
            throw new Phigrate_Exception_Argument(
                'Missing table name parameter'
            );
        }
        if (empty($columnName)) {
            throw new Phigrate_Exception_Argument(
                'Missing column name parameter'
            );
        }
        if (empty($type)) {
            throw new Phigrate_Exception_Argument('Missing type parameter');
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
        $sql .= $this->addColumnOptions($type, $options) . ';';

        return $this->executeDdl($sql);
    }

    /**
     * remove column
     *
     * @param string $tableName  Table name
     * @param string $columnName Column name
     *
     * @return boolean
     * @throws Phigrate_Exception_Argument
     */
    public function removeColumn($tableName, $columnName)
    {
        if (empty($tableName)) {
            throw new Phigrate_Exception_Argument(
                'Missing table name parameter'
            );
        }
        if (empty($columnName)) {
            throw new Phigrate_Exception_Argument(
                'Missing column name parameter'
            );
        }
        $sql = sprintf(
            'ALTER TABLE %s DROP COLUMN %s;',
            $this->identifier($tableName),
            $this->identifier($columnName)
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
     * @throws Phigrate_Exception_Argument
     */
    public function changeColumn($tableName, $columnName, $type, $options = array())
    {
        if (empty($tableName)) {
            throw new Phigrate_Exception_Argument(
                'Missing table name parameter'
            );
        }
        if (empty($columnName)) {
            throw new Phigrate_Exception_Argument(
                'Missing column name parameter'
            );
        }
        if (empty($type)) {
            throw new Phigrate_Exception_Argument('Missing type parameter');
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
        if (!array_key_exists('new_name', $options) || empty($options['new_name'])) {
            $options['new_name'] = $columnName;
        }
        $sql = sprintf(
            'ALTER TABLE %s CHANGE %s %s %s',
            $this->identifier($tableName),
            $this->identifier($columnName),
            $this->identifier($options['new_name']),
            $this->typeToSql($type, $options)
        );
        $sql .= $this->addColumnOptions($type, $options) . ';';

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
     * @throws Phigrate_Exception_Argument
     */
    public function renameColumn($tableName, $columnName, $newColumnName)
    {
        if (empty($tableName)) {
            throw new Phigrate_Exception_Argument(
                'Missing table name parameter'
            );
        }
        if (empty($columnName)) {
            throw new Phigrate_Exception_Argument(
                'Missing original column name parameter'
            );
        }
        if (empty($newColumnName)) {
            throw new Phigrate_Exception_Argument(
                'Missing new column name parameter'
            );
        }
        $columnInfo = $this->columnInfo($tableName, $columnName);
        $current_type = $columnInfo['type'];
        $sql = sprintf(
            'ALTER TABLE %s CHANGE %s %s %s;',
            $this->identifier($tableName),
            $this->identifier($columnName),
            $this->identifier($newColumnName),
            $current_type
        );

        return $this->executeDdl($sql);
    }

    /**
     * column info
     *
     * @param string $table  Table name
     * @param string $column Column name
     *
     * @return mixed
     * @throws Phigrate_Exception_Argument
     */
    public function columnInfo($table, $column)
    {
        if (empty($table)) {
            throw new Phigrate_Exception_Argument(
                'Missing table name parameter'
            );
        }
        if (empty($column)) {
            throw new Phigrate_Exception_Argument(
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
     * Add foreign key
     * 
     * @param string $tableName  The table name
     * @param string $columnName The column name
     * @param string $tableRef   The table ref name
     * @param string $columnRef  The column ref name
     * @param array  $options    The options array
     * 
     * @return boolean
     * @throws Phigrate_Exception_Argument
     */
    public function addForeignKey($tableName, $columnName, $tableRef, $columnRef = 'id', $options = array())
    {
        if (empty($tableName)) {
            throw new Phigrate_Exception_Argument(
                'Missing table name parameter'
            );
        }
        if (empty($columnName)) {
            throw new Phigrate_Exception_Argument(
                'Missing column name parameter'
            );
        }
        if (empty($tableRef)) {
            throw new Phigrate_Exception_Argument(
                'Missing table ref name parameter'
            );
        }
        if (empty($columnRef)) {
            $columnRef = 'id';
        }
        
        $constrainteName = $this->_getConstrainteName($tableName, $columnName, $tableRef, $columnRef, $options);
        if (strlen($constrainteName) > self::MAX_IDENTIFIER_LENGTH) {
            $msg = 'The auto-generated constrainte name is too long for '
                . 'MySQL (max is 64 chars). Considering using \'name\' option '
                . 'parameter to specify a custom name for this index. '
                . 'Note: you will also need to specify this custom name '
                . 'in a drop_index() - if you have one.';
            require_once 'Phigrate/Exception/InvalidIndexName.php';
            throw new Phigrate_Exception_InvalidIndexName($msg);
        }
        $actionsAllowed = array(
            'CASCADE',
            'SET NULL',
            'NO ACTION',
            'RESTRICT',
        );
        $update = 'NO ACTION';
        if (array_key_exists('update', $options) && in_array($options['update'], $actionsAllowed)) {
            $update = $options['update'];
        } elseif (array_key_exists('update', $options)) {
            throw new Phigrate_Exception_Argument(
                'Action (' . $options['update'] . ') for UPDATE not allowed. Actions allowed: '
                . implode(', ', $actionsAllowed)
            );
        }
        $delete = 'NO ACTION';
        if (array_key_exists('delete', $options) && in_array($options['delete'], $actionsAllowed)) {
            $delete = $options['delete'];
        } elseif (array_key_exists('delete', $options)) {
            throw new Phigrate_Exception_Argument(
                'Action (' . $options['delete'] . ') for DELETE not allowed. Actions allowed: '
                . implode(', ', $actionsAllowed)
            );
        }
        // Vérifier la presence de l'index si pas en mode export
        if (! $this->hasExport()) {
            // Check if constrainte exists
            if ($this->hasIndex($tableName, $columnName, array('name' => $constrainteName))) {
                throw new Phigrate_Exception_Argument(
                    'Constrainte already exists.'
                );
            }
            // Check if ref is primary or index
            if (! $this->isPrimaryKey($tableRef, $columnRef) && ! $this->hasIndex($tableRef, $columnRef)) {
                // Create index for ref
                $this->addIndex($tableRef, $columnRef);
            }
        } else {
            $this->addIndex($tableRef, $columnRef);
        }
        $sql = sprintf(
            'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s) ON DELETE %s ON UPDATE %s;',
            $this->identifier($tableName),
            $constrainteName,
            $this->identifier($columnName),
            $this->identifier($tableRef),
            $this->identifier($columnRef),
            $delete,
            $update
        );

        return $this->executeDdl($sql);
    }
    
    /**
     * Remove foreign key
     * 
     * @param string $tableName  The table name
     * @param string $columnName The column name
     * @param string $tableRef   The table ref name
     * @param string $columnRef  The column ref name
     * @param array  $options    The options array
     * 
     * @return boolean
     * @throws Phigrate_Exception_Argument
     */
    public function removeForeignKey($tableName, $columnName, $tableRef, $columnRef = 'id', $options = array())
    {
        if (empty($tableName)) {
            throw new Phigrate_Exception_Argument(
                'Missing table name parameter'
            );
        }
        if (empty($columnName)) {
            throw new Phigrate_Exception_Argument(
                'Missing column name parameter'
            );
        }
        if (empty($tableRef)) {
            throw new Phigrate_Exception_Argument(
                'Missing table ref name parameter'
            );
        }
        if (empty($columnRef)) {
            $columnRef = 'id';
        }
        
        $constrainteName = $this->_getConstrainteName($tableName, $columnName, $tableRef, $columnRef, $options);
        if (! $this->hasExport()) {
            // Check if constrainte exists
            if (! $this->hasIndex($tableName, $columnName, array('name' => $constrainteName))) {
                throw new Phigrate_Exception_Argument(
                    'Constrainte not exists.'
                );
            }
        }
        $sql = sprintf(
            'ALTER TABLE %s DROP FOREIGN KEY %s;',
            $this->identifier($tableName),
            $constrainteName
        );
        $result = false;
        if ($this->executeDdl($sql)) {
            $result = $this->removeIndex($tableName, $columnName, array('name' => $constrainteName));
            // Vérifier la presence de l'index si pas en mode export
            if (! $this->hasExport()) {
                // Check if ref is not primary key and is an index
                if (! $this->isPrimaryKey($tableRef, $columnRef) && $this->hasIndex($tableRef, $columnRef)) {
                    // Remove index for ref
                    $result = $this->removeIndex($tableRef, $columnRef);
                }
            }
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
     * @throws Phigrate_Exception_Argument
     * @throws Phigrate_Exception_InvalidIndexName
     */
    public function addIndex($tableName, $columnName, $options = array())
    {
        if (empty($tableName)) {
            throw new Phigrate_Exception_Argument(
                'Missing table name parameter'
            );
        }
        if (empty($columnName)) {
            throw new Phigrate_Exception_Argument(
                'Missing column name parameter'
            );
        }
        if (is_array($options) && array_key_exists('foreignKey', $options)
            && $options['foreignKey'] == true)
        {
            // Recuperer la table de reference
            $tableRef = null;
            if (array_key_exists('tableRef', $options)) {
                $tableRef = $options['tableRef'];
            }
            // Recuperer la colonne de reference
            $columnRef = null;
            if (array_key_exists('columnRef', $options)) {
                $columnRef = $options['columnRef'];
            }
            return $this->addForeignKey($tableName, $columnName, $tableRef, $columnRef, $options);
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
        if (strlen($indexName) > self::MAX_IDENTIFIER_LENGTH) {
            $msg = 'The auto-generated index name is too long for '
                . 'MySQL (max is 64 chars). Considering using \'name\' option '
                . 'parameter to specify a custom name for this index. '
                . 'Note: you will also need to specify this custom name '
                . 'in a drop_index() - if you have one.';
            require_once 'Phigrate/Exception/InvalidIndexName.php';
            throw new Phigrate_Exception_InvalidIndexName($msg);
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
            'CREATE %sINDEX %s ON %s(%s);',
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
     * @throws Phigrate_Exception_Argument
     */
    public function removeIndex($tableName, $columnName, $options = array())
    {
        if (empty($tableName)) {
            throw new Phigrate_Exception_Argument(
                'Missing table name parameter'
            );
        }
        if (empty($columnName)) {
            throw new Phigrate_Exception_Argument(
                'Missing column name parameter'
            );
        }
        if (is_array($options) && array_key_exists('foreignKey', $options)
            && $options['foreignKey'] == true)
        {
            // Recuperer la table de reference
            $tableRef = null;
            if (array_key_exists('tableRef', $options)) {
                $tableRef = $options['tableRef'];
            }
            // Recuperer la colonne de reference
            $columnRef = null;
            if (array_key_exists('columnRef', $options)) {
                $columnRef = $options['columnRef'];
            }
            return $this->removeForeignKey($tableName, $columnName, $tableRef, $columnRef, $options);
        }
        $indexName = $this->_getIndexName($tableName, $columnName, $options);
        $sql = sprintf(
            'DROP INDEX %s ON %s;',
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
     * @throws Phigrate_Exception_Argument
     */
    public function hasIndex($tableName, $columnName, $options = array())
    {
        if (empty($tableName)) {
            throw new Phigrate_Exception_Argument(
                'Missing table name parameter'
            );
        }
        if (empty($columnName)) {
            throw new Phigrate_Exception_Argument(
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
     * @throws Phigrate_Exception_Argument
     */
    public function indexes($tableName)
    {
        if (empty($tableName)) {
            throw new Phigrate_Exception_Argument(
                'Missing table name parameter'
            );
        }
        $sql = sprintf('SHOW KEYS FROM %s;', $this->identifier($tableName));
        $result = $this->selectAll($sql);
        $indexes = array();
        foreach ($result as $row) {
            //skip primary
            if ($row['Key_name'] == 'PRIMARY') {
                continue;
            }
            $indexes[] = array(
                'name' => $row['Key_name'],
                'unique' => (int)$row['Non_unique'] == 0 ? true : false,
            );
        }
        return $indexes;
    }

    /**
     * Indique si la colonne est une clef primaire
     *
     * @param string $tableName  The table name
     * @param string $columnName The column name
     *
     * @return boolean
     * @throws Phigrate_Exception_Argument
     */
    public function isPrimaryKey($tableName, $columnName)
    {
        if (empty($tableName)) {
            throw new Phigrate_Exception_Argument(
                'Missing table name parameter'
            );
        }
        if (empty($columnName)) {
            throw new Phigrate_Exception_Argument(
                'Missing column name parameter'
            );
        }
        $sql = sprintf('SHOW KEYS FROM %s;', $this->identifier($tableName));
        $result = $this->selectAll($sql);
        foreach ($result as $row) {
            if ($row['Key_name'] == 'PRIMARY' && $row['Column_name'] == $columnName) {
                return true;
            }
        }
        return false;
    }

    /**
     * type to sql : Return the type SQL of type generic
     *
     * @param string $type    The type generic
     * @param array  $options The options definition
     *
     * @return string
     * @throws Phigrate_Exception_Argument
     */
    public function typeToSql($type, $options = array())
    {
        $natives = $this->nativeDatabaseTypes();
        if (! is_array($options)) {
            $options = array();
        }

        if (! array_key_exists($type, $natives)) {
            $error = sprintf(
                'Error: I dont know what column type '
                . "of '%s' maps to for MySQL.",
                $type
            );
            $error .= "\nYou provided: {$type}\n"
                . "Valid types are: \n";
            $types = array_keys($natives);
            foreach ($types as $t) {
                if ($t == 'primary_key') {
                    continue;
                }
                $error .= "\t{$t}\n";
            }
            throw new Phigrate_Exception_Argument($error);
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
        }
        if ($type == 'decimal') {
            //ignore limit, use precison and scale
            if (isset($precision)) {
                if (isset($scale) && is_int($scale)) {
                    $column_type_sql .= sprintf('(%d, %d)', $precision, $scale);
                } else {
                    $column_type_sql .= sprintf('(%d)', $precision);
                }//scale
            } elseif ($scale) {
                throw new Phigrate_Exception_Argument(
                    'Error adding decimal column: precision cannot '
                    . 'be empty if scale is specified'
                );
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
        if (array_key_exists('options', $options)) {
            $column_type_sql .= ' ' . $options['options'];
        }

        return $column_type_sql;
    }

    /**
     * add column options
     *
     * @param array  $options The options definition
     *
     * @return string
     */
    public function addColumnOptions($type, $options)
    {
        $sql = '';

        if (!is_array($options)) {
            return $sql;
        }
        
        // unsigned
        if (array_key_exists('unsigned', $options)
            && $options['unsigned'] === true
        ) {
            $sql .= ' UNSIGNED';
        }

        // auto_increment
        if (array_key_exists('auto_increment', $options) && $options['auto_increment'] === true) {
            $sql .= ' auto_increment';
            $options['null'] = false;
        }

        // default null
        if ((array_key_exists('null', $options) && $options['null'] === false)
            || (array_key_exists('default', $options) && isset($options['default']))
            || (array_key_exists('primary_key', $options) && $options['primary_key'] === true))
        {
            $sql .= ' NOT NULL';
        } elseif (! array_key_exists('default', $options) || ! isset($options['default'])) {
            $sql .= ' NULL DEFAULT NULL';
        } else {
            $sql .= ' NULL';
        }

        // default value
        if (array_key_exists('default', $options)
            && isset($options['default'])
        ) {
            if ($this->_isSqlMethodCall($options['default'])) {
                //$default_value = $options['default'];
                throw new Exception(
                    'MySQL does not support function calls '
                    . 'as default values, constants only.'
                );
            }
            if (is_int($options['default'])) {
                $default_format = '%d';
            } elseif (is_bool($options['default'])) {
                $default_format = "'%d'";
            } elseif (($type == 'timestamp' || $type == 'datetime' || $type == 'date' || $type == 'time')
                && $options['default'] == 'CURRENT_TIMESTAMP')
            {
                $default_format = "%s";
            } else {
                $default_format = "'%s'";
            }
            $default_value = sprintf($default_format, $options['default']);
            $sql .= sprintf(' DEFAULT %s', $default_value);
        }
        
        // on update
        if (($type == 'timestamp' || $type == 'datetime' || $type == 'date' || $type == 'time')
            && array_key_exists('update', $options))
        {
            $sql .= ' ON UPDATE ' . $options['update'];
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
            "INSERT INTO %s (version) VALUES ('%s');",
            PHIGRATE_TS_SCHEMA_TBL_NAME,
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
            "DELETE FROM %s WHERE version = '%s';",
            PHIGRATE_TS_SCHEMA_TBL_NAME,
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
        return 'Phigrate_Adapter_Mysql_Adapter, version: ' . $this->_version;
    }

    /**
     * Return the version of server MySQL
     * 
     * @return void
     */
    public function getVersionServer()
    {
        $version = $this->_conn->getAttribute(PDO::ATTR_SERVER_VERSION);
        return $version;
    }

    /**
     * Add comment to code SQL
     *
     * @param string $comment The comment
     *
     * @return boolean
     */
    public function comment($comment)
    {
        if ($this->hasExport()) {
            $this->_sql .= "\n-- " . (string)$comment . "\n\n";
        }
        return true;
    }


    //-----------------------------------
    // PRIVATE METHODS
    //-----------------------------------

    /**
     * initialize DSN MySQL with URI or array config
     *
     * @return string
     */
    protected function _initDsn()
    {
        $dsn = 'mysql:';
        if (array_key_exists('uri', $this->_dbConfig)) {
            $dsn = 'uri:' . $this->_dbConfig['uri'];
        } elseif (array_key_exists('database', $this->_dbConfig)) {
            $dsn .= 'dbname=' . $this->_dbConfig['database'];

            if (array_key_exists('socket', $this->_dbConfig)) {
                $dsn .= ';unix_socket=' . $this->_dbConfig['socket'];
            } elseif (array_key_exists('host', $this->_dbConfig)) {
                $dsn .= ';host=' . $this->_dbConfig['host'];
                if (array_key_exists('port', $this->_dbConfig)) {
                    $dsn .= ';port=' . $this->_dbConfig['port'];
                }
            }
        }
        return $dsn;
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
            $results = $this->getConnexion()->query($qry);
            foreach ($results as $row) {
                $table = $row[0];
                $this->_tables[$table] = true;
            }
            $this->_tablesLoaded = true;
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
        $type = $match[0];
        switch (strtolower($type)) {
            case 'select':
                return self::SQL_SELECT;
            case 'update':
                return self::SQL_UPDATE;
            case 'delete':
                return self::SQL_DELETE;
            case 'insert':
                return self::SQL_INSERT;
            case 'alter':
                return self::SQL_ALTER;
            case 'drop':
                return self::SQL_DROP;
            case 'create':
                return self::SQL_CREATE;
            case 'show':
                return self::SQL_SHOW;
            case 'rename':
                return self::SQL_RENAME;
            case 'set':
                return self::SQL_SET;
            default:
                return self::SQL_UNKNOWN_QUERY_TYPE;
        }
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
            $indexName = Phigrate_Util_Naming::indexName(
                $tableName,
                $columnName
            );
        }
        return $indexName;
    }

    /**
     * _getIndexName
     *
     * @param string       $tableName  The table name
     * @param string|array $columnName The column name(s)
     * @param string       $tableRef   The table ref name
     * @param string|array $columnRef  The column ref name(s)
     * @param array        $options    The options definition of the index
     *
     * @return string
     */
    private function _getConstrainteName($tableName, $columnName, $tableRef, $columnRef, $options = array())
    {
        //did the user specify an index name?
        if (is_array($options) && array_key_exists('name', $options)) {
            $constrainteName = $options['name'];
        } else {
            $constrainteName = Phigrate_Util_Naming::constrainteName(
                $tableName,
                $columnName,
                $tableRef,
                $columnRef
            );
        }
        return $constrainteName;
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
        $this->getConnexion()->beginTransaction();
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
        $this->getConnexion()->commit();
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
        $this->getConnexion()->rollBack();
        $this->_inTrx = false;
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
