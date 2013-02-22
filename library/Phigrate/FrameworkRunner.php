<?php

/**
 * Phigrate
 *
 * PHP Version 5.3
 *
 * @category  Phigrate
 * @package   Phigrate
 * @author    Cody Caughlan <codycaughlan % gmail . com>
 * @author    Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright 2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license   GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/Azema/Phigrate
 */

/**
 * @see Phigrate_Task_Manager
 */
require_once 'Phigrate/Task/Manager.php';

/**
 * Primary work-horse class. This class bootstraps the framework by loading
 * all adapters and tasks.
 *
 * @category  Phigrate
 * @package   Phigrate
 * @author    Cody Caughlan <codycaughlan % gmail . com>
 * @author    Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright 2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license   GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/Azema/Phigrate
 */
class Phigrate_FrameworkRunner
{
    /**
     * reference to our DB connection
     *
     * @var array
     */
    protected $_db = null;

    /**
     * Available config of application
     *
     * @var array
     */
    protected $_config;

    /**
     * Available DB config (e.g. test,development, production)
     *
     * @var array
     */
    protected $_configDb;

    /**
     * task manager
     *
     * @var Phigrate_Task_Manager
     */
    protected $_taskMgr = null;

    /**
     * adapter
     *
     * @var Phigrate_Adapter_Base
     */
    protected $_adapter = null;

    /**
     * current task name
     *
     * @var string
     */
    protected $_curTaskName;

    /**
     * Flag to display help of task
     * @see Phigrate_FrameworkRunner::_parseArgs
     *
     * @var boolean
     */
    protected $_helpTask = false;

    /**
     * task options
     *
     * @var string
     */
    protected $_taskOptions = '';

    /**
     * Directory of tasks
     *
     * @var string
     */
    protected $_taskDir;

    /**
     * Directory migration
     *
     * @var string
     */
    protected $_migrationDir;

    /**
     * Directory logs
     *
     * @var string
     */
    protected $_logDir;

    /**
     * Environment
     * default is development
     * but can also be one 'test', 'production', etc...
     *
     * @var string
     */
    protected $_env = 'development';

    /**
     * _logger
     *
     * @var Phigrate_Logger
     */
    protected $_logger;

    /**
     * __construct
     *
     * @param array $argv Arguments of the command line
     *
     * @return Phigrate_FrameworkRunner
     */
    function __construct($argv)
    {
        $this->_taskDir = array(PHIGRATE_BASE . '/library/Task');
        try {
            //parse arguments
            $this->_parseArgs($argv);
            $this->getLogger()->debug(__METHOD__ . ' Start');
            // initialize DB
            $this->_initializeDb();
            $this->_initTasks();
        } catch (Exception $e) {
            if (isset($this->_logger)) {
                $this->_logger->err('Exception: ' . $e->getMessage());
            }
            throw $e;
        }
        $this->_logger->debug(__METHOD__ . ' End');
    }

    //-------------------------
    // PUBLIC METHODS
    //-------------------------
    /**
     * getLogger
     *
     * @return Phigrate_Logger
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
            $this->_config = new Phigrate_Config_Ini(
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
            try {
                $this->_configDb = new Phigrate_Config_Ini(
                    $this->_getConfigDbFile(),
                    $this->_env
                );
            } catch (Exception $e) {
                $msg = 'Config file for DB not found! Please, create config file';
                throw new Phigrate_Exception_Config($msg, $e->getCode(), $e);
            }
        }
        $this->getLogger()->debug(__METHOD__ . ' End');
        return $this->_configDb;
    }

    /**
     * setAdapter
     *
     * @param Phigrate_Adapter_IAdapter $adapter Adapter RDBMS
     *
     * @return Phigrate_FrameworkRunner
     */
    public function setAdapter(Phigrate_Adapter_IAdapter $adapter)
    {
        $this->_adapter = $adapter;
        $this->_taskMgr->setAdapter($adapter);
        return $this;
    }

    /**
     * execute
     *
     * @return void
     */
    public function execute()
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        $output = '';
        if ($this->_curTaskName != 'db:export') {
            $output = $this->_getHeaderScript();
        }
        if ($this->_taskMgr->hasTask($this->_curTaskName)) {
            if ($this->_helpTask) {
                $output .= $this->_taskMgr->help($this->_curTaskName);
            } else {
                $output .= $this->_taskMgr->execute(
                    $this->_curTaskName,
                    $this->_taskOptions
                );
            }
        } else {
            $msg = 'Task not found: ' . $this->_curTaskName;
            $this->_logger->err($msg);
            require_once 'Phigrate/Exception/InvalidTask.php';
            throw new Phigrate_Exception_InvalidTask($msg);
        }
        $this->_logger->debug(__METHOD__ . ' End');
        return $output;
    }

    /**
     * getTaskDir : Return the directory of tasks
     *
     * @return array
     */
    public function getTaskDir()
    {
        if (count($this->_taskDir) == 1) {
            $config = $this->getConfig();
            if (isset($config->task) && isset($config->task->dir)) {
                $this->_taskDir[] = $config->task->dir;
            }
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
        } elseif (substr($this->_migrationDir, 0, 1) != '/') {
            $this->_migrationDir = $this->_initMigrationDir($this->_migrationDir);
        }
        $this->getLogger()->debug('migrationDir: ' . $this->_migrationDir);
        $this->getLogger()->debug(__METHOD__ . ' End');
        return $this->_migrationDir;
    }

    //-------------------------
    // PROTECTED METHODS
    //-------------------------

    /**
     * initialize tasks
     *
     * @return void
     */
    protected function _initTasks()
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        $this->_taskMgr = new Phigrate_Task_Manager(
            $this->_adapter, $this->getTaskDir(), $this->getMigrationDir()
        );
        $this->_logger->debug(__METHOD__ . ' End');
    }

    /**
     * _initMigrationDir
     *
     * @param string $migrationDir The directory of migrations files
     *
     * @return string
     */
    protected function _initMigrationDir($migrationDir = null)
    {
        $config = $this->getConfig();
        if (null === $migrationDir
            && (! isset($config->migration) || ! isset($config->migration->dir)))
        {
            require_once 'Phigrate/Exception/MissingMigrationDir.php';
            throw new Phigrate_Exception_MissingMigrationDir(
                'Please, inform the variable "migration.dir" '
                . 'in the configuration file'
            );
        } elseif (null === $migrationDir) {
            $migrationDir = $config->migration->dir;
        }
        return $this->_fileWithRealPath($migrationDir);
    }

    /**
     * initialize db
     *
     * @return void
     */
    protected function _initializeDb()
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
            throw $ex;
        }
        $this->_logger->debug(__METHOD__ . ' End');
    }

    //-------------------------
    // PRIVATE METHODS
    //-------------------------

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
                        if (! array_key_exists($i, $argv)) {
                            require_once 'Phigrate/Exception/Argument.php';
                            throw new Phigrate_Exception_Argument(
                                'Please, specify the configuration file if you'
                                . ' use the argument -c or --configuration'
                            );
                        }
                        $this->_configFile = $argv[$i];
                        break;
                    // configuration db file path
                    case '-d':
                    case '--database':
                        $i++;
                        if (! array_key_exists($i, $argv)) {
                            require_once 'Phigrate/Exception/Argument.php';
                            throw new Phigrate_Exception_Argument(
                                'Please, specify the configuration database file'
                                . ' if you use the argument -d or --database'
                            );
                        }
                        $this->_configDbFile = $argv[$i];
                        break;
                    // task directory
                    case '-t':
                    case '--taskdir':
                        $i++;
                        if (! array_key_exists($i, $argv)) {
                            require_once 'Phigrate/Exception/Argument.php';
                            throw new Phigrate_Exception_Argument(
                                'Please, specify the directory of tasks'
                                . ' if you use the argument -t or --taskdir'
                            );
                        }
                        $this->_taskDir[] = $argv[$i];
                        break;
                    // migration directory
                    case '-m':
                    case '--migrationdir':
                        $i++;
                        if (! array_key_exists($i, $argv)) {
                            require_once 'Phigrate/Exception/Argument.php';
                            throw new Phigrate_Exception_Argument(
                                'Please, specify the directory of migration files'
                                . ' if you use the argument -m or --migrationdir'
                            );
                        }
                        $this->_migrationDir = $argv[$i];
                        break;
                    // logs directory
                    case '-l':
                    case '--logdir':
                        $i++;
                        if (! array_key_exists($i, $argv)) {
                            require_once 'Phigrate/Exception/Argument.php';
                            throw new Phigrate_Exception_Argument(
                                'Please, specify the directory of log files'
                                . ' if you use the argument -l or --logdir'
                            );
                        }
                        $this->_logDir = $argv[$i];
                        break;
                    // other
                    default:
                        $arg = $argv[$i];
                        if (strpos($arg, ':') !== false) {
                            $this->_curTaskName = $arg;
                            continue;
                        } elseif ($arg == '-h' || $arg == '--help' || $arg == 'help') {
                            $this->_helpTask = true;
                            continue;
                        } elseif (strpos($arg, '=') !== false) {
                            list($key, $value) = explode('=', $arg);
                            if ($key == 'ENV') {
                                $this->_env = $value;
                            }
                            $options[$key] = $value;
                        } elseif (array_key_exists($i+1, $argv) && substr($argv[$i+1], 0, 1) != '-') {
                            $i++;
                            $options[(string)$arg] = $argv[$i];
                        } else {
                            $options[(string)$arg] = true;
                        }
                        break;
                }
            }
            $this->_taskOptions = $options;
        }
        if ($numArgs < 2 || ! isset($this->_curTaskName)) {
            require_once 'Phigrate/Exception/Argument.php';
            throw new Phigrate_Exception_Argument('No task found!');
        }
    }

    /**
     * _getConfigFile : Return the filename of config application
     *
     * @return string
     */
    private function _getConfigFile()
    {
        if (! isset($this->_configFile)) {
            require_once 'Phigrate/Exception/Config.php';
            throw new Phigrate_Exception_Config(
                'Config file not found! Please, '
                . 'create config file for application'
            );
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
            if (isset($this->getConfig()->database)
                && isset($this->getConfig()->database->config))
            {
                $this->_configDbFile = $this->_fileWithRealPath($this->getConfig()->database->config);
            } else {
                require_once 'Phigrate/Exception/Config.php';
                throw new Phigrate_Exception_Config(
                    'Config file for DB not found! Please, create config file'
                );
            }
        }
        $this->getLogger()->info('configDbFile: ' . $this->_configDbFile);
        $this->getLogger()->debug(__METHOD__ . ' End');
        return $this->_configDbFile;
    }

    /**
     * Return path file absolute
     *
     * @param string $file The file path
     *
     * @return string
     */
    private function _fileWithRealPath($file)
    {
        if (substr($file, 0, 1) === '/') {
            return $file;
        }
        return realpath(dirname($this->_getConfigFile()) . '/' . $file);
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
        if (! $configDb instanceof Phigrate_Config) {
            $msg = '(' . $env . ') DB is not configured!';
            require_once 'Phigrate/Exception/MissingConfigDb.php';
            throw new Phigrate_Exception_MissingConfigDb('Error: ' . $msg);
        }
        // Check only variable type for create Adapter
        // all other parameters will checked in adapter
        if (! isset($configDb->type)) {
            $msg = '"type" is not set for "' . $env . '" DB in config file';
            $this->_logger->err($msg);
            require_once 'Phigrate/Exception/MissingAdapterType.php';
            throw new Phigrate_Exception_MissingAdapterType('Error: ' . $msg);
        }
        $this->_logger->debug(__METHOD__ . ' End');
    }

    /**
     * Initialize and return an instance of logger
     *
     * @return Phigrate_Logger
     */
    private function _initLogger()
    {
        //initialize logger
        $logDir = '/tmp/';
        $config = $this->getConfig();
        if (isset($this->_logDir)) {
            // First in arguments of command line
            $logDir = $this->_logDir;
        } elseif (isset($config->log) && isset($config->log->dir)) {
            // Second in config file
            $logDir = $config->log->dir;
        }
        $tmp = $logDir;
        $logDir = $this->_fileWithRealPath($logDir);
        if (! is_dir($logDir)) {
            require_once 'Phigrate/Exception/InvalidLog.php';
            throw new Phigrate_Exception_InvalidLog(
                $tmp . ' does not exists.'
            );
        }
        if (is_dir($logDir) && ! is_writable($logDir)) {
            require_once 'Phigrate/Exception/InvalidLog.php';
            throw new Phigrate_Exception_InvalidLog(
                'Cannot write to log directory: '
                . $logDir . '. Check permissions.'
            );
        }
        $logger = Phigrate_Logger::instance(
            $logDir . '/' . $this->_env . '.log'
        );

        $priority = 99;
        if (isset($config->log->priority)) {
            $priority = $config->log->priority;
        } elseif ($this->_env == 'development') {
            $priority = Phigrate_Logger::DEBUG;
        } elseif ($this->_env == 'production') {
            $priority = Phigrate_Logger::INFO;
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
                $adapterClass = 'Phigrate_Adapter_Mysql_Adapter';
                break;
            case 'mssql':
            case 'pgsql':
            default:
                throw new Phigrate_Exception_InvalidAdapterType(
                    'Adapter "' . $dbType . '" not implemented!'
                );
        }
        return $adapterClass;
    }

    /**
     * Retourne l'en tête de presentation de Phigrate
     *
     * @return string
     */
    private function _getHeaderScript()
    {
        $head =<<<HEAD
 ____  _     _                 _
|  _ \| |__ (_) __ _ _ __ __ _| |_ ___
| |_) | '_ \| |/ _` | '__/ _` | __/ _ \
|  __/| | | | | (_| | | | (_| | ||  __/
|_|   |_| |_|_|\__, |_|  \__,_|\__\___|
               |___/


HEAD;
        return $head;
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
