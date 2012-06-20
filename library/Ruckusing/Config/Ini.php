<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Ruckusing
 * @package    Ruckusing_Config
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Ini.php 11206 2008-09-03 14:36:32Z ralph $
 */


/**
 * @see Zend_Config
 */
require_once 'Ruckusing/Config.php';


/**
 * Configuration for INI files
 * 
 * @category   Ruckusing
 * @package    Ruckusing_Config
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Ruckusing_Config_Ini extends Ruckusing_Config
{
    /**
     * String that separates nesting levels of configuration data identifiers
     *
     * @var string
     */
    protected $_nestSeparator = '.';

    /**
     * String that separates the parent section name
     *
     * @var string
     */
    protected $_sectionSeparator = ':';

    /**
     * Whether to skip extends or not
     *
     * @var boolean
     */
    protected $_skipExtends = false;

    /**
     * Loads the section $section from the config file $filename for
     * access facilitated by nested object properties.
     *
     * If the section name contains a ":" then the section name to the right
     * is loaded and included into the properties. Note that the keys in
     * this $section will override any keys of the same
     * name in the sections that have been included via ":".
     *
     * If the $section is null, then all sections in the ini file are loaded.
     *
     * If any key includes a ".", then this will act as a separator to
     * create a sub-property.
     *
     * example ini file:
     *      [all]
     *      db.connection = database
     *      hostname = live
     *
     *      [staging : all]
     *      hostname = staging
     *
     * after calling $data = new Ruckusing_Config_Ini($file, 'staging'); then
     *      $data->hostname === "staging"
     *      $data->db->connection === "database"
     *
     * The $options parameter may be provided as either a boolean or an array.
     * If provided as a boolean, this sets the $allowModifications option of
     * Ruckusing_Config. If provided as an array, there are two configuration
     * directives that may be set. For example:
     *
     * $options = array(
     *     'allowModifications' => false,
     *     'nestSeparator'      => '->'
     *      );
     *
     * @param  string        $filename
     * @param  string|null   $section
     * @param  boolean|array $options
     * @throws Ruckusing_Exception_Config
     * @return void
     */
    public function __construct($filename, $section = null, $options = false)
    {
        if (empty($filename)) {
            /**
             * @see Ruckusing_Exception_Config
             */
            require_once 'Ruckusing/Exception/Config.php';
            throw new Ruckusing_Exception_Config('Filename is not set');
        }

        if (! is_file($filename)) {
            /**
             * @see Ruckusing_Exception_Config
             */
            require_once 'Ruckusing/Exception/Config.php';
            throw new Ruckusing_Exception_Config('Config filename does not exist');
        }

        if (is_array($options)) {
            if (isset($options['nestSeparator'])) {
                $this->_nestSeparator = (string)$options['nestSeparator'];
            }
        }

        $iniArray = $this->_loadIniFile($filename);

        if (null === $section) {
            $dataArray = array();
            foreach ($iniArray as $sectionName => $sectionData) {
                if(!is_array($sectionData)) {
                    $dataArray = $this->_arrayMergeRecursive($dataArray, $this->_processKey(array(), $sectionName, $sectionData));
                } else {
                    $dataArray[$sectionName] = $this->_processSection($iniArray, $sectionName);
                }
            }
            parent::__construct($dataArray);
        } else {
            // Load one or more sections
            if (! is_array($section)) {
                $section = array($section);
            }

            $dataArray = array();
            foreach ($section as $sectionName) {
                if (! array_key_exists($sectionName, $iniArray)) {
                    /**
                     * @see Ruckusing_Exception_Config
                     */
                    require_once 'Ruckusing/Exception/Config.php';
                    throw new Ruckusing_Exception_Config(
                        "Section '$section' cannot be found in $filename"
                    );
                }
                $dataArray = $this->_arrayMergeRecursive(
                    $this->_processSection($iniArray, $sectionName),
                    $dataArray
                );
            }
            parent::__construct($dataArray);
        }

        $this->_loadedSection = $section;
    }

    /**
     * Load the INI file from disk using parse_ini_file(). Use a private error
     * handler to convert any loading errors into a Ruckusing_Exception_Config
     *
     * @param string $filename
     * @throws Ruckusing_Exception_Config
     * @return array
     */
    protected function _parseIniFile($filename)
    {
        set_error_handler(array($this, '_loadFileErrorHandler'));
        $iniArray = parse_ini_file($filename, true); // Warnings and errors are suppressed
        restore_error_handler();

        // Check if there was a error while loading file
        if ($this->_loadFileErrorStr !== null) {
            /**
             * @see Ruckusing_Exception_Config
             */
            require_once 'Ruckusing/Exception/Config.php';
            throw new Ruckusing_Exception_Config($this->_loadFileErrorStr);
        }

        return $iniArray;
    }

    /**
     * Load the ini file and preprocess the section separator (':' in the
     * section name (that is used for section extension) so that the resultant
     * array has the correct section names and the extension information is
     * stored in a sub-key called ';extends'. We use ';extends' as this can
     * never be a valid key name in an INI file that has been loaded using
     * parse_ini_file().
     *
     * @param string $filename
     * @throws Ruckusing_Exception_Config
     * @return array
     */
    protected function _loadIniFile($filename)
    {
        $loaded = $this->_parseIniFile($filename);
        $iniArray = array();
        foreach ($loaded as $key => $data)
        {
            $pieces = explode($this->_sectionSeparator, $key);
            $thisSection = trim($pieces[0]);
            switch (count($pieces)) {
                case 1:
                    $iniArray[$thisSection] = $data;
                    break;

                case 2:
                    $extendedSection = trim($pieces[1]);
                    $iniArray[$thisSection] = array_merge(
                        array(';extends'=>$extendedSection),
                        $data
                    );
                    break;

                default:
                    /**
                     * @see Ruckusing_Exception_Config
                     */
                    require_once 'Ruckusing/Exception/Config.php';
                    throw new Ruckusing_Exception_Config(
                        "Section '$thisSection' may not extend "
                        . "multiple sections in $filename"
                    );
            }
        }

        return $iniArray;
    }

    /**
     * Process each element in the section and handle the ";extends" inheritance
     * key. Passes control to _processKey() to handle the nest separator
     * sub-property syntax that may be used within the key name.
     *
     * @param  array  $iniArray
     * @param  string $section
     * @param  array  $config
     * @throws Ruckusing_Exception_Config
     * @return array
     */
    protected function _processSection($iniArray, $section, $config = array())
    {
        $thisSection = $iniArray[$section];

        foreach ($thisSection as $key => $value) {
            if (strtolower($key) == ';extends') {
                if (isset($iniArray[$value])) {
                    $this->_assertValidExtend($section, $value);

                    if (! $this->_skipExtends) {
                        $config = $this->_processSection($iniArray, $value, $config);
                    }
                } else {
                    /**
                     * @see Ruckusing_Exception_Config
                     */
                    require_once 'Ruckusing/Exception/Config.php';
                    throw new Ruckusing_Exception_Config(
                        "Parent section '$section' cannot be found"
                    );
                }
            } else {
                $config = $this->_processKey($config, $key, $value);
            }
        }
        return $config;
    }

    /**
     * Assign the key's value to the property list. Handle the "dot"
     * notation for sub-properties by passing control to
     * processLevelsInKey().
     *
     * @param  array  $config
     * @param  string $key
     * @param  string $value
     * @throws Ruckusing_Exception_Config
     * @return array
     */
    protected function _processKey($config, $key, $value)
    {
        if (strpos($key, $this->_nestSeparator) !== false) {
            $pieces = explode($this->_nestSeparator, $key, 2);
            if (strlen($pieces[0]) && strlen($pieces[1])) {
                if (! array_key_exists($pieces[0], $config)) {
                    if ($pieces[0] === '0' && ! empty($config)) {
                        // convert the current values in $config into an array
                        $config = array($pieces[0] => $config);
                    } else {
                        $config[$pieces[0]] = array();
                    }
                } elseif (! is_array($config[$pieces[0]])) {
                    /**
                     * @see Ruckusing_Exception_Config
                     */
                    require_once 'Ruckusing/Exception/Config.php';
                    throw new Ruckusing_Exception_Config(
                        'Cannot create sub-key for "'
                        . $pieces[0] .'" as key already exists'
                    );
                }
                $config[$pieces[0]] = $this->_processKey(
                    $config[$pieces[0]], $pieces[1], $value
                );
            } else {
                /**
                 * @see Ruckusing_Exception_Config
                 */
                require_once 'Ruckusing/Exception/Config.php';
                throw new Ruckusing_Exception_Config(
                    'Invalid key "' . $key . '"'
                );
            }
        } else {
            $config[$key] = $value;
        }
        return $config;
    }
}
