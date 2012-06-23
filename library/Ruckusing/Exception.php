<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
 * @package    Ruckusing_Exception
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */

/**
 * Abstract class exception of Ruckusing
 *
 * @category   RuckusingMigrations
 * @package    Ruckusing_Exception
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_Exception extends Exception
{
    /**
     * __construct 
     * 
     * @param string    $message  The message
     * @param int       $code     The code
     * @param Exception $previous The attached exception
     *
     * @return Ruckusing_Exception
     */
    public function __construct($message='', $code = 0, $previous = null)
    {
        if (PHP_VERSION_ID >= 50300) {
            parent::__construct($message, $code, $previous);
        } else {
            parent::__construct($message, $code);
        }
    }

    /**
     * getPrevious Return previous exception if exists
     * 
     * @return Exception|null
     */
    public function getPrevious()
    {
        if (PHP_VERSION_ID >= 50300) {
            return parent::getPrevious();
        }
        return null;
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
