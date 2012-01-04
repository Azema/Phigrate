<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
 * @package    Classes
 * @subpackage Task
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */

/**
 * @see Ruckusing_NamingUtil 
 */
require_once RUCKUSING_BASE . '/lib/classes/util/class.Ruckusing_NamingUtil.php';

/**
 * Manager of tasks
 *
 * @category   RuckusingMigrations
 * @package    Classes
 * @subpackage Task
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_TaskManager
{
    /**
     * adapter 
     * 
     * @var Ruckusing_BaseAdapter
     */
    private $_adapter;

    /**
     * tasks 
     * 
     * @var array
     */
    private $_tasks = array();

    /**
     * Directory of tasks 
     * 
     * @var string
     */
    private $_tasksDir;

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
     * @return Ruckusing_TaskManager
     */
    function __construct($adapter)
    {
        $this->setAdapter($adapter);
        $this->_logger = Ruckusing_Logger::instance();
        $this->_loadAllTasks();
    }

    /**
     * set directory of tasks 
     * 
     * @param string $tasksDir The path of directory of tasks
     *
     * @return Ruckusing_TaskManager
     */
    public function setDirectoryOfTasks($tasksDir)
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        if (! is_dir($taskDir)) {
            $msg = 'Task dir: "' . $taskDir . '" does not exist!';
            $this->_logger->err($msg);
            throw new InvalidArgumentException($msg);
        }
        $this->_tasksDir = $tasksDir;
        return $this;
        $this->_logger->debug(__METHOD__ . ' End');
    }
    
    /**
     * set adapter 
     *
     * @param Ruckusing_BaseAdapter $adapter Adapter RDBMS
     * 
     * @return Ruckusing_TaskManager
     */
    public function setAdapter($adapter) 
    {
        $this->_adapter = $adapter;
        return $this;
    }

    /**
     * get adapter 
     * 
     * @return Ruckusing_BaseAdapter
     */
    public function getAdapter()
    {
        return $this->_adapter;
    }
    
    /**
     * Searches for the given task, and if found
     * returns it. Otherwise null is returned.
     * 
     * @param mixed $key The key to identify the task
     *
     * @return Ruckusing_ITask
     * @throws Exception
     */
    public function getTask($key)
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        $this->_logger->debug('TaskName: ' . $key);
        if (! $this->hasTask($key)) {
            $this->_logger->err('Task (' . $key . ') is not registered.');
            throw new Exception("Task '{$key}' is not registered.");
        }
        $this->_logger->debug(__METHOD__ . ' End');
        return $this->_tasks[$key];
    }

    /**
     * has task 
     * 
     * @param string $key The key to identitfy the task
     *
     * @return boolean
     */
    public function hasTask($key)
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        $exists = false;
        if (array_key_exists($key, $this->_tasks)) {
            $exists = true;
        }
        $this->_logger->debug(__METHOD__ . ' End');
        return $exists;
    }

    /**
     * Register a new task name under the specified key.
     * $obj is a class which implements the ITask interface
     * and has an execute() method defined.
     * 
     * @param string          $taskName The name of task
     * @param Ruckusing_ITask $taskObj  The task object
     *
     * @return boolean
     */
    public function registerTask($taskName, Ruckusing_ITask $taskObj)
    {
        $this->_logger->debug(__METHOD__ . ' Start');

        if ($this->hasTask($taskName)) {
            $this->_logger->warn('Task name ' . $taskName . ' is already defined!');
            trigger_error(sprintf("Task name '%s' is already defined!", $taskName));
            return false;
        }

        if (! $taskObj instanceof Ruckusing_ITask) {
            $msg = 'Task (' . $taskName . ') does not implement Ruckusing_ITask';
            $this->_logger->warn($msg);
            trigger_error($msg);
            return false;
        }
        $this->_tasks[$taskName] = $taskObj;
        $this->_logger->debug(__METHOD__ . ' End');
        return true;
    }
    
    /**
     * get name 
     * 
     * @return void
     */
    public function getName()
    {
    }
    
    //---------------------
    // PRIVATE METHODS
    //---------------------
    /**
     * load all tasks 
     * 
     * @param string $taskDir Directory path of tasks
     *
     * @return void
     * @throws Exception
     */
    private function _loadAllTasks()
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        if (! isset($this->_tasksDir)) {
            $msg = 'Please: you must specify the directory of tasks!';
            $this->_logger->err($msg);
            throw new InvalidArgumentException($msg);
        }
        if (! is_dir($this->_tasksDir)) {
            $msg = 'Task dir: "' . $this->_tasksDir . '" does not exist!';
            $this->_logger->err($msg);
            throw new Exception($msg);
        }
        $files = scandir($this->_tasksDir);
        $regex = '/^class\.(\w+)\.php$/';
        foreach ($files as $f) {
            //skip over invalid files
            if ($f == '.' || $f == '..' || ! preg_match($regex, $f, $matches)) {
                continue;
            }
            $this->_logger->debug('include ' . $f);
            include_once $this->_tasksDir . '/' . $f;
            $taskName = Ruckusing_NamingUtil::taskFromClassName($matches[1]);
            $this->_logger->debug('TaskName: ' . $taskName);
            $klass = Ruckusing_NamingUtil::classFromFileName($f);
            $this->_logger->debug('className ' . $klass);
            $taskObj = new $klass($this->getAdapter());
            $this->_logger->debug('obj: ' . get_class($taskObj));
            $this->registerTask($taskName, $taskObj);
        }
        $this->_logger->debug(__METHOD__ . ' End');
    }
    
    /**
     * execute 
     * 
     * @param string $taskName The task name 
     * @param array  $options  The options of task
     *
     * @return string
     */
    public function execute($taskName, $options)
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        $task = $this->getTask($taskName);
        $output = $task->execute($options);
        $this->_logger->debug(__METHOD__ . ' End');
        return $output;
    }
    
    /**
     * Get display help of task
     * 
     * @param string $taskName The task name 
     *
     * @return string
     */
    public function help($taskName)
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        $task = $this->getTask($taskName);
        $output = $task->help();
        $this->_logger->debug(__METHOD__ . ' End');
        return $output;
    }
}
