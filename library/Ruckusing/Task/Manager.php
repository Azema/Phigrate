<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
 * @package    Ruckusing_Util
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */

/**
 * @see Ruckusing_Util_Naming 
 */
require_once 'Ruckusing/Util/Naming.php';

/**
 * Manager of tasks
 *
 * @category   RuckusingMigrations
 * @package    Ruckusing_Util
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_Task_Manager
{
    /**
     * adapter 
     * 
     * @var Ruckusing_Adapter_Base
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
     * @param Ruckusing_Adapter_Base $adapter  Adapter RDBMS
     * @param string                 $tasksDir The path of directory of tasks
     *
     * @return Ruckusing_Task_Manager
     */
    function __construct($adapter, $tasksDir)
    {
        $this->_logger = Ruckusing_Logger::instance();
        $this->setAdapter($adapter);
        $this->setDirectoryOfTasks($tasksDir);
        $this->_loadAllTasks();
    }

    /**
     * set directory of tasks 
     * 
     * @param string $tasksDir The path of directory of tasks
     *
     * @return Ruckusing_Task_Manager
     * @throws Ruckusing_Exception_Argument
     */
    public function setDirectoryOfTasks($tasksDir)
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        if (! is_dir($tasksDir)) {
            $msg = 'Task dir: "' . $tasksDir . '" does not exist!';
            $this->_logger->err($msg);
            require_once 'Ruckusing/Exception/Argument.php';
            throw new Ruckusing_Exception_Argument($msg);
        }
        $this->_tasksDir = $tasksDir;
        return $this;
        $this->_logger->debug(__METHOD__ . ' End');
    }
    
    /**
     * set adapter 
     *
     * @param Ruckusing_Adapter_Base $adapter Adapter RDBMS
     * 
     * @return Ruckusing_Task_Manager
     */
    public function setAdapter($adapter) 
    {
        $this->_adapter = $adapter;
        return $this;
    }

    /**
     * get adapter 
     * 
     * @return Ruckusing_Adapter_Base
     */
    public function getAdapter()
    {
        return $this->_adapter;
    }
    
    /**
     * Searches for the given task, and if found
     * returns it. Otherwise null is returned.
     * 
     * @param string $key The key to identify the task
     *
     * @return Ruckusing_ITask
     * @throws Ruckusing_Exception_InvalidTask
     */
    public function getTask($key)
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        $this->_logger->debug('TaskName: ' . $key);
        if (! $this->hasTask($key)) {
            $msg = 'Task (' . $key . ') is not registered.';
            $this->_logger->err($msg);
            require_once 'Ruckusing/Exception/InvalidTask.php';
            throw new Ruckusing_Exception_InvalidTask($msg);
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
     * @param string               $taskName The name of task
     * @param Ruckusing_Task_ITask $taskObj  The task object
     *
     * @return boolean
     */
    public function registerTask($taskName, Ruckusing_Task_ITask $taskObj)
    {
        $this->_logger->debug(__METHOD__ . ' Start');

        if ($this->hasTask($taskName)) {
            $this->_logger->warn('Task name ' . $taskName . ' is already defined!');
            trigger_error(sprintf("Task name '%s' is already defined!", $taskName));
            return false;
        }

        if (! $taskObj instanceof Ruckusing_Task_ITask) {
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
    
    /**
     * get name 
     * 
     * @TODO: Voir si cette methode est utilisée
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
     * @throws Ruckusing_Exception_Argument
     */
    private function _loadAllTasks()
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        if (! isset($this->_tasksDir)) {
            $msg = 'Please: you must specify the directory of tasks!';
            $this->_logger->err($msg);
            throw new Ruckusing_Exception_Argument($msg);
        }
        $namespaces = scandir($this->_tasksDir);
        $this->_logger->debug('Task dir: ' . $this->_tasksDir);
        $regex = '/^(\w+)\.php$/';
        foreach ($namespaces as $namespace) {
            $this->_logger->debug('Namespace: ' . $namespace);
            //skip over invalid files
            if ($namespace == '.' || $namespace == '..' || ! is_dir($this->_tasksDir . '/' . $namespace)) {
                continue;
            }
            $this->_logger->debug('Namespace: ' . $namespace);

            $files = scandir($this->_tasksDir . '/' . $namespace);
            foreach ($files as $file) {
                $ext = substr($file, -4);
                $basename = substr($file, 0, -4);
                //skip over invalid files
                if ($file == '.' || $file == '..' || $ext != '.php') {
                    continue;
                }
                $this->_logger->debug('include ' . $namespace . '/' . $file);
                //include_once $this->_tasksDir . '/' . $namespace . '/' . $file;
                $klass = Ruckusing_Util_Naming::classFromFileName($this->_tasksDir . '/' . $namespace . '/' . $file);
                $this->_logger->debug('className ' . $klass);
                $taskName = Ruckusing_Util_Naming::taskFromClassName($klass);
                $this->_logger->debug('TaskName: ' . $taskName);
                $taskObj = new $klass($this->getAdapter());
                $this->_logger->debug('obj: ' . get_class($taskObj));
                $this->registerTask($taskName, $taskObj);
            }
        }
        $this->_logger->debug(__METHOD__ . ' End');
    }
}
