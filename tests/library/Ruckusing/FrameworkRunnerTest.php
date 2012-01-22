<?php
/**
 * Generated by PHPUnit_SkeletonGenerator on 2012-01-18 at 13:10:26.
 *
 * @group Ruckusing
 */
class Ruckusing_FrameworkRunnerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Ruckusing_FrameworkRunner
     */
    protected $object;

    protected $_logger;

    protected $_existConfig = false;

    protected $_existConfigDb = false;

    public function __construct()
    {
        $this->_saveConfigFiles();
        $this->_logger = Ruckusing_Logger::instance(
            RUCKUSING_BASE . '/tests/logs/tests.log'
        );
    }

    public function __destruct()
    {
        $this->_restoreConfigFiles();
    }

    private function _saveConfigFiles()
    {
        $pathFileConfigDb = RUCKUSING_BASE . '/config/database.ini';
        if (file_exists($pathFileConfigDb)) {
            rename($pathFileConfigDb, $pathFileConfigDb.'-saveTest');
            $this->_existConfigDb = true;
        }
        $pathFileConfig = RUCKUSING_BASE . '/config/application.ini';
        if (file_exists($pathFileConfig)) {
            rename($pathFileConfig, $pathFileConfig.'-saveTest');
            $this->_existConfig = true;
        }
    }

    private function _restoreConfigFiles()
    {
        $pathFileConfigDb = RUCKUSING_BASE . '/config/database.ini';
        if ($this->_existConfigDb) {
            rename($pathFileConfigDb.'-saveTest', $pathFileConfigDb);
        }
        $pathFileConfig = RUCKUSING_BASE . '/config/application.ini';
        if ($this->_existConfig) {
            rename($pathFileConfig.'-saveTest', $pathFileConfig);
        }
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        //$this->object = new Ruckusing_FrameworkRunner;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        //$this->object = null;
    }

    public function testConstructorWithoutParameters()
    {
        $parameters = array();
        try {
            new Ruckusing_FrameworkRunner($parameters);
            $this->fail('Parameters is empty');
        } catch (Ruckusing_Exception_Argument $e) {
            $msg = 'No task found!';
            $this->assertEquals($msg, $e->getMessage());
        }
    }

    public function testConstructorWithParameterConfig()
    {
        $parameters = array(
            'monScript.php',
            '-d',
            RUCKUSING_BASE . '/tests/fixtures/config/database.ini',
            'db:version',
        );
        try {
            new Ruckusing_FrameworkRunner($parameters);
            $this->fail('No config file in parameters and the default config file does not exists');
        } catch (Ruckusing_Exception_Config $e) {
            $msg = 'Config file not found! Please, create config file for application';
            $this->assertEquals($msg, $e->getMessage());
        }
        $addParams = array(
            '-c',
            RUCKUSING_BASE . '/tests/fixtures/config/application.ini',
        );
        $parameters = array_merge($parameters, $addParams);
        $actual = new Ruckusing_FrameworkRunner($parameters);
        $this->assertInstanceOf('Ruckusing_FrameworkRunner', $actual);
        $config = $actual->getConfig();
        $this->assertInstanceOf('Ruckusing_Config_Ini', $config);
        $this->assertTrue(isset($config->task));
        $this->assertTrue(isset($config->task->dir));
        $this->assertEquals(RUCKUSING_BASE . '/library/Task', $config->task->dir);
        $parameters = array(
            'monScript.php',
            '--configuration',
            RUCKUSING_BASE . '/tests/fixtures/config/application.ini',
            '-d',
            RUCKUSING_BASE . '/tests/fixtures/config/database.ini',
            'db:version',
        );
        $actual = new Ruckusing_FrameworkRunner($parameters);
        $this->assertInstanceOf('Ruckusing_FrameworkRunner', $actual);
        $config = $actual->getConfig();
        $this->assertInstanceOf('Ruckusing_Config_Ini', $config);
        $this->assertTrue(isset($config->migration));
        $this->assertTrue(isset($config->migration->dir));
        $this->assertEquals(RUCKUSING_BASE . '/db/migrate', $config->migration->dir);
        $this->assertTrue(isset($config->debug));
        $this->assertEquals(1, $config->debug);
        $parameters = array(
            'monScript.php',
            '--configuration',
            RUCKUSING_BASE . '/tests/fixtures/config/application.ini',
            '-d',
            RUCKUSING_BASE . '/tests/fixtures/config/database.ini',
            'db:version',
            'ENV=test',
        );
        $actual = new Ruckusing_FrameworkRunner($parameters);
        $this->assertInstanceOf('Ruckusing_FrameworkRunner', $actual);
        $config = $actual->getConfig();
        $this->assertInstanceOf('Ruckusing_Config_Ini', $config);
        $this->assertTrue(isset($config->debug));
        $this->assertEquals(0, $config->debug);
    }

    public function testConstructorWithDefaultConfigFile()
    {
        $pathFile = RUCKUSING_BASE . '/config/application.ini';
        $pathFileFixture = RUCKUSING_BASE . '/tests/fixtures/config/application.ini';
        copy($pathFileFixture, $pathFile);

        $parameters = array(
            'monScript.php',
            'db:version',
            '-d',
            RUCKUSING_BASE . '/tests/fixtures/config/database.ini',
        );
        $actual = new Ruckusing_FrameworkRunner($parameters);
        $this->assertInstanceOf('Ruckusing_FrameworkRunner', $actual);
        $config = $actual->getConfig();
        $this->assertInstanceOf('Ruckusing_Config_Ini', $config);
        $this->assertTrue(isset($config->test));
        $this->assertEquals('default', $config->test->config);

        unlink($pathFile);
    }

    public function testConstructorWithParameterConfigDb()
    {
        $parameters = array(
            'monScript.php',
            '-c',
            RUCKUSING_BASE . '/tests/fixtures/config/application.ini',
            'db:version',
        );
        try {
            new Ruckusing_FrameworkRunner($parameters);
            $this->fail(
                'No config DB file in parameters and the default '
                . 'config DB file does not exists'
            );
        } catch (Ruckusing_Exception_Config $e) {
            $msg = 'Config file for DB not found! Please, create config file';
            $this->assertEquals($msg, $e->getMessage());
        }
        $addParams = array(
            '-d',
            RUCKUSING_BASE . '/tests/fixtures/config/databaseError.ini',
        );
        $parameters = array_merge($parameters, $addParams);
        $actual = new Ruckusing_FrameworkRunner($parameters);
        $this->assertInstanceOf('Ruckusing_FrameworkRunner', $actual);
        $config = $actual->getConfigDb();
        $this->assertInstanceOf('Ruckusing_Config_Ini', $config);
        $this->assertTrue(isset($config->type));
        $this->assertEquals('mysql', $config->type);
        $parameters = array(
            'monScript.php',
            '--database',
            RUCKUSING_BASE . '/tests/fixtures/config/databaseError.ini',
            '-c',
            RUCKUSING_BASE . '/tests/fixtures/config/application.ini',
            'db:version',
            'ENV=test',
        );
        try {
            new Ruckusing_FrameworkRunner($parameters);
            $this->fail(
                'Section test in database config file define '
                . 'adapter type SQLite not supported'
            );
        } catch(Ruckusing_Exception_InvalidAdapterType $e) {
            $msg = 'Adapter "sqlite" not implemented!';
            $this->assertEquals($msg, $e->getMessage());
        }
        $parameters = array(
            'monScript.php',
            '--database',
            RUCKUSING_BASE . '/tests/fixtures/config/databaseError.ini',
            '-c',
            RUCKUSING_BASE . '/tests/fixtures/config/application.ini',
            'db:version',
            'ENV=test2',
        );
        try {
            new Ruckusing_FrameworkRunner($parameters);
            $this->fail(
                'Section test2 in database config file does not define '
                . 'adapter type'
            );
        } catch(Ruckusing_Exception_MissingAdapterType $e) {
            $msg = 'Error: "type" is not set for "test2" DB in config file';
            $this->assertEquals($msg, $e->getMessage());
        }
    }

    public function testConstructorWithDefaultConfigDbFile()
    {
        $pathFile = RUCKUSING_BASE . '/config/database.ini';
        $pathFileFixture = RUCKUSING_BASE . '/tests/fixtures/config/database.ini';
        copy($pathFileFixture, $pathFile);

        $parameters = array(
            'monScript.php',
            'db:version',
            '-c',
            RUCKUSING_BASE . '/tests/fixtures/config/application.ini',
            'ENV=test',
        );
        $actual = new Ruckusing_FrameworkRunner($parameters);
        $this->assertInstanceOf('Ruckusing_FrameworkRunner', $actual);
        $config = $actual->getConfigDb();
        $this->assertInstanceOf('Ruckusing_Config_Ini', $config);
        $this->assertTrue(isset($config->test));
        $this->assertEquals('default', $config->test);

        unlink($pathFile);
    }

    public function testConstructorWithParameterTaskDir()
    {
        $parameters = array(
            'monScript.php',
            '-c',
            RUCKUSING_BASE . '/tests/fixtures/config/applicationWithoutTask.ini',
            '-d',
            RUCKUSING_BASE . '/tests/fixtures/config/database.ini',
            'db:version',
        );
        try {
            new Ruckusing_FrameworkRunner($parameters);
            $this->fail('No task dir in parameters and config file!');
        } catch (Ruckusing_Exception_MissingTaskDir $e) {
            $msg = 'Please, inform the variable "task.dir" '
                . 'in the configuration file';
            $this->assertEquals($msg, $e->getMessage());
        }
        $addParams = array(
            '-t',
            RUCKUSING_BASE . '/tests/fixtures/tasks',
        );
        $parameters = array_merge($parameters, $addParams);
        $actual = new Ruckusing_FrameworkRunner($parameters);
        $this->assertInstanceOf('Ruckusing_FrameworkRunner', $actual);
        $this->assertEquals(RUCKUSING_BASE . '/tests/fixtures/tasks', $actual->getTaskDir());
        $parameters[2] = RUCKUSING_BASE . '/tests/fixtures/config/application.ini';
        array_splice($parameters, 6);
        $actual = new Ruckusing_FrameworkRunner($parameters);
        $this->assertInstanceOf('Ruckusing_FrameworkRunner', $actual);
        $this->assertEquals(RUCKUSING_BASE . '/library/Task', $actual->getTaskDir());
        $addParams = array(
            '--taskdir',
            RUCKUSING_BASE . '/tests/fixtures/tasks',
        );
        $parameters = array_merge($parameters, $addParams);
        $actual = new Ruckusing_FrameworkRunner($parameters);
        $this->assertInstanceOf('Ruckusing_FrameworkRunner', $actual);
        $this->assertEquals(RUCKUSING_BASE . '/tests/fixtures/tasks', $actual->getTaskDir());
    }

    public function testConstructorWithParameterMigrationDir()
    {
        $parameters = array(
            'monScript.php',
            '-c',
            RUCKUSING_BASE . '/tests/fixtures/config/applicationWithoutMigration.ini',
            '-d',
            RUCKUSING_BASE . '/tests/fixtures/config/database.ini',
            'db:version',
        );
        try {
            new Ruckusing_FrameworkRunner($parameters);
            $this->fail('No migration dir in parameters and config file!');
        } catch (Ruckusing_Exception_MissingMigrationDir $e) {
            $msg = 'Please, inform the variable "migration.dir" '
                . 'in the configuration file';
            $this->assertEquals($msg, $e->getMessage());
        }
        $addParams = array(
            '-m',
            RUCKUSING_BASE . '/tests/dummy/db/migrate',
        );
        $parameters = array_merge($parameters, $addParams);
        $actual = new Ruckusing_FrameworkRunner($parameters);
        $this->assertInstanceOf('Ruckusing_FrameworkRunner', $actual);
        $this->assertEquals(RUCKUSING_BASE . '/tests/dummy/db/migrate', $actual->getMigrationDir());
        $parameters[2] = RUCKUSING_BASE . '/tests/fixtures/config/application.ini';
        array_splice($parameters, 6);
        $actual = new Ruckusing_FrameworkRunner($parameters);
        $this->assertInstanceOf('Ruckusing_FrameworkRunner', $actual);
        $this->assertEquals(RUCKUSING_BASE . '/db/migrate', $actual->getMigrationDir());
        $addParams = array(
            '--migrationdir',
            RUCKUSING_BASE . '/tests/dummy/db/migrate',
        );
        $parameters = array_merge($parameters, $addParams);
        $actual = new Ruckusing_FrameworkRunner($parameters);
        $this->assertInstanceOf('Ruckusing_FrameworkRunner', $actual);
        $this->assertEquals(RUCKUSING_BASE . '/tests/dummy/db/migrate', $actual->getMigrationDir());
    }

    public function testConstructorWithoutTaskName()
    {
        $parameters = array(
            'monScript.php',
            '-d',
            RUCKUSING_BASE . '/tests/fixtures/config/database.ini',
            '-c',
            RUCKUSING_BASE . '/tests/fixtures/config/application.ini',
        );
        try {
            new Ruckusing_FrameworkRunner($parameters);
            $this->fail('Parameters is empty');
        } catch (Ruckusing_Exception_Argument $e) {
            $msg = 'No task found!';
            $this->assertEquals($msg, $e->getMessage());
        }
        $parameters[] = 'db:version';
        $actual = new Ruckusing_FrameworkRunner($parameters);
        $this->assertInstanceOf('Ruckusing_FrameworkRunner', $actual);
    }

    public function testConstructorWithHelpTaskParameter()
    {
        $parameters = array(
            'monScript.php',
            '-d',
            RUCKUSING_BASE . '/tests/fixtures/config/database.ini',
            '-c',
            RUCKUSING_BASE . '/tests/fixtures/config/application.ini',
            'help',
        );
        try {
            new Ruckusing_FrameworkRunner($parameters);
            $this->fail('Parameters is empty');
        } catch (Ruckusing_Exception_Argument $e) {
            $msg = 'No task found!';
            $this->assertEquals($msg, $e->getMessage());
        }
        $parameters[] = 'db:version';
        $actual = new Ruckusing_FrameworkRunner($parameters);
        $this->assertInstanceOf('Ruckusing_FrameworkRunner', $actual);
        $expected =<<<USAGE
Task: \033[36mdb:version\033[0m

It is always possible to ask the framework (really the DB) what version it is
currently at.

This task not take arguments.

USAGE;
        $help = $actual->execute();
        $this->assertNotEmpty($help);
        $this->assertEquals($expected, $help);
    }

    public function testGetLogger()
    {
        $parameters = array(
            'monScript.php',
            '-d',
            RUCKUSING_BASE . '/tests/fixtures/config/database.ini',
            '-c',
            RUCKUSING_BASE . '/tests/fixtures/config/application.ini',
            'ENV=test',
            'db:version',
        );
        $actual = new Ruckusing_FrameworkRunner($parameters);
        $this->assertInstanceOf('Ruckusing_FrameworkRunner', $actual);
        $this->assertInstanceOf('Ruckusing_Logger', $actual->getLogger());
        $this->assertEquals(Ruckusing_Logger::DEBUG, $actual->getLogger()->getPriority());

        $parameters = array(
            'monScript.php',
            '-d',
            RUCKUSING_BASE . '/tests/fixtures/config/database.ini',
            '-c',
            RUCKUSING_BASE . '/tests/fixtures/config/application.ini',
            'ENV=test3',
            'db:version',
        );
        try {
            new Ruckusing_FrameworkRunner($parameters);
            $this->fail('The variable log directory, in config file, is not writable');
        } catch (Ruckusing_Exception_InvalidLog $e) {
            $msg = 'Cannot write to log directory: /usr/bin. Check permissions.';
            $this->assertEquals($msg, $e->getMessage());
        }
        $parameters = array(
            'monScript.php',
            '-d',
            RUCKUSING_BASE . '/tests/fixtures/config/database.ini',
            '-c',
            RUCKUSING_BASE . '/tests/fixtures/config/application.ini',
            'ENV=test4',
            'db:version',
        );
        try {
            new Ruckusing_FrameworkRunner($parameters);
            $this->fail('The variable log directory, in config file, does not exists');
        } catch (Ruckusing_Exception_InvalidLog $e) {
            $msg = '/tmp/ruckusing/logs does not exists.';
            $this->assertEquals($msg, $e->getMessage());
        }
        $parameters = array(
            'monScript.php',
            '-d',
            RUCKUSING_BASE . '/tests/fixtures/config/database.ini',
            '-c',
            RUCKUSING_BASE . '/tests/fixtures/config/application.ini',
            'ENV=production',
            'db:version',
        );
        $actual = new Ruckusing_FrameworkRunner($parameters);
        $this->assertInstanceOf('Ruckusing_FrameworkRunner', $actual);
        $this->assertInstanceOf('Ruckusing_Logger', $actual->getLogger());
        $this->assertEquals(Ruckusing_Logger::INFO, $actual->getLogger()->getPriority());
    }

    /**
     */
    public function testExecuteWithUnknownTask()
    {
        $parameters = array(
            'monScript.php',
            '-d',
            RUCKUSING_BASE . '/tests/fixtures/config/database.ini',
            '-c',
            RUCKUSING_BASE . '/tests/fixtures/config/application.ini',
            'db:unknown',
        );
        $actual = new Ruckusing_FrameworkRunner($parameters);
        try {
            $actual->execute();
            $this->fail('The task Parameters is unknown');
        } catch (Ruckusing_Exception_InvalidTask $e) {
            $msg = 'Task not found: db:unknown';
            $this->assertEquals($msg, $e->getMessage());
        }
    }
    
    public function testExecuteWithVersionTaskWithTableSchema()
    {
        $parameters = array(
            'monScript.php',
            '-d',
            RUCKUSING_BASE . '/tests/fixtures/config/database.ini',
            '-c',
            RUCKUSING_BASE . '/tests/fixtures/config/application.ini',
            'db:version',
        );
        $actual = new Ruckusing_FrameworkRunner($parameters);
        $adapter = new adapterTaskMock(array(), '');
        $adapter->setTableSchemaExist(true);
        $adapter->versions = array(array('version' => '20120110064438'));
        $actual->setAdapter($adapter);
        $task = $actual->execute();
        $regexp = '/^Started: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3}\012+'
        . '\[db:version\]:\012+\t+Current version: \d+\012+Finished: '
        . '\d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3}\012+$/';
        $this->assertNotEmpty($task);
        $this->assertRegExp($regexp, $task);
    }

    public function testExecuteWithVersionTaskWithoutTableSchema()
    {
        $parameters = array(
            'monScript.php',
            '-d',
            RUCKUSING_BASE . '/tests/fixtures/config/database.ini',
            '-c',
            RUCKUSING_BASE . '/tests/fixtures/config/application.ini',
            'db:version',
        );
        $actual = new Ruckusing_FrameworkRunner($parameters);
        $adapter = new adapterTaskMock(array(), '');
        $adapter->setTableSchemaExist(false);
        $actual->setAdapter($adapter);
        $task = $actual->execute(array());
        $regexp = '/^Started: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3}\012+'
            . '\[db:version\]:\012+\t+Schema version table \(schema_migrations\) '
            . "does not exist\. Do you need to run 'db:setup'\?"
            . '\012+Finished: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3}\012+$/';
        $this->assertNotEmpty($task);
        $this->assertRegExp($regexp, $task);
    }

    public function testParseArgumentConfigurationWithoutConfigFile()
    {
        $parameters = array(
            'monScript.php',
            '-d',
            RUCKUSING_BASE . '/tests/fixtures/config/database.ini',
            'db:version',
            '-c',
        );
        try {
            new Ruckusing_FrameworkRunner($parameters);
            $this->fail('The configuration file is not informed');
        } catch (Ruckusing_Exception_Argument $e) {
            $msg = 'Please, specify the configuration file if you use the '
                . 'argument -c or --configuration';
            $this->assertEquals($msg, $e->getMessage());
        }
    }
    
    public function testParseArgumentConfigurationDbWithoutConfigDbFile()
    {
        $parameters = array(
            'monScript.php',
            'db:version',
            '-c',
            RUCKUSING_BASE . '/tests/fixtures/config/application.ini',
            '-d',
        );
        try {
            new Ruckusing_FrameworkRunner($parameters);
            $this->fail('The configuration database file is not informed');
        } catch (Ruckusing_Exception_Argument $e) {
            $msg = 'Please, specify the configuration database file if you '
                . 'use the argument -d or --database';
            $this->assertEquals($msg, $e->getMessage());
        }
    }
    
    public function testParseArgumentMigrationWithoutDirectory()
    {
        $parameters = array(
            'monScript.php',
            '-c',
            RUCKUSING_BASE . '/tests/fixtures/config/application.ini',
            '-d',
            RUCKUSING_BASE . '/tests/fixtures/config/database.ini',
            'db:version',
            '-m',
        );
        try {
            new Ruckusing_FrameworkRunner($parameters);
            $this->fail('The directory of migration files is not informed');
        } catch (Ruckusing_Exception_Argument $e) {
            $msg = 'Please, specify the directory of migration files if you '
                . 'use the argument -m or --migrationdir';
            $this->assertEquals($msg, $e->getMessage());
        }
    }
    
    public function testParseArgumentTasksWithoutDirectory()
    {
        $parameters = array(
            'monScript.php',
            '-c',
            RUCKUSING_BASE . '/tests/fixtures/config/application.ini',
            '-d',
            RUCKUSING_BASE . '/tests/fixtures/config/database.ini',
            'db:version',
            '-t',
        );
        try {
            new Ruckusing_FrameworkRunner($parameters);
            $this->fail('The directory of tasks files is not informed');
        } catch (Ruckusing_Exception_Argument $e) {
            $msg = 'Please, specify the directory of tasks if you '
                . 'use the argument -t or --taskdir';
            $this->assertEquals($msg, $e->getMessage());
        }
    }
    
    /**
     * @todo   Implement testUpdateSchemaForTimestamps().
     */
    public function testUpdateSchemaForTimestamps()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
}
