<?php

/**
 * Generated by PHPUnit_SkeletonGenerator on 2012-01-11 at 08:15:35.
 *
 * @group Ruckusing_Adapter
 * @group Ruckusing_Adapter_Mysql
 */
class Ruckusing_Adapter_Mysql_TableDefinitionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Ruckusing_Adapter_Mysql_TableDefinition
     */
    protected $object;

    public function __construct()
    {
        $this->_adapter = new adapterMock(array(), '');
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
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

    public function testConstructor()
    {
        try {
            new Ruckusing_Adapter_Mysql_TableDefinition('string', 'test');
            $this->fail(
                'Constructor Mysql TableDefinition '
                . 'require adapter Ruckusing_Adapter_Base'
            );
        } catch (Ruckusing_Exception_MissingAdapter $e) {
            $msg = 'Invalid MySQL Adapter instance.';
            $this->assertEquals($msg, $e->getMessage());
        }
        try {
            new Ruckusing_Adapter_Mysql_TableDefinition($this->_adapter, '');
            $this->fail(
                'Constructor Mysql TableDefinition does not accept empty string'
            );
        } catch (Ruckusing_Exception_Argument $e) {
            $msg = 'Invalid \'name\' parameter';
            $this->assertEquals($msg, $e->getMessage());
        }
        $options = array(
            'id' => false,
        );
        $table = new Ruckusing_Adapter_Mysql_TableDefinition(
            $this->_adapter,
            'users',
            $options
        );
        $sql = $table->finish(true);
        $expected = "CREATE TABLE `users` (
) ;";
        $this->assertEquals($expected, $sql);
        $table = new Ruckusing_Adapter_Mysql_TableDefinition(
            $this->_adapter,
            'users'
        );
        $sql = $table->finish(true);
        $expected = "CREATE TABLE `users` (
`id` int(11) UNSIGNED auto_increment NOT NULL,
,
 PRIMARY KEY (`id`)) ;";
        $this->assertEquals($expected, $sql);
        $options = array(
            'id' => 'my_id',
        );
        $table = new Ruckusing_Adapter_Mysql_TableDefinition(
            $this->_adapter,
            'users',
            $options
        );
        $sql = $table->finish(true);
        $expected = "CREATE TABLE `users` (
`my_id` int(11) UNSIGNED auto_increment NOT NULL,
 PRIMARY KEY (`my_id`)) ;";
        $this->assertEquals($expected, $sql);
        $options = array(
            'options' => 'CHARSET utf8',
        );
        $table = new Ruckusing_Adapter_Mysql_TableDefinition(
            $this->_adapter,
            'users',
            $options
        );
        $sql = $table->finish(true);
        $expected = "CREATE TABLE `users` (
`id` int(11) UNSIGNED auto_increment NOT NULL,
,
 PRIMARY KEY (`id`)) CHARSET utf8;";
        $this->assertEquals($expected, $sql);
        $options = array(
            'force' => true,
        );
        $table = new Ruckusing_Adapter_Mysql_TableDefinition(
            $this->_adapter,
            'users',
            $options
        );
        $sql = $table->finish(true);
        $expected = "CREATE TABLE `users` (
`id` int(11) UNSIGNED auto_increment NOT NULL,
,
 PRIMARY KEY (`id`)) ;";
        $this->assertEquals($expected, $sql);
        $options = array(
            'temporary' => true,
        );
        $table = new Ruckusing_Adapter_Mysql_TableDefinition(
            $this->_adapter,
            'users',
            $options
        );
        $sql = $table->finish(true);
        $expected = "CREATE TEMPORARY TABLE `users` (
`id` int(11) UNSIGNED auto_increment NOT NULL,
,
 PRIMARY KEY (`id`)) ;";
        $this->assertEquals($expected, $sql);
    }

    public function testColumn()
    {
        $table = new Ruckusing_Adapter_Mysql_TableDefinition(
            $this->_adapter,
            'users'
        );
        $table->column('test', 'integer');
        $sql = $table->finish(true);
        $expected = "CREATE TABLE `users` (
`id` int(11) UNSIGNED auto_increment NOT NULL,
`test` int(11),
 PRIMARY KEY (`id`)) ;";
        $this->assertEquals($expected, $sql);
        $table = new Ruckusing_Adapter_Mysql_TableDefinition(
            $this->_adapter,
            'users'
        );
        $table->column('test', 'integer', array('primary_key' => true));
        $sql = $table->finish(true);
        $expected = "CREATE TABLE `users` (
`id` int(11) UNSIGNED auto_increment NOT NULL,
`test` int(11),
 PRIMARY KEY (`test`,`id`)) ;";
        $this->assertEquals($expected, $sql);
        $table = new Ruckusing_Adapter_Mysql_TableDefinition(
            $this->_adapter,
            'users'
        );
        $table->column('test', 'integer', array('auto_increment' => true));
        $sql = $table->finish(true);
        $expected = "CREATE TABLE `users` (
`id` int(11) UNSIGNED auto_increment NOT NULL,
`test` int(11) auto_increment,
 PRIMARY KEY (`id`)) ;";
        $this->assertEquals($expected, $sql);
        $table = new Ruckusing_Adapter_Mysql_TableDefinition(
            $this->_adapter,
            'users'
        );
        $table->column('test', 'string', array('auto_increment' => true));
        $sql = $table->finish(true);
        $expected = "CREATE TABLE `users` (
`id` int(11) UNSIGNED auto_increment NOT NULL,
`test` varchar(255) auto_increment,
 PRIMARY KEY (`id`)) ;";
        $this->assertEquals($expected, $sql);
        $table = new Ruckusing_Adapter_Mysql_TableDefinition(
            $this->_adapter,
            'users'
        );
        $table->column('test', 'string');
        $table->column('test', 'string');
        $sql = $table->finish(true);
        $expected = "CREATE TABLE `users` (
`id` int(11) UNSIGNED auto_increment NOT NULL,
`test` varchar(255),
 PRIMARY KEY (`id`)) ;";
        $this->assertEquals($expected, $sql);
    }

    /**
     */
    public function testFinish()
    {
        $table = new Ruckusing_Adapter_Mysql_TableDefinition(
            $this->_adapter,
            'users'
        );
        $table->column('test', 'string', array('auto_increment' => true));
        $table->finish();
        $expected = array("CREATE TABLE `users` (
`id` int(11) UNSIGNED auto_increment NOT NULL,
`test` varchar(255) auto_increment,
 PRIMARY KEY (`id`)) ;");
        $queries = $this->_adapter->getConnexion()->getQueries();
        $this->assertSame($expected, $queries);
    }

    public function testIncluded()
    {
        $table = new Ruckusing_Adapter_Mysql_TableDefinition(
            $this->_adapter,
            'users'
        );
        $table->column('test', 'string');
        $this->assertTrue($table->included('test'));
        $column = new Ruckusing_Adapter_Mysql_ColumnDefinition(
            $this->_adapter,
            'test',
            'string'
        );
        $this->assertInstanceOf('Ruckusing_Adapter_ColumnDefinition', $column);
        $this->assertTrue($table->included($column));
        $this->assertFalse($table->included('new'));
        $column = new Ruckusing_Adapter_Mysql_ColumnDefinition(
            $this->_adapter,
            'new',
            'integer'
        );
        $this->assertInstanceOf('Ruckusing_Adapter_Mysql_ColumnDefinition', $column);
        $this->assertFalse($table->included($column));
    }

    public function testToSql()
    {
        $table = new Ruckusing_Adapter_Mysql_TableDefinition(
            $this->_adapter,
            'users'
        );
        $table->column('test', 'string');
        $expected = '`test` varchar(255)';
        $sql = $table->toSql();
        $this->assertEquals($expected, $sql);
        $table->column('new', 'integer');
        $expected = '`test` varchar(255),`new` int(11)';
        $sql = $table->toSql();
        $this->assertEquals($expected, $sql);
    }

    public function testUnknownMethod()
    {
        $table = new Ruckusing_Adapter_Mysql_TableDefinition(
            $this->_adapter,
            'users'
        );
        try {
            $table->unknown();
            $this->fail('Unknown method called.');
        } catch (Ruckusing_Exception_MissingMigrationMethod $e) {
            $msg = 'The method (unknown) is unknown.';
            $this->assertEquals($msg, $e->getMessage());
        }
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
