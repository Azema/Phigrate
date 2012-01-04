<?php

if(!defined('BASE')) {
  define('BASE', dirname(__FILE__) . '/..');
}
require_once BASE  . '/test_helper.php';
require_once RUCKUSING_BASE  . '/lib/classes/util/class.Ruckusing_MigratorUtil.php';
require_once RUCKUSING_BASE  . '/lib/classes/class.Ruckusing_BaseAdapter.php';
require_once RUCKUSING_BASE  . '/lib/classes/class.Ruckusing_IAdapter.php';
require_once RUCKUSING_BASE  . '/lib/classes/adapters/class.Ruckusing_MySQLAdapter.php';
require_once RUCKUSING_BASE  . '/config/config.inc.php';

define('RUCKUSING_TEST_HOME', RUCKUSING_BASE . '/tests');

class MigratorUtilTest extends PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        require RUCKUSING_BASE  . '/config/database.inc.php';
        if( !is_array($ruckusing_db_config) || !array_key_exists("test", $ruckusing_db_config)) {
            die("\n'test' DB is not defined in config/database.inc.php\n\n");
        }

        $this->test_db = $ruckusing_db_config['test'];
        //setup our log
        $this->logger = Ruckusing_Logger::instance(RUCKUSING_BASE . '/tests/logs/test.log');
        $this->logger->setPriority(Ruckusing_Logger::DEBUG);
    }
    
    protected function setUp()
    {
        $this->adapter = new Ruckusing_MySQLAdapter($this->test_db, $this->logger);
        $this->adapter->getLogger()->log("Test run started: " . date('Y-m-d g:ia T') );

        //create the schema table if necessary
        $this->adapter->createSchemaVersionTable();    
    }//setUp()
  
    protected function tearDown() {			
        //clear out any tables we populated
        $this->adapter->query('DELETE FROM ' . RUCKUSING_TS_SCHEMA_TBL_NAME);
    }

    private function insertDummyVersionData($data) {
        foreach($data as $version) {
            $insert_sql = sprintf(
                "INSERT INTO %s (version) VALUES ('%s')", 
                RUCKUSING_TS_SCHEMA_TBL_NAME, 
                $version
            );
            $this->adapter->query($insert_sql);
        }
    }
  
    private function clearDummyData() {
        $this->adapter->query('DELETE FROM ' . RUCKUSING_TS_SCHEMA_TBL_NAME);
    }
  
    public function testGetMaxVersion() {
        $migrator_util = new Ruckusing_MigratorUtil($this->adapter);

        $this->clearDummyData();
        $this->assertEquals(null, $migrator_util->getMaxVersion() );
        
        $this->insertDummyVersionData(array(3));
        $this->assertEquals("3", $migrator_util->getMaxVersion() );
        $this->clearDummyData();
    }

    public function testResolveCurrentVersionGoingUp() {
        $this->clearDummyData();
        $this->insertDummyVersionData(array(1));
        
        $migrator_util = new Ruckusing_MigratorUtil($this->adapter);
        $migrator_util->resolveCurrentVersion(3, 'up');
        
        $executed = $migrator_util->getExecutedMigrations();
        $this->assertEquals(true, in_array(3, $executed));
        $this->assertEquals(true, in_array(1, $executed));
        $this->assertEquals(false, in_array(2, $executed));
    }

    public function testResolveCurrentVersionGoingDown() {
        $this->clearDummyData();
        $this->insertDummyVersionData(array(1,2,3));
        
        $migrator_util = new Ruckusing_MigratorUtil($this->adapter);
        $migrator_util->resolveCurrentVersion(3, 'down');
        
        $executed = $migrator_util->getExecutedMigrations();
        $this->assertEquals(false, in_array(3, $executed) );
        $this->assertEquals(true, in_array(1, $executed) );
        $this->assertEquals(true, in_array(2, $executed) );
    }

    public function testGetRunnableMigrationsGoingUpNoTargetVersion() {
        $migrator_util      = new Ruckusing_MigratorUtil($this->adapter);
        $actual_up_files    = $migrator_util->getRunnableMigrations(RUCKUSING_MIGRATION_DIR, 'up', false);
        $expect_up_files = array(
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
        $this->assertEquals($expect_up_files, $actual_up_files);
    }
  
    public function testGetRunnableMigrationsGoingDownNoTargetVersion() {
        $migrator_util      = new Ruckusing_MigratorUtil($this->adapter);
        $actual_down_files  = $migrator_util->getRunnableMigrations(RUCKUSING_MIGRATION_DIR, 'down', false);
        $this->assertEquals(array(), $actual_down_files);
    }

    public function testGetRunnableMigrationsGoingUpWithTargetVersionNoCurrent() {
        $migrator_util      = new Ruckusing_MigratorUtil($this->adapter);
        $actual_up_files    = $migrator_util->getRunnableMigrations(RUCKUSING_MIGRATION_DIR, 'up', 3, false);
        $expect_up_files = array(
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
        $this->assertEquals($expect_up_files, $actual_up_files);
    }

    public function testGetRunnableMigrationsGoingUpWithTargetVersionWithCurrent() {
        $migrator_util      = new Ruckusing_MigratorUtil($this->adapter);
        //pretend we already executed version 1
        $this->insertDummyVersionData( array(1) );    
        $actual_up_files    = $migrator_util->getRunnableMigrations(RUCKUSING_MIGRATION_DIR, 'up', 3, false);
        $expect_up_files = array(
            array(
                'version' => 3,
                'class' 	=> 'AddIndexToBlogs',
                'file'		=> '003_AddIndexToBlogs.php',
            ),
        );  
        $this->assertEquals($expect_up_files, $actual_up_files);
        $this->clearDummyData();

        //now pre-register some migrations that we have already executed
        $this->insertDummyVersionData( array(1,3) );    
        $actual_up_files    = $migrator_util->getRunnableMigrations(RUCKUSING_MIGRATION_DIR, 'up', 3, false);
        $this->assertEquals(array(), $actual_up_files);
    }
  
    public function testGetRunnableMigrationsGoingDownWithTargetVersionNoCurrent() {
        $migrator_util      = new Ruckusing_MigratorUtil($this->adapter);
        $this->insertDummyVersionData( array(3, '20090122193325') );    
        $actual_down_files    = $migrator_util->getRunnableMigrations(RUCKUSING_MIGRATION_DIR, 'down', 1, false);
        $expect_down_files = array(
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
        $this->assertEquals($expect_down_files, $actual_down_files);

        $this->clearDummyData();

        $this->insertDummyVersionData( array(3) );    
        $actual_down_files    = $migrator_util->getRunnableMigrations(RUCKUSING_MIGRATION_DIR, 'down', 1, false);
        $expect_down_files = array(
            array(
                'version' => 3,
                'class' 	=> 'AddIndexToBlogs',
                'file'		=> '003_AddIndexToBlogs.php',
            ),
        );  
        $this->assertEquals($expect_down_files, $actual_down_files);

        //go all the way down!
        $this->clearDummyData();
        $this->insertDummyVersionData( array(1, 3, '20090122193325') );    
        $actual_down_files    = $migrator_util->getRunnableMigrations(RUCKUSING_MIGRATION_DIR, 'down', 0, false);
        $expect_down_files = array(
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
        $this->assertEquals($expect_down_files, $actual_down_files);    
    } //test_getRunnableMigrations_going_down_with_target_version_no_current;
} // class MigratorUtilTest
