<?php
/**
 * Rucksing Migrations
 *
 * PHP Version 5
 *
 * @category   RuckusingMigrations
 * @package    classes
 * @subpackage Exception
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    
 * @link       https://github.com/ruckus/ruckusing-migrations
 */

/**
 * Class exception of missing schema info table
 *
 * @category   RuckusingMigrations
 * @package    classes
 * @subpackage Exception
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_MissingSchemaInfoTableException extends Exception {

    /**
     * __construct 
     * 
     * @param string $msg 
     * @param int $code 
     *
     * @return void
     */
	public function __construct($msg = '', $code = 0) {
        parent::__construct($msg, $code);
    }
}

/**
 * Class exception of invalid index name
 *
 * @category   RuckusingMigrations
 * @package    classes
 * @subpackage Exception
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_InvalidIndexNameException extends Exception {

    /**
     * __construct 
     * 
     * @param string $msg 
     * @param int $code 
     *
     * @return void
     */
	public function __construct($msg = '', $code = 0) {
        parent::__construct($msg, $code);
    }
}

/**
 * Class exception of missing migration directory
 *
 * @category   RuckusingMigrations
 * @package    classes
 * @subpackage Exception
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_MissingMigrationDirException extends Exception {

    /**
     * __construct 
     * 
     * @param string $msg 
     * @param int $code 
     *
     * @return void
     */
	public function __construct($msg = '', $code = 0) {
        parent::__construct($msg, $code);
    }
}

/**
 * Class exception of missing table
 *
 * @category   RuckusingMigrations
 * @package    classes
 * @subpackage Exception
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_MissingTableException extends Exception {

    /**
     * __construct 
     * 
     * @param string $msg 
     * @param int $code 
     *
     * @return void
     */
	public function __construct($msg = '', $code = 0) {
        parent::__construct($msg, $code);
    }
}

/**
 * Class exception of missing adapter
 *
 * @category   RuckusingMigrations
 * @package    classes
 * @subpackage Exception
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_MissingAdapterException extends Exception {

    /**
     * __construct 
     * 
     * @param string $msg 
     * @param int $code 
     *
     * @return void
     */
	public function __construct($msg = '', $code = 0) {
        parent::__construct($msg, $code);
    }
}

/**
 * Class exception of invalid argument
 *
 * @category   RuckusingMigrations
 * @package    classes
 * @subpackage Exception
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_ArgumentException extends Exception {

    /**
     * __construct 
     * 
     * @param string $msg 
     * @param int $code 
     *
     * @return void
     */
	public function __construct($msg = '', $code = 0) {
        parent::__construct($msg, $code);
    }
}

/**
 * Class exception of invalid table definition
 *
 * @category   RuckusingMigrations
 * @package    classes
 * @subpackage Exception
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_InvalidTableDefinitionException extends Exception {

    /**
     * __construct 
     * 
     * @param string $msg 
     * @param int $code 
     *
     * @return void
     */
	public function __construct($msg = '', $code = 0) {
        parent::__construct($msg, $code);
    }
}

/**
 * Class exception of invalid column type
 *
 * @category   RuckusingMigrations
 * @package    classes
 * @subpackage Exception
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_InvalidColumnTypeException extends Exception {

    /**
     * __construct 
     * 
     * @param string $msg 
     * @param int $code 
     *
     * @return void
     */
	public function __construct($msg = '', $code = 0) {
        parent::__construct($msg, $code);
    }
}

/**
 * Class exception of missing adapter type
 *
 * @category   RuckusingMigrations
 * @package    classes
 * @subpackage Exception
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_MissingAdapterTypeException extends Exception {

    /**
     * __construct 
     * 
     * @param string $msg 
     * @param int $code 
     *
     * @return void
     */
	public function __construct($msg = '', $code = 0) {
        parent::__construct($msg, $code);
    }
}

/**
 * Class SQL exception
 *
 * @category   RuckusingMigrations
 * @package    classes
 * @subpackage Exception
 * @author     Cody Caughlan <toolbag@gmail.com>
 * @copyright  2010-2011 Cody Caughlan
 * @license    
 * @link       https://github.com/ruckus/ruckusing-migrations
 */
class Ruckusing_SQLException extends Exception {

    /**
     * __construct 
     * 
     * @param string $msg 
     * @param int $code 
     *
     * @return void
     */
	public function __construct ($msg = '', $code = 0) {
        parent::__construct($msg, $code);
    }
}

?>
