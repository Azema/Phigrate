<?php

/**
 * Phigrate
 *
 * PHP Version 5.3
 *
 * @category   Phigrate
 * @package    Task
 * @subpackage Db
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */

/**
 * @see Task_Base
 */
require_once 'Task/Base.php';

/**
 * @see Phigrate_Task_ITask
 */
require_once 'Phigrate/Task/ITask.php';

/**
 * This is the primary work-horse method, it runs all migrations available,
 * up to the current version.
 *
 * @category   Phigrate
 * @package    Task
 * @subpackage Db
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 * @abstract
 */
abstract class Task_Db_AMigration extends Task_Base implements Phigrate_Task_ITask
{
    /** @var integer */
    const STYLE_REGULAR = 1;
    /** @var integer */
    const STYLE_OFFSET = 2;

    /** @var string */
    const DIRECTION_DOWN = 'down';
    /** @var string */
    const DIRECTION_UP = 'up';

    /**
     * migrator util
     *
     * @var Phigrate_Util_Migrator
     */
    protected $_migratorUtil = null;

    /**
     * Return executed string
     *
     * @var string
     */
    protected $_return = '';

    /**
     * Task name
     *
     * @var string
     */
    protected $_task;

    /**
     * Prefix the text of output
     *
     * @var string
     */
    protected $_prefixText = '';

    /**
     * Primary task entry point
     *
     * @param array $args Arguments of task
     *
     * @return void
     */
    protected function _execute($args)
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        $this->_taskArgs = $args;
        $this->_logger->debug('Args of task: ' . var_export($args, true));

        // Flag d'échec de migration
        $failed = false;
        try {
            // Check that the schema_version table exists,
            // and if not, automatically create it
            $this->_verifyEnvironment();

            $this->_migratorUtil = new Phigrate_Util_Migrator($this->_adapter);

            $targetVersion = null;
            $style = self::STYLE_REGULAR;

            //did the user specify an explicit version?
            if (array_key_exists('VERSION', $this->_taskArgs)) {
                $targetVersion = trim($this->_taskArgs['VERSION']);
                $this->_logger->info('Version specified: ' . $targetVersion);
            }

            // did the user specify a relative offset, e.g. "-2" or "+3" ?
            $matches = array();
            if ($targetVersion !== null
                && preg_match('/^([\+-])(\d+)$/', $targetVersion, $matches)
            ) {
                if (count($matches) == 3) {
                    $direction = ($matches[1] === '-') ? self::DIRECTION_DOWN : self::DIRECTION_UP;
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
                $direction = $this->_fetchDirection($targetVersion, $currentVersion);
                // Up or down to version specified by user or up to max if version not specified
                $this->_prepareToMigrate($targetVersion, $direction);
            } elseif ($style == self::STYLE_OFFSET) {
                $this->_logger->debug('STYLE OFFSET');
                $this->_migrateFromOffset($offset, $currentVersion, $direction);
            }
        } catch (Phigrate_Exception_MissingSchemaInfoTable $ex) {
            $this->_return .= $ex->getMessage();
        } catch (Phigrate_Exception_MissingMigrationMethod $ex) {
            $failed = true;
            $this->_return .= $ex->getMessage();
        } catch (Phigrate_Exception $ex) {
            $failed = true;
            $this->_logger->err('Exception: ' . $ex->getMessage());
            $this->_return .= "\n" . $ex->getMessage() . "\n";
        }

        // Si une erreur de migration est détecté et que la version courante est renseignée
        if ($failed && !is_null($currentVersion) && $this instanceof Task_Db_Migrate) {
            $targetVersion = $currentVersion;
            $currentVersion = $this->_migratorUtil->getMaxVersion();
            $this->_logger->debug('Retour en arriere: ' . var_export($targetVersion . ' ' . $currentVersion, true));
            if ($currentVersion != $targetVersion) {
                $this->_return .= "\n\n" . $this->_prefixText . 'Back to original version: ' . $targetVersion . "\n\n";
                $direction = $this->_fetchDirection($targetVersion, $currentVersion);
                // On retourne en arrière pour annuler les migrations
                $this->_prepareToMigrate($targetVersion, $direction);
            }
        }

        $this->_logger->debug(__METHOD__ . ' End');
        return $this->_return;
    }

    /**
     * Retourne la direction de migration en fonction de la version désirée
     *
     * @param string $targetVersion La version à migrer
     *
     * @return string
     */
    protected function _fetchDirection($targetVersion, $currentVersion)
    {
        if (is_null($targetVersion) || $currentVersion <= $targetVersion) {
            $direction = self::DIRECTION_UP;
        } else {
            $direction = self::DIRECTION_DOWN;
        }
        return $direction;
    }

    /**
     * verify environment
     *
     * @return void
     */
    protected function _verifyEnvironment()
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        if (! $this->_adapter->tableExists(PHIGRATE_TS_SCHEMA_TBL_NAME)) {
            $msg = 'Schema version table does not exist.';
            $this->_logger->warn($msg);
            require_once 'Phigrate/Exception/MissingSchemaInfoTable.php';
            throw new Phigrate_Exception_MissingSchemaInfoTable("\n\t" . $msg);
        }
        $this->_logger->debug(__METHOD__ . ' End');
    }

    /**
     * diff timer
     *
     * @param integer $s Start time
     * @param integer $e End time
     *
     * @return integer
     */
    protected function _diffTimer($s, $e)
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        $this->_logger->debug('start: ' . $s . ' - end: ' . $e);
        $result = $e - $s;
        $this->_logger->debug('result: ' . $result);
        $this->_logger->debug(__METHOD__ . ' End');
        return $result;
    }

    /**
     * export from offset
     *
     * @param int    $offset         The offset
     * @param int    $currentVersion The current version
     * @param string $direction      Up or Down
     *
     * @return void
     */
    protected function _migrateFromOffset($offset, $currentVersion, $direction)
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        $this->_logger->debug(
            'offset: ' . $offset . ' - currentVersion: ' . $currentVersion
            . ' - direction: ' . $direction
        );
        $migrations = $this->_migratorUtil
            ->getRunnableMigrations($this->_migrationDir, $direction);
        $this->_logger->debug('Migrations: ' . var_export($migrations, true));
        if (count($migrations) < $offset) {
            $names = array();
            foreach ($migrations as $a) {
                $names[] = $a['file'];
            }
            $numAvailable = count($names);
            $prefix = $direction == 'down' ? '-' : '+';
            $this->_logger->warn(
                'Cannot migration ' . $direction . ' via offset ' . $prefix . $offset
            );
            $this->_return .= $this->_prefixText . "\tCannot {$this->_task} " . strtoupper($direction)
                . " via offset \"{$prefix}{$offset}\": not enough migrations exist to execute.\n"
                . $this->_prefixText . "\tYou asked for ({$offset}) but only available are "
                . '(' . $numAvailable . '): ' . implode(', ', $names);
            return;
        }

        $start = 0;
        if ($direction == self::DIRECTION_DOWN) {
            $start = 1;
        }
        // check to see if we have enough migrations to run - the user
        // might have asked to run more than we have available
        $available = array_slice($migrations, $start, $offset);
        $this->_logger->debug('Available: ' . var_export($available, true));
        if (count($available) != $offset) {
            $this->_prepareToMigrate(null, $direction);
            return;
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
    protected function _prepareToMigrate($destination, $direction)
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        $this->_logger->debug(
            'Destination: ' . $destination
            . ' - direction: ' . $direction
        );
        try {
            $this->_return .= $this->_prefixText . "\tMigrating " . strtoupper($direction);
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
                $this->_return .= $this->_prefixText . "\n"
                    . trim($this->_prefixText . " {$msg}\n");
                return;
            }
            $this->_runMigrations($migrations, $direction);
        } catch (Exception $ex) {
            $this->_logger->err('Exception: ' . $ex->getMessage());
            throw $ex;
        }
        $this->_logger->debug(__METHOD__ . ' End');
    }
}
