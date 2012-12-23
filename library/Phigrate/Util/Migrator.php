<?php

/**
 * Phigrate
 *
 * PHP Version 5.3
 *
 * @category   Phigrate
 * @package    Phigrate_Util
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */

/**
 * Class of tools
 *
 * @category   Phigrate
 * @package    Phigrate_Util
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */
class Phigrate_Util_Migrator
{
    /**
     * adapter
     *
     * @var Phigrate_Adapter_Base
     */
    private $_adapter = null;

    /**
     * migrations
     *
     * @var array
     */
    private $_migrations = array();

    /**
     * __construct
     *
     * @param Phigrate_Adapter_Base $adapter Adapter RDBMS
     *
     * @return Phigrate_Util_Migrator
     */
    function __construct($adapter)
    {
        $this->setAdapter($adapter);
    }

    /**
     * set adapter
     *
     * @param Phigrate_Adapter_Base $adapter Adapter RDBMS
     *
     * @return Phigrate_Util_Migrator
     */
    public function setAdapter($adapter)
    {
        if (! $adapter instanceof Phigrate_Adapter_Base) {
            $msg = 'adapter must be implement Phigrate_Adapter_Base!';
            throw new Phigrate_Exception_Argument($msg);
        }
        $this->_adapter = $adapter;
        return $this;
    }

    /**
     * Return the max version number from the DB,
     * or "0" in the case of no versions available.
     * We must use strings because our date/timestamp
     * when treated as an integer would cause overflow.
     *
     * @return string
     */
    public function getMaxVersion()
    {
        // We only want one row but we cannot assume that we are using MySQL and use a LIMIT statement
        // as it is not part of the SQL standard. Thus we have to select all rows and use PHP to return
        // the record we need
        $versions_nested = $this->_adapter->selectAll(
            sprintf(
                'SELECT version FROM %s',
                $this->_adapter->Identifier(PHIGRATE_TS_SCHEMA_TBL_NAME)
            )
        );
        $versions = array();
        foreach ($versions_nested as $v) {
            $versions[] = $v['version'];
        }
        $num_versions = count($versions);
        $version = null;
        if ($num_versions) {
            sort($versions); //sorts lowest-to-highest (ascending)
            $version = (string)$versions[$num_versions-1];
        }
        return $version;
    }

    /**
     * This methods calculates the actual set of migrations
     * that should be performed, taking into account
     * the current version, the target version and the direction (up/down).
     * When going up this method will skip migrations that have not been
     * executed, when going down this method will only include migrations
     * that have been executed.
     *
     * @param string  $directory   The directory of migrations files
     * @param string  $direction   Up or Down
     * @param int     $destination The version desired
     * @param boolean $useCache    Flag for use a cache
     *
     * @return array
     */
    public function getRunnableMigrations($directory, $direction,
        $destination = null, $useCache = true)
    {
        // cache migration lookups and early return if we've seen
        // this requested set
        if ($useCache == true) {
            $key = $direction . '-' . $destination;
            if (array_key_exists($key, $this->_migrations)) {
                return($this->_migrations[$key]);
            }
        }

        $runnable = array();
        $migrations = array();
        $migrations = $this->getMigrationFiles($directory, $direction);
        $current = $this->_findVersion($migrations, $this->getMaxVersion());
        $target = $this->_findVersion($migrations, $destination);
        if (is_null($target) && ! is_null($destination) && $destination > 0) {
            throw new Phigrate_Exception_InvalidTargetMigration(
                'Could not find target version ' . $destination
                . ' in set of migrations.'
            );
        }
        $start = $direction == 'up' ? 0 : array_search($current, $migrations);
        $start = $start !== false ? $start : 0;
        $finish = array_search($target, $migrations);
        $finish = $finish !== false ? $finish : (count($migrations) - 1);
        $item_length = ($finish - $start) + 1;

        $runnable = array_slice($migrations, $start, $item_length);

        //dont include first item if going down but not if going all the way to the bottom
        if ($direction == 'down' && count($runnable) > 0 && $target != null) {
            array_pop($runnable);
        }

        $executed = $this->getExecutedMigrations();
        $to_execute = array();

        foreach ($runnable as $migration) {
            $version = (string)$migration['version'];
            //Skip ones that we have already executed
            if ($direction == 'up' && in_array($version, $executed)) {
                continue;
            }
            //Skip ones that we never executed
            if ($direction == 'down' && ! in_array($version, $executed)) {
                continue;
            }
            $to_execute[] = $migration;
        }
        if ($useCache == true) {
            $this->_migrations[$key] = $to_execute;
        }
        return $to_execute;
    }

    /**
     * Generate a timestamp for the current time in UTC format
     * Returns a string like '20090122193325'
     *
     * @return string
     */
    public static function generateTimestamp()
    {
        return gmdate('YmdHis', time());
    }

    /**
     * If we are going UP then log this version as executed,
     * if going DOWN then delete this version from our set of executed migrations.
     *
     * @param string $version   The version desired
     * @param string $direction Up or Down
     *
     * @return string
     */
    public function resolveCurrentVersion($version, $direction)
    {
        $direction = strtolower($direction);
        if ($direction === 'up') {
            $this->_adapter->setCurrentVersion($version);
        }
        if ($direction === 'down') {
            $this->_adapter->removeVersion($version);
        }
        return $version;
    }

    /**
     * Returns an array of strings which represent version numbers
     * that we *have* migrated
     *
     * @return array
     */
    public function getExecutedMigrations()
    {
        $query_sql = sprintf(
            'SELECT version FROM %s',
            $this->_adapter->identifier(PHIGRATE_TS_SCHEMA_TBL_NAME)
        );
        $versions = $this->_adapter->selectAll($query_sql);
        $executed = array();
        foreach ($versions as $v) {
            $executed[] = (string)$v['version'];
        }
        sort($executed);
        return $executed;
    }

    /**
     * Return a set of migration files, according to the given direction.
     * If nested, then return a complex array with the migration parts
     * broken up into parts which make analysis much easier.
     *
     * @param string $directory The directory of migration files
     * @param string $direction Up or Down
     *
     * @return array
     */
    public static function getMigrationFiles($directory, $direction = 'up')
    {
        $validFiles = array();
        if (!is_dir($directory)) {
            throw new Phigrate_Exception_InvalidMigrationDir(
                'Phigrate_Util_Migrator - (' . $directory . ') '
                . 'is not a directory.'
            );
        }
        $files = scandir($directory);
        $nbFiles = count($files);
        for ($i = 0; $i < $nbFiles; $i++) {
            if (preg_match('/^(\d+)_(.*)\.php$/', $files[$i], $matches)) {
                if (count($matches) == 3) {
                    $validFiles[] = array(
                        'version' => $matches[1],
                        'class'   => $matches[2],
                        'file'    => $matches[0]
                    );
                }
            }
        }
        sort($validFiles); //sorts in place
        if ($direction == 'down') {
            $validFiles = array_reverse($validFiles);
        }
        return $validFiles;
    }

    //== Private methods


    /**
     * Find the specified structure (representing a migration)
     * that matches the given version
     *
     * @param array  $migrations The table of migrations
     * @param string $version    The version desired
     *
     * @return string
     */
    private function _findVersion($migrations, $version)
    {
        $len = count($migrations);
        for ($i = 0; $i < $len; $i++) {
            if ($migrations[$i]['version'] == $version) {
                return $migrations[$i];
            }
        }
        return null;
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
