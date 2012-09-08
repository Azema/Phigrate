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
 * Class migration DB of CreateUsers
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
class CreateUsers extends Ruckusing_Migration_Base
{
    /**
     * up 
     * 
     * @return void
     */
    public function up()
    {
        // Add your code here
        $table = $this->createTable('users');
        $table->column('name', 'text', array('length' => 50));
        $table->finish();
    }

    /**
     * down 
     * 
     * @return void
     */
    public function down()
    {
        // Add your code here
        $this->dropTable('users');
    }
}
