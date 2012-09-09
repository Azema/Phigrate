<?php
/**
 * Generated by PHPUnit_SkeletonGenerator on 2012-01-20 at 08:33:48.
 *
 * @group Task_Db
 */
class Task_Db_ExportTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Task_Db_Export
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
        $this->object = new Task_Db_Export($this->_adapter);
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

    public function testExecuteWithAdapterNoSupportMigration()
    {
        $this->_adapter->supportMigration = false;
        try {
            $this->object->execute(array());
            $this->fail('Adapter not support migration!');
        } catch (Ruckusing_Exception_Task $e) {
            $msg = 'This database does not support migrations.';
            $this->assertEquals($msg, $e->getMessage());
        }
    }

    public function testExecuteWithoutSchemaTable()
    {
        $this->_adapter->setTableSchemaExist(false);
        $actual = $this->object->execute(array());
        $regexp = '/^--\012+--\tExport SQL by Ruckusing\012+'
            . '--\012+'
            . '-- Started: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+'
            . '-- \[db:export\]:\012+\tSchema version table does not exist\.\012+'
            . '-- Finished: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+$/';
        $this->assertNotEmpty($actual);
        $this->assertRegExp($regexp, $actual);
    }

    public function testExecuteWithDownOneOffsetButNothingVersionsInTableSchema()
    {
        $this->_adapter->setTableSchemaExist(true);
        $this->_adapter->versions = array();
        $actual = $this->object->execute(array('VERSION'=>'-1'));
        $regexp = '/^--\012+--\tExport SQL by Ruckusing\012+--\012+'
            . '-- Started: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+'
            . '-- \[db:export\]:\012+--\tMigrating DOWN to: 20120112064438\012+'
            . '--\012+-- No relevant migrations to run\. Exiting\.\.\.\012+'
            . '-- Finished: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+$/';
        $this->assertNotEmpty($actual);
        $this->assertRegExp($regexp, $actual);
    }

    public function testExecuteWithDownOneOffset()
    {
        $this->_adapter->setTableSchemaExist(true);
        $versions = array(
            array('version' => '20120109064438'),
            array('version' => '20120110064438'),
            array('version' => '20120111064438'),
        );
        $this->_adapter->versions = $versions;
        $actual = $this->object->execute(array('VERSION'=>'-1'));
        $regexp = '/^--\012+--\tExport SQL by Ruckusing\012+--\012+'
            . '-- Started: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+'
            . '-- \[db:export\]:\012+--\tMigrating DOWN to: 20120110064438\012+'
            . '-- ========= CreateAddresses ======== \(\d+.\d{2}\)\012+'
            . 'DROP INDEX `idx_addresses_user_id` ON `addresses`;\012+'
            . 'DROP TABLE IF EXISTS `addresses`;\012+'
            . '-- Finished: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+$/';
        $this->assertNotEmpty($actual);
        $this->assertRegExp($regexp, $actual);
        array_pop($versions);
        $this->assertEquals($versions, $this->_adapter->versions);
    }

    public function testExecuteWithUpOneOffsetAndNothingInSchemaTable()
    {
        $this->_adapter->setTableSchemaExist(true);
        $this->_adapter->versions = array();
        $actual = $this->object->execute(array('VERSION'=>'+1'));
        $regexp = '/^--\012+--\tExport SQL by Ruckusing\012+--\012+'
            . '-- Started: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+'
            . '-- \[db:export\]:\012+--\tMigrating UP to: 20120109064438\012+'
            . '-- ========= CreateUsers ======== \(\d+.\d{2}\)\012+'
            . 'CREATE TABLE `users` \(\012+`id` int\(11\) UNSIGNED auto_increment NOT NULL,\012'
            . '`name` text NULL DEFAULT NULL,\012+ PRIMARY KEY \(`id`\)\);\012+'
            . '-- Finished: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+$/';
        $this->assertNotEmpty($actual);
        $this->assertRegExp($regexp, $actual);
        $expected = array(
            array('version' => '20120109064438'),
        );
        $this->assertEquals($expected, $this->_adapter->versions);
    }

    public function testExecuteWithUpOneOffsetAndOneVersionInSchemaTable()
    {
        $this->_adapter->setTableSchemaExist(true);
        $versions = array(
            array('version' => '20120109064438'),
        );
        $this->_adapter->versions = $versions;
        $actual = $this->object->execute(array('VERSION'=>'+1'));
        $regexp = '/^--\012+--\tExport SQL by Ruckusing\012+--\012+'
            . '-- Started: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+'
            . '-- \[db:export\]:\012+--\tMigrating UP to: 20120110064438\012+'
            . '-- ========= AddIndexToUsers ======== \(\d+.\d{2}\)\012+'
            . 'CREATE INDEX idx_users_name ON `users`\(`name`\);\012+'
            . '-- Finished: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+$/';
        $this->assertNotEmpty($actual);
        $this->assertRegExp($regexp, $actual);
        $versions[] = array('version' => '20120110064438');
        $this->assertEquals($versions, $this->_adapter->versions);
    }

    public function testExecuteWithUpOneOffsetAndFullVersionsInSchemaTable()
    {
        $this->_adapter->setTableSchemaExist(true);
        $versions = array(
            array('version' => '20120109064438'),
            array('version' => '20120110064438'),
            array('version' => '20120111064438'),
            array('version' => '20120112064438'),
        );
        $this->_adapter->versions = $versions;
        $offset = '1';
        $actual = $this->object->execute(array('VERSION' => '+' . $offset));
        $regexp = '/^--\012+--\tExport SQL by Ruckusing\012+--\012+'
            . '-- Started: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+'
            . '-- \[db:export\]:\012+'
            . '--\tCannot export UP via offset "\+'.$offset.'": not enough migrations '
            . 'exist to execute\.\012+'
            . '--\tYou asked for \('.$offset.'\) but only available are \(0\): \012+'
            . '-- Finished: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+$/';
        $this->assertNotEmpty($actual);
        $this->assertRegExp($regexp, $actual);
        $this->assertEquals($versions, $this->_adapter->versions);
    }

    public function testExecuteWithUpFiveOffsetButTwoMigrationsAvailable()
    {
        $this->_adapter->setTableSchemaExist(true);
        $versions = array(
            array('version' => '20120109064438'),
        );
        $this->_adapter->versions = $versions;
        $offset = '5';
        $actual = $this->object->execute(array('VERSION' => '+' . $offset));
        $regexp = '/^--\012+--\tExport SQL by Ruckusing\012+--\012+'
            . '-- Started: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+'
            . '-- \[db:export\]:\012+'
            . '--\tCannot export UP via offset "\+'.$offset.'": not enough migrations '
            . 'exist to execute\.\012+'
            . '--\tYou asked for \('.$offset.'\) but only available are \(3\): '
            . '20120110064438_AddIndexToUsers\.php, 20120111064438_CreateAddresses\.php'
            . ', 20120112064438_MissingMethodUp\.php\012+'
            . '-- Finished: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+$/';
        $this->assertNotEmpty($actual);
        $this->assertRegExp($regexp, $actual);
        $this->assertEquals($versions, $this->_adapter->versions);
    }

    public function testExecuteWithNextVersion()
    {
        $this->_adapter->setTableSchemaExist(true);
        $versions = array(
            array('version' => '20120109064438'),
        );
        $this->_adapter->versions = $versions;
        $actual = $this->object->execute(array('VERSION' => '20120110064438'));
        $regexp = '/^--\012+--\tExport SQL by Ruckusing\012+--\012+'
            . '-- Started: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+'
            . '-- \[db:export\]:\012+--\tMigrating UP to: 20120110064438\012+'
            . '-- ========= AddIndexToUsers ======== \(\d+.\d{2}\)\012+'
            . 'CREATE INDEX idx_users_name ON `users`\(`name`\);\012+'
            . '-- Finished: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+$/';
        $this->assertNotEmpty($actual);
        $this->assertRegExp($regexp, $actual);
        $versions[] = array('version' => '20120110064438');
        $this->assertEquals($versions, $this->_adapter->versions);
    }

    public function testExecuteWithPreviousVersion()
    {
        $this->_adapter->setTableSchemaExist(true);
        $versions = array(
            array('version' => '20120109064438'),
            array('version' => '20120110064438'),
            array('version' => '20120111064438'),
        );
        $this->_adapter->versions = $versions;
        $actual = $this->object->execute(array('VERSION' => '20120110064438'));
        $regexp = '/^--\012+--\tExport SQL by Ruckusing\012+--\012+'
            . '-- Started: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+'
            . '-- \[db:export\]:\012+--\tMigrating DOWN to: 20120110064438\012+'
            . '-- ========= CreateAddresses ======== \(\d+.\d{2}\)\012+'
            . 'DROP INDEX `idx_addresses_user_id` ON `addresses`;\012+'
            . 'DROP TABLE IF EXISTS `addresses`;\012+'
            . '-- Finished: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+$/';
        $this->assertNotEmpty($actual);
        $this->assertRegExp($regexp, $actual);
        array_pop($versions);
        $this->assertEquals($versions, $this->_adapter->versions);
    }

    public function testExecuteWithWrongDirectoryOfMigrations()
    {
        $this->_adapter->setTableSchemaExist(true);
        $this->object->setDirectoryOfMigrations('/tmp/migrate');
        $versions = array(
            array('version' => '20120109064438'),
            array('version' => '20120110064438'),
            array('version' => '20120111064438'),
        );
        $this->_adapter->versions = $versions;
        $actual = $this->object->execute(array('VERSION' => '20120110064438'));
        $regexp = '/^--\012+--\tExport SQL by Ruckusing\012+--\012+'
            . '-- Started: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+'
            . '-- \[db:export\]:\012+--\tMigrating DOWN to: 20120110064438\012+'
            . 'Ruckusing_Util_Migrator - \(\/tmp\/migrate\) is not a directory\.\012+'
            . '-- Finished: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+$/';
        $this->assertNotEmpty($actual);
        $this->assertRegExp($regexp, $actual);
        $this->assertEquals($versions, $this->_adapter->versions);
    }

    public function testExecuteWithEmptyDirectoryOfMigrations()
    {
        $this->_adapter->setTableSchemaExist(true);
        $this->object->setDirectoryOfMigrations('');
        $versions = array(
            array('version' => '20120109064438'),
            array('version' => '20120110064438'),
            array('version' => '20120111064438'),
        );
        $this->_adapter->versions = $versions;
        $actual = $this->object->execute(array('VERSION' => '20120110064438'));
        $regexp = '/^--\012+--\tExport SQL by Ruckusing\012+--\012+'
            . '-- Started: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+'
            . '-- \[db:export\]:\012+--\tMigrating DOWN to: 20120110064438\012+'
            . 'Ruckusing_Util_Migrator - \(\) is not a directory\.\012+'
            . '-- Finished: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+$/';
        $this->assertNotEmpty($actual);
        $this->assertRegExp($regexp, $actual);
        $this->assertEquals($versions, $this->_adapter->versions);
    }

    public function testExecuteWithNextVersionMissingMethodUp()
    {
        $this->_adapter->setTableSchemaExist(true);
        $versions = array(
            array('version' => '20120109064438'),
            array('version' => '20120110064438'),
            array('version' => '20120111064438'),
        );
        $this->_adapter->versions = $versions;
        $actual = $this->object->execute(array());
        $regexp = '/^--\012+--\tExport SQL by Ruckusing\012+--\012+'
            . '-- Started: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+'
            . '-- \[db:export\]:\012+--\tMigrating UP:\012+'
            . 'MissingMethodUp does not have \(up\) method defined\!\012+'
            . '-- Finished: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+$/';
        $this->assertNotEmpty($actual);
        $this->assertRegExp($regexp, $actual);
        $this->assertEquals($versions, $this->_adapter->versions);
    }

    public function testExecuteWithPreviousVersionException()
    {
        $this->_adapter->setTableSchemaExist(true);
        $versions = array(
            array('version' => '20120109064438'),
            array('version' => '20120110064438'),
            array('version' => '20120111064438'),
            array('version' => '20120112064438'),
        );
        $this->_adapter->versions = $versions;
        $actual = $this->object->execute(array('VERSION' => '20120111064438'));
        $regexp = '/^--\012+--\tExport SQL by Ruckusing\012+--\012+'
            . '-- Started: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+'
            . '-- \[db:export\]:\012+--\tMigrating DOWN to: 20120111064438\012+'
            . 'MissingMethodUp - Query for selectOne\(\) is not one of SELECT'
            . ' or SHOW: UPDATE `users` SET `login` `login` VARCHAR\(20\);\012+'
            . '-- Finished: \d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(am|pm) \w{3,4}\012+$/';
        $this->assertNotEmpty($actual);
        $this->assertRegExp($regexp, $actual);
        $this->assertEquals($versions, $this->_adapter->versions);
    }

    public function testHelp()
    {
        $expected =<<<USAGE
Task: \033[36mdb:export\033[0m [\033[33mVERSION\033[0m]

The primary purpose of the framework is to run migrations, and the
execution of migrations is all handled by just a regular ol' task.

\t\033[33mVERSION\033[0m can be specified to go up (or down) to a specific
\tversion, based on the current version. If not specified,
\tall migrations greater than the current database version
\twill be executed.

\t\033[37mExample A:\033[0m The database is fresh and empty, assuming there
\tare 5 actual migrations, but only the first two should be run.

\t\t\033[35mphp ruckusing db:export VERSION=20101006114707\033[0m

\t\033[37mExample B:\033[0m The current version of the DB is 20101006114707
\tand we want to go down to 20100921114643

\t\t\033[35mphp ruckusing db:export VERSION=20100921114643\033[0m

\t\033[37mExample C:\033[0m You can also use relative number of revisions
\t(positive export up, negative export down).

\t\t\033[35mphp ruckusing db:export VERSION=-2\033[0m

USAGE;
        $actual = $this->object->help();
        $this->assertEquals($expected, $actual);
    }
}
