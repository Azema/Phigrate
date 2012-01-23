<?php

/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
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
 * @see https://github.com/Azema/ruckusing-migrations/wiki/Migration-Methods
 *
 * @category   RuckusingMigrations
 * @package    Migrations
 * @author     
 * @copyright  
 * @license    
 * @link       
 */
class MissingMethodUp extends Ruckusing_Migration_Base
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
