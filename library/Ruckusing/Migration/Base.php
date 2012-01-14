<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category  RuckusingMigrations
 * @package   Ruckusing_Migration
 * @author    Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright 2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license   GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/ruckus/ruckusing-migrations
 */

/**
 * @see Ruckusing_Adapter_IAdapter 
 */
require_once 'Ruckusing/Adapter/IAdapter.php';

/**
 * Migration base
 *
 * @category  RuckusingMigrations
 * @package   Ruckusing_Migration
 * @author    Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright 2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license   GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/ruckus/ruckusing-migrations
 */
abstract class Ruckusing_Migration_Base
{
    /**
     * adapter 
     * 
     * @var Ruckusing_Adapter_Base
     */
	private $_adapter;
	
    /**
     * set adapter 
     * 
     * @param Ruckusing_Adapter_Base $adapter Adapter RDBMS
     *
     * @return void
     */
    public function setAdapter($adapter)
    {
		$this->_adapter = $adapter;
	}
	
    /**
     * get adapter 
     * 
     * @return Ruckusing_Adapter_Base
     */
    public function getAdapter()
    {
		return $this->_adapter;
	}
	
    /**
     * create database 
     * 
     * @param string $name    The database name
     * @param array  $options The options definition of the database
     *
     * @return boolean
     */
    public function createDatabase($name, $options = null)
    {
		return $this->_adapter->createDatabase($name, $options);
	}
	
    /**
     * drop database 
     * 
     * @param string $name The database name
     *
     * @return boolean
     */
    public function dropDatabase($name)
    {
		return $this->_adapter->dropDatabase($name);		
	}
	
    /**
     * drop table 
     * 
     * @param string $tbl The table name
     *
     * @return boolean
     */
    public function dropTable($tbl)
    {
		return $this->_adapter->dropTable($tbl);				
	}
	
    /**
     * rename table 
     * 
     * @param string $name    The old name of table
     * @param string $newName The new name of table
     *
     * @return boolean
     */
    public function renameTable($name, $newName)
    {
		return $this->_adapter->renameTable($name, $newName);						
	}
		
    /**
     * rename column 
     * 
     * @param string $tblName       The table name where is the column
     * @param string $columnName    The old column name
     * @param string $newColumnName The new column name
     *
     * @return boolean
     */
    public function renameColumn($tblName, $columnName, $newColumnName)
    {
		return $this->_adapter->renameColumn($tblName, $columnName, $newColumnName);
	}

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
    public function addColumn($tableName, $columnName, $type,
        $options = array()
    ) {
        return $this->_adapter
            ->addColumn($tableName, $columnName, $type, $options);
	}
	
    /**
     * remove column 
     * 
     * @param string $tableName  The table name
     * @param string $columnName The column name
     *
     * @return boolean
     */
    public function removeColumn($tableName, $columnName)
    {
		return $this->_adapter->removeColumn($tableName, $columnName);
	}

    /**
     * change column 
     * 
     * @param string $tableName  The table name
     * @param string $columnName The column name
     * @param string $type       The type generic of the column
     * @param array  $options    The options definition of the column
     *
     * @return boolean
     */
    public function changeColumn($tableName, $columnName, $type, 
        $options = array()
    ) {
        return $this->_adapter
            ->changeColumn($tableName, $columnName, $type, $options);	
	}
	
    /**
     * add index 
     * 
     * @param string       $tableName  The table name
     * @param string|array $columnName The column name
     * @param array        $options    The options defintion of the index
     *
     * @return boolean
     */
    public function addIndex($tableName, $columnName, $options = array())
    {
		return $this->_adapter->addIndex($tableName, $columnName, $options);			
	}
	
    /**
     * remove index 
     * 
     * @param string       $tableName  The table name
     * @param string|array $columnName The column name
     * @param array        $options    The options definition of the index
     *
     * @return boolean
     */
    public function removeIndex($tableName, $columnName, $options = array())
    {
		return $this->_adapter->removeIndex($tableName, $columnName, $options);
	}
	
    /**
     * create table 
     * 
     * @param string $tableName The table name
     * @param array  $options   Options definition table
     *
     * @return boolean
     */
    public function createTable($tableName, $options = array())
    {
		return $this->_adapter->createTable($tableName, $options);
	}
	
    /**
     * execute 
     * 
     * @param string $query Query SQL
     *
     * @return boolean
     */
    public function execute($query)
    {
		return $this->_adapter->query($query);
	}
	
    /**
     * select one 
     * 
     * @param string $sql Query SQL
     *
     * @return mixed
     */
    public function selectOne($sql)
    {
		return $this->_adapter->selectOne($sql);
	}

    /**
     * select all 
     * 
     * @param string $sql Query SQL
     *
     * @return mixed
     */
    public function selectAll($sql)
    {
		return $this->_adapter->selectAll($sql);
    }

    /**
     * query 
     * 
     * @param string $sql Query SQL
     *
     * @return boolean
     */
    public function query($sql)
    {
		return $this->_adapter->query($sql);		
	}
	
    /**
     * Quote a raw string.
     * 
     * @param string|int|float|string[] $value Raw string
     *
     * @return string
     */
    public function quote($value)
    {
        return $this->_adapter->quote($value); 
    }
}
