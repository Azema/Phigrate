<?php

if(!defined('BASE')) {
  define('BASE', dirname(__FILE__) . '/..');
}

require_once BASE . '/test_helper.php';
require_once RUCKUSING_BASE . '/lib/classes/class.Ruckusing_FrameworkRunner.php';
require_once RUCKUSING_BASE . '/lib/classes/adapters/class.Ruckusing_MySQLAdapter.php';
require_once RUCKUSING_BASE . '/lib/classes/Ruckusing_exceptions.php';

/*
	To run these unit-tests an empty test database needs to be setup in database.inc.php
	and of course, it has to really exist.
*/

class FrameworkRunnerTest extends PHPUnit_Framework_TestCase
{
    /**
     * _configDb 
     * Table of config DBs
     * 
     * @var array
     */
    private $_configDb;

    /**
     * _env 
     * Environment
     * 
     * @var string
     */
    private $_env;

    /**
     * _logger 
     * 
     * @var Ruckusing_Logger
     */
    private $_logger;

    public function __construct()
    {
        require RUCKUSING_BASE . '/config/database.inc.php';
        $this->_configDb = $ruckusing_db_config;
        $this->_env = 'test';
        $this->_logger = Ruckusing_Logger::instance(
            RUCKUSING_BASE . '/tests/logs/test.log'
        );
        $this->_logger->setPriority(Ruckusing_Logger::DEBUG);
    }

    public function setUp()
    {
        parent::setUp();
        //setup our log
    }
	
    public function tearDown()
    {
        parent::tearDown();
    }

    public function testConstructWithoutDbConfig()
    {
        $this->setExpectedException(
            'Ruckusing_MissingConfigDbException', 
            'Error: (test) DB is not configured!'
        );
        new Ruckusing_FrameworkRunner(null, array(), $this->_env, $this->_logger);
    }
    
    public function testConstructWithoutDbType()
    {
        $this->setExpectedException(
            'Ruckusing_MissingAdapterTypeException', 
            'Error: "type" is not set for "test" DB'
        );
        $dbConfig = array(
            'test' => array(
                //'type'     => 'mysql',
                'host'     => 'localhost',
                'port'     => 3306,
                'database' => 'ruckusing_migrations_test',
                'user'     => 'rucku_test',
                'password' => 'rucku',
            ),
        );
        new Ruckusing_FrameworkRunner($dbConfig, array(), $this->_env, $this->_logger);
    }

    public function testConstructWithoutDbHost()
    {
        $this->setExpectedException(
            'Ruckusing_MissingConfigDbException', 
            'Error: "host" is not set for "test" DB'
        );
        $dbConfig = array(
            'test' => array(
                'type'     => 'mysql',
                //'host'     => 'localhost',
                'port'     => 3306,
                'database' => 'ruckusing_migrations_test',
                'user'     => 'rucku_test',
                'password' => 'rucku',
            ),
        );
        new Ruckusing_FrameworkRunner($dbConfig, array(), $this->_env, $this->_logger);
    }

    public function testConstructWithoutDbDatabase()
    {
        $this->setExpectedException(
            'Ruckusing_MissingConfigDbException', 
            'Error: "database" is not set for "test" DB'
        );
        $dbConfig = array(
            'test' => array(
                'type'     => 'mysql',
                'host'     => 'localhost',
                'port'     => 3306,
                //'database' => 'ruckusing_migrations_test',
                'user'     => 'rucku_test',
                'password' => 'rucku',
            ),
        );
        new Ruckusing_FrameworkRunner($dbConfig, array(), $this->_env, $this->_logger);
    }

    public function testConstructWithoutDbUser()
    {
        $this->setExpectedException(
            'Ruckusing_MissingConfigDbException', 
            'Error: "user" is not set for "test" DB'
        );
        $dbConfig = array(
            'test' => array(
                'type'     => 'mysql',
                'host'     => 'localhost',
                'database' => 'ruckusing_migrations_test',
                'port'     => 3306,
                //'user'     => 'rucku_test',
                'password' => 'rucku',
            ),
        );
        new Ruckusing_FrameworkRunner($dbConfig, array(), $this->_env, $this->_logger);
    }

    public function testConstructWithoutDbPassword()
    {
        $this->setExpectedException(
            'Ruckusing_MissingConfigDbException', 
            'Error: "password" is not set for "test" DB'
        );
        $dbConfig = array(
            'test' => array(
                'type'     => 'mysql',
                'host'     => 'localhost',
                'database' => 'ruckusing_migrations_test',
                'port'     => 3306,
                'user'     => 'rucku_test',
                //'password' => 'rucku',
            ),
        );
        new Ruckusing_FrameworkRunner($dbConfig, array(), $this->_env, $this->_logger);
    }

    public function testConstructWithWrongType()
    {
        $this->setExpectedException(
            'Ruckusing_InvalidAdapterTypeException', 
            'Adapter "mssql" not implemented!'
        );
        $dbConfig = array(
            'test' => array(
                'type'     => 'mssql',
                'host'     => 'localhost',
                'database' => 'ruckusing_migrations_test',
                'port'     => 3306,
                'user'     => 'rucku_test',
                'password' => 'rucku',
            ),
        );
        new Ruckusing_FrameworkRunner($dbConfig, array(), $this->_env, $this->_logger);
    }

    public function testConstructWithUnknownType()
    {
        $this->setExpectedException(
            'Ruckusing_InvalidAdapterTypeException', 
            'Adapter "unknown" not implemented!'
        );
        $dbConfig = array(
            'test' => array(
                'type'     => 'unknown',
                'host'     => 'localhost',
                'database' => 'ruckusing_migrations_test',
                'port'     => 3306,
                'user'     => 'rucku_test',
                'password' => 'rucku',
            ),
        );
        new Ruckusing_FrameworkRunner($dbConfig, array(), $this->_env, $this->_logger);
    }

    public function testConstructWithoutOptions()
    {
        $this->setExpectedException(
            'InvalidArgumentException', 
            'No task found!'
        );
        $dbConfig = array(
            'test' => array(
                'type'     => 'mysql',
                'host'     => 'localhost',
                'database' => 'ruckusing_migrations_test',
                'port'     => 3306,
                'user'     => 'rucku_test',
                'password' => 'rucku',
            ),
        );
        $obj = new Ruckusing_FrameworkRunner($dbConfig, array(), $this->_env, $this->_logger);
    }

    public function testConstructWithTaskNameWithoutNamespace()
    {
        $this->setExpectedException(
            'InvalidArgumentException', 
            'No task found!'
        );
        $dbConfig = array(
            'test' => array(
                'type'     => 'mysql',
                'host'     => 'localhost',
                'database' => 'ruckusing_migrations_test',
                'port'     => 3306,
                'user'     => 'rucku_test',
                'password' => 'rucku',
            ),
        );
        $args = array('migrate', 'VERSION=+1');
        $obj = new Ruckusing_FrameworkRunner($dbConfig, $args, $this->_env, $this->_logger);
    }

    public function testConstruct()
    {
        $dbConfig = array(
            'test' => array(
                'type'     => 'mysql',
                'host'     => 'localhost',
                'database' => 'ruckusing_migrations_test',
                'port'     => 3306,
                'user'     => 'rucku_test',
                'password' => 'rucku',
            ),
        );
        $args = array('main', 'ko:migrate', 'VERSION=+1');
        $obj = new Ruckusing_FrameworkRunner($dbConfig, $args, $this->_env, $this->_logger);
        $this->assertInstanceOf('Ruckusing_FrameworkRunner', $obj);
    }

    public function testConstructWithHelpOption()
    {
        $dbConfig = array(
            'test' => array(
                'type'     => 'mysql',
                'host'     => 'localhost',
                'database' => 'ruckusing_migrations_test',
                'port'     => 3306,
                'user'     => 'rucku_test',
                'password' => 'rucku',
            ),
        );
        $args = array('main', 'ko:migrate', 'help');
        $obj = new Ruckusing_FrameworkRunner($dbConfig, $args, $this->_env, $this->_logger);
        $this->assertInstanceOf('Ruckusing_FrameworkRunner', $obj);
    }

    public function testExecuteWithUnknownTaskName()
    {
        $this->setExpectedException(
            'Ruckusing_InvalidTaskException', 
            'Task not found: ko:version'
        );

        $dbConfig = array(
            'test' => array(
                'type'     => 'mysql',
                'host'     => 'localhost',
                'database' => 'ruckusing_migrations_test',
                'port'     => 3306,
                'user'     => 'rucku_test',
                'password' => 'rucku',
            ),
        );
        $args = array('main', 'ko:version');
        $framework = new Ruckusing_FrameworkRunner(
            $dbConfig, 
            $args, 
            $this->_env, 
            $this->_logger
        );
        $this->assertInstanceOf('Ruckusing_FrameworkRunner', $framework);
        $framework->execute();
    }

    public function testExecuteWithTaskName()
    {
        $dbConfig = array(
            'test' => array(
                'type'     => 'mysql',
                'host'     => 'localhost',
                'database' => 'ruckusing_migrations_test',
                'port'     => 3306,
                'user'     => 'rucku_test',
                'password' => 'rucku',
            ),
        );
        $args = array('main', 'db:version');
        $framework = new Ruckusing_FrameworkRunner(
            $dbConfig, 
            $args, 
            $this->_env, 
            $this->_logger
        );
        $this->assertInstanceOf('Ruckusing_FrameworkRunner', $framework);
        $framework->execute();
    }

    public function testExecuteWithHelpTaskName()
    {
        $dbConfig = array(
            'test' => array(
                'type'     => 'mysql',
                'host'     => 'localhost',
                'database' => 'ruckusing_migrations_test',
                'port'     => 3306,
                'user'     => 'rucku_test',
                'password' => 'rucku',
            ),
        );
        $args = array('main', 'db:version', 'help');
        $framework = new Ruckusing_FrameworkRunner(
            $dbConfig, 
            $args, 
            $this->_env, 
            $this->_logger
        );
        $this->assertInstanceOf('Ruckusing_FrameworkRunner', $framework);
        $expected =<<<USAGE
Task: \033[36mdb:version\033[0m

It is always possible to ask the framework (really the DB) what version it is 
currently at.

This task not take arguments.

USAGE;
        $this->assertEquals($expected, $framework->execute());
    }
}
