<?php

/**
 * Generated by PHPUnit_SkeletonGenerator on 2012-01-20 at 08:35:10.
 *
 * @group Task_Db
 */
class Task_Db_StatusTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Task_Db_Status
     */
    protected $object;

    /**
     * @var adapterMock
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
        $this->object = new Task_Db_Status($this->_adapter);
        $this->object->setDirectoryOfMigrations(
            FIXTURES_PATH . '/tasks/Db/migrate'
        );
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

    public function testExecuteWithoutVersionsExecuted()
    {
        $this->_adapter->setTableSchemaExist(true);
        $this->_adapter->versions = array();
        $regexp = '/^Started: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+'
            . '\[db:status\]:\012+'
            . '===================== NOT APPLIED =======================\012+'
            . '\tCreateUsers \[ 20120109064438 \]\012+'
            . '\tAddIndexToUsers \[ 20120110064438 \]\012+'
            . '\tCreateAddresses \[ 20120111064438 \]\012+'
            . '\tMissingMethodUp \[ 20120112064438 \]\012+'
            . '\012+Finished: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+$/';
        $actual = $this->object->execute(array());
        $this->assertNotEmpty($actual);
        $this->assertRegExp($regexp, $actual);
    }

    public function testExecuteWithOneVersionExecuted()
    {
        $this->_adapter->setTableSchemaExist(true);
        $this->_adapter->versions = array(
            array('version' => '20120109064438'),
        );
        $regexp = '/^Started: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+'
            . '\[db:status\]:\012+'
            . '===================== APPLIED =======================\012+'
            . '\tCreateUsers \[ 20120109064438 \]\012+'
            . '===================== NOT APPLIED =======================\012+'
            . '\tAddIndexToUsers \[ 20120110064438 \]\012+'
            . '\tCreateAddresses \[ 20120111064438 \]\012+'
            . '\tMissingMethodUp \[ 20120112064438 \]\012+'
            . '\012+Finished: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+$/';
        $actual = $this->object->execute(array());
        $this->assertNotEmpty($actual);
        $this->assertRegExp($regexp, $actual);
    }

    public function testExecuteWithAllVersionsExecuted()
    {
        $this->_adapter->setTableSchemaExist(true);
        $this->_adapter->versions = array(
            array('version' => '20120109064438'),
            array('version' => '20120110064438'),
            array('version' => '20120111064438'),
            array('version' => '20120112064438'),
        );
        $regexp = '/^Started: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+'
            . '\[db:status\]:\012+'
            . '===================== APPLIED =======================\012+'
            . '\tCreateUsers \[ 20120109064438 \]\012+'
            . '\tAddIndexToUsers \[ 20120110064438 \]\012+'
            . '\tCreateAddresses \[ 20120111064438 \]\012+'
            . '\tMissingMethodUp \[ 20120112064438 \]\012+'
            . '\012+Finished: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+$/';
        $actual = $this->object->execute(array());
        $this->assertNotEmpty($actual);
        $this->assertRegExp($regexp, $actual);
    }

    public function testHelp()
    {
        $expected =<<<USAGE
Task: \033[36mdb:status\033[0m

With this taks you'll get an overview of the already executed migrations and
which will be executed when running db:migrate.

This task not take arguments.

USAGE;
        $actual = $this->object->help();
        $this->assertEquals($expected, $actual);
    }
}
