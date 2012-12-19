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
 * Class migration DB of AddIndexToUsers
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
class AddIndexToUsers extends Phigrate_Migration_Base
{
    /**
     * up 
     * 
     * @return void
     */
    public function up()
    {
        // Add your code here
        $this->addIndex('users', 'name');
    }

    /**
     * down 
     * 
     * @return void
     */
    public function down()
    {
        // Add your code here
        $this->removeIndex('users', 'name');
    }
}