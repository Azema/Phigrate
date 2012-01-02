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

/** @var String Directory of tasks */
define('RUCKUSING_TASK_DIR', RUCKUSING_BASE . '/lib/tasks');

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
     * __construct 
     * 
     * @param Ruckusing_BaseAdapter $adapter Adapter RDBMS
     *
     * @return Ruckusing_TaskManager
     */
    function __construct($adapter)
    {
		$this->setAdapter($adapter);
		$this->_loadAllTasks(RUCKUSING_TASK_DIR);
	}
	
    /**
     * set adapter 
     *
     * @param Ruckusing_BaseAdapter $adapter Adapter RDBMS
     * 
     * @return void
     */
    public function setAdapter($adapter) 
    {
		$this->_adapter = $adapter;
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
     * @return Ruckusing_iTask
     */
    public function getTask($key)
    {
		if (array_key_exists($key, $this->_tasks)) {
			return $this->_tasks[$key];
		} else {
			return null;
		}
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
		if (array_key_exists($key, $this->_tasks)) {
			return true;
		} else {
			return false;
		}
	}

    /**
	 * Register a new task name under the specified key.
	 * $obj is a class which implements the iTask interface
	 * and has an execute() method defined.
     * 
     * @param string          $key The key to identify task
     * @param Ruckusing_iTask $obj The task object
     *
     * @return boolean
     */
    public function registerTask($key, $obj)
    {
		if (array_key_exists($key, $this->_tasks)) {
			trigger_error(sprintf("Task key '%s' is already defined!", $key));
			return false;
		}
		
		//Reflect on the object and make sure it has an "execute()" method
		$refl = new ReflectionObject($obj);
		if (! $refl->hasMethod('execute')) {
            trigger_error(
                sprintf(
                    "Task '%s' does not have an 'execute' method defined",
                    $key
                )
            );
			return false;
		}
		$this->_tasks[$key] = $obj;
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
    private function _loadAllTasks($taskDir)
    {
		if (!is_dir($taskDir)) {
            throw new Exception(
                sprintf("Task dir: %s does not exist", $taskDir)
            );
			return false;
		}
		$files = scandir($taskDir);
		$regex = '/^class\.(\w+)\.php$/';
		foreach ($files as $f) {
			//skip over invalid files
            if ($f == '.' || $f == ".." || !preg_match($regex, $f, $matches)) {
                continue;
            }
			include_once $taskDir . '/' . $f;
			$taskName = Ruckusing_NamingUtil::taskFromClassName($matches[1]);
			$klass = Ruckusing_NamingUtil::classFromFileName($f);
			$this->registerTask($taskName, new $klass($this->getAdapter()));
		}
	}
	
	/**
     * Execute the supplied Task object
     *
     * @param Ruckusing_iTask $taskObj Task object
     *
     * @return void
	 */	
    private function _executeTask($taskObj)
    {
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
		if (! $this->hasTask($taskName)) {
			throw new Exception("Task '{$taskName}' is not registered.");
		}
		$task = $this->getTask($taskName);
		if ($task) {
			return $task->execute($options);
		}
		return '';
	}
}
