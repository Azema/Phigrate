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
 * Interface that all tasks must implement.
 *
 * @category   RuckusingMigrations
 * @package    Classes
 * @subpackage Task
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
interface Ruckusing_ITask
{
    /**
     * execute the task
     * 
     * @param array $args Argument to the task
     *
     * @return string
     */
    public function execute($args);
    
    /**
     * Return the usage of the task
     * 
     * @return string
     */
    public function help();
}
