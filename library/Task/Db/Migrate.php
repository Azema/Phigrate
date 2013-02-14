<?php

/**
 * Phigrate
 *
 * PHP Version 5.3
 *
 * @category   Phigrate
 * @package    Task
 * @subpackage Db
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */

/**
 * @see Task_Base
 */
require_once 'Task/Db/AMigration.php';

/**
 * This is the primary work-horse method, it runs all migrations available,
 * up to the current version.
 *
 * @category   Phigrate
 * @package    Task
 * @subpackage Db
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */
class Task_Db_Migrate extends Task_Db_AMigration
{
    /**
     * Task name
     *
     * @var string
     */
    protected $_task = 'migrate';

    /**
     * Flag to force migration
     *
     * @var boolean
     */
    protected $_force = false;

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
            require_once 'Phigrate/Exception/Task.php';
            throw new Phigrate_Exception_Task($msg);
        }

        $this->_return = 'Started: ' . date('Y-m-d g:ia T') . PHP_EOL . PHP_EOL
            . '[db:migrate]:' . PHP_EOL;

        // Recuperation du flag pour forcer les migrations
        if (array_key_exists('--force', $args)) {
            $this->_force = true;
            unset($args['--force']);
        }
        $this->_execute($args);

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

To force the execution of migrations on existing database, you can use
the flag --force, \033[1;31mbut it is your own risk\033[0m.

\t\033[33mVERSION\033[0m can be specified to go up (or down) to a specific
\tversion, based on the current version. If not specified,
\tall migrations greater than the current database version
\twill be executed.

\t\033[37mExample A:\033[0m The database is fresh and empty, assuming there
\tare 5 actual migrations, but only the first two should be run.

\t\t\033[35mphigrate db:migrate VERSION=20101006114707\033[0m

\t\033[37mExample B:\033[0m The current version of the DB is 20101006114707
\tand we want to go down to 20100921114643

\t\t\033[35mphigrate db:migrate VERSION=20100921114643\033[0m

\t\033[37mExample C:\033[0m You can also use relative number of revisions
\t(positive migrate up, negative migrate down).

\t\t\033[35mphigrate db:migrate VERSION=-2\033[0m

USAGE;
        $this->_logger->debug(__METHOD__ . ' End');
        return $output;
    }

    /**
     * run migrations
     *
     * @param array                   $migrations   The table of migration files
     * @param Phigrate_BaseMigration $targetMethod The migration class
     *
     * @return void
     */
    protected function _runMigrations($migrations, $targetMethod)
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        $lastVersion = -1;
        $objMigrations = array();
        foreach ($migrations as $file) {
            $fullPath = $this->_migrationDir . '/' . $file['file'];
            $this->_logger->debug('file: ' . var_export($file, true));
            if (! is_file($fullPath) || ! is_readable($fullPath)) {
                continue;
            }
            $this->_logger->debug('include file: ' . $file['file']);
            include_once $fullPath;
            require_once 'Phigrate/Util/Naming.php';
            $class = Phigrate_Util_Naming::classFromMigrationFile($file['file']);
            /** @param Phigrate_Migration_Base $obj */
            $obj = new $class($this->_adapter);
            $refl = new ReflectionObject($obj);
            if (! $refl->hasMethod($targetMethod)) {
                $msg = $class . ' does not have (' . $targetMethod
                    . ') method defined!';
                $this->_logger->warn($msg);
                require_once 'Phigrate/Exception/MissingMigrationMethod.php';
                throw new Phigrate_Exception_MissingMigrationMethod($msg);
            }
            $objMigrations[] = array(
                'obj'  => $obj,
                'file' => $file,
            );
        }
        try {
            //start transaction
            $this->_adapter->startTransaction();
            $this->_logger->info('Start transaction called');
            foreach ($objMigrations as $migration) {
                $start = microtime(true);
                try {
                    $migration['obj']->$targetMethod();
                } catch (Exception $e) {
                    //wrap the caught exception in our own
                    $msg = $migration['file']['class'] . ' - ' . $e->getMessage();
                    $this->_logger->err($msg);
                    $this->_logger->debug('force: ' . var_export($this->_force, true));
                    // Migrations forcée ?
                    if ($this->_force !== true) {
                        throw new Phigrate_Exception($msg, $e->getCode(), $e);
                    }
                    $msgForce = $e->getMessage();
                }
                $end = microtime(true);
                $diff = $this->_diffTimer($start, $end);
                $this->_return .= sprintf(
                    "========= %s ======== (%.2f)\n",
                    $migration['file']['class'],
                    $diff
                );
                // Ajout du message d'erreur en cas de forçage
                if ($this->_force && isset($msgForce)) {
                    $this->_return .= "\033[1;31mError:\033[0m {$msgForce}\n\n";
                    unset($msgForce);
                }
                //successfully ran migration, update our version and commit
                $this->_migratorUtil
                    ->resolveCurrentVersion($migration['file']['version'], $targetMethod);
                $lastVersion = $migration['file']['version'];
                $this->_logger->info('last_version: ' . $lastVersion);
            }
            $this->_adapter->commitTransaction();
            $this->_logger->info('Commit transaction called');
        } catch (\Exception $e) {
            $this->_adapter->rollbackTransaction();
            $this->_logger->info('Rollback transaction called');
            throw $e;
        }
        //update the schema info
        $this->_logger->debug(__METHOD__ . ' End');
        return array('last_version' => $lastVersion);
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
