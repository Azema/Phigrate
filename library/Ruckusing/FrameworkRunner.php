<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category  RuckusingMigrations
 * @package   Ruckusing
 * @author    Cody Caughlan <toolbag@gmail.com>
 * @copyright 2010-2011 Cody Caughlan
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
 * @author    Cody Caughlan <toolbag@gmail.com>
 * @copyright 2010-2011 Cody Caughlan
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
     * the currently active config 
     * 
     * @var mixed
     */
    private $_activeDbConfig;

    /**
     * all available DB configs (e.g. test,development, production)
     * 
     * @var array
     */
    private $_dbConfig = array();

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
     * @param array            $db     Config environment of DBs
     * @param array            $argv   Arguments of the command line
     * @param string           $env    Environment
     * @param Ruckusing_Logger $logger Instance logger
     *
     * @return Ruckusing_FrameworkRunner
     */
    function __construct($config, $configDb, $argv, $env, $logger)
    {
        $this->setLogger($logger);
        $this->_logger->debug(__METHOD__ . ' Start');
        $this->_env = $env;
        try {
            //include all adapters
            $this->_loadAllAdapters(RUCKUSING_BASE . '/library/Ruckusing/Adapter');
            // initialize DB
            $this->_dbConfig = $configDb;
            $this->initializeDb();
            //parse arguments
            $this->_parseArgs($argv);
            $this->initTasks($config);
        } catch (Exception $e) {
            $this->_logger->err('Exception: ' . $e->getMessage());
            throw $e;
        }
        $this->_logger->debug(__METHOD__ . ' End');
    }

    /**
     * setLogger 
     * 
     * @param Ruckusing_Logger $logger The logger
     *
     * @return Ruckusing_FrameworkRunner
     */
    public function setLogger(Ruckusing_Logger $logger)
    {
        $this->_logger = $logger;
        return $this;
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
    public function initTasks($config)
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        $this->_taskMgr = new Ruckusing_Task_Manager($this->_adapter, $config['task.dir']);
        $this->_logger->debug(__METHOD__ . ' End');
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
            $this->_logger->debug('Config DB ' . var_export($this->_dbConfig, true));
            $adapter = $this->_getAdapterClass($this->_dbConfig['type']);
            $this->_logger->debug('adapter ' . $adapter);
            //construct our adapter         
            $this->_adapter = new $adapter($this->_dbConfig, $this->_logger);
        } catch (Ruckusing_Exception $rex) {
            $this->_logger->warn('Exception: ' . $rex->getMessage());
            //trigger_error(sprintf("\n%s\n", $ex->getMessage()));
            throw $ex;
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
        $this->_logger->debug(__METHOD__ . ' Start');
        $num_args = count($argv);

        if ($num_args >= 2) {
            $options = array();
            for ($i = $num_args-1; $i >= 1; $i--) {
                $arg = $argv[$i];
                $this->_logger->debug(
                    'arg: ' . $arg . ' - index: ' . $i
                );
                if (strpos($arg, ':') !== false) {
                    $this->_curTaskName = $arg;
                    continue;
                } elseif ($arg == 'help') {
                    $this->_helpTask = true;
                    continue;
                } elseif (strpos($arg, '=') !== false) {
                    list($key, $value) = explode('=', $arg);
                    $options[$key] = $value;
                }
            }
            $options['ENV'] = $this->_env;
            $this->_taskOptions = $options;
        }
        if ($num_args < 2 || !isset($this->_curTaskName)) {
            throw new InvalidArgumentException('No task found!');
        }
        $this->_logger->debug(__METHOD__ . ' End');
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
        $files = $migrator_util->getMigrationFiles(RUCKUSING_MIGRATION_DIR, 'up');
        foreach ($files as $file) {
            if ((int)$file['version'] >= PHP_INT_MAX) {
                //its new style like '20081010170207' so its not a candidate
                continue;
            }
            //query old table, if it less than or equal to our max version, then its a candidate for insertion     
            $query_sql = sprintf(
                'SELECT version FROM %s WHERE version >= %d', 
                RUCKUSING_SCHEMA_TBL_NAME, 
                $file['version']
            );
            $existing_version_old_style = $this->_adapter->selectOne($query_sql);
            if (count($existing_version_old_style) > 0) {
                //make sure it doesnt exist in our new table, who knows how it got inserted?
                $new_vers_sql = sprintf(
                    'SELECT version FROM %s WHERE version = %d', 
                    RUCKUSING_TS_SCHEMA_TBL_NAME, 
                    $file['version']
                );
                $existing_version_new_style = $this->_adapter->selectOne($new_vers_sql);
                if (empty($existing_version_new_style)) {       
                    // use printf & %d to force it to be stripped of any leading zeros, we *know* this represents an old version style
                    // so we dont have to worry about PHP and integer overflow
                    $insert_sql = sprintf(
                        'INSERT INTO %s (version) VALUES (%d)', 
                        RUCKUSING_TS_SCHEMA_TBL_NAME,
                        $file['version']
                    );
                    $this->_adapter->query($insert_sql);
                }
            }
        }
    }

    //-------------------------
    // PRIVATE METHODS
    //------------------------- 
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
        if (! is_array($this->_dbConfig)) {
            $msg = '(' . $env . ') DB is not configured!';
            $this->_logger->err($msg);
            throw new Ruckusing_Exception_MissingConfigDb('Error: ' . $msg);
        }
        $exception = null;
        if (! array_key_exists('type', $this->_dbConfig)) {
            $msg = '"type" is not set for "' . $env . '" DB';
            $this->_logger->err($msg);
            $exception = new Ruckusing_Exception_MissingAdapterType('Error: ' . $msg);
        }
        if (! array_key_exists('host', $this->_dbConfig)) {
            $msg = '"host" is not set for "' . $env . '" DB';
            $this->_logger->err($msg);
            $exception = new Ruckusing_Exception_MissingConfigDb('Error: ' . $msg);
        }
        if (! array_key_exists('database', $this->_dbConfig)) {
            $msg = '"database" is not set for "' . $env . '" DB';
            $this->_logger->err($msg);
            $exception = new Ruckusing_Exception_MissingConfigDb('Error: ' . $msg);
        }
        if (! array_key_exists('user', $this->_dbConfig)) {
            $msg = '"user" is not set for "' . $env . '" DB';
            $this->_logger->err($msg);
            $exception = new Ruckusing_Exception_MissingConfigDb('Error: ' . $msg);
        }
        if (! array_key_exists('password', $this->_dbConfig)) {
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
