<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
 * @package    Ruckusing_Adapter
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */

/**
 * Interface of adapters
 *
 * @category   RuckusingMigrations
 * @package    Ruckusing_Adapter
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
interface Ruckusing_Adapter_IAdapter
{
    /**
     * supports migrations ?
     *
     * @return boolean
     */
    public function supportsMigrations();

    /**
     * native database types
     *
     * @return array
     */
    public function nativeDatabaseTypes();

    /**
     * schema
     *
     * @return void
     */
    public function schema();

    /**
     * execute
     *
     * @param string $query Query SQL
     *
     * @return void
     */
    public function execute($query);

    /**
     * Quote a raw string.
     *
     * @param string|int|float|string[] $value Raw string
     *
     * @return string
     */
    public function quote($value);

    //database level operations
    /**
     * database exists
     *
     * @param string $db The database name
     *
     * @return boolean
     */
    public function databaseExists($db);

    /**
     * create table
     *
     * @param string $tableName The table name
     * @param array  $options   Options for definition table
     *
     * @return boolean
     */
    public function createTable($tableName, $options = array());

    /**
     * drop database
     *
     * @param string $db The database name
     *
     * @return boolean
     */
    public function dropDatabase($db);

    /*
     * table level operations
     */

    /**
     * show fields from
     *
     * @param string $tbl The table name
     *
     * @return string
     */
    public function showFieldsFrom($tbl);

    /**
     * table exists ?
     *
     * @param string $tbl Table name
     *
     * @return boolean
     */
    public function tableExists($tbl);

    /**
     * drop table
     *
     * @param string $tbl The table name
     *
     * @return boolean
     */
    public function dropTable($tbl);

    /**
     * rename table
     *
     * @param string $name    The old name of table
     * @param string $newName The new name
     *
     * @return boolean
     */
    public function renameTable($name, $newName);

    /*
     * column level operations
     */

    /**
     * rename column
     *
     * @param string $tableName     The table name where is the column
     * @param string $columnName    The old column name
     * @param string $newColumnName The new column name
     *
     * @return boolean
     */
    public function renameColumn($tableName, $columnName, $newColumnName);

    /**
     * add column
     *
     * @param string $tableName  The table name
     * @param string $columnName The column name
     * @param string $type       The type generic of the column
     * @param array  $options    The options definition of the column
     *
     * @return boolean
     */
    public function addColumn($tableName, $columnName, $type, $options = array());

    /**
     * remove column
     *
     * @param string $tableName  The table name
     * @param string $columnName The column name
     *
     * @return boolean
     */
    public function removeColumn($tableName, $columnName);

    /**
     * change column
     *
     * @param string $tableName  The table name
     * @param string $columnName The column name
     * @param string $type       The type generic of the column
     * @param array  $options    The options definition of the column
     *
     * @return void
     */
    public function changeColumn($tableName, $columnName, $type, $options = array());

    /**
     * remove index
     *
     * @param string $tableName  The table name
     * @param string $columnName The column name
     *
     * @return boolean
     */
    public function removeIndex($tableName, $columnName);

    /**
     * add index
     *
     * @param string $tableName  The table name
     * @param string $columnName The column name
     * @param array  $options    The options definition of the index
     *
     * @return boolean
     */
    public function addIndex($tableName, $columnName, $options = array());
}
