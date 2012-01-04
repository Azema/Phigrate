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
 * @see Ruckusing_ITask 
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
     * _logger 
     * 
     * @var Ruckusing_Logger
     */
    private $_logger;
	
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
        $this->_logger = $adapter->getLogger();
        $this->_logger->debug(__METHOD__ . ' Start');
        $this->_migratorUtil = new Ruckusing_MigratorUtil($this->_adapter);
        $this->_logger->debug(__METHOD__ . ' End');
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
        $this->_logger->debug(__METHOD__ . ' Start');
        $output = '';
        if (! $this->_adapter->supportsMigrations()) {
            $msg = 'This database does not support migrations.';
            $this->_logger->warn($msg);
            die($msg);
        }
        $this->_taskArgs = $args;
        $this->_logger->debug('Args of task: ' . var_export($args, true));
		echo 'Started: ' . date('Y-m-d g:ia T') . PHP_EOL . PHP_EOL;
		echo '[db:migrate]: ' . PHP_EOL;
		try {
            // Check that the schema_version table exists, 
            // and if not, automatically create it
            $this->_verifyEnvironment();

			$targetVersion = null;
			$style = STYLE_REGULAR;
			
			//did the user specify an explicit version?
			if (array_key_exists('VERSION', $this->_taskArgs)) {
                $targetVersion = trim($this->_taskArgs['VERSION']);
                $this->_logger->info('Version specified: ' . $targetVersion);
			}

            // did the user specify a relative offset, e.g. "-2" or "+3" ?
			if ($targetVersion !== null 
                && preg_match('/^([\+-])(\d+)$/', $targetVersion, $matches)
            ) {
			    if (count($matches) == 3) {
                    $direction = ($matches[1] === '-') ? 'down' : 'up';
                    $offset = intval($matches[2]);
                    $style = STYLE_OFFSET;
                    $this->_logger->debug(
                        'direction: ' . $direction . ' - offset: ' . $offset
                    );
                }
            }
			//determine our direction and target version
            $currentVersion = $this->_migratorUtil->getMaxVersion();

			if ($style == STYLE_REGULAR) {
                $this->_logger->debug('STYLE REGULAR');
                if (is_null($targetVersion)) {
                    // Up to max version
                    $this->_prepareToMigrate($targetVersion, 'up');
                } elseif ($currentVersion > $targetVersion) {
                    // Down to version specified by user
                    $this->_prepareToMigrate($targetVersion, 'down');
                } else {
                    // Up to version specified by user
                    $this->_prepareToMigrate($targetVersion, 'up');
                }
            } elseif ($style == STYLE_OFFSET) {
                $this->_logger->debug('STYLE OFFSET');
                $this->_migrateFromOffset($offset, $currentVersion, $direction);
            }
		} catch (Ruckusing_MissingSchemaInfoTableException $ex) {
            $this->_logger->warn('No schema info table.');
            echo "\tSchema info table does not exist. "
                . "I tried creating it but failed. Check permissions.";
        } catch (Ruckusing_MissingMigrationDirException $ex) {
            $this->_logger->warn('Migration directory not exist: ' . RUCKUSING_MIGRATION_DIR);
            echo "\tMigration directory does not exist: " 
                . RUCKUSING_MIGRATION_DIR;
		} catch (Ruckusing_Exception $ex) {
            $this->_logger->err('Exception: ' . $ex->getMessage());
			die("\n\n" . $ex->getMessage() . "\n\n");
		}	
		echo "\n\nFinished: " . date('Y-m-d g:ia T') . "\n\n";			
        $this->_logger->debug(__METHOD__ . ' End');
	}

    /**
     * Return the usage of the task
     * 
     * @return string
     */
    public function help()
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        $output =<<<USAGE
Task: \033[36mdb:migrate\033[0m [\033[33mVERSION\033[0m]

The primary purpose of the framework is to run migrations, and the 
execution of migrations is all handled by just a regular ol' task.

\t\033[33mVERSION\033[0m can be specified to go up (or down) to a specific 
\tversion, based on the current version. If not specified, 
\tall migrations greater than the current database version 
\twill be executed.

\t\033[37mExample A:\033[0m The database is fresh and empty, assuming there 
\tare 5 actual migrations, but only the first two should be run.

\t\t\033[35mphp main.php db:migrate VERSION=20101006114707\033[0m

\t\033[37mExample B:\033[0m The current version of the DB is 20101006114707 
\tand we want to go down to 20100921114643

\t\t\033[35mphp main.php db:migrate VERSION=20100921114643\033[0m

\t\033[37mExample C:\033[0m You can also use relative number of revisions 
\t(positive migrate up, negative migrate down).

\t\t\033[35mphp main.php db:migrate VERSION=-2\033[0m

USAGE;
        $this->_logger->debug(__METHOD__ . ' End');
        return $output;
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
        $this->_logger->debug(__METHOD__ . ' Start');
        $this->_logger->debug(
            'offset: ' . $offset . ' - currentVersion: ' . $currentVersion
            . ' - direction: ' . $direction
        );
        //$migrations = $this->_migratorUtil->getRunnableMigrations(RUCKUSING_MIGRATION_DIR, $direction, null);
        $migrations = $this->_migratorUtil
            ->getMigrationFiles(RUCKUSING_MIGRATION_DIR, $direction);
        $versions = array();
        $currentIndex = -1;
        $nbMigrations = count($migrations);
        $this->_logger->debug('Nb migrations: ' . $nbMigrations);
        for ($i = 0; $i < $nbMigrations; $i++) {
            $versions[] = $migrations[$i]['version'];
            if ($migrations[$i]['version'] === $currentVersion) {
                $currentIndex = $i;
            }
        }
        $this->_logger->debug('current index: ' . $currentIndex);
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
        $this->_logger->debug('Available: ' . var_export($available, true));
        // echo "\n------------- AVAILABLE ------------------\n";
        // print_r($available);
        if (count($available) != $offset) {
            $names = array();
            foreach ($available as $a) {
                $names[] = $a['file'];
            }
            $numAvailable = count($names);
            $prefix = $direction == 'down' ? '-' : '+';
            $this->_logger->warn(
                'Cannot migration ' . $direction . ' via offset ' 
                . $prefix . $offset
            );
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
        $this->_logger->debug(__METHOD__ . ' End');
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
        $this->_logger->debug(__METHOD__ . ' Start');
        $this->_logger->debug(
            'Destination: ' . $destination 
            . ' - direction: ' . $direction
        );
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
                $msg = 'No relevant migrations to run. Exiting...';
                $this->_logger->info($msg);
                return "\n{$msg}\n";
            }
            $result = $this->_runMigrations(
                $migrations, 
                $direction
            );
        } catch (Exception $ex) {
            $this->_logger->err('Exception: ' . $ex->getMessage());
            throw $ex;
        }
        $this->_logger->debug(__METHOD__ . ' End');
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
        $this->_logger->debug(__METHOD__ . ' Start');
		$last_version = -1;
		foreach ($migrations as $file) {
            $fullPath = RUCKUSING_MIGRATION_DIR . '/' . $file['file'];
            $this->_logger->debug('file: ' . var_export($file, true));
            if (! is_file($fullPath) || ! is_readable($fullPath)) {
                continue;
            }
            $this->_logger->debug('include file: ' . $file['file']);
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
                    $result =  $obj->$targetMethod();
                    //successfully ran migration, update our version and commit
                    $this->_migratorUtil
                        ->resolveCurrentVersion($file['version'], $targetMethod);
                    $this->_adapter->commitTransaction();
                } catch (Exception $e) {
                    $this->_adapter->rollbackTransaction();
                    //wrap the caught exception in our own
                    $msg = sprintf('%s - %s', $file['class'], $e->getMessage());
                    $this->_logger->err($msg);
                    $ex = new Exception($msg);
                    throw $ex;
                }
                $end = $this->_endTimer();
                $diff = $this->_diffTimer($start, $end);
                printf("========= %s ======== (%.2f)\n", $file['class'], $diff);
                $lastVersion = $file['version'];
                $this->_logger->info('last_version: ' . $lastVersion);
            } else {
                $msg = $klass . ' does not have ' . $targetMethod 
                    . 'method defined!';
                $this->_logger->warn($msg);
                trigger_error('ERROR: ' . $msg);
            }
		}//foreach
        //update the schema info
        $this->_logger->debug(__METHOD__ . ' End');
		return array('last_version' => $lastVersion);
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
        $this->_logger->debug(__METHOD__ . ' Start');
        $this->_logger->debug('start: ' . $s . ' - end: ' . $e);
        $result = $e - $s;
        $this->_logger->debug('result: ' . $result);
        $this->_logger->debug(__METHOD__ . ' End');
		return $result;
	}
	
    /**
     * verify environment 
     * 
     * @return void
     */
    private function _verifyEnvironment()
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        if (! $this->_adapter->tableExists(RUCKUSING_TS_SCHEMA_TBL_NAME)) {
            $this->_logger
                ->info('Schema version table does not exist. Auto-creating.');
            echo "\n\tSchema version table does not exist. Auto-creating.";
            $this->_autoCreateSchemaInfoTable();
        }
        $this->_logger->debug(__METHOD__ . ' End');
    }
	
    /**
     * auto_create_schema_info_table 
     * 
     * @return void
     */
    private function _autoCreateSchemaInfoTable()
    {
        $this->_logger->debug(__METHOD__ . ' Start');
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
        $this->_logger->debug(__METHOD__ . ' End');
	}
}
