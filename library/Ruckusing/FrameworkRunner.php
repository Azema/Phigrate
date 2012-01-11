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
            //include all adapters
            $this->_loadAllAdapters(RUCKUSING_BASE . '/library/Ruckusing/Adapter');
            // initialize DB
            $this->initializeDb();
            $this->initTasks();
        } catch (Exception $e) {
            $this->_logger->err('Exception: ' . $e->getMessage());
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
                ob_start();
                $this->_taskMgr->execute(
                    $this->_curTaskName, 
                    $this->_taskOptions
                );
                $output = ob_get_clean();
            }
        } else {
            $msg = 'Task not found: ' . $this->_curTaskName;
            $this->_logger->err($msg);
            throw new Ruckusing_Exception_InvalidTask($msg);
        }
        $this->_logger->debug(__METHOD__ . ' End');
        return $output;
    }
    
    /**
     * initialize tasks 
     *
     * @param array $config Config application
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
                throw new Ruckusing_Exception(
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
        if (! isset($config->migration) && ! isset($config->migration->dir)) {
            throw new Ruckusing_Exception(
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
        $num_args = count($argv);

        if ($num_args >= 2) {
            $options = array();
            for ($i = 1; $i < $num_args; $i++) {
                switch ($argv[$i]) {
                    // help for command line
                    case '-h':
                    case '--help':
                    case '-?':
                        printHelp(true);
                        break;
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
                        $this->_taskdir = $argv[$i];
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
        if ($num_args < 2 || !isset($this->_curTaskName)) {
            throw new InvalidArgumentException('No task found!');
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
        $migrator_util = new Ruckusing_Util_Migrator($this->_adapter);
        $files = $migrator_util->getMigrationFiles(
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
        if (!isset($this->_configFile)) {
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
        if (!isset($this->_configDbFile)) {
            $this->_configDbFile = RUCKUSING_BASE . '/config/database.ini';
        }
        $this->getLogger()->info('configDbFile: ' . $this->_configDbFile);
        $this->getLogger()->debug(__METHOD__ . ' End');
        return $this->_configDbFile;
    }

    /**
     * getConfigFromFile : Return sectionName from filename 
     * 
     * @param string $filename    The config file name
     * @param string $sectionName The section name
     *
     * @return array
     */
    private function _getConfigFromFile($filename, $sectionName)
    {
        if (! is_file($filename)) {
            throw new Exception('Config file not found (' . $filename . ')');
        }
        $ini_array = parse_ini_file($filename, true);
        if (! array_key_exists($sectionName, $ini_array)) {
            $found = false;
            $regExp = '/^'.$sectionName.'\s?:\s?(\w+)$/';
            foreach ($ini_array as $name => $section) {
                if (preg_match($regExp, $name, $matches)) {
                    $sectionExtended = $this->_getConfigFromFile(
                        $filename, 
                        $matches[1]
                    );
                    $config = array_merge($sectionExtended, $section);
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                throw new Exception(
                    'Section "' . $sectionName 
                    . '" not found in config file : ' . $filename
                );
            }
        } else {
            $config = $ini_array[$sectionName];
        }
        return $config;
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
            throw new Ruckusing_Exception_MissingConfigDb('Error: ' . $msg);
        }
        $exception = null;
        if (! isset($configDb->type)) {
            $msg = '"type" is not set for "' . $env . '" DB';
            $this->_logger->err($msg);
            $exception = new Ruckusing_Exception_MissingAdapterType('Error: ' . $msg);
        }
        if (! isset($configDb->host)) {
            $msg = '"host" is not set for "' . $env . '" DB';
            $this->_logger->err($msg);
            $exception = new Ruckusing_Exception_MissingConfigDb('Error: ' . $msg);
        }
        if (! isset($configDb->database)) {
            $msg = '"database" is not set for "' . $env . '" DB';
            $this->_logger->err($msg);
            $exception = new Ruckusing_Exception_MissingConfigDb('Error: ' . $msg);
        }
        if (! isset($configDb->user)) {
            $msg = '"user" is not set for "' . $env . '" DB';
            $this->_logger->err($msg);
            $exception = new Ruckusing_Exception_MissingConfigDb('Error: ' . $msg);
        }
        if (! isset($configDb->password)) {
            $msg = '"password" is not set for "' . $env . '" DB';
            $this->_logger->err($msg);
            $exception = new Ruckusing_Exception_MissingConfigDb('Error: ' . $msg);
        }
        if (isset($exception)) {
            throw $exception;
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
        $log_dir = RUCKUSING_BASE . '/logs';
        $config = $this->getConfig();
        // @TODO : Ajouter une verification de la presence de la variable 'log'
        if (isset($config->log->dir)) {
            $log_dir = $config->log->dir;
        }
        if (is_dir($log_dir) && ! is_writable($log_dir)) {
            throw new Exception(
                "\n\nCannot write to log directory: "
                . "{$log_dir}\n\nCheck permissions.\n\n"
            );
        }
        $logger = Ruckusing_Logger::instance($log_dir . '/' . $this->_env . '.log');

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
    
    /**
     * DB adapters are classes in lib/classes/adapters
     * and they follow the file name syntax of "class.<DB Name>Adapter.php".
     * 
     * See the function "_getAdapterClass" in this class for examples.
     * 
     * @param string $adapter_dir Directory path of adapters
     *
     * @return void
     */
    private function _loadAllAdapters($adapter_dir)
    {
        if (! is_dir($adapter_dir)) {
            trigger_error(
                sprintf("Adapter dir: %s does not exist", $adapter_dir)
            );
            return false;
        }
        $files = scandir($adapter_dir);
        $regex = '/^class\.(\w+)Adapter\.php$/';
        foreach ($files as $f) {
            if ($f == '.' || $f == ".." || ! is_dir($f)) continue;
            include_once $adapter_dir . $f . '/Adapter.php';
        }
    }
}
