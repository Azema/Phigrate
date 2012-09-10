<?php

/**
 * Generated by PHPUnit_SkeletonGenerator on 2012-01-18 at 06:37:08.
 */
class Phigrate_Util_NamingTest extends PHPUnit_Framework_TestCase
{
    /**
     */
    public function testTaskNameFromNamespaceAndBasename()
    {
        try {
            Phigrate_Util_Naming::taskNameFromNamespaceAndBasename('', 'migrate');
            $this->fail('The namespace must not be empty');
        } catch (Phigrate_Exception_Argument $e) {
            $msg = 'The arguments must not be empty';
            $this->assertEquals($msg, $e->getMessage());
        }
        try {
            Phigrate_Util_Naming::taskNameFromNamespaceAndBasename('db', '');
            $this->fail('The basename must not be empty');
        } catch (Phigrate_Exception_Argument $e) {
            $msg = 'The arguments must not be empty';
            $this->assertEquals($msg, $e->getMessage());
        }
        $actual = Phigrate_Util_Naming::taskNameFromNamespaceAndBasename('DB', 'Migrate');
        $this->assertEquals('db:migrate', $actual);
    }

    /**
     */
    public function testTaskFromClassName()
    {
        $className = 'Task_DB_Migrate';
        $expected = 'db:migrate';
        $actual = Phigrate_Util_Naming::taskFromClassName($className);
        $this->assertEquals($expected, $actual);
        try {
            Phigrate_Util_Naming::taskFromClassName('Db_Migrate');
            $this->fail(
                'The class name must be start with '
                . Phigrate_Util_Naming::CLASS_NS_PREFIX
            );
        } catch (Phigrate_Exception_Argument $e) {
            $msg = 'The class name must start with '
                . Phigrate_Util_Naming::CLASS_NS_PREFIX;
            $this->assertEquals($msg, $e->getMessage());
        }
    }

    /**
     */
    public function testTaskToClassName()
    {
        $taskName = 'db:migrate';
        $expected = 'Task_Db_Migrate';
        $actual = Phigrate_Util_Naming::taskToClassName($taskName);
        $this->assertEquals($expected, $actual);
        try {
            Phigrate_Util_Naming::taskToClassName('');
            $this->fail('The task name must not be empty');
        } catch (Phigrate_Exception_Argument $e) {
            $msg = 'Task name () must be contains ":"';
            $this->assertEquals($msg, $e->getMessage());
        }
        try {
            Phigrate_Util_Naming::taskToClassName('migrate');
            $this->fail('The task name must be contains ":"');
        } catch (Phigrate_Exception_Argument $e) {
            $msg = 'Task name (migrate) must be contains ":"';
            $this->assertEquals($msg, $e->getMessage());
        }
    }

    /**
     */
    public function testClassFromFileName()
    {
        $os = php_uname('s');

        if (strtoupper(substr($os, 0, 3)) === 'WIN') {       
            $filename = 'c:\\tmp\\Task\\Db\\Migrate.php';
            $expected = 'Task_Db_Migrate';
            $actual = Phigrate_Util_Naming::classFromFileName($filename);
            $this->assertEquals($expected, $actual);
        } else {
            $filename = '/tmp/Task/Db/Migrate.php';
            $expected = 'Task_Db_Migrate';
            $actual = Phigrate_Util_Naming::classFromFileName($filename);
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     */
    public function testClassFromMigrationFile()
    {
        $fileName = '20090122193325_AddNewTable.php';
        $expected = 'AddNewTable';
        $actual = Phigrate_Util_Naming::classFromMigrationFile($fileName);
        $this->assertEquals($expected, $actual);
        $this->assertFalse(
            Phigrate_Util_Naming::classFromMigrationFile('AddNewTable.php')
        );
        $this->assertFalse(
            Phigrate_Util_Naming::classFromMigrationFile('20090122193325.php')
        );
        $klass = '001_CreateUsers.php';
        $this->assertEquals(
            'CreateUsers',
            Phigrate_Util_Naming::classFromMigrationFile($klass)
        );

        $klass = '120_AddIndexToPeopleTable.php';
        $this->assertEquals(
            'AddIndexToPeopleTable',
            Phigrate_Util_Naming::classFromMigrationFile($klass)
        );
    }

    /**
     */
    public function testCamelcase()
    {
        $a = 'add index to users';
        $this->assertEquals(
            'AddIndexToUsers', Phigrate_Util_Naming::camelcase($a)
        );

        $b = 'add index to Users';
        $this->assertEquals(
            'AddIndexToUsers', Phigrate_Util_Naming::camelcase($b)
        );

        $c = 'AddIndexToUsers';
        $this->assertEquals(
            'AddIndexToUsers', Phigrate_Util_Naming::camelcase($c)
        );

        $d = 'AddindextoUsers';
        $this->assertEquals(
            'AddindextoUsers', Phigrate_Util_Naming::camelcase($d)
        );
    }

    /**
     */
    public function testUnderscore()
    {
        $this->assertEquals(
            'users_and_children',
            Phigrate_Util_Naming::underscore('users and children')
        );
        $this->assertEquals(
            'animals',
            Phigrate_Util_Naming::underscore('animals')
        );
        $this->assertEquals(
            'bobby_pins',
            Phigrate_Util_Naming::underscore('bobby!pins')
        );
        $this->assertEquals(
            'bobby_pins',
            Phigrate_Util_Naming::underscore('bobby!&pins')
        );
    }

    /**
     */
    public function testIndexName()
    {
        $column = 'first_name';
        $this->assertEquals(
            'idx_users_first_name',
            Phigrate_Util_Naming::indexName('users', $column)
        );

        $column = 'age';
        $this->assertEquals(
            'idx_users_age',
            Phigrate_Util_Naming::indexName('users', $column)
        );

        $column = array('listing_id', 'review_id');
        $this->assertEquals(
            'idx_users_listing_id_and_review_id',
            Phigrate_Util_Naming::indexName('users', $column)
        );

        $column = array('listing_id', 'review_id');
        $this->assertEquals(
            'idx_users_addresses_listing_id_and_review_id',
            Phigrate_Util_Naming::indexName('users__addresses', $column)
        );

        $column = 'listing__id';
        $this->assertEquals(
            'idx_users_addresses_listing_id',
            Phigrate_Util_Naming::indexName('users__addresses', $column)
        );
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */