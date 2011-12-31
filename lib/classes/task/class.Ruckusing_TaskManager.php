<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
 * @package    classes
 * @subpackage task
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    
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
 * @package    classes
 * @subpackage task
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_TaskManager  {
	
    /**
     * adapter 
     * 
     * @var Ruckusing_BaseAdapter
     */
	private $adapter;
    /**
     * tasks 
     * 
     * @var array
     */
	private $tasks = array();
	
    /**
     * __construct 
     * 
     * @param Ruckusing_BaseAdapter $adapter 
     *
     * @return Ruckusing_TaskManager
     */
	function __construct($adapter) {
		$this->set_adapter($adapter);
		$this->load_all_tasks(RUCKUSING_TASK_DIR);
	}//__construct
	
    /**
     * set_adapter 
     *
     * @param Ruckusing_BaseAdapter $adapter
     * 
     * @return void
     */
	public function set_adapter($adapter) { 
		$this->adapter = $adapter;
    }

    /**
     * get_adapter 
     * 
     * @return Ruckusing_BaseAdapter
     */
	public function get_adapter() {
		return $this->adapter;
	}
	
    /**
	 * Searches for the given task, and if found
	 * returns it. Otherwise null is returned.
     * 
     * @param mixed $key 
     * @return Ruckusing_iTask
     */
	public function get_task($key) {
		if( array_key_exists($key, $this->tasks)) {
			return $this->tasks[$key];
		} else {
			return null;
		}		
	}

    /**
     * has_task 
     * 
     * @param string $key 
     *
     * @return boolean
     */
	public function has_task($key) {
		if( array_key_exists($key, $this->tasks)) {
			return true;
		} else {
			return false;
		}		
	}

	
	/*
	*/
    /**
	 * Register a new task name under the specified key.
	 * $obj is a class which implements the iTask interface
	 * and has an execute() method defined.
     * 
     * @param string $key 
     * @param Ruckusing_iTask $obj 
     *
     * @return boolean
     */
	public function register_task($key, $obj) {
		
		if( array_key_exists($key, $this->tasks)) {
			trigger_error(sprintf("Task key '%s' is already defined!", $key));
			return false;
		}
		
		//Reflect on the object and make sure it has an "execute()" method
		$refl = new ReflectionObject($obj);
		if( !$refl->hasMethod('execute')) {
			trigger_error(sprintf("Task '%s' does not have an 'execute' method defined", $key));
			return false;
		}
		$this->tasks[$key] = $obj;
		return true;
	}
	
    /**
     * get_name 
     * 
     * @return void
     */
	public function get_name() {
	}
	
	//---------------------
	// PRIVATE METHODS
	//---------------------
    /**
     * load_all_tasks 
     * 
     * @param string $task_dir 
     *
     * @return void
     * @throws Exception
     */
	private function load_all_tasks($task_dir) {
		if(!is_dir($task_dir)) {
			throw new Exception(sprintf("Task dir: %s does not exist", $task_dir));
			return false;
		}
		$files = scandir($task_dir);
		$regex = '/^class\.(\w+)\.php$/';
		foreach($files as $f) {			
			//skip over invalid files
			if($f == '.' || $f == ".." || !preg_match($regex,$f, $matches) ) { continue; }
			require_once $task_dir . '/' . $f;
			$task_name = Ruckusing_NamingUtil::task_from_class_name($matches[1]);
			$klass = Ruckusing_NamingUtil::class_from_file_name($f);
			$this->register_task($task_name, new $klass($this->get_adapter()));
		}
	}//require_tasks
	
	/**
     * Execute the supplied Task object
     *
     * @param Ruckusing_iTask $task_obj 
     *
     * @return void
	 */	
	private function execute_task($task_obj) {		
	}
	
    /**
     * execute 
     * 
     * @param string $task_name 
     * @param array $options 
     *
     * @return string
     */
	public function execute($task_name, $options) {
		if (!$this->has_task($task_name)) {
			throw new Exception("Task '$task_name' is not registered.");
		}
		$task = $this->get_task($task_name);
		if ($task) {
			return $task->execute($options);
		}
		return "";		
	}
}

?>
