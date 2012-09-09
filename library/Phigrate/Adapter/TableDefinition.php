<?php

/**
 * Phigrate
 *
 * PHP Version 5.3
 *
 * @category   Phigrate
 * @package    Phigrate_Adapter
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */

/**
 * @see Phigrate_Adapter_ColumnDefinition
 */
require_once 'Phigrate/Adapter/ColumnDefinition.php';

/**
 * Class of table definition
 *
 * @category   Phigrate
 * @package    Phigrate_Adapter
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */
abstract class Phigrate_Adapter_TableDefinition
{
    /**
     * columns
     *
     * @var array
     */
    protected $_columns = array();
    /**
     * adapter
     *
     * @var Phigrate_Adapter_Base
     */
    protected $_adapter;
    /**
     * name
     *
     * @var string
     */
    protected $_name;
    /**
     * options
     *
     * @var mixed
     */
    protected $_options;

    /**
     * __construct
     *
     * @param Phigrate_Adapter_Base $adapter Adapter RDBMS
     * @param string                 $name    The table name
     * @param array                  $options The table options
     *
     * @return Phigrate_Adapter_TableDefinition
     */
    function __construct($adapter, $name, $options = array())
    {
        //sanity check
        if (! $adapter instanceof Phigrate_Adapter_Base) {
            require_once 'Phigrate/Exception/MissingAdapter.php';
            throw new Phigrate_Exception_MissingAdapter(
                'Invalid MySQL Adapter instance.'
            );
        }
        if (empty($name) || ! is_string($name)) {
            require_once 'Phigrate/Exception/Argument.php';
            throw new Phigrate_Exception_Argument("Invalid 'name' parameter");
        }

        $this->_adapter = $adapter;
        $this->_name = $name;
        $this->_options = $options;
    }

    /**
     * Determine whether or not the given column already exists in our
     * table definition.
     *
     * This method is lax enough that it can take either a string column name
     * or a Phigrate_Adpater_ColumnDefinition object.
     *
     * @param Phigrate_Adpater_ColumnDefinition|string $column The column to included
     *
     * @return boolean
     */
    public function included($column)
    {
        $columnName = '';
        if ($column instanceof Phigrate_Adapter_ColumnDefinition) {
            $columnName = $column->name;
        } elseif (is_string($column)) {
            $columnName = $column;
        }
        $nbCols = count($this->_columns);
        for ($i = 0; $i < $nbCols; $i++) {
            if ($this->_columns[$i]->name == $columnName) {
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
        return join(',', $this->_columns);
    }

    /**
     * __call 
     * 
     * @param string $name The method name
     * @param array  $args The parameters of method called
     *
     * @return void
     * @throws Phigrate_Exception_MissingMigrationMethod
     */
    public function __call($name, $args)
    {
        if (!method_exists($this, $name)) {
            $backtrace = debug_backtrace();
            require_once 'Phigrate/Exception/MissingMigrationMethod.php';
            throw new Phigrate_Exception_MissingMigrationMethod(
                'Method unknown (' . $name . ' in file '
                . $backtrace[0]['file'] . ':' . $backtrace[0]['line'] . ')'
            );
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
    abstract public function column($column_name, $type, $options = array());

    /**
     * finish
     *
     * @param boolean $wants_sql Flag to get SQL generated
     *
     * @return mixed
     * @throws Phigrate_Exception_InvalidTableDefinition
     */
    abstract public function finish($wants_sql = false);
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
