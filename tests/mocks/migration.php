<?php


/**
 * Migration Mock
 *
 * @category  Phigrate
 * @package   Phigrate_Migration
 * @author    Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright 2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license   GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/Azema/Phigrate
 */
require_once 'Phigrate/Migration/Base.php';
class migrationMock extends Phigrate_Migration_Base
{}

require_once 'Phigrate/Adapter/Mysql/Adapter.php';
/**
 * Mock class adapter RDBMS
 *
 * @category   Phigrate
 * @package    Mocks
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/azema/phigrate-migrations
 */
class migrationAdapterMock extends Phigrate_Adapter_Mysql_Adapter
{
    public $createDatabase;

    public function __construct($dbConfig, $logger)
    {
        $this->_conn = new pdoMock();
        $this->_logger = new logMock();
    }

    public function createDatabase($name)
    {
        $this->datas['createDatabase'] = array(
            'name' => $name,
        );
        return true;
    }

    public function dropDatabase($name)
    {
        $this->datas['dropDatabase'] = array(
            'name' => $name,
        );
        return true;
    }

    public function createTable($tableName, $options = array())
    {
        $this->datas['createTable'] = array(
            'name' => $tableName,
            'options' => $options,
        );
        return parent::createTable($tableName, $options);
    }

    public function dropTable($tableName)
    {
        $this->datas['dropTable'] = array(
            'name' => $tableName,
        );
        return true;
    }

    public function renameTable($name, $newName)
    {
        $this->datas['renameTable'] = array(
            'name' => $name,
            'newName' => $newName,
        );
        return true;
    }

    public function addColumn($tableName, $columnName, $type, $options = array())
    {
        $this->datas['addColumn'] = array(
            'tableName' => $tableName,
            'columnName' => $columnName,
            'type' => $type,
            'options' => $options,
        );
        return true;
    }

    public function removeColumn($tableName, $columnName)
    {
        $this->datas['removeColumn'] = array(
            'tableName' => $tableName,
            'columnName' => $columnName,
        );
        return true;
    }

    public function changeColumn($tableName, $columnName, $type, $options = array())
    {
        $this->datas['changeColumn'] = array(
            'tableName' => $tableName,
            'columnName' => $columnName,
            'type' => $type,
            'options' => $options,
        );
        return true;
    }

    public function renameColumn($tableName, $columnName, $newColumnName)
    {
        $this->datas['renameColumn'] = array(
            'tableName' => $tableName,
            'columnName' => $columnName,
            'newColumnName' => $newColumnName,
        );
        return true;
    }

    public function addIndex($tableName, $columnName, $options = array())
    {
        if (array_key_exists('foreignKey', $options)) {
            return $this->addForeignKey(
                $tableName, $columnName, $options['tableRef'], $options['columnRef'], $options
            );
        }
        $this->datas['addIndex'] = array(
            'tableName' => $tableName,
            'columnName' => $columnName,
            'options' => $options,
        );
        return true;
    }

    public function removeIndex($tableName, $columnName, $options = array())
    {
        if (array_key_exists('foreignKey', $options)) {
            return $this->removeForeignKey(
                $tableName, $columnName, $options['tableRef'], $options['columnRef'], $options
            );
        }
        $this->datas['removeIndex'] = array(
            'tableName' => $tableName,
            'columnName' => $columnName,
            'options' => $options,
        );
        return true;
    }

    public function query($query)
    {
        $this->datas['execute'] = array(
            'query' => $query,
        );
        return true;
    }

    public function selectOne($query)
    {
        $this->datas['selectOne'] = array(
            'query' => $query,
        );
        return array('name' => 'resultOne');
    }

    public function selectAll($query)
    {
        $this->datas['selectAll'] = array(
            'query' => $query,
        );
        return array(
            array(
                'name' => 'resultAll',
            ),
            array(
                'name' => 'second'
            ),
        );
    }

    public function quote($value)
    {
        $this->datas['quote'] = array(
            'value' => $value,
        );
        return parent::quote($value);
    }

    public function comment($comment)
    {
        $this->datas['comments'][] = $comment;
        return true;
    }

    public function addForeignKey($tableName, $columnName, $tableRef, $columnRef = 'id', $options = array())
    {
        $this->datas['addForeignKey'] = array(
            'tableName' => $tableName,
            'columnName' => $columnName,
            'tableRef' => $tableRef,
            'columnRef' => $columnRef,
            'options' => $options,
        );
        return true;
    }

    public function removeForeignKey($tableName, $columnName, $tableRef, $columnRef = 'id', $options = array())
    {
        $this->datas['removeForeignKey'] = array(
            'tableName' => $tableName,
            'columnName' => $columnName,
            'tableRef' => $tableRef,
            'columnRef' => $columnRef,
            'options' => $options,
        );
        return true;
    }
}


/* vim: set expandtab sw=4: */
