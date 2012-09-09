<?php

/**
 * Generated by PHPUnit_SkeletonGenerator on 2012-01-16 at 07:58:12.
 *
 * @group Phigrate_Util
 */
class Phigrate_Util_MigratorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Phigrate_Util_Migrator
     */
    protected $object;

    /**
     * @var utilAdapterMock
     */
    protected $_adapter;

    public function __construct()
    {
        $this->_adapter = new utilAdapterMock(array(), '');
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        require_once 'Phigrate/Util/Migrator.php';
        $this->object = new Phigrate_Util_Migrator($this->_adapter);
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
            new Phigrate_Util_Migrator('wrong adapter');
            $this->fail('Adapter not implement Phigrate_Adapter_Base');
        } catch (Phigrate_Exception_Argument $e) {
            $msg = 'adapter must be implement Phigrate_Adapter_Base!';
            $this->assertEquals($msg, $e->getMessage());
        }
        $migrator = new Phigrate_Util_Migrator($this->_adapter);
        $this->assertInstanceOf('Phigrate_Util_Migrator', $migrator);
    }

    public function testGetMaxVersion()
    {
        $versions = array();
        $this->_adapter->versions = $versions;
        $this->assertNull($this->object->getMaxVersion());
        $versions = array(
            array(
                'version' => 1,
                'class' 	=> 'CreateUsers',
                'file'		=> '001_CreateUsers.php',
            ),
        );
        $this->_adapter->versions = $versions;
        $actual = $this->object->getMaxVersion();
        $this->assertNotNull($actual);
        $this->assertEquals('1', $actual);
        $versions = array(
            array(
                'version' => 1,
                'class' 	=> 'CreateUsers',
                'file'		=> '001_CreateUsers.php',
            ),
            array(
                'version' => 2,
                'class'   => 'AddNewTable',
                'file'    => '002_AddNewTable.php',
            ),
            array(
                'version' => 3,
                'class' 	=> 'AddIndexToBlogs',
                'file'		=> '003_AddIndexToBlogs.php',
            ),
        );
        $this->_adapter->versions = $versions;
        $actual = $this->object->getMaxVersion();
        $this->assertNotNull($actual);
        $this->assertEquals('3', $actual);
    }

    public function testGenerateTimestamp()
    {
        $actual = $this->object->generateTimestamp();
        $expected = gmdate('YmdHi', time());
        $this->assertInternalType('string', $actual);
        $this->assertRegExp('/^'.$expected.'\d{2}$/', $actual);
    }

    public function testResolveCurrentVersion()
    {
        $this->assertNull($this->_adapter->currentVersion);
        $actual = $this->object->resolveCurrentVersion('2.1', 'up');
        $this->assertNotNull($this->_adapter->currentVersion);
        $this->assertEquals($this->_adapter->currentVersion, '2.1');
        $this->_adapter->currentVersion = null;
        $this->assertNull($this->_adapter->removeVersion);
        $actual = $this->object->resolveCurrentVersion('2.1', 'down');
        $this->assertNotNull($this->_adapter->removeVersion);
        $this->assertEquals($this->_adapter->removeVersion, '2.1');
        $this->_adapter->removeVersion = null;
        $this->assertNull($this->_adapter->currentVersion);
        $actual = $this->object->resolveCurrentVersion('4.1', 'UP');
        $this->assertNotNull($this->_adapter->currentVersion);
        $this->assertEquals($this->_adapter->currentVersion, '4.1');
        $this->_adapter->currentVersion = null;
        $this->assertNull($this->_adapter->removeVersion);
        $actual = $this->object->resolveCurrentVersion('2.3', 'DOWN');
        $this->assertNotNull($this->_adapter->removeVersion);
        $this->assertEquals($this->_adapter->removeVersion, '2.3');
        $this->_adapter->removeVersion = null;
    }

    public function testGetRunnableMigrationsGoingUpNoTargetVersion()
    {
        $actualUpFiles = $this->object->getRunnableMigrations(
            PHIGRATE_MIGRATION_DIR,
            'up',
            false
        );
        $expectUpFiles = array(
            array(
                'version' => 1,
                'class' 	=> 'CreateUsers',
                'file'		=> '001_CreateUsers.php',
            ),
            array(
                'version' => 3,
                'class' 	=> 'AddIndexToBlogs',
                'file'		=> '003_AddIndexToBlogs.php',
            ),
            array(
                'version' => '20090122193325',
                'class'   => 'AddNewTable',
                'file'    => '20090122193325_AddNewTable.php',
            ),
        );  
        $this->assertEquals($expectUpFiles, $actualUpFiles);
    }
  
    public function testGetRunnableMigrationsGoingDownNoTargetVersion()
    {
        $actualDownFiles = $this->object->getRunnableMigrations(
            PHIGRATE_MIGRATION_DIR,
            'down',
            false
        );
        $this->assertEquals(array(), $actualDownFiles);
    }

    public function testGetRunnableMigrationsGoingUpWithTargetVersionNoCurrent()
    {
        $actualUpFiles = $this->object->getRunnableMigrations(
            PHIGRATE_MIGRATION_DIR,
            'up',
            3,
            false
        );
        $expectUpFiles = array(
            array(
                'version' => 1,
                'class' 	=> 'CreateUsers',
                'file'		=> '001_CreateUsers.php',
            ),
            array(
                'version' => 3,
                'class' 	=> 'AddIndexToBlogs',
                'file'		=> '003_AddIndexToBlogs.php',
            ),
        );  
        $this->assertEquals($expectUpFiles, $actualUpFiles);
    }

    public function testGetRunnableMigrationsGoingDownWithTargetVersionNoCurrent()
    {
        $expectDownFiles = array(
            array(
                'version' => '20090122193325',
                'class'   => 'AddNewTable',
                'file'    => '20090122193325_AddNewTable.php',
            ),
            array(
                'version' => 3,
                'class' 	=> 'AddIndexToBlogs',
                'file'		=> '003_AddIndexToBlogs.php',
            ),
        );
        $this->_adapter->versions = $expectDownFiles;
        $actualDownFiles    = $this->object->getRunnableMigrations(
            PHIGRATE_MIGRATION_DIR,
            'down',
            1,
            false
        );
        $this->assertEquals($expectDownFiles, $actualDownFiles);

        $this->_adapter->versions = array();

        $expectDownFiles = array(
            array(
                'version' => 3,
                'class'	  => 'AddIndexToBlogs',
                'file'    => '003_AddIndexToBlogs.php',
            ),
        );
        $this->_adapter->versions = $expectDownFiles;
        $actualDownFiles = $this->object->getRunnableMigrations(
            PHIGRATE_MIGRATION_DIR,
            'down',
            1,
            false
        );
        $this->assertEquals($expectDownFiles, $actualDownFiles);

        //go all the way down!
        $this->_adapter->versions = array();
        $expectDownFiles = array(
            array(
                'version' => '20090122193325',
                'class'   => 'AddNewTable',
                'file'    => '20090122193325_AddNewTable.php',
            ),
            array(
                'version' => 3,
                'class' 	=> 'AddIndexToBlogs',
                'file'		=> '003_AddIndexToBlogs.php',
            ),
            array(
                'version' => 1,
                'class' 	=> 'CreateUsers',
                'file'		=> '001_CreateUsers.php',
            ),
        );
        $this->_adapter->versions = $expectDownFiles;
        $actualDownFiles = $this->object->getRunnableMigrations(
            PHIGRATE_MIGRATION_DIR,
            'down',
            0,
            false
        );
        $this->assertEquals($expectDownFiles, $actualDownFiles);
        $expectDownFiles = array(
            array(
                'version' => 3,
                'class'   => 'AddIndexToBlogs',
                'file'    => '003_AddIndexToBlogs.php',
            ),
        );
        $this->_adapter->versions = $expectDownFiles;
        $actualDownFiles = $this->object->getRunnableMigrations(
            PHIGRATE_MIGRATION_DIR,
            'down',
            1,
            true
        );
        $this->assertEquals($expectDownFiles, $actualDownFiles);
        $actualDownFiles = $this->object->getRunnableMigrations(
            PHIGRATE_MIGRATION_DIR,
            'down',
            1,
            true
        );
        $this->assertEquals($expectDownFiles, $actualDownFiles);
        require_once 'Phigrate/Exception/InvalidTargetMigration.php';
        try {
            $destination = 5;
            $this->object->getRunnableMigrations(
                PHIGRATE_MIGRATION_DIR,
                'down',
                $destination,
                false
            );
            $this->fail('Target version does not exists in set migration');
        } catch (Phigrate_Exception_InvalidTargetMigration $e) {
            $msg = 'Could not find target version ' . $destination
                . ' in set of migrations.';
            $this->assertEquals($msg, $e->getMessage());
        }

    }

    public function testGetRunnableMigrationsGoingUp()
    {
        $versions = array(
            array(
                'version' => 1,
                'class' 	=> 'CreateUsers',
                'file'		=> '001_CreateUsers.php',
            ),
            array(
                'version' => 3,
                'class' 	=> 'AddIndexToBlogs',
                'file'		=> '003_AddIndexToBlogs.php',
            ),
        );
        $this->_adapter->versions = $versions;
        $actualDownFiles    = $this->object->getRunnableMigrations(
            PHIGRATE_MIGRATION_DIR,
            'up',
            '20090122193325',
            false
        );
        $expectDownFiles = array(
            array(
                'version' => '20090122193325',
                'class'   => 'AddNewTable',
                'file'    => '20090122193325_AddNewTable.php',
            ),
        );
        $this->assertEquals($expectDownFiles, $actualDownFiles);
    }

    public function testGetExecutedMigrations()
    {
        $this->_adapter->versions = array();
        $expectDownFiles = array(
            array(
                'version' => '20090122193325',
                'class'   => 'AddNewTable',
                'file'    => '20090122193325_AddNewTable.php',
            ),
            array(
                'version' => 3,
                'class' 	=> 'AddIndexToBlogs',
                'file'		=> '003_AddIndexToBlogs.php',
            ),
            array(
                'version' => 1,
                'class' 	=> 'CreateUsers',
                'file'		=> '001_CreateUsers.php',
            ),
        );
        $this->_adapter->versions = $expectDownFiles;
        $expectedVersions = array(1, 3, '20090122193325');
        $actualMigrateVersions = $this->object->getExecutedMigrations();
        $this->assertEquals($expectedVersions, $actualMigrateVersions);
        $this->_adapter->versions = array();
        $expectDownFiles = array();
        $this->_adapter->versions = $expectDownFiles;
        $expectedVersions = array();
        $actualMigrateVersions = $this->object->getExecutedMigrations();
        $this->assertEquals($expectedVersions, $actualMigrateVersions);
    }

    public function testGetMigrationFiles()
    {
        require_once 'Phigrate/Exception/InvalidMigrationDir.php';
        try {
            $directory = '/tmp/migrate';
            $this->object->getMigrationFiles($directory, 'up');
            $this->fail('The directory of migration files is incorrect!');
        } catch (Phigrate_Exception_InvalidMigrationDir $e) {
            $msg = 'Phigrate_Util_Migrator - (' . $directory 
                . ') is not a directory.';
            $this->assertEquals($msg, $e->getMessage());
        }
        $actualFiles = $this->object->getMigrationFiles('/tmp', 'up');
        $this->assertEmpty($actualFiles);
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
