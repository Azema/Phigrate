<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category  RuckusingMigrations
 * @package   Classes
 * @author    Cody Caughlan <toolbag@gmail.com>
 * @copyright 2010-2011 Cody Caughlan
 * @license   GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/ruckus/ruckusing-migrations
 */

/**
 * @see Ruckusing_TaskManager 
 */
require_once RUCKUSING_BASE . '/lib/classes/task/class.Ruckusing_TaskManager.php';

/**
 * Primary work-horse class. This class bootstraps the framework by loading
 * all adapters and tasks.
 *
 * @category  RuckusingMigrations
 * @package   Classes
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
     * @var Ruckusing_TaskManager
     */
	private $_taskMgr = null;
    /**
     * adapter 
     * 
     * @var Ruckusing_BaseAdapter
     */
	private $_adapter = null;
    /**
     * current task name 
     * 
     * @var string
     */
	private $_curTaskName = "";
    /**
     * task options 
     * 
     * @var string
     */
	private $_taskOptions = "";
    /**
     * Environment
     * default is development 
     * but can also be one 'test', 'production'
     * 
     * @var string
     */
	private $_env = "development"; //
	
    /**
	 * set up some defaults
     * 
     * @var array
     */
	private $_optMap = array('ENV' => 'development');
	
    /**
     * __construct 
     * 
     * @param array $db   Configs DB
     * @param array $argv Arguments of the command line
     *
     * @return Ruckusing_FrameworkRunner
     */
    function __construct($db, $argv)
    {
		try {
            set_error_handler(
                array(
                    'Ruckusing_FrameworkRunner', 
                    'scrErrorHandler',
                ), 
                E_ALL
            );

			//parse arguments
			$this->_parseArgs($argv);

			//initialize logger
			$log_dir = RUCKUSING_BASE . "/logs";
			if (is_dir($log_dir) && ! is_writable($log_dir)) {
                die(
                    "\n\nCannot write to log directory: "
                    . "{$log_dir}\n\nCheck permissions.\n\n"
                );
			} else if (!is_dir($log_dir)) {
				//try and create the log directory
				mkdir($log_dir);
			}
			$log_name = sprintf('%s.log', $this->_env);
            $this->logger = Ruckusing_Logger::instance(
                $log_dir . '/' . $log_name
            );
			
			//include all adapters
			$this->_loadAllAdapters(RUCKUSING_BASE . '/lib/classes/adapters');
			$this->_dbConfig = $db;
			$this->initializeDb();
			$this->initTasks();
		} catch (Exception $e) {
		}
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
		if ($this->_taskMgr->hasTask($this->_curTaskName)) {
            $output = $this->_taskMgr->execute(
                $this->_curTaskName, 
                $this->_taskOptions
            );
			$this->_displayResults($output);
			exit(0); // 0 is success
		} else {
			trigger_error(sprintf('Task not found: %s', $this->_curTaskName));
			exit(1);
		}
		if ($this->logger) {
            $this->logger->close();
        }
	}
	
    /**
     * initialize tasks 
     * 
     * @return void
     */
    public function initTasks()
    {
		$this->_taskMgr = new Ruckusing_TaskManager($this->_adapter);
	}
	
    /**
     * initialize db 
     * 
     * @return void
     */
    public function initializeDb()
    {
		try {
			$this->_verifyDbConfig();			
			$db = $this->_dbConfig[$this->_env];
			$adapter = $this->_getAdapterClass($db['type']);
			
			if ($adapter === null) {
                trigger_error(
                    sprintf(
                        'No adapter available for DB type: %s', 
                        $db['type']
                    )
                );
			}			
			//construct our adapter			
			$this->_adapter = new $adapter($db, $this->logger);
		} catch (Exception $ex) {
			trigger_error(sprintf("\n%s\n", $ex->getMessage()));
		}
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
			$this->_curTaskName = $argv[1];			
			$options = array();
			for ($i = 2; $i < $num_args;$i++) {
				$arg = $argv[$i];
				if (strpos($arg, '=') !== false) {
					list($key, $value) = explode("=", $arg);
					$options[$key] = $value;
					if ($key == 'ENV') {
						$this->_env = $value;
					}
				}
			}
			$this->_taskOptions = $options;
		}
	}

    /**
     * error_handler 
	 * Global error handler to process all errors during script execution
     * 
     * @param integer $errno   Error number
     * @param string  $errstr  Error message
     * @param string  $errfile Error file
     * @param string  $errline Error line
     *
     * @return void
     */
    public static function scrErrorHandler($errno, $errstr, $errfile, $errline)
    {
        echo sprintf(
            "\n\n(%s:%d) %s\n\n", 
            basename($errfile), 
            $errline, 
            $errstr
        );
		exit(1); // exit with error
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
        $migrator_util = new Ruckusing_MigratorUtil($this->_adapter);
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
     * display results 
     *
     * @param string $output The output to display
     * 
     * @deprecated
     *
     * @return void
     */
    private function _displayResults($output)
    {
		return;
		//deprecated
		echo "\nStarted: " . date('Y-m-d g:ia T') . "\n\n";
		echo "\n\n";
		echo "\n$output\n";
		echo "\nFinished: " . date('Y-m-d g:ia T') . "\n\n";
	}
	
    /**
     * set opt
     * 
     * @param string $key   The key of value
     * @param mixed  $value The value to set
     *
     * @return void
     */
    private function _setOpt($key, $value)
    {
		if (!$key) return;
		$this->_optMap[$key] = $value;		
	}
	
    /**
     * verify db config 
     * 
     * @return void
     * @throws Exception
     */
    private function _verifyDbConfig()
    {
        if (! is_array($this->_dbConfig) 
            || ! array_key_exists($this->_env, $this->_dbConfig)
        ) {
            throw new Exception(
                sprintf(
                    "Error: '%s' DB is not configured",
                    $this->_optMap[$ENV]
                )
            );
		}
		$env = $this->_env;
		$this->_activeDbConfig = $this->_dbConfig[$env];
		if (! array_key_exists('type', $this->_activeDbConfig)) {
            throw new Exception(
                sprintf("Error: 'type' is not set for '%s' DB", $env)
            );
		}
		if (! array_key_exists('host', $this->_activeDbConfig)) {
            throw new Exception(
                sprintf("Error: 'host' is not set for '%s' DB", $env)
            );			
		}
		if (! array_key_exists('database', $this->_activeDbConfig)) {
            throw new Exception(
                sprintf("Error: 'database' is not set for '%s' DB", $env)
            );			
		}
		if (! array_key_exists('user', $this->_activeDbConfig)) {
            throw new Exception(
                sprintf("Error: 'user' is not set for '%s' DB", $env)
            );			
		}
		if (! array_key_exists('password', $this->_activeDbConfig)) {
            throw new Exception(
                sprintf("Error: 'password' is not set for '%s' DB", $env)
            );			
		}
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
            $adapterClass = 'Ruckusing_MySQLAdapter';
            break;
        case 'mssql':
            throw new Exception('Adapter not implemented!');
            $adapterClass = 'Ruckusing_MSSQLAdapter';
            break;
        case 'pgsql':
            throw new Exception('Adapter not implemented!');
            $adapterClass = 'Ruckusing_PostgresAdapter';
            break;
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
			//skip over invalid files
			if ($f == '.' || $f == ".." || !preg_match($regex, $f)) continue;
			include_once $adapter_dir . '/' . $f;
		}
	}
}
