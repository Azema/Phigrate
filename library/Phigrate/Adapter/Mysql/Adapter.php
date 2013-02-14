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
            'smalltext'     => array('name' => 'tinytext'),
            'text'          => array('name' => 'text'),
            'mediumtext'    => array('name' => 'mediumtext'),
            'longtext'      => array('name' => 'longtext'),
            'integer'       => array(
                'name' => 'int',
                'limit' => 11,
            ),
            'tinyinteger'   => array('name' => 'tinyint'),
            'smallinteger'  => array('name' => 'smallint'),
            'mediuminteger' => array('name' => 'mediumint'),
            'biginteger'    => array('name' => 'bigint'),
            'float'         => array('name' => 'float'),
            'decimal'       => array('name' => 'decimal'),
            'datetime'      => array('name' => 'datetime'),
            'timestamp'     => array('name' => 'timestamp'),
            'time'          => array('name' => 'time'),
            'date'          => array('name' => 'date'),
            'tinybinary'    => array('name' => 'tinyblob'),
            'binary'        => array('name' => 'blob'),
            'mediumbinary'  => array('name' => 'mediumblob'),
            'longbinary'    => array('name' => 'longblob'),
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
            $this->createTable(
                PHIGRATE_TS_SCHEMA_TBL_NAME,
                array('id' => false))
                ->column('version', 'string')
                ->finish();
            $this->addIndex(
                PHIGRATE_TS_SCHEMA_TBL_NAME,
                'version',
                array('unique' => true)
            );
        }
        return true;
    }

    /**
     * start a transaction if not started
     *
     * @return void
     */
    public function startTransaction()
    {
        if ($this->hasExport()) {
            return 'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO"' . $this->_delimiter
                . "\nSET AUTOCOMMIT=0" . $this->_delimiter
                . "\nSTART TRANSACTION" . $this->_delimiter;
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
            return 'COMMIT' . $this->_delimiter;
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
            return '';
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
        $ddl = 'SHOW DATABASES' . $this->_delimiter;
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
        $ddl = sprintf('CREATE DATABASE %s%s', $this->identifier($db), $this->_delimiter);
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
        $ddl = sprintf('DROP DATABASE IF EXISTS %s%s', $this->identifier($db), $this->_delimiter);
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

            $stmt = sprintf('SHOW CREATE TABLE %s%s', $this->identifier($tbl), $this->_delimiter);
            $result = $this->execute($stmt);

            if (is_array($result) && count($result) == 1) {
                $row = $result[0];
                if (array_key_exists('Create Table', $row)) {
                    $final .= $row['Create Table'] . $this->_delimiter . "\n\n";
                } else if (array_key_exists('Create View', $row)) {
                    $views .= $row['Create View'] . $this->_delimiter . "\n\n";
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
        if (substr($query, -1) != $this->_delimiter) {
            $query .= $this->_delimiter;
        }
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
        // Add semicolon on query if not exists
        if (substr($query, -1) != $this->_delimiter) {
            $query .= $this->_delimiter;
        }
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
        $ddl = sprintf('DROP TABLE IF EXISTS %s%s', $this->identifier($tbl), $this->_delimiter);
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
        $this->_checkMissingParameters(
            array(
                array(
                    'name' => 'original table name',
                    'arg'  => $name,
                ),
                array(
                    'name' => 'new table name',
                    'arg'  => $newName,
                ),
            )
        );
        $sql = sprintf(
            'RENAME TABLE %s TO %s%s',
            $this->identifier($name),
            $this->identifier($newName),
            $this->_delimiter
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
        $this->_checkMissingParameters(
            array(
                array(
                    'name' => 'table name',
                    'arg'  => $tableName,
                ),
                array(
                    'name' => 'column name',
                    'arg'  => $columnName,
                ),
                array(
                    'name' => 'type',
                    'arg'  => $type,
                ),
            )
        );
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
        $sql .= $this->addColumnOptions($type, $options) . $this->_delimiter;

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
        $this->_checkMissingParameters(
            array(
                array(
                    'name' => 'table name',
                    'arg'  => $tableName,
                ),
                array(
                    'name' => 'column name',
                    'arg'  => $columnName,
                ),
            )
        );
        $sql = sprintf(
            'ALTER TABLE %s DROP COLUMN %s%s',
            $this->identifier($tableName),
            $this->identifier($columnName),
            $this->_delimiter
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
        $this->_checkMissingParameters(
            array(
                array(
                    'name' => 'table name',
                    'arg'  => $tableName,
                ),
                array(
                    'name' => 'column name',
                    'arg'  => $columnName,
                ),
                array(
                    'name' => 'type',
                    'arg'  => $type,
                ),
            )
        );
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
        $sql .= $this->addColumnOptions($type, $options) . $this->_delimiter;

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
        $this->_checkMissingParameters(
            array(
                array(
                    'name' => 'table name',
                    'arg'  => $tableName,
                ),
                array(
                    'name' => 'original column name',
                    'arg'  => $columnName,
                ),
                array(
                    'name' => 'new column name',
                    'arg'  => $newColumnName,
                ),
            )
        );
        $columnInfo = $this->columnInfo($tableName, $columnName);
        $current_type = $columnInfo['type'];
        $sql = sprintf(
            'ALTER TABLE %s CHANGE %s %s %s%s',
            $this->identifier($tableName),
            $this->identifier($columnName),
            $this->identifier($newColumnName),
            $current_type,
            $this->_delimiter
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
        $this->_checkMissingParameters(
            array(
                array(
                    'name' => 'table name',
                    'arg'  => $table,
                ),
                array(
                    'name' => 'column name',
                    'arg'  => $column,
                ),
            )
        );
        $sql = sprintf(
            'SHOW COLUMNS FROM %s LIKE \'%s\'%s',
            $this->identifier($table),
            $column,
            $this->_delimiter
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
        $this->_checkMissingParameters(
            array(
                array(
                    'name' => 'table name',
                    'arg'  => $tableName,
                ),
                array(
                    'name' => 'column name',
                    'arg'  => $columnName,
                ),
                array(
                    'name' => 'table ref name',
                    'arg'  => $tableRef,
                ),
            )
        );
        if (empty($columnRef)) {
            $columnRef = 'id';
        }
        // Check if table has engine InnoDB
        $this->_checkEngineForForeignKey($tableName);

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
            'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s) ON DELETE %s ON UPDATE %s%s',
            $this->identifier($tableName),
            $constrainteName,
            $this->identifier($columnName),
            $this->identifier($tableRef),
            $this->identifier($columnRef),
            $delete,
            $update,
            $this->_delimiter
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
        $this->_checkMissingParameters(
            array(
                array(
                    'name' => 'table name',
                    'arg'  => $tableName,
                ),
                array(
                    'name' => 'column name',
                    'arg'  => $columnName,
                ),
                array(
                    'name' => 'table ref name',
                    'arg'  => $tableRef,
                ),
            )
        );
        if (empty($columnRef)) {
            $columnRef = 'id';
        }
        // Check if table has engine InnoDB
        $this->_checkEngineForForeignKey($tableName);

        $constrainteName = $this->_getConstrainteName($tableName, $columnName, $tableRef, $columnRef, $options);
        $sql = sprintf(
            'ALTER TABLE %s DROP FOREIGN KEY %s%s',
            $this->identifier($tableName),
            $constrainteName,
            $this->_delimiter
        );
        $result = false;
        try {
            if ($this->executeDdl($sql)) {
                $result = true;
                // Vérifier la presence de l'index si pas en mode export
                if (! $this->hasExport()) {
                    if ($this->hasIndex($tableName, $columnName, array('name' => $constrainteName))) {
                        $result = $this->removeIndex($tableName, $columnName, array('name' => $constrainteName));
                    }
                    // Check if ref is not primary key and is an index
                    if (! $this->isPrimaryKey($tableRef, $columnRef) && $this->hasIndex($tableRef, $columnRef)) {
                        // Remove index for ref
                        $result = $this->removeIndex($tableRef, $columnRef);
                    }
                }
            }
        } catch (Exception $e) {
            $this->_logger->err('Constrainte ('.$constrainteName.') does not exists.');
            throw new Phigrate_Exception_AdapterQuery(
                'Constrainte ('.$constrainteName.') does not exists.', $e->getCode(), $e
            );
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
        $this->_checkMissingParameters(
            array(
                array(
                    'name' => 'table name',
                    'arg'  => $tableName,
                ),
                array(
                    'name' => 'column name',
                    'arg'  => $columnName,
                ),
            )
        );
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
            'CREATE %sINDEX %s ON %s(%s)%s',
            ($unique === true) ? 'UNIQUE ' : '',
            $indexName,
            $this->identifier($tableName),
            join(', ', $cols),
            $this->_delimiter
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
        $this->_checkMissingParameters(
            array(
                array(
                    'name' => 'table name',
                    'arg'  => $tableName,
                ),
                array(
                    'name' => 'column name',
                    'arg'  => $columnName,
                ),
            )
        );
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
            'DROP INDEX %s ON %s%s',
            $this->identifier($indexName),
            $this->identifier($tableName),
            $this->_delimiter
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
        $this->_checkMissingParameters(
            array(
                array(
                    'name' => 'table name',
                    'arg'  => $tableName,
                ),
                array(
                    'name' => 'column name',
                    'arg'  => $columnName,
                ),
            )
        );
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
        $this->_checkMissingParameters(
            array(
                array(
                    'name' => 'table name',
                    'arg'  => $tableName,
                ),
            )
        );
        $sql = sprintf('SHOW KEYS FROM %s%s', $this->identifier($tableName), $this->_delimiter);
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
        $this->_checkMissingParameters(
            array(
                array(
                    'name' => 'table name',
                    'arg'  => $tableName,
                ),
                array(
                    'name' => 'column name',
                    'arg'  => $columnName,
                ),
            )
        );
        if (!is_array($columnName)) {
            $columnName = array($columnName);
        }
        $sql = sprintf('SHOW KEYS FROM %s%s', $this->identifier($tableName), $this->_delimiter);
        $result = $this->selectAll($sql);
        $primary = array_fill_keys($columnName, false);
        foreach ($result as $row) {
            if ($row['Key_name'] == 'PRIMARY' && array_key_exists($row['Column_name'], $primary)) {
                $primary[$row['Column_name']] = true;
            }
        }
        foreach ($primary as $col) {
            if (false === $col) {
                return false;
            }
        }
        return true;
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
     * Check the charset
     *
     * @param string $character The character
     *
     * @return boolean
     */
    protected function _checkCaracter($character)
    {
        // En mode export, on ne peut pas vérifier le charset
        if ($this->hasExport()) {
            return true;
        }
        $sql = sprintf('SHOW CHARACTER SET%s', $this->_delimiter);
        $result = $this->selectAll($sql);
        foreach ($result as $row) {
            if ($row['Charset'] == $character) {
                return true;
            }
        }
    }

    /**
     * Check the collation
     *
     * @param string $collation The collation
     * @param string $charset   The charset
     *
     * @return boolean
     */
    protected function _checkCollation($collation, $charset)
    {
        // En mode export, on ne peut pas vérifier le charset
        if ($this->hasExport()) {
            return true;
        }
        $sql = sprintf('SHOW COLLATION LIKE \'%s\%\'%s', $character, $this->_delimiter);
        $result = $this->selectAll($sql);
        foreach ($result as $row) {
            if ($row['Collation'] == $collation) {
                return true;
            }
        }
        return false;
    }

    /**
     * add column options
     *
     * @param string $type    The type of column
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
        if (array_key_exists('unsigned', $options) && $options['unsigned'] === true) {
            $sql .= ' UNSIGNED';
        }
        // Check the type string for options character and collate
        if ($type == 'string' || $type == 'tinytext' || $type == 'text'
            || $type == 'mediumtext' || $type == 'longtext')
        {
            // Add character option
            if (array_key_exists('character', $options) && $this->_checkCaracter($options['character'])) {
                $sql .= ' CHARACTER SET ' . $this->identifier($options['character']);
                // Add collate option
                if (array_key_exists('collate', $options)
                    && $this->_checkCollation($options['collate'], $options['character']))
                {
                    $sql .= ' COLLATE ' . $this->identifier($options['collate']);
                }
            }
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

        // Add comment column option
        if (array_key_exists('comment', $options)) {
            $sql .= sprintf(" COMMENT %s", $this->quote((string)$options['comment']));
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
            "INSERT INTO %s (version) VALUES ('%s')%s",
            PHIGRATE_TS_SCHEMA_TBL_NAME,
            $version,
            $this->_delimiter
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
            "DELETE FROM %s WHERE version = '%s'%s",
            PHIGRATE_TS_SCHEMA_TBL_NAME,
            $version,
            $this->_delimiter
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
        $version = $this->getConnexion()->getAttribute(PDO::ATTR_SERVER_VERSION);
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

    /**
     * Add view
     *
     * @param string $viewName The view name
     * @param string $select   The select statement
     * @param array  $options  The options
     *
     * @return boolean
     */
    public function createView($viewName, $select, $options = array())
    {
        $create = 'CREATE';
        if (array_key_exists('replace', $options) && $options['replace'] === true) {
            $create .= ' OR REPLACE';
        }
        $query = $create . ' %s %s VIEW %s%s AS %s%s;';
        return $this->_createOrAlterView($viewName, $select, $query, 'create', $options);
    }

    /**
     * Change view
     *
     * @param string $viewName The view name
     * @param string $select   The select statement
     * @param array  $options  The options
     *
     * @return boolean
     */
    public function changeView($viewName, $select, $options = array())
    {
        $query = 'ALTER %s %s VIEW %s%s AS %s%s;';
        return $this->_createOrAlterView($viewName, $select, $query, 'change', $options);
    }

    /**
     * Drop view
     *
     * @param string $viewName The view name
     *
     * @return boolean
     */
    public function dropView($viewName)
    {
        $this->_checkMissingParameters(
            array(
                array(
                    'name' => 'view name',
                    'arg'  => $viewName,
                ),
            )
        );
        $ddl = sprintf('DROP VIEW IF EXISTS %s;', $this->identifier($viewName));
        return $this->executeDdl($ddl);
    }

    //-----------------------------------
    // PRIVATE METHODS
    //-----------------------------------

    /**
     * Check missing parameters
     *
     * @param array $parameters The array of parameters
     *
     * @return void
     * @throws Phigrate_Exception_Argument
     */
    protected function _checkMissingParameters($parameters)
    {
        foreach ($parameters as $parameter) {
            if (empty($parameter['arg'])) {
                throw new Phigrate_Exception_Argument(
                    'Missing ' . $parameter['name'] . ' parameter'
                );
            }
        }
    }

    /**
     * Check engine of table for foreign key constraints
     *
     * @param string $tableName The name of table
     *
     * @return void
     * @throws Phigrate_Exception_InvalidTableDefinition
     */
    protected function _checkEngineForForeignKey($tableName)
    {
        // Check if table has engine InnoDB
        if (!$this->hasExport()) {
            $stmt = sprintf('SHOW CREATE TABLE %s%s', $this->identifier($tableName), $this->_delimiter);
            $result = $this->execute($stmt);

            if (is_array($result) && count($result) == 1) {
                $row = $result[0];
                if (array_key_exists('Create Table', $row) && false != strstr($row['Create Table'], 'ENGINE')) {
                    $matches = array();
                    preg_match('/ENGINE=(\w*)/', $row['Create Table'], $matches);
                    if (count($matches) > 1 && strtoupper($matches[1]) != 'INNODB') {
                        throw new Phigrate_Exception_InvalidTableDefinition(
                            $matches[1] . ' does not supports foreign key constraints.'
                        );
                    }
                }
            }
        }
    }

    /**
     * Create or Alter view
     *
     * @param string $viewName The view name
     * @param string $select   The select statement
     * @param string $query    The query for view
     * @param string $funcName The function name
     * @param array  $options  The options
     *
     * @return boolean
     */
    protected function _createOrAlterView($viewName, $select, $query, $funcName,  $options = array())
    {
        $this->_checkMissingParameters(
            array(
                array(
                    'name' => 'view name',
                    'arg'  => $viewName,
                ),
            )
        );
        $query_type = $this->_determineQueryType($select);
        if ($query_type != self::SQL_SELECT) {
            require_once 'Phigrate/Exception/AdapterQuery.php';
            throw new Phigrate_Exception_AdapterQuery(
                'Sql for ' . $funcName . 'View() is not a SELECT : ' . $select
            );
        }
        $algorithm = $this->_getAlgorithm($options, $funcName);
        $definer = $this->_getDefiner($options);
        $columnList = $this->_getColumnList($options);
        $check = $this->_getCheckOption($options, $funcName);
        $ddl = sprintf(
            $query,
            $algorithm,
            $definer,
            $this->identifier($viewName),
            $columnList,
            $select,
            $check
        );
        return $this->executeDdl($ddl);
    }

    /**
     * Return algorithm for create and alter view
     *
     * @param array  $options  Array of options containing algorithm key
     * @param string $funcName The fonction name
     *
     * @return string
     */
    protected function _getAlgorithm($options, $funcName)
    {
        $algorithm = 'ALGORITHM=UNDEFINED';
        $allowedAlgos = array('UNDEFINED', 'MERGE', 'TEMPTABLE');
        if (array_key_exists('algorithm', $options) && !empty($options['algorithm'])) {
            if (!in_array($options['algorithm'], $allowedAlgos)) {
                throw new Phigrate_Exception_Argument(
                    'algorithm allowed for ' . $funcName . ' view : ' . implode(', ', $allowedAlgos)
                );
            }
            $algorithm = 'ALGORITHM=' . $options['algorithm'];
        }
        return $algorithm;
    }

    /**
     * Return definer for create and alter view
     *
     * @param array $options Array of options containing definer key
     *
     * @return string
     */
    protected function _getDefiner($options)
    {
        $definer = 'DEFINER=CURRENT_USER';
        // Get definer
        if (array_key_exists('definer', $options) && !empty($options['definer'])) {
            // check definer format
            if (preg_match("/'\w*'@'\w*'/", $options['definer']) == false) {
                throw new Phigrate_Exception_Argument(
                    "The definer should be specified as 'user'@'host'."
                );
            }
            $definer = 'DEFINER=' . $options['definer'];
        }
        return $definer;
    }

    /**
     * Return list of column for create and alter view
     *
     * @param array $options Array of options containing columnList key
     *
     * @return string
     */
    protected function _getColumnList($options)
    {
        $columnList = '';
        if (array_key_exists('columnList', $options) && is_array($options['columnList'])
            && !empty($options['columnList']))
        {
            $columns = array_map(array($this, 'identifier'), $options['columnList']);
            $columnList = ' (' . implode(',', $columns) . ')';
        }
        return $columnList;
    }

    /**
     * Return check option for create and alter view
     *
     * @param array  $options  Array of options containing check key
     * @param string $funcName The fonction name
     *
     * @return string
     */
    protected function _getCheckOption($options, $funcName)
    {
        $check = '';
        $allowedCheck = array('LOCAL', 'CASCADED');
        if (array_key_exists('check', $options) && !empty($options['check'])) {
            $versionServer = preg_replace('/[^\.\d]/', '', $this->getVersionServer());
            if (!$this->hasExport() && version_compare($versionServer, '5.0.2') < 0) {
                throw new Phigrate_Exception_AdapterQuery(
                    'The WITH CHECK OPTION clause was implemented in MySQL 5.0.2.'
                );
            } elseif (!is_bool($options['check']) && !in_array($options['check'], $allowedCheck)) {
                throw new Phigrate_Exception_Argument(
                    'check option allowed for ' . $funcName . ' view : ' . implode(', ', $allowedCheck)
                );
            } elseif ($options['check'] === true) {
                $options['check'] = 'CASCADED';
            }
            $check = ' WITH ' . $options['check'] . ' CHECK OPTION';
        }
        return $check;
    }

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
            $qry = 'SHOW TABLES' . $this->_delimiter;
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
