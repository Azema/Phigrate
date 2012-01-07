<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
 * @package    Ruckusing_Adapter
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */

/**
 * Class of table definition
 *
 * @category   RuckusingMigrations
 * @package    Ruckusing_Adapter
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_Adapter_TableDefinition
{
    /**
     * columns 
     * 
     * @var array
     */
	protected $_columns = array();
    /**
     * adapter 
     * 
     * @var Ruckusing_Adapter_Base
     */
	protected $_adapter;
    /**
     * name 
     * 
     * @var string
     */
	protected $_name;
    /**
     * options 
     * 
     * @var mixed
     */
	protected $_options;
	
    /**
     * __construct 
     * 
     * @param Ruckusing_Adapter_Base $adapter Adapter MySQL
     *
     * @return Ruckusing_Adapter_TableDefinition
     */
    function __construct($adapter, $name, $options = array())
    {
		$this->_adapter = $adapter;
	}
	
    /**
     * included 
	 * Determine whether or not the given column already exists in our 
	 * table definition.
	 * 
	 * This method is lax enough that it can take either a string column name
	 * or a Ruckusing_Adpater_ColumnDefinition object.
     * 
     * @param Ruckusing_Adpater_ColumnDefinition|string $column The column to included
     *
     * @return boolean
     */
    public function included($column)
    {
		$k = count($this->_columns);
		for ($i = 0; $i < $k; $i++) {
			$col = $this->_columns[$i];
			if (is_string($column) && $col->name == $column) {
				return true;
			}
            if ($column instanceof Ruckusing_Adpater_ColumnDefinition 
                && $col->name == $column->name
            ) {
				return true;
			}
		}
		return false;
	}	
	
    /**
     * toSql 
     * 
     * @return string
     */
    public function toSql()
    {
		return join(",", $this->_columns);
	}
}
