<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category  RuckusingMigrations
 * @package   Tasks
 * @author    Cody Caughlan <toolbag@gmail.com>
 * @copyright 2010-2011 Cody Caughlan
 * @license   GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/ruckus/ruckusing-migrations
 */

/**
 * @see Ruckusing_iTask 
 */
require_once RUCKUSING_BASE . '/lib/classes/task/class.Ruckusing_ITask.php';
/**
 * get config 
 */
require_once RUCKUSING_BASE . '/config/config.inc.php';
/**
 * @see Ruckusing_Exceptions
 */
require_once RUCKUSING_BASE . '/lib/classes/Ruckusing_exceptions.php';
/**
 * @see Ruckusing_MigratorUtil 
 */
require_once RUCKUSING_BASE . '/lib/classes/util/class.Ruckusing_MigratorUtil.php';
/**
 * @see Ruckusing_BaseMigration 
 */
require_once RUCKUSING_BASE . '/lib/classes/class.Ruckusing_BaseMigration.php';

/** @var integer */
define('STYLE_REGULAR', 1);
/** @var integer */
define('STYLE_OFFSET', 2);

/**
 * This is the primary work-horse method, it runs all migrations available,
 * up to the current version.
 *
 * @category  RuckusingMigrations
 * @package   Tasks
 * @author    Cody Caughlan <toolbag@gmail.com>
 * @copyright 2010-2011 Cody Caughlan
 * @license   GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_DB_Migrate implements Ruckusing_ITask
{
    /**
     * adapter 
     * 
     * @var Ruckusing_BaseAdapter
     */
    private $_adapter = null;

    /**
     * migrator util 
     * 
     * @var Ruckusing_MigratorUtil
     */
    private $_migratorUtil = null;

    /**
     * task args 
     * 
     * @var array
     */
    private $_taskArgs = array();

    /**
     * regexp 
     * 
     * @var string
     */
    private $_regexp = '/^(\d+)\_/';

    /**
     * debug 
     * 
     * @var boolean
     */
	private $_debug = false;
	
    /**
     * __construct 
     * 
     * @param Ruckusing_BaseAdapter $adapter Adapter RDBMS
     *
     * @return Ruckusing_DB_Migrate
     */
    function __construct($adapter)
    {
		$this->_adapter = $adapter;
        $this->_migratorUtil = new Ruckusing_MigratorUtil($this->_adapter);
	}
	
    /**
     * Primary task entry point
     * 
     * @param array $args Arguments of task
     *
     * @return void
     */
    public function execute($args)
    {
        $output = '';
        if (! $this->_adapter->supportsMigrations()) {
            die('This database does not support migrations.');
        }
		$this->_taskArgs = $args;
		echo 'Started: ' . date('Y-m-d g:ia T') . "\n\n";		
		echo "[db:migrate]: \n";
		try {
            // Check that the schema_version table exists, and if not, automatically create it
            $this->_verifyEnvironment();

			$targetVersion = null;
			$style = STYLE_REGULAR;
			
			//did the user specify an explicit version?
			if (array_key_exists('VERSION', $this->_taskArgs)) {
                $targetVersion = trim($this->_taskArgs['VERSION']);
			}

            // did the user specify a relative offset, e.g. "-2" or "+3" ?
			if ($targetVersion !== null 
                && preg_match('/^([\-\+])(\d+)$/', $targetVersion, $matches)
            ) {
			    if (count($matches) == 3) {
                    $direction = $matches[1] == '-' ? 'down' : 'up';
                    $offset = intval($matches[2]);
                    $style = STYLE_OFFSET;
                }
            }
			//determine our direction and target version
			$currentVersion = $this->_migratorUtil->getMaxVersion();
			if ($style == STYLE_REGULAR) {
                if (is_null($targetVersion)) {
                    $this->_prepareToMigrate($targetVersion, 'up');
                } elseif ($currentVersion > $targetVersion) {
                    $this->_prepareToMigrate($targetVersion, 'down');
                } else {
                    $this->_prepareToMigrate($targetVersion, 'up');
                }
            }
		  
            if ($style == STYLE_OFFSET) {
                $this->migrate_from_offset($offset, $currentVersion, $direction);
            }

            // Completed - display accumulated output
			if (! empty($output)) {
                echo $output . "\n\n";
            }
		} catch (Ruckusing_MissingSchemaInfoTableException $ex) {
            echo "\tSchema info table does not exist. "
                . "I tried creating it but failed. Check permissions.";
		} catch (Ruckusing_MissingMigrationDirException $ex) {
            echo "\tMigration directory does not exist: " 
                . RUCKUSING_MIGRATION_DIR;
		} catch (Ruckusing_Exception $ex) {
			die("\n\n" . $ex->getMessage() . "\n\n");
		}	
		echo "\n\nFinished: " . date('Y-m-d g:ia T') . "\n\n";			
	}
	
    /**
     * migrate from offset 
     * 
     * @param int    $offset         The offset
     * @param int    $currentVersion The current version
     * @param string $direction      Up or Down
     *
     * @return void
     */
    private function _migrateFromOffset($offset, $currentVersion, $direction)
    {
        //$migrations = $this->_migratorUtil->getRunnableMigrations(RUCKUSING_MIGRATION_DIR, $direction, null);
        $migrations = $this->_migratorUtil
            ->getMigrationFiles(RUCKUSING_MIGRATION_DIR, $direction);
        $versions = array();
        $currentIndex = -1;
        $nbMigrations = count($migrations);
        for ($i = 0; $i < $nbMigrations; $i++) {
            $versions[] = $migrations[$i]['version'];
            if ($migrations[$i]['version'] === $currentVersion) {
                $currentIndex = $i;
            }
        }
        if ($this->_debug == true) {
            print_r($migrations);
            echo "\ncurrent_index: " . $currentIndex . "\n";
            echo "\ncurrentVersion: " . $currentVersion . "\n";
            echo "\noffset: " . $offset . "\n";
        }
        
        // If we are not at the bottom then adjust our index (to satisfy array_slice)
        if ($currentIndex == -1) {
            $currentIndex = 0;
        } else {
            $currentIndex += 1;
        }
        
        // check to see if we have enough migrations to run - the user
        // might have asked to run more than we have available
        $available = array_slice($migrations, $currentIndex, $offset);
        // echo "\n------------- AVAILABLE ------------------\n";
        // print_r($available);
        if (count($available) != $offset) {
            $names = array();
            foreach ($available as $a) { 
                $names[] = $a['file'];
            }
            $numAvailable = count($names);
            $prefix = $direction == 'down' ? '-' : '+';
            echo "\n\nCannot migrate " . strtoupper($direction) 
                . " via offset \"{$prefix}{$offset}\": "
                . "not enough migrations exist to execute.\n";
            echo "You asked for ({$offset}) but only available are "
                . "({$numAvailable}): " . implode(", ", $names) . "\n\n";
        } else {
            // run em
            $target = end($available);
            if ($this->_debug == true) {
                echo "\n------------- TARGET ------------------\n";
                print_r($target);
            }
            $this->_prepareToMigrate($target['version'], $direction);
        }
    }

    /**
     * prepare to migrate 
     * 
     * @param string $destination The version desired
     * @param string $direction   Up or Down
     *
     * @return void
     */
    private function _prepareToMigrate($destination, $direction)
    {
        try {
            echo "\tMigrating " . strtoupper($direction);
            if (! is_null($destination)) {
                echo " to: {$destination}\n";				
            } else {
                echo ":\n";
            }
            $migrations = $this->_migratorUtil
                ->getRunnableMigrations(
                    RUCKUSING_MIGRATION_DIR, 
                    $direction, 
                    $destination
                );			
            if (count($migrations) == 0) {
                return "\nNo relevant migrations to run. Exiting...\n";
            }
            $result = $this->_runMigrations(
                $migrations, 
                $direction
            );
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * run migrations 
     * 
     * @param array                   $migrations   The table of migration files
     * @param Ruckusing_BaseMigration $targetMethod The migration class
     *
     * @return void
     */
    private function _runMigrations($migrations, $targetMethod)
    {
		$last_version = -1;
		foreach ($migrations as $file) {
            $fullPath = RUCKUSING_MIGRATION_DIR . '/' . $file['file'];
            if (! is_file($fullPath) || ! is_readable($fullPath)) {
                continue;
            }
            include_once $fullPath;
            $klass = Ruckusing_NamingUtil::classFromMigrationFile($file['file']);
            $obj = new $klass();
            $refl = new ReflectionObject($obj);
            if ($refl->hasMethod($targetMethod)) {
                $obj->setAdapter($this->_adapter);
                $start = $this->_startTimer();
                try {
                    //start transaction
                    $this->_adapter->startTransaction();
                    $result =  $obj->$target_method();
                    //successfully ran migration, update our version and commit
                    $this->_migratorUtil
                        ->resolveCurrentVersion($file['version'], $target_method);
                    $this->_adapter->commitTransaction();
                } catch (Exception $e) {
                    $this->_adapter->rollbackTransaction();
                    //wrap the caught exception in our own
                    $ex = new Exception(
                        sprintf('%s - %s', $file['class'], $e->getMessage())
                    );
                    throw $ex;
                }
                $end = $this->_endTimer();
                $diff = $this->_diffTimer($start, $end);
                printf("========= %s ======== (%.2f)\n", $file['class'], $diff);
                $last_version = $file['version'];
                $exec = true;
            } else {
                trigger_error(
                    "ERROR: {$klass} does not have a "
                    . "'{$target_method}' method defined!"
                );
            }
		}//foreach
		//update the schema info
		return array('last_version' => $last_version);
	}
	
    /**
     * start timer 
     * 
     * @return integer
     */
    private function _startTimer()
    {
		return microtime(true);
	}

    /**
     * end timer 
     * 
     * @return integer
     */
    private function _endTimer()
    {
		return microtime(true);
	}
	
    /**
     * diff timer 
     * 
     * @param integer $s Start time
     * @param integer $e End time
     *
     * @return integer
     */
    private function _diffTimer($s, $e)
    {
		return $e - $s;
	}
	
    /**
     * verify environment 
     * 
     * @return void
     */
    private function _verifyEnvironment()
    {
        if (! $this->_adapter->tableExists(RUCKUSING_TS_SCHEMA_TBL_NAME)) {
                echo "\n\tSchema version table does not exist. Auto-creating.";
            $this->_autoCreateSchemaInfoTable();
        }	 
    }
	
    /**
     * auto_create_schema_info_table 
     * 
     * @return void
     */
    private function _autoCreateSchemaInfoTable()
    {
        try {
            echo sprintf(
                "\n\tCreating schema version table: %s", 
                RUCKUSING_TS_SCHEMA_TBL_NAME . "\n\n"
            );
            $this->_adapter->createSchemaVersionTable();
            return true;
        } catch (Exception $e) {
            die(
                "\nError auto-creating 'schema_info' table: "
                . $e->getMessage() . "\n\n"
            );
        }
	}
}
