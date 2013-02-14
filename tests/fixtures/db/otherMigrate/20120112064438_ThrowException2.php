<?php


/**
 * Phigrate
 *
 * PHP Version 5.3
 *
 * @category   Phigrate
 * @package    Migrations
 * @author     
 * @copyright  
 * @license    
 * @link       
 */

/**
 * Class migration DB of CreateAddresses
 * 
 * For documentation on the methods of migration
 *
 * @see https://github.com/Azema/phigrate-migrations/wiki/Migration-Methods
 *
 * @category   Phigrate
 * @package    Migrations
 * @author     
 * @copyright  
 * @license    
 * @link       
 */
class ThrowExceptionBis extends Phigrate_Migration_Base
{
    /**
     * up 
     * 
     * @return void
     */
    public function up()
    {
        throw new Exception('Test exception in up method');
    }

    /**
     * down 
     * 
     * @return void
     */
    public function down()
    {
        // Add your code here
        throw new Exception('Test exception in down method');
    }
}
