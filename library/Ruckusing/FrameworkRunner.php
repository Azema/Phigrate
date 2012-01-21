<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category  RuckusingMigrations
 * @package   Ruckusing
 * @author    Cody Caughlan <codycaughlan % gmail . com>
 * @author    Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright 2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license   GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/ruckus/ruckusing-migrations
 */

/**
 * @see Ruckusing_Task_Manager 
 */
require_once 'Ruckusing/Task/Manager.php';

/**
 * Primary work-horse class. This class bootstraps the framework by loading
 * all adapters and tasks.
 *
 * @category  RuckusingMigrations
 * @package   Ruckusing
 * @author    Cody Caughlan <codycaughlan % gmail . com>
 * @author    Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright 2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license   GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_FrameworkRunner
{
    /**
     * reference to our DB connection
     * 
     * @var array
     */
    private $_db = null;

    /**
     * Available config of application
     * 
     * @var array
     */
    private $_config;

    /**
     * Available DB config (e.g. test,development, production)
     * 
     * @var array
     */
    private $_configDb;

    /**
     * task manager 
     * 
     * @var Ruckusing_Task_Manager
     */
    private $_taskMgr = null;

    /**
     * adapter 
     * 
     * @var Ruckusing_Adapter_Base
     */
    private $_adapter = null;

    /**
     * current task name 
     * 
     * @var string
     */
    private $_curTaskName;

    /**
     * Flag to display help of task
     * @see Ruckusing_FrameworkRunner::_parseArgs
     * 
     * @var boolean
     */
    private $_helpTask = false;

    /**
     * task options 
     * 
     * @var string
     */
    private $_taskOptions = '';

    /**
     * Directory of tasks
     * 
     * @var string
     */
    private $_taskDir;

    /**
     * Directory migration
     * 
     * @var string
     */
    private $_migrationDir;

    /**
     * Environment
     * default is development 
     * but can also be one 'test', 'production', etc...
     * 
     * @var string
     */
    private $_env = 'development';
    
    /**
     * _logger 
     * 
     * @var Ruckusing_Logger
     */
    private $_logger;
    
    /**
     * __construct 
     * 
     * @param array $argv Arguments of the command line
     *
     * @return Ruckusing_FrameworkRunner
     */
    function __construct($argv)
    {
        try {
            //parse arguments
            $this->_parseArgs($argv);
            $this->getLogger()->debug(__METHOD__ . ' Start');
            // initialize DB
            $this->initializeDb();
            $this->initTasks();
        } catch (Exception $e) {
            if (isset($this->_logger)) {
                $this->_logger->err('Exception: ' . $e->getMessage());
            }
            throw $e;
        }
        $this->_logger->debug(__METHOD__ . ' End');
    }

    /**
     * getLogger 
     * 
     * @return Ruckusing_Logger
     */
    public function getLogger()
    {
        if (! isset($this->_logger)) {
            $this->_logger = $this->_initLogger();
        }
        return $this->_logger;
    }

    /**
     * getConfig : Return the config application
     * 
     * @return array
     */
    public function getConfig()
    {
        if (! isset($this->_config)) {
            $this->_config = new Ruckusing_Config_Ini(
                $this->_getConfigFile(),
                $this->_env
            );
        }
        return $this->_config;
    }

    /**
     * getConfigDb : Return the config DB
     * 
     * @return array
     */
    public function getConfigDb()
    {
        $this->getLogger()->debug(__METHOD__ . ' Start');
        if (! isset($this->_configDb)) {
            $this->_configDb = new Ruckusing_Config_Ini(
                $this->_getConfigDbFile(),
                $this->_env
            );
        }
        $this->getLogger()->debug(__METHOD__ . ' End');
        return $this->_configDb;
    }

    //-------------------------
    // PUBLIC METHODS
    //------------------------- 
    /**
     * execute 
     * 
     * @return void
     */
    public function execute()
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        $output = '';
        if ($this->_taskMgr->hasTask($this->_curTaskName)) {
            if ($this->_helpTask) {
                $output = $this->_taskMgr->help($this->_curTaskName);
            } else {
                $output = $this->_taskMgr->execute(
                    $this->_curTaskName, 
                    $this->_taskOptions
                );
            }
        } else {
            $msg = 'Task not found: ' . $this->_curTaskName;
            $this->_logger->err($msg);
            require_once 'Ruckusing/Exception/InvalidTask.php';
            throw new Ruckusing_Exception_InvalidTask($msg);
        }
        $this->_logger->debug(__METHOD__ . ' End');
        return $output;
    }
    
    /**
     * initialize tasks 
     *
     * @return void
     */
    public function initTasks()
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        $this->_taskMgr = new Ruckusing_Task_Manager(
            $this->_adapter, $this->getTaskDir(), $this->getMigrationDir()
        );
        $this->_logger->debug(__METHOD__ . ' End');
    }

    /**
     * getTaskDir : Return the directory of tasks
     * 
     * @return string
     */
    public function getTaskDir()
    {
        if (! isset($this->_taskDir)) {
            $config = $this->getConfig();
            if (! isset($config->task) || ! isset($config->task->dir)) {
                require_once 'Ruckusing/Exception/MissingTaskDir.php';
                throw new Ruckusing_Exception_MissingTaskDir(
                    'Please, inform the variable "task.dir" '
                    . 'in the configuration file'
                );
            }
            $this->_taskDir = $config->task->dir;
        }
        return $this->_taskDir;
    }

    /**
     * getMigrationDir : Return the directory of migrations
     * 
     * @return string
     */
    public function getMigrationDir()
    {
        $this->getLogger()->debug(__METHOD__ . ' Start');
        if (! isset($this->_migrationDir)) {
            $this->_migrationDir = $this->_initMigrationDir();
        }
        $this->getLogger()->debug('migrationDir: ' . $this->_migrationDir);
        $this->getLogger()->debug(__METHOD__ . ' End');
        return $this->_migrationDir;
    }

    /**
     * _initMigrationDir 
     * 
     * @return string
     */
    private function _initMigrationDir()
    {
        $config = $this->getConfig();
        if (! isset($config->migration) || ! isset($config->migration->dir)) {
            require_once 'Ruckusing/Exception/MissingMigrationDir.php';
            throw new Ruckusing_Exception_MissingMigrationDir(
                'Please, inform the variable "migration.dir" '
                . 'in the configuration file'
            );
        }
        return $config->migration->dir;
    }

    /**
     * initialize db 
     * 
     * @return void
     */
    public function initializeDb()
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        try {
            $this->_verifyDbConfig();
            $configDb = $this->getConfigDb();
            $this->_logger->debug('Config DB ' . var_export($configDb, true));
            $adapter = $this->_getAdapterClass($configDb->type);
            $this->_logger->debug('adapter ' . $adapter);
            //construct our adapter         
            $this->_adapter = new $adapter($configDb->toArray(), $this->_logger);
        } catch (Exception $ex) {
            $this->_logger->err('Exception: ' . $ex->getMessage());
            //trigger_error(sprintf("\n%s\n", $ex->getMessage()));
            throw $ex;
        }
        $this->_logger->debug(__METHOD__ . ' End');
    }
    
    /**
     * parse args 
     * $argv is our complete command line argument set.
     * PHP gives us: 
     * [0] = the actual file name we're executing
     * [1..N] = all other arguments
     * 
     * Our task name should be at slot [1] 
     * Anything else are additional parameters that we can pass
     * to our task and they can deal with them as they see fit.
     * 
     * @param array $argv Arguments of command line
     *
     * @return void
     */
    private function _parseArgs($argv)
    {
        $numArgs = count($argv);

        if ($numArgs >= 2) {
            $options = array();
            for ($i = 1; $i < $numArgs; $i++) {
                switch ($argv[$i]) {
                    // configuration file path
                    case '-c':
                    case '--configuration':
                        $i++;
                        $this->_configFile = $argv[$i];
                        break;
                    // configuration db file path
                    case '-d':
                    case '--database':
                        $i++;
                        $this->_configDbFile = $argv[$i];
                        break;
                    // task directory
                    case '-t':
                    case '--taskdir':
                        $i++;
                        $this->_taskDir = $argv[$i];
                        break;
                    // migration directory
                    case '-m':
                    case '--migrationdir':
                        $i++;
                        $this->_migrationDir = $argv[$i];
                        break;
                    // other
                    default:
                        $arg = $argv[$i];
                        if (strpos($arg, ':') !== false) {
                            $this->_curTaskName = $arg;
                            continue;
                        } elseif ($arg == 'help') {
                            $this->_helpTask = true;
                            continue;
                        } elseif (strpos($arg, '=') !== false) {
                            list($key, $value) = explode('=', $arg);
                            if ($key == 'ENV') {
                                $this->_env = $value;
                            }
                            $options[$key] = $value;
                        }
                        break;
                }
            }
            $this->_taskOptions = $options;
        }
        if ($numArgs < 2 || ! isset($this->_curTaskName)) {
            require_once 'Ruckusing/Exception/Argument.php';
            throw new Ruckusing_Exception_Argument('No task found!');
        }
    }

    /**
     * Update the local schema to handle multiple records versus the prior architecture
     * of storing a single version. In addition take all existing migration files
     * and register them in our new table, as they have already been executed.
     * 
     * @return void
     */
    public function updateSchemaForTimestamps()
    {
        //only create the table if it doesnt already exist
        $this->_adapter->createSchemaVersionTable();
        //insert all existing records into our new table
        $migratorUtil = new Ruckusing_Util_Migrator($this->_adapter);
        $files = $migratorUtil->getMigrationFiles(
            $this->getMigrationDir(), 'up'
        );
        foreach ($files as $file) {
            if ((int)$file['version'] >= PHP_INT_MAX) {
                //its new style like '20081010170207' so its not a candidate
                continue;
            }
            // query old table, if it less than or equal to our max version, 
            // then its a candidate for insertion     
            $querySql = sprintf(
                'SELECT version FROM %s WHERE version >= %d', 
                RUCKUSING_SCHEMA_TBL_NAME, 
                $file['version']
            );
            $existingVersionOldStyle = $this->_adapter->selectOne($querySql);
            if (count($existingVersionOldStyle) > 0) {
                // make sure it doesnt exist in our new table, 
                // who knows how it got inserted?
                $newVersSql = sprintf(
                    'SELECT version FROM %s WHERE version = %d', 
                    RUCKUSING_TS_SCHEMA_TBL_NAME, 
                    $file['version']
                );
                $existingVersionNewStyle = $this->_adapter
                    ->selectOne($newVersSql);
                if (empty($existingVersionNewStyle)) {       
                    // use printf & %d to force it to be stripped of any 
                    // leading zeros, we *know* this represents an old version style
                    // so we dont have to worry about PHP and integer overflow
                    $insertSql = sprintf(
                        'INSERT INTO %s (version) VALUES (%d)', 
                        RUCKUSING_TS_SCHEMA_TBL_NAME,
                        $file['version']
                    );
                    $this->_adapter->query($insertSql);
                }
            }
        }
    }

    //-------------------------
    // PRIVATE METHODS
    //------------------------- 
    
    /**
     * _getConfigFile : Return the filename of config application 
     * 
     * @return string
     */
    private function _getConfigFile()
    {
        if (! isset($this->_configFile)) {
            if (! is_file(RUCKUSING_BASE . '/config/application.ini')) {
                require_once 'Ruckusing/Exception/Config.php';
                throw new Ruckusing_Exception_Config(
                    'Config file not found! Please, '
                    . 'create config file for application'
                );
            }
            $this->_configFile = RUCKUSING_BASE . '/config/application.ini';
        }
        return $this->_configFile;
    }

    /**
     * _getConfigDbFile : Return the filename of config DB
     * 
     * @return string
     */
    private function _getConfigDbFile()
    {
        $this->getLogger()->debug(__METHOD__ . ' Start');
        if (! isset($this->_configDbFile)) {
            $this->_configDbFile = RUCKUSING_BASE . '/config/database.ini';
            if (! is_file(RUCKUSING_BASE . '/config/database.ini')) {
                require_once 'Ruckusing/Exception/Config.php';
                throw new Ruckusing_Exception_Config(
                    'Config file for DB not found! Please, create config file'
                );
            }
        }
        $this->getLogger()->info('configDbFile: ' . $this->_configDbFile);
        $this->getLogger()->debug(__METHOD__ . ' End');
        return $this->_configDbFile;
    }

    /**
     * verify db config 
     * 
     * @return void
     * @throws Exception
     */
    private function _verifyDbConfig()
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        $env = $this->_env;
        $configDb = $this->getConfigDb();
        if (! $configDb instanceof Ruckusing_Config) {
            $msg = '(' . $env . ') DB is not configured!';
            require_once 'Ruckusing/Exception/MissingConfigDb.php';
            throw new Ruckusing_Exception_MissingConfigDb('Error: ' . $msg);
        }
        // Check only variable type for create Adapter
        // all other parameters will checked in adapter
        if (! isset($configDb->type)) {
            $msg = '"type" is not set for "' . $env . '" DB in config file';
            $this->_logger->err($msg);
            require_once 'Ruckusing/Exception/MissingAdapterType.php';
            throw new Ruckusing_Exception_MissingAdapterType('Error: ' . $msg);
        }
        $this->_logger->debug(__METHOD__ . ' End');
    }

    /**
     * Initialize and return an instance of logger
     * 
     * @return Ruckusing_Logger
     */
    private function _initLogger()
    {
        //initialize logger
        $logDir = RUCKUSING_BASE . '/logs';
        $config = $this->getConfig();
        if (isset($config->log) && isset($config->log->dir)) {
            $logDir = $config->log->dir;
        }
        if (! is_dir($logDir)) {
            require_once 'Ruckusing/Exception/InvalidLog.php';
            throw new Ruckusing_Exception_InvalidLog(
                $logDir . ' does not exists.'
            );
        }
        if (is_dir($logDir) && ! is_writable($logDir)) {
            require_once 'Ruckusing/Exception/InvalidLog.php';
            throw new Ruckusing_Exception_InvalidLog(
                'Cannot write to log directory: '
                . $logDir . '. Check permissions.'
            );
        }
        $logger = Ruckusing_Logger::instance($logDir . '/' . $this->_env . '.log');

        $priority = 99;
        if (isset($config->log->priority)) {
            $priority = $config->log->priority;
        } elseif ($this->_env == 'development') {
            $priority = Ruckusing_Logger::DEBUG;
        } elseif ($this->_env == 'production') {
            $priority = Ruckusing_Logger::INFO;
        }
        $logger->setPriority($priority);

        return $logger;
    }

    /**
     * get adapter class 
     * 
     * @param string $dbType The type of RDBMS
     *
     * @return string
     */
    private function _getAdapterClass($dbType)
    {
        $adapterClass = null;
        switch (strtolower($dbType)) {
            case 'mysql':
                $adapterClass = 'Ruckusing_Adapter_Mysql_Adapter';
                break;
            case 'mssql':
            case 'pgsql':
            default:
                throw new Ruckusing_Exception_InvalidAdapterType(
                    'Adapter "' . $dbType . '" not implemented!'
                );
        }
        return $adapterClass;
    }
}
