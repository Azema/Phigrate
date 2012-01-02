<?php

if(!defined('BASE')) {
  define('BASE', dirname(__FILE__) . '/..');
}
require_once BASE  . '/test_helper.php';
require_once RUCKUSING_BASE  . '/lib/classes/util/class.Ruckusing_NamingUtil.php';

if(!defined('RUCKUSING_TEST_HOME')) {
  define('RUCKUSING_TEST_HOME', RUCKUSING_BASE . '/tests');
}

 
class NamingUtilTest extends PHPUnit_Framework_TestCase
{
    public function testTaskFromClassMethod() {
        $klass = "Ruckusing_DB_Schema";
        $this->assertEquals('db:schema', Ruckusing_NamingUtil::taskFromClassName($klass) );
    }

    public function testTaskToClassMethod() {
        $task_name = "db:schema";
        $this->assertEquals('Ruckusing_DB_Schema', Ruckusing_NamingUtil::taskToClassName($task_name) );
    }

    public function testClassNameFromFileName() {
        $klass = RUCKUSING_TEST_HOME . '/dummy/class.Ruckusing_DB_Setup.php';
        $this->assertEquals('Ruckusing_DB_Setup', Ruckusing_NamingUtil::classFromFileName($klass) );
    }

    public function testClassNameFromString() {
        $klass = 'class.Ruckusing_DB_Schema.php';
        $this->assertEquals('Ruckusing_DB_Schema', Ruckusing_NamingUtil::classFromFileName($klass) );
    }

    public function testClassFromMigrationFileName() {
        $klass = '001_CreateUsers.php';
        $this->assertEquals('CreateUsers', Ruckusing_NamingUtil::classFromMigrationFile($klass) );

        $klass = '120_AddIndexToPeopleTable.php';
        $this->assertEquals('AddIndexToPeopleTable', Ruckusing_NamingUtil::classFromMigrationFile($klass) );
    }

    public function testCamelcase() {
        $a = "add index to users";
        $this->assertEquals('AddIndexToUsers', Ruckusing_NamingUtil::camelcase($a) );

        $b = "add index to Users";
        $this->assertEquals('AddIndexToUsers', Ruckusing_NamingUtil::camelcase($b) );

        $c = "AddIndexToUsers";
        $this->assertEquals('AddIndexToUsers', Ruckusing_NamingUtil::camelcase($c) );
    }
		
    public function testUnderscore() {
        $this->assertEquals("users_and_children", Ruckusing_NamingUtil::underscore("users and children") );
        $this->assertEquals("animals", Ruckusing_NamingUtil::underscore("animals") );
        $this->assertEquals("bobby_pins", Ruckusing_NamingUtil::underscore("bobby!pins") );			
    }
		
    public function testIndexName() {
        $column = "first_name";
        $this->assertEquals("idx_users_first_name", Ruckusing_NamingUtil::indexName("users", $column));

        $column = "age";
        $this->assertEquals("idx_users_age", Ruckusing_NamingUtil::indexName("users", $column));

        $column = array('listing_id', 'review_id');
        $this->assertEquals("idx_users_listing_id_and_review_id", Ruckusing_NamingUtil::indexName("users", $column));
    }
}
