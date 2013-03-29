<?php

class adapterTaskMock extends adapterMock
{
    public $upExceptionSchema = false;

    public $versions = array();

    public $supportMigration = true;

    public $exception;

    public function __construct($dbConfig, $logger)
    {
        require_once 'Phigrate/Logger.php';
        $logger = Phigrate_Logger::instance(PHIGRATE_BASE . '/tests/logs/tests.log');
        $this->_conn = new pdoTaskMock();
        $this->setLogger($logger);
    }

    public function supportsMigrations()
    {
        return $this->supportMigration;
    }

    public function addIndex($table, $column, $options = array())
    {
        if (isset($this->exception)) {
            throw $this->exception;
        }
        parent::addIndex($table, $column, $options);
    }

    public function removeIndex($table, $column)
    {
        if (isset($this->exception)) {
            throw $this->exception;
        }
        parent::removeIndex($table, $column);
    }

    public function throwException($e)
    {
        $this->exception = $e;
    }

    public function setTableSchemaExist($exist)
    {
        $this->_conn->tableSchemaExist = $exist;
        return $this;
    }

    public function createSchemaVersionTable()
    {
        $this->_conn->tableSchemaExist = true;
    }

    public function schema()
    {
        if ($this->upExceptionSchema) {
            throw new Exception('Up exception required');
        }
        $schema = '';
        if ($this->_conn->tableSchemaExist) {
            $schema = file_get_contents(FIXTURES_PATH . '/db/schema.txt');
        }
        return $schema;
    }

    public function selectAll($query)
    {
        if ($query == 'SELECT version FROM `'.PHIGRATE_TS_SCHEMA_TBL_NAME.'`') {
            return $this->versions;
        }
    }

    public function startTransaction()
    {
    }

    public function commitTransaction()
    {
    }

    public function rollbackTransaction()
    {
    }

    public function setCurrentVersion($version)
    {
        $this->versions[] = array('version' => $version);
    }

    public function removeVersion($version)
    {
        $versions = $this->versions;
        foreach($versions as $index => $v) {
            if ($v['version'] == $version) {
                unset($this->versions[$index]);
            }
        }
    }
}

/**
 * Mock class PDO
 *
 * @category   Phigrate
 * @package    Mocks
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/azema/phigrate-migrations
 */
class pdoTaskMock
{
    public $tableSchemaExist = false;

    protected $_queries = array();

    public function query($query)
    {
        $return = array();
        if (preg_match('/^SHOW TABLES/', $query) && $this->tableSchemaExist) {
            $return = array(
                array(PHIGRATE_TS_SCHEMA_TBL_NAME),
            );
        } elseif (preg_match('/^SHOW CREATE TABLE `(.*)`$/', $query, $matches)) {
            if (count($matches) > 1 && $matches[1] == PHIGRATE_TS_SCHEMA_TBL_NAME) {
                return array(
                    array('Create Table' => 'CREATE TABLE `schema_migrations` (
  `version` varchar(255) DEFAULT NULL,
  UNIQUE KEY `idx_schema_migrations_version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8'),
                );
            }
        }
        return $return;
    }

    public function getQueries()
    {
        return $this->_queries;
    }
}

/**
 * @see Phigrate_Adapter_IAdapter
 * @see Phigrate_Task_ITask
 */
require_once 'Phigrate/Adapter/IAdapter.php';
require_once 'Phigrate/Task/ITask.php';
class taskMock implements Phigrate_Task_ITask
{
    public $dir;

    public $adapter;

    public $manager;

    /**
     * execute the task
     *
     * @param array $args Argument to the task
     *
     * @return string
     */
    public function execute($args)
    {
        return __METHOD__ . ': ' . implode(', ', $args);
    }

    /**
     * Return the usage of the task
     *
     * @return string
     */
    public function help()
    {
        return 'my help task';
    }

    public function setDirectoryOfMigrations($dir)
    {
        $this->dir = $dir;
    }

    public function setAdapter(Phigrate_Adapter_IAdapter $adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }

    public function setManager(Phigrate_Task_Manager $manager)
    {
        $this->manager = $manager;
        return $this;
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
