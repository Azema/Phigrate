<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
 * @package    Ruckusing_Adapter
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */

/**
 * Class of column definition
 *
 * @category   RuckusingMigrations
 * @package    Ruckusing_Adapter
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_Adapter_ColumnDefinition
{
    /**
     * name 
     * 
     * @var string
     */
	public $name;
    /**
     * type 
     * 
     * @var mixed
     */
	public $type;
    /**
     * properties 
     * 
     * @var mixed
     */
	public $properties;
    /**
     * options 
     * 
     * @var array
     */
	protected $_options = array();
    /**
     * adapter 
     * 
     * @var Ruckusing_Adapter_Base
     */
	protected $_adapter;
	
    /**
     * __construct 
     * 
     * @param Ruckusing_Adapter_Base $adapter Adapter of RDBMS
     * @param string                $name    Name column
     * @param string                $type    Type generic
     * @param array                 $options Options column
     * 
     * @return Ruckusing_Adapter_ColumnDefinition
     */
    function __construct($adapter, $name, $type, $options = array())
    {
		$this->_adapter = $adapter;
		$this->name = $name;
		$this->type = $type;
	    $this->_options = $options;
	}

    /**
     * toSql 
     * 
     * @return string
     */
    public function toSql()
    {
        $column_sql = sprintf(
            '%s %s', 
            $this->_adapter->identifier($this->name), 
            $this->_sqlType()
        );
        $column_sql .= $this->_adapter->addColumnOptions(
            $this->type,
            $this->_options
        );
		return $column_sql;
	}

    /**
     * __toString 
     * 
     * @return string
     */
    public function __toString()
    {
        //Dont catch any exceptions here, let them bubble up
        return $this->toSql();
	}

    /**
     * sql type 
     * 
     * @return string
     */
    private function _sqlType()
    {
        return $this->_adapter->typeToSql($this->type, $this->_options);
	}
}
