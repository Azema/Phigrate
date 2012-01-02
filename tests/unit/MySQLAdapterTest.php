<?php
if(!defined('BASE')) {
  define('BASE', dirname(__FILE__) . '/..');
}
require_once BASE  . '/test_helper.php';
require_once RUCKUSING_BASE  . '/lib/classes/class.Ruckusing_BaseAdapter.php';
require_once RUCKUSING_BASE  . '/lib/classes/class.Ruckusing_BaseMigration.php';
require_once RUCKUSING_BASE  . '/lib/classes/class.Ruckusing_IAdapter.php';
require_once RUCKUSING_BASE  . '/lib/classes/adapters/class.Ruckusing_MySQLAdapter.php';
require_once RUCKUSING_BASE  . '/lib/classes/Ruckusing_exceptions.php';

/*
	To run these unit-tests an empty test database needs to be setup in database.inc.php
	and of course, it has to really exist.
*/

class MySQLAdapterTest extends PHPUnit_Framework_TestCase {
		
    protected function setUp() {
        require RUCKUSING_BASE . '/config/database.inc.php';

        if( !is_array($ruckusing_db_config) || !array_key_exists("test", $ruckusing_db_config)) {
            die("\n'test' DB is not defined in config/database.inc.php\n\n");
        }

        $test_db = $ruckusing_db_config['test'];

        //setup our log
        $logger = Ruckusing_Logger::instance(RUCKUSING_BASE . '/tests/logs/test.log');

        $this->adapter = new Ruckusing_MySQLAdapter($test_db, $logger);
        $this->adapter->getLogger()->log("Test run started: " . date('Y-m-d g:ia T') );
        
    }//setUp()

    protected function tearDown() {			

        //delete any tables we created
        if($this->adapter->hasTable('users',true)) {
            $this->adapter->dropTable('users');
        }

        if($this->adapter->hasTable(RUCKUSING_TS_SCHEMA_TBL_NAME,true)) {
            $this->adapter->dropTable(RUCKUSING_TS_SCHEMA_TBL_NAME);
        }

        $db = "test_db";
        //delete any databases we created
        if($this->adapter->databaseExists($db)) {
            $this->adapter->dropDatabase($db);				
        }			
    }
		
    public function testCreateSchemaVersionTable() {
        //force drop, start from a clean slate
        if($this->adapter->hasTable(RUCKUSING_TS_SCHEMA_TBL_NAME,true)) {
            $this->adapter->dropTable(RUCKUSING_TS_SCHEMA_TBL_NAME);
        }
        $this->adapter->createSchemaVersionTable();
        $this->assertEquals(true, $this->adapter->hasTable(RUCKUSING_TS_SCHEMA_TBL_NAME,true) );
    }
		
    public function test_ensure_table_does_not_exist() {
        $this->assertEquals(false, $this->adapter->hasTable('unknown_table') );
    }

    public function test_ensure_table_does_exist() {
        //first make sure the table does not exist
        $users = $this->adapter->hasTable('users',true);
        $this->assertEquals(false, $users);
        
        //create it
        //$this->adapter->executeDdl("CREATE TABLE `users` ( name varchar(20) );");
        
        $t1 = new Ruckusing_MySQLTableDefinition($this->adapter, "users", array('options' => 'Engine=InnoDB') );
        $t1->column("name", "string", array('limit' => 20));
        $sql = $t1->finish();
        
        //now make sure it does exist
        $users = $this->adapter->tableExists('users',true);
        $this->assertEquals(true, $users);			
    }

    public function testDatabaseCreation() {
        $db = "test_db";
        $this->assertEquals(true, $this->adapter->createDatabase($db) );
        $this->assertEquals(true, $this->adapter->databaseExists($db) );
        
        $db = "db_does_not_exist";
        $this->assertEquals(false, $this->adapter->databaseExists($db) );			
    }

    public function test_database_droppage() {
        $db = "test_db";
        //create it
        $this->assertEquals(true, $this->adapter->createDatabase($db) );
        $this->assertEquals(true, $this->adapter->databaseExists($db) );
        
        //drop it
        $this->assertEquals(true, $this->adapter->dropDatabase($db) );
        $this->assertEquals(false, $this->adapter->databaseExists($db) );
    }
		
    public function testIndexNameTooLongThrowsException() {
        $this->setExpectedException('Ruckusing_InvalidIndexNameException');
        $bm = new Ruckusing_BaseMigration();
        $bm->setAdapter($this->adapter);
        $ts = time();
        $tableName = "users_${ts}";
        $table = $bm->createTable($tableName, array('id' => false));
        $table->column('somecolumnthatiscrazylong', 'integer');
        $table->column('anothercolumnthatiscrazylongrodeclown', 'integer');
        $sql = $table->finish();
        $bm->addIndex(
            $tableName, 
            array(
                'somecolumnthatiscrazylong', 
                'anothercolumnthatiscrazylongrodeclown',
            )
        );
    }

    public function testCustomPrimaryKey1() {
        $t1 = new Ruckusing_MySQLTableDefinition($this->adapter, "users", array('id' => true, 'options' => 'Engine=InnoDB') );
  		$t1->column("user_id", "integer", array("primary_key" => true));
  		$actual = $t1->finish(true);
    }

    public function testColumnDefinition() {
        $expected = "`age` varchar(255)";
        $this->assertEquals($expected, $this->adapter->columnDefinition("age", "string"));

        $expected = "`age` varchar(32)";
        $this->assertEquals($expected, $this->adapter->columnDefinition("age", "string", array('limit' => 32)));

        $expected = "`age` varchar(32) NOT NULL";
        $this->assertEquals($expected, $this->adapter->columnDefinition("age", "string", 
                                                    array('limit' => 32, 'null' => false)));

        $expected = "`age` varchar(32) DEFAULT 'abc' NOT NULL";
        $this->assertEquals($expected, $this->adapter->columnDefinition("age", "string", 
                                                    array('limit' => 32, 'default' => 'abc', 'null' => false)));

        $expected = "`age` varchar(32) DEFAULT 'abc'";
        $this->assertEquals($expected, $this->adapter->columnDefinition("age", "string", 
                                                    array('limit' => 32, 'default' => 'abc')));

        $expected = "`age` int(11)";
        $this->assertEquals($expected, $this->adapter->columnDefinition("age", "integer"));

        $expected = "`active` tinyint(1)";
        $this->assertEquals($expected, $this->adapter->columnDefinition("active", "boolean"));	
        
        $expected = "`weight` bigint(20)";
        $this->assertEquals($expected, $this->adapter->columnDefinition("weight", "biginteger", array('limit' => 20)));
        
        $expected = "`age` int(11) AFTER `height`";
        $this->assertEquals($expected, $this->adapter->columnDefinition("age", "integer", array("after" => "height")));	
    }

    public function testColumnInfo() {			
        //create it
        $this->adapter->executeDdl("CREATE TABLE `users` ( name varchar(20) );");	

        $expected = array();
        $actual = $this->adapter->columnInfo("users", "name");
        $this->assertInternalType('array', $actual);
        $this->assertEquals('varchar(20)', $actual['type'] );			
        $this->assertEquals('name', $actual['field'] );			
    }
		
    public function test_rename_table() {
        //create it
        $this->adapter->executeDdl("CREATE TABLE `users` ( name varchar(20) );");	
        $this->assertEquals(true, $this->adapter->hasTable('users') );
        $this->assertEquals(false, $this->adapter->hasTable('users_new') );
        //rename it
        $this->adapter->renameTable('users', 'users_new');
        $this->assertEquals(false, $this->adapter->hasTable('users') );
        $this->assertEquals(true, $this->adapter->hasTable('users_new') );
        //clean up
        $this->adapter->dropTable('users_new');
    }

    public function testRenameColumn() {			
        //create it
        $this->adapter->executeDdl("CREATE TABLE `users` ( name varchar(20) );");	

        $before = $this->adapter->columnInfo("users", "name");
        $this->assertEquals('varchar(20)', $before['type'] );			
        $this->assertEquals('name', $before['field'] );			
        
        //rename the name column
        $this->adapter->renameColumn('users', 'name', 'new_name');

        $after = $this->adapter->columnInfo("users", "new_name");
        $this->assertEquals('varchar(20)', $after['type'] );			
        $this->assertEquals('new_name', $after['field'] );				
    }

    public function testSupportMigrations()
    {
        $this->assertTrue($this->adapter->supportsMigrations());
    }

    public function testQuoteTable()
    {
        $tableName = 'test';
        $expected = '`' . $tableName . '`';
        $actual = $this->adapter->quoteTable($tableName);
        $this->assertEquals($expected, $actual);
        $actual = $this->adapter->identifier($tableName);
        $this->assertEquals($expected, $actual);
    }

    public function testDatabaseExists()
    {
        $this->assertFalse($this->adapter->databaseExists('unknownDb'));
    }

    public function testCreateAndDropDatabase()
    {
        $this->assertTrue($this->adapter->createDatabase('ruckusing'));
        $this->assertTrue($this->adapter->databaseExists('ruckusing'));
        $this->assertFalse($this->adapter->createDatabase('ruckusing'));
        $this->assertTrue($this->adapter->dropDatabase('ruckusing'));
        $this->assertFalse($this->adapter->databaseExists('ruckusing'));
        $this->assertFalse($this->adapter->dropDatabase('ruckusing'));
    }

    public function testSchema()
    {
        $schema = $this->adapter->schema();
        $this->assertNotEmpty($schema);
    }

    /**
     * testQuery 
     * 
     * @return void
     */
    public function testQuery()
    {
        $this->setExpectedException(
            'PHPUnit_Framework_Error', 
            'You have an error in your SQL syntax'
        );
        $query = 'SHOW DATABASE `ruckusing`'; // not correct
        $this->assertTrue($this->adapter->query($query));
    }

    public function testExecute()
    {
        $this->setExpectedException(
            'PHPUnit_Framework_Error', 
            'You have an error in your SQL syntax'
        );
        $query = 'SHOW DATABASE `ruckusing`'; // not correct
        $this->assertTrue($this->adapter->execute($query));
    }

    public function testExecuteDdl()
    {
        $this->setExpectedException(
            'PHPUnit_Framework_Error', 
            'You have an error in your SQL syntax'
        );
        $query = 'SHOW DATABASE `ruckusing`'; // not correct
        $this->assertTrue($this->adapter->executeDdl($query));
    }

    public function testSelectOneWithUpdateQuery()
    {
        $this->setExpectedException(
            'PHPUnit_Framework_Error', 
            'selectOne()'
        );
        $query = 'UPDATE aTable SET id=1';
        $this->adapter->selectOne($query);
    }

    public function testSelectAllWithUpdateQuery()
    {
        $this->setExpectedException(
            'PHPUnit_Framework_Error', 
            'selectAll()'
        );
        $query = 'UPDATE aTable SET id=1';
        $this->adapter->selectAll($query);
    }

    public function testRenameTableWithEmptyOldName()
    {
        $this->setExpectedException(
            'Ruckusing_ArgumentException', 
            'Missing original table name parameter'
        );
        $this->adapter->renameTable('', 'newName');
    }

    public function testRenameTableWithEmptyNewName()
    {
        $this->setExpectedException(
            'Ruckusing_ArgumentException', 
            'Missing new table name parameter'
        );
        $this->adapter->renameTable('oldName', '');
    }

    public function testAddColumnWithEmptyTableName()
    {
        $this->setExpectedException(
            'Ruckusing_ArgumentException', 
            'Missing table name parameter'
        );
        $this->adapter->addColumn('', 'columnName', 'type');
    }

    public function testAddColumnWithEmptyColumnName()
    {
        $this->setExpectedException(
            'Ruckusing_ArgumentException', 
            'Missing column name parameter'
        );
        $this->adapter->addColumn('tableName', '', 'type');
    }

    public function testAddColumnWithEmptyType()
    {
        $this->setExpectedException(
            'Ruckusing_ArgumentException', 
            'Missing type parameter'
        );
        $this->adapter->addColumn('tableName', 'columnName', '');
    }

    public function testRemoveColumnWithEmptyTableName()
    {
        $this->setExpectedException(
            'Ruckusing_ArgumentException', 
            'Missing table name parameter'
        );
        $this->adapter->removeColumn('', 'columnName');
    }

    public function testRemoveColumnWithEmptyColumnName()
    {
        $this->setExpectedException(
            'Ruckusing_ArgumentException', 
            'Missing column name parameter'
        );
        $this->adapter->removeColumn('tableName', '');
    }

    public function testRenameColumnWithEmptyTableName()
    {
        $this->setExpectedException(
            'Ruckusing_ArgumentException', 
            'Missing table name parameter'
        );
        $this->adapter->renameColumn('', 'oldColumnName', 'newColumnName');
    }

    public function testRenameColumnWithEmptyOldColumnName()
    {
        $this->setExpectedException(
            'Ruckusing_ArgumentException', 
            'Missing original column name parameter'
        );
        $this->adapter->renameColumn('tableName', '', 'newColumnName');
    }

    public function testRenameColumnWithEmptyNewColumnName()
    {
        $this->setExpectedException(
            'Ruckusing_ArgumentException', 
            'Missing new column name parameter'
        );
        $this->adapter->renameColumn('tableName', 'oldColumnName', '');
    }

    public function testChangeColumnWithEmptyTableName()
    {
        $this->setExpectedException(
            'Ruckusing_ArgumentException', 
            'Missing table name parameter'
        );
        $this->adapter->changeColumn('', 'columnName', 'type');
    }

    public function testChangeColumnWithEmptyColumnName()
    {
        $this->setExpectedException(
            'Ruckusing_ArgumentException', 
            'Missing column name parameter'
        );
        $this->adapter->changeColumn('tableName', '', 'type');
    }

    public function testChangeColumnWithEmptyType()
    {
        $this->setExpectedException(
            'Ruckusing_ArgumentException', 
            'Missing type parameter'
        );
        $this->adapter->changeColumn('tableName', 'columnName', '');
    }

    public function testColumnInfoWithEmptyTableName()
    {
        $this->setExpectedException(
            'Ruckusing_ArgumentException', 
            'Missing table name parameter'
        );
        $this->adapter->columnInfo('', 'columnName', 'type');
    }

    public function testColumnInfoWithEmptyColumnName()
    {
        $this->setExpectedException(
            'Ruckusing_ArgumentException', 
            'Missing column name parameter'
        );
        $this->adapter->columnInfo('tableName', '', 'type');
    }

    public function testAddIndexWithEmptyTableName()
    {
        $this->setExpectedException(
            'Ruckusing_ArgumentException', 
            'Missing table name parameter'
        );
        $this->adapter->addIndex('', 'columnName');
    }

    public function testAddIndexWithEmptyColumnName()
    {
        $this->setExpectedException(
            'Ruckusing_ArgumentException', 
            'Missing column name parameter'
        );
        $this->adapter->addIndex('tableName', '');
    }

    public function testRemoveIndexWithEmptyTableName()
    {
        $this->setExpectedException(
            'Ruckusing_ArgumentException', 
            'Missing table name parameter'
        );
        $this->adapter->removeIndex('', 'columnName');
    }

    public function testRemoveIndexWithEmptyColumnName()
    {
        $this->setExpectedException(
            'Ruckusing_ArgumentException', 
            'Missing column name parameter'
        );
        $this->adapter->removeIndex('tableName', '');
    }

    public function testHasIndexWithEmptyTableName()
    {
        $this->setExpectedException(
            'Ruckusing_ArgumentException', 
            'Missing table name parameter'
        );
        $this->adapter->hasIndex('', 'columnName');
    }

    public function testHasIndexWithEmptyColumnName()
    {
        $this->setExpectedException(
            'Ruckusing_ArgumentException', 
            'Missing column name parameter'
        );
        $this->adapter->hasIndex('tableName', '');
    }

    public function testTypeToSqlWithUnknownType()
    {
        $this->setExpectedException(
            'Ruckusing_ArgumentException', 
            'I dont know what column type of \'unknown\' maps to for MySQL.'
        );
        $this->adapter->typeToSql('unknown');
    }

    public function testStartTransactionWithTransactionAlreadyBegin()
    {
        $this->setExpectedException(
            'PHPUnit_Framework_Error', 
            'Transaction already started'
        );
        $this->adapter->startTransaction();
        $this->adapter->startTransaction();
    }

    public function testCommitTransactionWithTransactionNotBegin()
    {
        $this->setExpectedException(
            'PHPUnit_Framework_Error', 
            'Transaction not started'
        );
        $this->adapter->commitTransaction();
    }

    public function testRollbackTransactionWithTransactionNotBegin()
    {
        $this->setExpectedException(
            'PHPUnit_Framework_Error', 
            'Transaction not started'
        );
        $this->adapter->rollbackTransaction();
    }

    public function testAddColumn() {			
        //create it
        $this->adapter->executeDdl("CREATE TABLE `users` ( name varchar(20) );");	

        $col = $this->adapter->columnInfo("users", "name");
        $this->assertEquals("name", $col['field']);			
        
        //add column
        $this->adapter->addColumn("users", "fav_color", "string", array('limit' => 32));
        $col = $this->adapter->columnInfo("users", "fav_color");
        $this->assertEquals("fav_color", $col['field']);			
        $this->assertEquals('varchar(32)', $col['type'] );			

        //add column
        $this->adapter->addColumn("users", "latitude", "decimal", array('precision' => 10, 'scale' => 2));
        $col = $this->adapter->columnInfo("users", "latitude");
        $this->assertEquals("latitude", $col['field']);			
        $this->assertEquals('decimal(10,2)', $col['type'] );			
        
        //add column with unsigned parameter
        $this->adapter->addColumn("users", "age", "integer", array('unsigned' => true));
        $col = $this->adapter->columnInfo("users", "age");
        $this->assertEquals("age", $col['field']);			
        $this->assertEquals('int(11) unsigned', $col['type'] );
        
        //add column with biginteger datatype
        $this->adapter->addColumn("users", "weight", "biginteger", array('limit' => 20));
        $col = $this->adapter->columnInfo("users", "weight");
        $this->assertEquals("weight", $col['field']);			
        $this->assertEquals('bigint(20)', $col['type'] );
    }

    public function testRemoveColumn() {			
        //create it
        $this->adapter->executeDdl("CREATE TABLE `users` ( name varchar(20), age int(3) );");	

        //verify it exists
        $col = $this->adapter->columnInfo("users", "name");
        $this->assertEquals("name", $col['field']);			
        
        //drop it
        $this->adapter->removeColumn("users", "name");

        //verify it does not exist
        $col = $this->adapter->columnInfo("users", "name");
        $this->assertEquals(null, $col);			
    }

    public function testChangeColumn() {			
        //create it
        $this->adapter->executeDdl("CREATE TABLE `users` ( name varchar(20), age int(3) );");	

        //verify its type
        $col = $this->adapter->columnInfo("users", "name");
        $this->assertEquals('varchar(20)', $col['type'] );			
        $this->assertEquals('', $col['default'] );			
        
        //change it, add a default too!
        $this->adapter->changeColumn("users", "name", "string", array('default' => 'abc', 'limit' => 128));
        
        $col = $this->adapter->columnInfo("users", "name");
        $this->assertEquals('varchar(128)', $col['type'] );						
        $this->assertEquals('abc', $col['default'] );			
    }
		
    public function testAddIndex() {
        //create it
        $this->adapter->executeDdl("CREATE TABLE `users` ( name varchar(20), age int(3), title varchar(20) );");	
        $this->adapter->addIndex("users", "name");
        
        $this->assertEquals(true, $this->adapter->hasIndex("users", "name") );						
        $this->assertEquals(false, $this->adapter->hasIndex("users", "age") );								
        
        $this->adapter->addIndex("users", "age", array('unique' => true));
        $this->assertEquals(true, $this->adapter->hasIndex("users", "age") );								
        
        $this->adapter->addIndex("users", "title", array('name' => 'index_on_super_title'));
        $this->assertEquals(true, $this->adapter->hasIndex("users", "title", array('name' => 'index_on_super_title')));								
    }
		
    public function testMultiColumnIndex() {
        //create it
        $this->adapter->executeDdl("CREATE TABLE `users` ( name varchar(20), age int(3) );");	
        $this->adapter->addIndex("users", array("name", "age"));
        
        $this->assertEquals(true, $this->adapter->hasIndex("users", array("name", "age") ));						
        
        //drop it
        $this->adapter->removeIndex("users", array("name", "age"));
        $this->assertEquals(false, $this->adapter->hasIndex("users", array("name", "age") ));
    }

    public function testRemoveIndexWithDefaultIndexName() {
        //create it
        $this->adapter->executeDdl("CREATE TABLE `users` ( name varchar(20), age int(3) );");	
        $this->adapter->addIndex("users", "name");
        
        $this->assertEquals(true, $this->adapter->hasIndex("users", "name") );						
        
        //drop it
        $this->adapter->removeIndex("users", "name");
        $this->assertEquals(false, $this->adapter->hasIndex("users", "name") );						
    }

    public function testRemoveIndexWithCustomIndexName() {
        //create it
        $this->adapter->executeDdl("CREATE TABLE `users` ( name varchar(20), age int(3) );");	
        $this->adapter->addIndex("users", "name", array('name' => 'my_special_index'));
        
        $this->assertEquals(true, $this->adapter->hasIndex("users", "name", array('name' => 'my_special_index')) );						
        
        //drop it
        $this->adapter->removeIndex("users", "name", array('name' => 'my_special_index'));
        $this->assertEquals(false, $this->adapter->hasIndex("users", "name", array('name' => 'my_special_index')) );						
    }
	/*
    public function test_determine_query_type() {
        $q = 'SELECT * from users';
        $this->assertEquals(SQL_SELECT, $this->adapter->determine_query_type($q));

        $q = 'select * from users';
        $this->assertEquals(SQL_SELECT, $this->adapter->determine_query_type($q));

        $q = "INSERT INTO foo (name, age) VALUES ('foo bar', 28)";
        $this->assertEquals(SQL_INSERT, $this->adapter->determine_query_type($q));

        $q = "UPDATE foo SET name = 'bar'";
        $this->assertEquals(SQL_UPDATE, $this->adapter->determine_query_type($q));

        $q = 'DELETE FROM foo WHERE age > 100';
        $this->assertEquals(SQL_DELETE, $this->adapter->determine_query_type($q));

        $q = 'ALTER TABLE foo ADD COLUMN bar int(11)';
        $this->assertEquals(SQL_ALTER, $this->adapter->determine_query_type($q));

        $q = 'CREATE INDEX idx_foo ON foo(users)';
        $this->assertEquals(SQL_CREATE, $this->adapter->determine_query_type($q));

        $q = 'DROP TABLE foo';
        $this->assertEquals(SQL_DROP, $this->adapter->determine_query_type($q));

        $q = 'SET GLOBAL query_cache_size = 40000;';
        $this->assertEquals(SQL_SET, $this->adapter->determine_query_type($q));
    }
     */

    public function test_string_quoting() {
        $unquoted = "Hello Sam's";
        $quoted = "Hello Sam\'s";
        $this->assertEquals($quoted, $this->adapter->quoteString($unquoted));
    }
		
}//class
