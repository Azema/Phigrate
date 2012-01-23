<?php
/**
 * Generated by PHPUnit_SkeletonGenerator on 2012-01-20 at 08:35:31.
 *
 * @group Task_Db
 */
class Task_Db_VersionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Task_Db_Version
     */
    protected $object;

    /**
     * @var adapterTaskMock
     */
    protected $_adapter;

    public function __construct()
    {
        $this->_adapter = new adapterTaskMock(array(), '');
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->object = new Task_Db_Version($this->_adapter);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        $this->object = null;
        parent::tearDown();
    }

    public function testExecuteWithoutTableSchema()
    {
        $this->_adapter->setTableSchemaExist(false);
        $actual = $this->object->execute(array());
        $regexp = '/^Started: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3}\012+'
            . '\[db:version\]:\012+\t+Schema version table \(schema_migrations\) '
            . "does not exist\. Do you need to run 'db:setup'\?"
            . '\012+Finished: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3}\012+$/';
        $this->assertNotEmpty($actual);
        $this->assertRegExp($regexp, $actual);
    }

    public function testExecuteWithTableSchemaWihtoutVersions()
    {
        $this->_adapter->setTableSchemaExist(true);
        $actual = $this->object->execute(array());
        $regexp = '/^Started: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3}\012+'
            . '\[db:version\]:\012+\t+No migrations have been executed\.'
            . '\012+Finished: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3}\012+$/';
        $this->assertNotEmpty($actual);
        $this->assertRegExp($regexp, $actual);
    }

    public function testExecuteWithTableSchemaWihtOneVersion()
    {
        $this->_adapter->setTableSchemaExist(true);
        $this->_adapter->versions = array(array('version' => '20120110064438'));
        $actual = $this->object->execute(array());
        $regexp = '/^Started: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3}\012+'
            . '\[db:version\]:\012+\t+Current version: 20120110064438'
            . '\012+Finished: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3}\012+$/';
        $this->assertNotEmpty($actual);
        $this->assertRegExp($regexp, $actual);
    }

    public function testExecuteWithTableSchemaWihtManyVersions()
    {
        $this->_adapter->setTableSchemaExist(true);
        $this->_adapter->versions = array(
            array('version' => '20120124064438'),
            array('version' => '20120112064438'),
            array('version' => '20120110064438'),
        );
        $actual = $this->object->execute(array());
        $regexp = '/^Started: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3}\012+'
            . '\[db:version\]:\012+\t+Current version: 20120124064438'
            . '\012+Finished: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3}\012+$/';
        $this->assertNotEmpty($actual);
        $this->assertRegExp($regexp, $actual);
    }

    public function testHelp()
    {
        $expected =<<<USAGE
Task: \033[36mdb:version\033[0m

It is always possible to ask the framework (really the DB) what version it is
currently at.

This task not take arguments.

USAGE;
        $actual = $this->object->help();
        $this->assertEquals($expected, $actual);
    }
}