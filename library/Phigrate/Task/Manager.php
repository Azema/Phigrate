<?php

/**
 * Phigrate
 *
 * PHP Version 5.3
 *
 * @category   Phigrate
 * @package    Phigrate_Task
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */

/**
 * @see Phigrate_Util_Naming
 */
require_once 'Phigrate/Util/Naming.php';

/**
 * Manager of tasks
 *
 * @category   Phigrate
 * @package    Phigrate_Task
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */
class Phigrate_Task_Manager
{
    /**
     * adapter
     *
     * @var Phigrate_Adapter_Base
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
     * _migrationDir
     *
     * @var string
     */
    private $_migrationDir;

    /**
     * _logger
     *
     * @var Phigrate_Logger
     */
    private $_logger;

    /**
     * __construct
     *
     * @param Phigrate_Adapter_Base $adapter       Adapter RDBMS
     * @param string|array          $tasksDir      The path of directory of tasks
     * @param string                $migrationsDir The path of directory of migrations.
     *
     * @return Phigrate_Task_Manager
     */
    public function __construct(Phigrate_Adapter_Base $adapter, $tasksDir = null, $migrationsDir = null)
    {
        $this->_logger = $adapter->getLogger();
        $this->setAdapter($adapter);
        if (isset($migrationsDir)) {
            $this->setDirectoryOfMigrations($migrationsDir);
        }
        if (isset($tasksDir)) {
            $this->setDirectoryOfTasks($tasksDir, true);
        }
    }

    /**
     * set directory of tasks
     *
     * @param string|array  $tasksDir The path of directory of tasks
     * @param boolean       $reload   Reload all tasks
     *
     * @return Phigrate_Task_Manager
     * @throws Phigrate_Exception_Argument
     */
    public function setDirectoryOfTasks($tasksDir, $reload = false)
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        if (!is_array($tasksDir)) {
            $tasksDir = array($tasksDir);
        }
        $this->_tasks = array();
        foreach ($tasksDir as $dir) {
            if (! is_dir($dir)) {
                $msg = 'Task dir: "' . $dir . '" does not exist!';
                $this->_logger->err($msg);
                require_once 'Phigrate/Exception/Argument.php';
                throw new Phigrate_Exception_Argument($msg);
            }
            if (empty($this->_tasksDir) || !in_array($dir, $this->_tasksDir)) {
                $this->_tasksDir[] = $dir;
            }
        }
        if ($reload) {
            $this->_loadAllTasks();
        }
        $this->_logger->debug(__METHOD__ . ' End');
        return $this;
    }

    /**
     * set directory of migrations
     *
     * @param string $migrationsDir The path of directory of migrations
     *
     * @return Phigrate_Task_Manager
     * @throws Phigrate_Exception_Argument
     */
    public function setDirectoryOfMigrations($migrationsDir)
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        if (! is_dir($migrationsDir)) {
            $msg = 'Migration dir: "' . $migrationsDir . '" does not exist!';
            $this->_logger->err($msg);
            require_once 'Phigrate/Exception/Argument.php';
            throw new Phigrate_Exception_Argument($msg);
        }
        $this->_migrationDir = $migrationsDir;
        $this->_logger->debug('migrationDir: ' . $this->_migrationDir);
        $this->_logger->debug(__METHOD__ . ' End');
        return $this;
    }

    /**
     * set adapter
     *
     * @param Phigrate_Adapter_Base $adapter Adapter RDBMS
     *
     * @return Phigrate_Task_Manager
     */
    public function setAdapter($adapter)
    {
        if (! $adapter instanceof Phigrate_Adapter_Base) {
            require_once 'Phigrate/Exception/Argument.php';
            throw new Phigrate_Exception_Argument(
                'Adapter must be implement Phigrate_Adapter_Base!'
            );
        }
        $this->_adapter = $adapter;
        foreach ($this->_tasks as $task) {
            $task->setAdapter($adapter);
        }
        return $this;
    }

    /**
     * get adapter
     *
     * @return Phigrate_Adapter_Base
     */
    public function getAdapter()
    {
        return $this->_adapter;
    }

    /**
     * getLogger
     *
     * @return Phigrate_Logger
     */
    public function getLogger()
    {
        return $this->_logger;
    }

    /**
     * Searches for the given task, and if found
     * returns it. Otherwise null is returned.
     *
     * @param string $key The key to identify the task
     *
     * @return Phigrate_ITask
     * @throws Phigrate_Exception_InvalidTask
     */
    public function getTask($key)
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        $this->_logger->debug('TaskName: ' . $key);
        if (empty($this->_tasks)) {
            $this->_loadAllTasks();
        }
        if (! $this->hasTask($key)) {
            $msg = 'Task (' . $key . ') is not registered.';
            $this->_logger->err($msg);
            require_once 'Phigrate/Exception/InvalidTask.php';
            throw new Phigrate_Exception_InvalidTask($msg);
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
        $this->_logger->debug(__METHOD__ . ' Start : ' . $key);
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
     * @param Phigrate_Task_ITask $taskObj  The task object
     *
     * @return boolean
     */
    public function registerTask($taskName, $taskObj)
    {
        $this->_logger->debug(__METHOD__ . ' Start');

        if ($this->hasTask($taskName)) {
            $msg = sprintf("Task name '%s' is already defined!", $taskName);
            $this->_logger->warn($msg);
            require_once 'Phigrate/Exception/Argument.php';
            throw new Phigrate_Exception_Argument($msg);
        }

        if (! $taskObj instanceof Phigrate_Task_ITask) {
            $msg = 'Task (' . $taskName . ') does not implement Phigrate_ITask';
            $this->_logger->warn($msg);
            require_once 'Phigrate/Exception/Argument.php';
            throw new Phigrate_Exception_Argument($msg);
        }
        $this->_logger->debug('migrationDir: ' . $this->_migrationDir);
        $taskObj->setDirectoryOfMigrations($this->_migrationDir);
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
    public function execute($taskName, $options = array())
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

    //---------------------
    // PRIVATE METHODS
    //---------------------
    /**
     * load all tasks
     *
     * @return void
     * @throws Phigrate_Exception_Argument
     */
    private function _loadAllTasks()
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        if (! isset($this->_tasksDir)) {
            $msg = 'Please: you must specify the directory of tasks!';
            $this->_logger->err($msg);
            throw new Phigrate_Exception_Argument($msg);
        }
        foreach ($this->_tasksDir as $index => $taskDir) {
            $namespaces = scandir($taskDir);
            $this->_logger->debug('Tasks dir['.$index.']: ' . $taskDir);
            //$this->_tasks = array();
            foreach ($namespaces as $namespace) {
                //skip over invalid files
                if ($namespace == '.' || $namespace == '..'
                    || ! is_dir($taskDir . '/' . $namespace))
                {
                    continue;
                }
                $this->_logger->debug('Namespace: ' . $namespace);

                $files = scandir($taskDir . '/' . $namespace);
                foreach ($files as $file) {
                    $ext = substr($file, -4);
                    //skip over invalid files
                    if ($file == '.' || $file == '..' || $ext != '.php') {
                        continue;
                    }
                    $this->_logger->debug('include ' . $namespace . '/' . $file);
                    require_once $taskDir . '/' . $namespace . '/' . $file;
                    $class = Phigrate_Util_Naming::classFromFileName(
                        $taskDir . '/' . $namespace . '/' . $file
                    );
                    $refl = new ReflectionClass($class);
                    if ($refl->isInstantiable()) {
                        $this->_logger->debug('className ' . $class);
                        $taskName = Phigrate_Util_Naming::taskFromClassName($class);
                        $this->_logger->debug('TaskName: ' . $taskName);
                        $taskObj = new $class($this->getAdapter());
                        $this->_logger->debug('obj: ' . get_class($taskObj));
                        $this->registerTask($taskName, $taskObj);
                    }
                }
            }
        }
        $this->_logger->debug(__METHOD__ . ' End');
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
