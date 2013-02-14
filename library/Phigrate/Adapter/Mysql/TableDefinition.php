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
 * @see Phigrate_Adapter_TableDefinition
 */
require_once 'Phigrate/Adapter/TableDefinition.php';

/**
 * Class of mysql table definition
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
class Phigrate_Adapter_Mysql_TableDefinition extends Phigrate_Adapter_TableDefinition
{
    /**
     * adapter MySQL
     *
     * @var Phigrate_Adapter_Mysql_Adapter
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
     * @param Phigrate_Adapter_Base $adapter Adapter MySQL
     * @param string                 $name    The table name
     * @param array                  $options The options definition
     *
     * @return Phigrate_Adapter_Mysql_TableDefinition
     * @throws Phigrate_Exception_MissingAdapter
     * @throws Phigrate_Exception_Argument
     */
    public function __construct($adapter, $name, $options = array())
    {
        parent::__construct($adapter, $name, $options);
        $this->_initSql($name, $options);

        if (array_key_exists('id', $options)) {
            if ($options['id'] === false) {
                $this->_autoGenerateId = false;
            } elseif (is_string($options['id']) && ! empty($options['id'])) {
                //if its a string then we want to auto-generate an integer-based
                //primary key with this name
                $this->_autoGenerateId = false;
                $primaryName = $options['id'];
                $options = array(
                    'unsigned' => true,
                    'null' => false,
                    'auto_increment' => true,
                    'primary_key' => true,
                );
                $this->column($primaryName, 'integer', $options);
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
     * @return Phigrate_Adapter_TableDefinition
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
            if ($options['primary_key'] === true) {
                $this->_primaryKeys[] = $column_name;
            }
        }

        if (array_key_exists('auto_increment', $options)) {
            if ($options['auto_increment'] === true) {
                $column_options['auto_increment'] = true;
            }
        }
        $column_options = array_merge($column_options, $options);
        $column = new Phigrate_Adapter_Mysql_ColumnDefinition(
            $this->_adapter,
            $this->_prefix . $column_name,
            $type,
            $column_options
        );

        $this->_columns[] = $column;

        return $this;
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
            $quoted = array_map(
                array($this->_adapter, 'identifier'),
                $this->_primaryKeys
            );
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
     * @throws Phigrate_Exception_InvalidTableDefinition
     */
    public function finish($wants_sql = false)
    {
        if ($this->_initialized == false) {
            require_once 'Phigrate/Exception/InvalidTableDefinition.php';
            throw new Phigrate_Exception_InvalidTableDefinition(
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
            $opt_str = ' ' . $this->_options['options'];
        }

        $close_sql = sprintf(')%s', $opt_str);
        $createTableSql = $this->_sql;

        if ($this->_autoGenerateId === true) {
            $this->_primaryKeys[] = 'id';
            $primary_id = new Phigrate_Adapter_Mysql_ColumnDefinition(
                $this->_adapter,
                $this->_prefix . 'id',
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
        $createTableSql .= $this->_keys() . $close_sql . $this->_adapter->getDelimiter();

        if ($wants_sql) {
            return $createTableSql;
        }
        return $this->_adapter->executeDdl($createTableSql);
    }

    /**
     * columns to str
     *
     * @return string
     */
    protected function _columnsToStr()
    {
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
        if (! is_array($options)) {
            $options = array();
        }

        //are we forcing table creation? If so, drop it first
        if (array_key_exists('force', $options) && $options['force'] == true) {
            $this->_adapter->dropTable($name);
        }
        $temp = '';
        if (array_key_exists('temporary', $options)) {
            $temp = ' TEMPORARY';
        }
        $create_sql = sprintf('CREATE%s TABLE ', $temp);
        $create_sql .= sprintf("%s (\n", $this->_adapter->identifier($name));
        $this->_sql .= $create_sql;
        $this->_initialized = true;
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
