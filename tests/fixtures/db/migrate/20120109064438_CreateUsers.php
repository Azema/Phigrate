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
 * Class migration DB of CreateUsers
 * 
 * For documentation on the methods of migration
 *
 * @see https://github.com/Azema/Phigrate/wiki/Migration-Methods
 *
 * @category   Phigrate
 * @package    Migrations
 * @author     
 * @copyright  
 * @license    
 * @link       
 */
class CreateUsers extends Phigrate_Migration_Base
{
    protected $_comment = "This is a long comment for see the cut of string in 50 caracters";
    /**
     * up 
     * 
     * @return void
     */
    public function up()
    {
        // Add your code here
        $this->comment('Add table users');
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
