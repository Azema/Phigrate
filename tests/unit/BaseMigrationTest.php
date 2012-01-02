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

class BaseMigrationTest extends PHPUnit_Framework_TestCase {
		
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
    }
		
    public function testCanCreateIndexWithCustomName() {
        //create it
        $this->adapter->executeDdl("CREATE TABLE `users` ( name varchar(20), age int(3) );");	
        $base = new Ruckusing_BaseMigration();
        $base->setAdapter($this->adapter);
        $base->addIndex("users", "name", array('name' => 'my_special_index'));
        
        //ensure it exists
        $this->assertEquals(true, $this->adapter->hasIndex("users", "name", array('name' => 'my_special_index')));
        
        //drop it
        $base->removeIndex("users", "name", array('name' => 'my_special_index'));
        $this->assertEquals(false, $this->adapter->hasIndex("users", "my_special_index") );
    }
}
