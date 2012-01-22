<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
 * @package    Ruckusing_Task
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
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
 * @package    Ruckusing_Task
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
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
     * _migrationDir
     *
     * @var string
     */
    private $_migrationDir;

    /**
     * _logger
     *
     * @var Ruckusing_Logger
     */
    private $_logger;

    /**
     * __construct
     *
     * @param Ruckusing_Adapter_Base $adapter       Adapter RDBMS
     * @param string                 $tasksDir      The path of directory of tasks
     * @param string                 $migrationsDir The path of directory of migrations.
     *
     * @return Ruckusing_Task_Manager
     */
    public function __construct(Ruckusing_Adapter_Base $adapter, $tasksDir = null, $migrationsDir = null)
    {
        $this->_logger = $adapter->getLogger();
        $this->setAdapter($adapter);
        if (isset($tasksDir)) {
            $this->setDirectoryOfTasks($tasksDir, true);
        }
        if (isset($migrationsDir)) {
            $this->setDirectoryOfMigrations($migrationsDir);
        }
    }

    /**
     * set directory of tasks
     *
     * @param string  $tasksDir The path of directory of tasks
     * @param boolean $reload   Reload all tasks
     *
     * @return Ruckusing_Task_Manager
     * @throws Ruckusing_Exception_Argument
     */
    public function setDirectoryOfTasks($tasksDir, $reload = false)
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        if (! is_dir($tasksDir)) {
            $msg = 'Task dir: "' . $tasksDir . '" does not exist!';
            $this->_logger->err($msg);
            require_once 'Ruckusing/Exception/Argument.php';
            throw new Ruckusing_Exception_Argument($msg);
        }
        if (! isset($this->_tasksDir) || $tasksDir != $this->_tasksDir) {
            $this->_tasksDir = $tasksDir;
            $this->_tasks = array();
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
     * @return Ruckusing_Task_Manager
     * @throws Ruckusing_Exception_Argument
     */
    public function setDirectoryOfMigrations($migrationsDir)
    {
        $this->_logger->debug(__METHOD__ . ' Start');
        if (! is_dir($migrationsDir)) {
            $msg = 'Migration dir: "' . $migrationsDir . '" does not exist!';
            $this->_logger->err($msg);
            require_once 'Ruckusing/Exception/Argument.php';
            throw new Ruckusing_Exception_Argument($msg);
        }
        $this->_migrationDir = $migrationsDir;
        $this->_logger->debug('migrationDir: ' . $this->_migrationDir);
        $this->_logger->debug(__METHOD__ . ' End');
        return $this;
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
        if (! $adapter instanceof Ruckusing_Adapter_Base) {
            require_once 'Ruckusing/Exception/Argument.php';
            throw new Ruckusing_Exception_Argument(
                'Adapter must be implement Ruckusing_Adapter_Base!'
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
     * @return Ruckusing_Adapter_Base
     */
    public function getAdapter()
    {
        return $this->_adapter;
    }

    /**
     * getLogger
     *
     * @return Ruckusing_Logger
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
     * @return Ruckusing_ITask
     * @throws Ruckusing_Exception_InvalidTask
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
    public function registerTask($taskName, $taskObj)
    {
        $this->_logger->debug(__METHOD__ . ' Start');

        if ($this->hasTask($taskName)) {
            $msg = sprintf("Task name '%s' is already defined!", $taskName);
            $this->_logger->warn($msg);
            require_once 'Ruckusing/Exception/Argument.php';
            throw new Ruckusing_Exception_Argument($msg);
        }

        if (! $taskObj instanceof Ruckusing_Task_ITask) {
            $msg = 'Task (' . $taskName . ') does not implement Ruckusing_ITask';
            $this->_logger->warn($msg);
            require_once 'Ruckusing/Exception/Argument.php';
            throw new Ruckusing_Exception_Argument($msg);
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
        $this->_tasks = array();
        foreach ($namespaces as $namespace) {
            $this->_logger->debug('Namespace: ' . $namespace);
            //skip over invalid files
            if ($namespace == '.' || $namespace == '..'
                || ! is_dir($this->_tasksDir . '/' . $namespace)
            ) {
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
                $klass = Ruckusing_Util_Naming::classFromFileName(
                    $this->_tasksDir . '/' . $namespace . '/' . $file
                );
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
