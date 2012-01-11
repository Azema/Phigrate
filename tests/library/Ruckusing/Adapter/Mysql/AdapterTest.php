<?php
/**
 * Generated by PHPUnit_SkeletonGenerator on 2012-01-11 at 08:13:31.
 */
// DB table where the version info is stored
if (!defined('RUCKUSING_SCHEMA_TBL_NAME')) {
	define('RUCKUSING_SCHEMA_TBL_NAME', 'schema_info');
}

if (!defined('RUCKUSING_TS_SCHEMA_TBL_NAME')) {
	define('RUCKUSING_TS_SCHEMA_TBL_NAME', 'schema_migrations');
}

class Ruckusing_Adapter_Mysql_AdapterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Ruckusing_Adapter_Mysql_Adapter
     */
    protected $object;

    public function __construct()
    {
        $this->_dsn = array(
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'ruckusing_migrations_test',
            'user' => 'rucku',
            'password' => 'rucku',
        );
        $this->_logger = Ruckusing_Logger::instance(RUCKUSING_BASE . '/tests/logs/tests.log');
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->object = new Ruckusing_Adapter_Mysql_Adapter($this->_dsn, $this->_logger);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        if ($this->object->hasTable(RUCKUSING_TS_SCHEMA_TBL_NAME)) {
            $this->object->dropTable(RUCKUSING_TS_SCHEMA_TBL_NAME);
        }

        if ($this->object->hasTable('users')) {
            $this->object->dropTable('users');
        }

        if ($this->object->hasTable('contacts')) {
            $this->object->dropTable('contacts');
        }

        $this->object = null;
        parent::tearDown();
    }

    public function testSetDsn()
    {
        $expected = array(
            'host' => 'testhost',
            'database' => 'ruckutest',
            'user' => 'toto',
            'password' => 'pass',
        );
        $actual = $this->object->setDsn($expected);
        $this->assertInstanceOf('Ruckusing_Adapter_Mysql_Adapter', $actual);
        $this->assertSame($expected, $this->object->getDsn());
        $dsn = 'dsn';
        try {
            $this->object->setDsn($dsn);
            $this->fail('checkDsn do not accept string argument!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'The argument DSN must be a array!';
            $this->assertEquals($msg, $ex->getMessage());
        }
    }

    public function testGetDsn()
    {
        $this->assertSame($this->_dsn, $this->object->getDsn());
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::checkDsn
     */
    public function testCheckDsn()
    {
        $dsn = 'dsn';
        try {
            $this->object->checkDsn($dsn);
            $this->fail('checkDsn do not accept string argument!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'The argument DSN must be a array!';
            $this->assertEquals($msg, $ex->getMessage());
        }
        $dsn = array();
        try {
            $this->object->checkDsn($dsn);
            $this->fail('checkDsn wait for the "host" argument!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'The argument DSN must be contains index "host"';
            $this->assertEquals($msg, $ex->getMessage());
        }
        $dsn = array('host' => 'localhost');
        try {
            $this->object->checkDsn($dsn);
            $this->fail('checkDsn wait for the "database" argument!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'The argument DSN must be contains index "database"';
            $this->assertEquals($msg, $ex->getMessage());
        }
        $dsn = array(
            'host' => 'localhost',
            'database' => 'test',
        );
        try {
            $this->object->checkDsn($dsn);
            $this->fail('checkDsn wait for the "user" argument!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'The argument DSN must be contains index "user"';
            $this->assertEquals($msg, $ex->getMessage());
        }
        $dsn = array(
            'host' => 'localhost',
            'database' => 'test',
            'user' => 'test',
        );
        try {
            $this->object->checkDsn($dsn);
            $this->fail('checkDsn wait for the "password" argument!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'The argument DSN must be contains index "password"';
            $this->assertEquals($msg, $ex->getMessage());
        }
        $dsn = array(
            'host' => 'localhost',
            'database' => 'test',
            'user' => 'test',
            'password' => 'test',
        );
        $this->assertTrue($this->object->checkDsn($dsn));
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::supportsMigrations
     */
    public function testSupportsMigrations()
    {
        $this->assertTrue($this->object->supportsMigrations());
    }

    public function testGetLogger()
    {
        $this->assertInstanceOf('Ruckusing_Logger', $this->object->getLogger());
    }

    public function testSetLogger()
    {
        $logger = 'the logger';
        try {
            $this->object->setLogger($logger);
            $this->fail('setLogger do not accept string argument!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'Logger parameter must be instance of Ruckusing_Logger';
            $this->assertEquals($msg, $ex->getMessage());
        }
        $logger = $this->object->getLogger();
        $actual = $this->object->setLogger($logger);
        $this->assertInstanceOf('Ruckusing_Adapter_Mysql_Adapter', $actual);
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::nativeDatabaseTypes
     */
    public function testNativeDatabaseTypes()
    {
		$expected = array(
            'primary_key'   => array(
                'name' => 'integer',
                'limit' => 11,
                'null' => false,
            ),
            'string'        => array(
                'name' => 'varchar',
                'limit' => 255,
            ),
            'text'          => array('name' => 'text'),
            'mediumtext'    => array('name' => 'mediumtext'),
            'integer'       => array(
                'name' => 'int',
                'limit' => 11,
            ),
            'smallinteger'  => array('name' => 'smallint'),
            'biginteger'    => array('name' => 'bigint'),
            'float'         => array('name' => 'float'),
            'decimal'       => array('name' => 'decimal'),
            'datetime'      => array('name' => 'datetime'),
            'timestamp'     => array('name' => 'timestamp'),
            'time'          => array('name' => 'time'),
            'date'          => array('name' => 'date'),
            'binary'        => array('name' => 'blob'),
            'boolean'       => array(
                'name' => 'tinyint',
                'limit' => 1,
            ),
        );
        $this->assertSame($expected, $this->object->nativeDatabaseTypes());
    }

    public function testHasTable()
    {
        $this->assertFalse($this->object->hasTable('unknown_table'));
        $this->assertFalse($this->object->hasTable('users'));
        
        //create it
        $this->object->executeDdl("CREATE TABLE `users` ( name varchar(20) );");
        
        //now make sure it does exist
        $this->assertTrue($this->object->hasTable('users'));
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::createSchemaVersionTable
     */
    public function testCreateSchemaVersionTable()
    {
        $this->assertFalse($this->object->hasTable(RUCKUSING_TS_SCHEMA_TBL_NAME));
        $this->object->createSchemaVersionTable();
        $this->assertTrue($this->object->hasTable(RUCKUSING_TS_SCHEMA_TBL_NAME));
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::startTransaction
     */
    public function testStartTransaction()
    {
        $this->setExpectedException(
            'PHPUnit_Framework_Error', 
            'Transaction already started'
        );
        $this->object->startTransaction();
        $this->object->startTransaction();
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::commitTransaction
     */
    public function testCommitTransaction()
    {
        $this->object->startTransaction();
        $this->object->commitTransaction();
        $this->setExpectedException(
            'PHPUnit_Framework_Error', 
            'Transaction not started'
        );
        $this->object->commitTransaction();
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::rollbackTransaction
     */
    public function testRollbackTransaction()
    {
        $this->object->startTransaction();
        $this->object->rollbackTransaction();
        $this->setExpectedException(
            'PHPUnit_Framework_Error', 
            'Transaction not started'
        );
        $this->object->rollbackTransaction();
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::quoteTable
     */
    public function testQuoteTable()
    {
        $tableName = 'test';
        $expected = '`' . $tableName . '`';
        $actual = $this->object->quoteTable($tableName);
        $this->assertEquals($expected, $actual);
        $actual = $this->object->identifier($tableName);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::columnDefinition
     */
    public function testColumnDefinition()
    {
        $expected = '`age` varchar(255)';
        $this->assertEquals(
            $expected,
            $this->object->columnDefinition('age', 'string')
        );

        $expected = '`age` varchar(32)';
        $this->assertEquals(
            $expected,
            $this->object->columnDefinition(
                'age',
                'string',
                array('limit' => 32)
            )
        );

        $expected = '`age` varchar(32) NOT NULL';
        $this->assertEquals(
            $expected,
            $this->object->columnDefinition(
                'age',
                'string',
                array(
                    'limit' => 32,
                    'null' => false,
                )
            )
        );

        $expected = '`age` varchar(32) DEFAULT \'abc\' NOT NULL';
        $this->assertEquals(
            $expected,
            $this->object->columnDefinition(
                'age',
                'string',
                array(
                    'limit' => 32,
                    'default' => 'abc',
                    'null' => false,
                )
            )
        );

        $expected = '`age` varchar(32) DEFAULT \'abc\'';
        $this->assertEquals(
            $expected,
            $this->object->columnDefinition(
                'age',
                'string',
                array(
                    'limit' => 32,
                    'default' => 'abc',
                )
            )
        );

        $expected = '`age` int(11)';
        $this->assertEquals(
            $expected,
            $this->object->columnDefinition('age', 'integer')
        );

        $expected = '`active` tinyint(1)';
        $this->assertEquals(
            $expected,
            $this->object->columnDefinition('active', 'boolean')
        );	
        
        $expected = '`weight` bigint(20)';
        $this->assertEquals(
            $expected,
            $this->object->columnDefinition(
                'weight',
                'biginteger',
                array('limit' => 20)
            )
        );
        
        $expected = '`age` int(11) AFTER `height`';
        $this->assertEquals(
            $expected,
            $this->object->columnDefinition(
                'age',
                'integer',
                array('after' => 'height')
            )
        );	
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::databaseExists
     */
    public function testDatabaseExists()
    {
        $this->assertFalse($this->object->databaseExists('unknownDb'));
        $this->assertTrue($this->object->databaseExists('ruckusing_migrations_test'));
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::createDatabase
     */
    public function testCreateDatabase()
    {
        $dsn = array(
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'ruckusing_migrations_test',
            'user' => 'rucku_test',
            'password' => 'rucku',
        );
        $object = new Ruckusing_Adapter_Mysql_Adapter($dsn, $this->_logger);
        $db = "users";
        $object->dropDatabase($db);
        $this->assertTrue($object->createDatabase($db));
        $this->assertFalse($object->createDatabase($db));
        $this->assertTrue($object->databaseExists($db));
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::dropDatabase
     */
    public function testDropDatabase()
    {
        $dsn = array(
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'ruckusing_migrations_test',
            'user' => 'rucku_test',
            'password' => 'rucku',
        );
        $object = new Ruckusing_Adapter_Mysql_Adapter($dsn, $this->_logger);
        $db = "users";
        $this->assertTrue($object->dropDatabase($db));
        $this->assertFalse($object->databaseExists($db));
        $this->assertFalse($object->dropDatabase($db));
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::schema
     */
    public function testSchema()
    {
        $schema = $this->object->schema();
        $this->assertNotEmpty($schema);
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::tableExists
     */
    public function testTableExists()
    {
        $this->assertFalse($this->object->tableExists('unknown_table'));
        $this->assertFalse($this->object->tableExists('users'));
        
        //create it
        $this->object->executeDdl("CREATE TABLE `users` ( name varchar(20) );");
        
        //now make sure it does exist
        $this->assertFalse($this->object->tableExists('users'));
        // reload all tables
        $this->assertTrue($this->object->tableExists('users', true));
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::showFieldsFrom
     */
    public function testShowFieldsFrom()
    {
        $this->assertEmpty($this->object->showFieldsFrom('tableName'));
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::execute
     */
    public function testExecute()
    {
        $this->setExpectedException(
            'PHPUnit_Framework_Error', 
            'You have an error in your SQL syntax'
        );
        $query = 'SHOW DATABASE `ruckusing`'; // not correct
        $this->assertTrue($this->object->execute($query));
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::query
     */
    public function testQuery()
    {
        $this->setExpectedException(
            'PHPUnit_Framework_Error', 
            'You have an error in your SQL syntax'
        );
        $query = 'SHOW DATABASE `ruckusing`'; // not correct
        $this->assertTrue($this->object->query($query));
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::selectOne
     * @todo   Implement testSelectOne().
     */
    public function testSelectOne()
    {
        $this->object->executeDdl("CREATE TABLE `users` (name varchar(20));");
        $this->object->executeDdl("INSERT INTO `users` (`name`) VALUES ('first'), ('second'), ('third');");
        $expected = array('name' => 'first');
        $sql = "SELECT name FROM `users`;";
        $actual = $this->object->selectOne($sql);
        $expected = array('name' => 'third');
        $sql = "SELECT name FROM `users` ORDER BY `name` DESC;";
        $actual = $this->object->selectOne($sql);
        $this->assertSame($expected, $actual);
    }

    public function testSelectOneError()
    {
        $this->setExpectedException(
            'PHPUnit_Framework_Error', 
            'selectOne()'
        );
        $query = 'UPDATE aTable SET id=1';
        $this->object->selectOne($query);
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::selectAll
     */
    public function testSelectAll()
    {
        $this->object->executeDdl("CREATE TABLE `users` (name varchar(20));");
        $this->object->executeDdl("INSERT INTO `users` (`name`) VALUES ('first'), ('second'), ('third');");
        $expected = array(
            array('name' => 'first'),
            array('name' => 'second'),
            array('name' => 'third'),
        );
        $sql = "SELECT name FROM `users`;";
        $actual = $this->object->selectAll($sql);
        $expected = array(
            array('name' => 'third'),
            array('name' => 'second'),
            array('name' => 'first'),
        );
        $sql = "SELECT name FROM `users` ORDER BY `name` DESC;";
        $actual = $this->object->selectAll($sql);
        $this->assertSame($expected, $actual);
    }

    public function testSelectAllError()
    {
        $this->setExpectedException(
            'PHPUnit_Framework_Error', 
            'selectAll()'
        );
        $query = 'DELETE aTable';
        $this->object->selectAll($query);
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::executeDdl
     */
    public function testExecuteDdl()
    {
        $sql = 'CREATE TABLE `users` (name varchar(20));';
        $actual = $this->object->executeDdl($sql);
        $this->assertTrue($actual);
        $query = 'UNKNOWN aTable';
        $actual = $this->object->executeDdl($query);
        $this->assertFalse($actual);
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::dropTable
     */
    public function testDropTable()
    {
        $actual = $this->object->dropTable('users');
        $this->assertTrue($actual);
        $actual = $this->object->dropTable('users');
        $this->assertTrue($actual);
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::createTable
     * @todo   Gestion exception
     */
    public function testCreateTable()
    {
        $tableName = 'users';
        $table = $this->object
            ->createTable('users', array('id' => false));
        $this->assertInstanceOf('Ruckusing_Adapter_Mysql_TableDefinition', $table);
        $table = $this->object->createTable('users');
        $this->assertInstanceOf('Ruckusing_Adapter_Mysql_TableDefinition', $table);
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::quoteString
     */
    public function testQuoteString()
    {
        $string = "string'with'simple'quote";
        $expected = "string\'with\'simple\'quote";
        $actual = $this->object->quoteString($string);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::identifier
     */
    public function testIdentifier()
    {
        $tableName = 'test';
        $expected = '`' . $tableName . '`';
        $actual = $this->object->identifier($tableName);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::renameTable
     */
    public function testRenameTable()
    {
        try {
            $this->object->renameTable('', '');
            $this->fail('renameTable does not accept empty string for original table name!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'Missing original table name parameter';
            $this->assertEquals($msg, $ex->getMessage());
        }
        try {
            $this->object->renameTable('users', '');
            $this->fail('renameTable does not accept empty string for new table name!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'Missing new table name parameter';
            $this->assertEquals($msg, $ex->getMessage());
        }
        $sql = 'CREATE TABLE `users` (name varchar(20));';
        $this->object->executeDdl($sql);
        $actual = $this->object->renameTable('users', 'contacts');
        $this->assertTrue($actual);
        $this->assertFalse($this->object->tableExists('users', true));
        $this->assertTrue($this->object->tableExists('contacts'));
        $this->object->dropTable('contacts');
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::addColumn
     */
    public function testAddColumn()
    {
        try {
            $this->object->addColumn('', '', '');
            $this->fail('addColumn does not accept empty string for table name!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'Missing table name parameter';
            $this->assertEquals($msg, $ex->getMessage());
        }
        try {
            $this->object->addColumn('users', '', '');
            $this->fail('addColumn does not accept empty string for column name!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'Missing column name parameter';
            $this->assertEquals($msg, $ex->getMessage());
        }
        try {
            $this->object->addColumn('users', 'name', '');
            $this->fail('addColumn does not accept empty string for type!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'Missing type parameter';
            $this->assertEquals($msg, $ex->getMessage());
        }
        //create it
        $this->object->executeDdl('CREATE TABLE `users` (name varchar(20));');	

        $col = $this->object->columnInfo('users', 'name');
        $this->assertEquals('name', $col['field']);
        
        //add column
        $this->object->addColumn(
            'users',
            'fav_color',
            'string',
            array('limit' => 32)
        );
        $col = $this->object->columnInfo('users', 'fav_color');
        $this->assertEquals('fav_color', $col['field']);
        $this->assertEquals('varchar(32)', $col['type']);

        //add column
        $this->object->addColumn(
            'users',
            'latitude',
            'decimal',
            array(
                'precision' => 10,
                'scale' => 2,
            )
        );
        $col = $this->object->columnInfo('users', 'latitude');
        $this->assertEquals('latitude', $col['field']);
        $this->assertEquals('decimal(10,2)', $col['type']);
        
        //add column with unsigned parameter
        $this->object->addColumn(
            'users',
            'age',
            'integer',
            array('unsigned' => true)
        );
        $col = $this->object->columnInfo('users', 'age');
        $this->assertEquals('age', $col['field']);
        $this->assertEquals('int(11) unsigned', $col['type']);
        
        //add column with biginteger datatype
        $this->object->addColumn(
            'users',
            'weight',
            'biginteger',
            array('limit' => 20)
        );
        $col = $this->object->columnInfo('users', 'weight');
        $this->assertEquals('weight', $col['field']);
        $this->assertEquals('bigint(20)', $col['type']);
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::removeColumn
     */
    public function testRemoveColumn()
    {
        try {
            $this->object->removeColumn('', '');
            $this->fail('removeColumn does not accept empty string for table name!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'Missing table name parameter';
            $this->assertEquals($msg, $ex->getMessage());
        }
        try {
            $this->object->removeColumn('users', '');
            $this->fail('removeColumn does not accept empty string for column name!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'Missing column name parameter';
            $this->assertEquals($msg, $ex->getMessage());
        }
        //create it
        $sql = "CREATE TABLE `users` ( name varchar(20), age int(3) );";
        $this->object->executeDdl($sql);

        //verify it exists
        $col = $this->object->columnInfo('users', 'name');
        $this->assertEquals('name', $col['field']);
        
        //drop it
        $this->object->removeColumn('users', 'name');

        //verify it does not exist
        $col = $this->object->columnInfo('users', 'name');
        $this->assertEquals(null, $col);
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::renameColumn
     */
    public function testRenameColumn()
    {
        try {
            $this->object->renameColumn('', '', '');
            $this->fail('renameColumn does not accept empty string for table name!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'Missing table name parameter';
            $this->assertEquals($msg, $ex->getMessage());
        }
        try {
            $this->object->renameColumn('users', '', '');
            $this->fail('renameColumn does not accept empty string for original column name!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'Missing original column name parameter';
            $this->assertEquals($msg, $ex->getMessage());
        }
        try {
            $this->object->renameColumn('users', 'name', '');
            $this->fail('renameColumn does not accept empty string for new column name!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'Missing new column name parameter';
            $this->assertEquals($msg, $ex->getMessage());
        }
        //create it
        $this->object->executeDdl("CREATE TABLE `users` (name varchar(20));");	

        $before = $this->object->columnInfo('users', 'name');
        $this->assertEquals('varchar(20)', $before['type'] );			
        $this->assertEquals('name', $before['field'] );			
        
        //rename the name column
        $this->object->renameColumn('users', 'name', 'new_name');

        $after = $this->object->columnInfo('users', 'new_name');
        $this->assertEquals('varchar(20)', $after['type'] );			
        $this->assertEquals('new_name', $after['field'] );				
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::changeColumn
     */
    public function testChangeColumn()
    {
        try {
            $this->object->changeColumn('', '', '');
            $this->fail('changeColumn does not accept empty string for table name!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'Missing table name parameter';
            $this->assertEquals($msg, $ex->getMessage());
        }
        try {
            $this->object->changeColumn('users', '', '');
            $this->fail('changeColumn does not accept empty string for column name!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'Missing column name parameter';
            $this->assertEquals($msg, $ex->getMessage());
        }
        try {
            $this->object->changeColumn('users', 'name', '');
            $this->fail('changeColumn does not accept empty string for type!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'Missing type parameter';
            $this->assertEquals($msg, $ex->getMessage());
        }
        //create it
        $sql = "CREATE TABLE `users` (name varchar(20), age int(3));";
        $this->object->executeDdl($sql);

        //verify its type
        $col = $this->object->columnInfo('users', 'name');
        $this->assertEquals('varchar(20)', $col['type'] );			
        $this->assertEmpty($col['default']);			
        
        //change it, add a default too!
        $this->object->changeColumn(
            'users',
            'name',
            'string',
            array(
                'default' => 'abc',
                'limit' => 128,
            )
        );
        
        $col = $this->object->columnInfo('users', 'name');
        $this->assertEquals('varchar(128)', $col['type'] );						
        $this->assertEquals('abc', $col['default'] );			

        //change it, add a default too!
        $this->object->changeColumn(
            'users',
            'name',
            'integer',
            array(
                'default' => '1',
            )
        );
        
        $col = $this->object->columnInfo('users', 'name');
        $this->assertEquals('int(11)', $col['type'] );						
        $this->assertEquals('1', $col['default'] );			
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::columnInfo
     */
    public function testColumnInfo()
    {
        try {
            $this->object->columnInfo('', '');
            $this->fail('columnInfo does not accept empty string for table name!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'Missing table name parameter';
            $this->assertEquals($msg, $ex->getMessage());
        }
        try {
            $this->object->columnInfo('users', '');
            $this->fail('columnInfo does not accept empty string for column name!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'Missing column name parameter';
            $this->assertEquals($msg, $ex->getMessage());
        }
        //create it
        $this->object->executeDdl("CREATE TABLE `users` (name varchar(20));");	

        $expected = array();
        $actual = $this->object->columnInfo('users', 'name');
        $this->assertInternalType('array', $actual);
        $this->assertEquals('varchar(20)', $actual['type'] );			
        $this->assertEquals('name', $actual['field'] );			
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::addIndex
     * @todo   Implement testAddIndex().
     */
    public function testAddIndex()
    {
        try {
            $this->object->addIndex('', '');
            $this->fail('addIndex does not accept empty string for table name!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'Missing table name parameter';
            $this->assertEquals($msg, $ex->getMessage());
        }
        try {
            $this->object->addIndex('users', '');
            $this->fail('addIndex does not accept empty string for column name!');
        } catch (Ruckusing_Exception_Argument $ex) {
            $msg = 'Missing column name parameter';
            $this->assertEquals($msg, $ex->getMessage());
        }
        //create it
        $sql = "CREATE TABLE `users` (name varchar(20), age int(3), title varchar(20), other tinyint(1));";
        $this->object->executeDdl($sql);
        $this->object->addIndex('users', 'name');
        
        $this->assertTrue($this->object->hasIndex('users', 'name'));
        $this->assertFalse($this->object->hasIndex('users', 'age'));
        
        $this->object->addIndex('users', 'age', array('unique' => true));
        $this->assertTrue($this->object->hasIndex('users', 'age'));
        
        $this->object->addIndex(
            'users',
            'title',
            array('name' => 'index_on_super_title')
        );
        $this->assertTrue($this->object->hasIndex(
            'users',
            'title',
            array('name' => 'index_on_super_title')
        ));
        try {
            $this->object->addIndex(
                'users',
                'other',
                array('name' => 'index_on_super_super_very_maxi_mega_giga_long_title_aaaaaaaahhhhh')
            );
            $this->fail('Max identifier length');
        } catch (Ruckusing_Exception_InvalidIndexName $ex) {
            $msg = 'The auto-generated index name is too long for '
                . 'MySQL (max is 64 chars). Considering using \'name\' option '
                . 'parameter to specify a custom name for this index. '
                . 'Note: you will also need to specify this custom name '
                . 'in a drop_index() - if you have one.';
            $this->assertEquals($msg, $ex->getMessage());
        }
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::removeIndex
     * @todo   Implement testRemoveIndex().
     */
    public function testRemoveIndex()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::hasIndex
     * @todo   Implement testHasIndex().
     */
    public function testHasIndex()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::indexes
     * @todo   Implement testIndexes().
     */
    public function testIndexes()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::typeToSql
     * @todo   Implement testTypeToSql().
     */
    public function testTypeToSql()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::addColumnOptions
     * @todo   Implement testAddColumnOptions().
     */
    public function testAddColumnOptions()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::setCurrentVersion
     * @todo   Implement testSetCurrentVersion().
     */
    public function testSetCurrentVersion()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::removeVersion
     * @todo   Implement testRemoveVersion().
     */
    public function testRemoveVersion()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Ruckusing_Adapter_Mysql_Adapter::__toString
     * @todo   Implement test__toString().
     */
    public function test__toString()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
}
