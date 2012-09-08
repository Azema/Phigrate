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
 * Class migration DB of CreateAddresses
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
class CreateAddresses extends Ruckusing_Migration_Base
{
    /**
     * up 
     * 
     * @return void
     */
    public function up()
    {
        // Add your code here
        $table = $this->createTable('addresses');
        $table->column('street', 'text');
        $table->column('user_id', 'integer');
        $table->finish();
        $this->addIndex('addresses', 'user_id');
    }

    /**
     * down 
     * 
     * @return void
     */
    public function down()
    {
        // Add your code here
        $this->removeIndex('addresses', 'user_id');
        $this->dropTable('addresses');
    }
}
