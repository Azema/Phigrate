<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
 * @package    Ruckusing_Exception
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */

/**
 * Abstract class exception of Ruckusing
 *
 * @category   RuckusingMigrations
 * @package    Ruckusing_Exception
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
abstract class Ruckusing_Exception extends Exception
{
    /**
     * __construct 
     * 
     * @param string $msg  Ruckusing_Exception message
     * @param int    $code Ruckusing_Exception code
     *
     * @return void
     */
    public function __construct($msg = '', $code = 0)
    {
        parent::__construct($msg, $code);
    }
}
