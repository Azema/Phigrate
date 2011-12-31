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
 * Interface that all tasks must implement.
 *
 * @category   RuckusingMigrations
 * @package    classes
 * @subpackage task
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
interface Ruckusing_iTask
{
    /**
     * execute 
     * 
     * @param array $args
     *
     * @return string
     */
    public function execute($args);
}

?>
