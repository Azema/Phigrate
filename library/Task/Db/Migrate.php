<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
 * @package    Task
 * @subpackage Db
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */

/**
 * @see Task_Base
 */
require_once 'Task/Base.php';

/**
 * @see Ruckusing_Task_ITask
 */
require_once 'Ruckusing/Task/ITask.php';

/**
 * This is the primary work-horse method, it runs all migrations available,
 * up to the current version.
 *
 * @category   RuckusingMigrations
 * @package    Task
 * @subpackage Db
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Task_Db_Migrate extends Task_Base implements Ruckusing_Task_ITask
{
    /** @var integer */
    const STYLE_REGULAR = 1;
    /** @var integer */
    const STYLE_OFFSET = 2;

    /**
     * migrator util
     *
     * @var Ruckusing_Util_Migrator
     */
    private $_migratorUtil = null;

    /**
     * Return executed string
     *
     * @var string
     */
    protected $_return = '';

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
        if (! $this->_adapter->supportsMigrations()) {
            $msg = 'This database does not support migrations.';
            $this->_logger->warn($msg);
            require_once 'Ruckusing/Exception/Task.php';
            throw new Ruckusing_Exception_Task($msg);
        }
        $this->_taskArgs = $args;
        $this->_logger->debug('Args of task: ' . var_export($args, true));
        $this->_return = 'Started: ' . date('Y-m-d g:ia T') . PHP_EOL . PHP_EOL
            . '[db:migrate]:' . PHP_EOL;
        try {
            // Check that the schema_version table exists,
            // and if not, automatically create it
            $this->_verifyEnvironment();

            $this->_migratorUtil = new Ruckusing_Util_Migrator($this->_adapter);

            $targetVersion = null;
            $style = self::STYLE_REGULAR;

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
                    $style = self::STYLE_OFFSET;
                    $this->_logger->debug(
                        '[OFFSET] direction: ' . $direction . ' - offset: ' . $offset
                    );
                }
            }
            //determine our direction and target version
            $currentVersion = $this->_migratorUtil->getMaxVersion();

            if ($style == self::STYLE_REGULAR) {
                $this->_logger->debug('STYLE REGULAR');
                // Up to max version            // Up to version specified by user
                if (is_null($targetVersion) || $currentVersion <= $targetVersion) {
                    $this->_prepareToMigrate($targetVersion, 'up');
                } elseif ($currentVersion > $targetVersion) {
                    // Down to version specified by user
                    $this->_prepareToMigrate($targetVersion, 'down');
                }
            } elseif ($style == self::STYLE_OFFSET) {
                $this->_logger->debug('STYLE OFFSET');
                $this->_migrateFromOffset($offset, $currentVersion, $direction);
            }
        } catch (Ruckusing_Exception_MissingSchemaInfoTable $ex) {
            $this->_return .= $ex->getMessage();
        } catch (Ruckusing_Exception_MissingMigrationMethod $ex) {
            $this->_return .= $ex->getMessage();
        } catch (Ruckusing_Exception $ex) {
            $this->_logger->err('Exception: ' . $ex->getMessage());
            $this->_return .= "\n" . $ex->getMessage() . "\n";
        }
        $this->_return .= "\n\nFinished: " . date('Y-m-d g:ia T') . "\n\n";
        $this->_logger->debug(__METHOD__ . ' End');
        return $this->_return;
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
        $migrations = $this->_migratorUtil
            ->getMigrationFiles($this->_migrationDir, $direction);
        $versions = array();
        $currentIndex = -1;
        $nbMigrations = count($migrations);
        $this->_logger->debug('Nb migrations: ' . $nbMigrations);
        for ($i = 0; $i < $nbMigrations; $i++) {
            $versions[] = $migrations[$i]['version'];
            if ($migrations[$i]['version'] === $currentVersion) {
                $currentIndex = $i;
                break;
            }
        }
        $this->_logger->debug('current index: ' . $currentIndex);

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
            $this->_return .= "\tCannot migrate " . strtoupper($direction)
                . " via offset \"{$prefix}{$offset}\": "
                . "not enough migrations exist to execute.\n"
                . "\tYou asked for ({$offset}) but only available are "
                . '(' . $numAvailable . '): ' . implode(', ', $names);
        } else {
            // run em
            $target = end($available);
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
            $this->_return .= "\tMigrating " . strtoupper($direction);
            if (! is_null($destination)) {
                $this->_return .= " to: {$destination}\n";
            } else {
                $this->_return .= ":\n";
            }
            $migrations = $this->_migratorUtil
                ->getRunnableMigrations(
                    $this->_migrationDir,
                    $direction,
                    $destination
                );
            if (count($migrations) == 0) {
                $msg = 'No relevant migrations to run. Exiting...';
                $this->_logger->info($msg);
                $this->_return .= "\n{$msg}\n";
                return;
            }
            $this->_runMigrations($migrations, $direction);
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
        $lastVersion = -1;
        foreach ($migrations as $file) {
            $fullPath = $this->_migrationDir . '/' . $file['file'];
            $this->_logger->debug('file: ' . var_export($file, true));
            if (! is_file($fullPath) || ! is_readable($fullPath)) {
                continue;
            }
            $this->_logger->debug('include file: ' . $file['file']);
            include_once $fullPath;
            require_once 'Ruckusing/Util/Naming.php';
            $klass = Ruckusing_Util_Naming::classFromMigrationFile($file['file']);
            $obj = new $klass($this->_adapter);
            $refl = new ReflectionObject($obj);
            if (! $refl->hasMethod($targetMethod)) {
                $msg = $klass . ' does not have (' . $targetMethod
                    . ') method defined!';
                $this->_logger->warn($msg);
                require_once 'Ruckusing/Exception/MissingMigrationMethod.php';
                throw new Ruckusing_Exception_MissingMigrationMethod($msg);
            }
            $start = microtime(true);
            try {
                //start transaction
                $this->_adapter->startTransaction();
                $obj->$targetMethod();
                //successfully ran migration, update our version and commit
                $this->_migratorUtil
                    ->resolveCurrentVersion($file['version'], $targetMethod);
                $this->_adapter->commitTransaction();
            } catch (Exception $e) {
                $this->_adapter->rollbackTransaction();
                //wrap the caught exception in our own
                $msg = $file['class'] . ' - ' . $e->getMessage();
                $this->_logger->err($msg);
                throw new Ruckusing_Exception($msg);
            }
            $end = microtime(true);
            $diff = $this->_diffTimer($start, $end);
            $this->_return .= sprintf(
                "========= %s ======== (%.2f)\n",
                $file['class'],
                $diff
            );
            $lastVersion = $file['version'];
            $this->_logger->info('last_version: ' . $lastVersion);
        }//foreach
        //update the schema info
        $this->_logger->debug(__METHOD__ . ' End');
        return array('last_version' => $lastVersion);
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
            $msg = 'Schema version table does not exist.';
            $this->_logger->warn($msg);
            require_once 'Ruckusing/Exception/MissingSchemaInfoTable.php';
            throw new Ruckusing_Exception_MissingSchemaInfoTable("\n\t" . $msg);
        }
        $this->_logger->debug(__METHOD__ . ' End');
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
