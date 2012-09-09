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
 * Class migration DB without UP method
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
class MissingMethodUp extends Phigrate_Migration_Base
{
    /**
     * down 
     * 
     * @return void
     */
    public function down()
    {
        $this->selectOne('UPDATE `users` SET `login` `login` VARCHAR(20);');
    }
}
