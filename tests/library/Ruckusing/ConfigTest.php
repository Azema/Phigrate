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
 * @category   Zend
 * @package    Ruckusing_Config
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * Ruckusing_Config
 */
require_once 'Ruckusing/Config.php';

/**
 * @category   Zend
 * @package    Ruckusing_Config
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Ruckusing_Config
 */
class Ruckusing_ConfigTest extends PHPUnit_Framework_TestCase
{
    protected $_iniFileConfig;
    protected $_iniFileNested;

    public function setUp()
    {
        // Arrays representing common config configurations
        $this->_all = array(
            'hostname' => 'all',
            'name' => 'thisname',
            'db' => array(
                'host' => '127.0.0.1',
                'user' => 'username',
                'pass' => 'password',
                'name' => 'live'
                ),
            'one' => array(
                'two' => array(
                    'three' => 'multi'
                    )
                )
            );

        $this->_numericData = array(
             0 => 34,
             1 => 'test',
            );

        $this->_menuData1 = array(
            'button' => array(
                'b0' => array(
                    'L1' => 'button0-1',
                    'L2' => 'button0-2',
                    'L3' => 'button0-3'
                ),
                'b1' => array(
                    'L1' => 'button1-1',
                    'L2' => 'button1-2'
                ),
                'b2' => array(
                    'L1' => 'button2-1'
                    )
                )
            );

        $this->_leadingdot = array('.test' => 'dot-test');
        $this->_invalidkey = array(' ' => 'test', ''=>'test2');

    }

    public function testLoadSingleSection()
    {
        $config = new Ruckusing_Config($this->_all, false);

        $this->assertEquals('all', $config->hostname);
        $this->assertEquals('live', $config->db->name);
        $this->assertEquals('multi', $config->one->two->three);
        $this->assertNull($config->nonexistent); // property doesn't exist
    }

    public function testIsset()
    {
        if (version_compare(PHP_VERSION, '5.1', '>=')) {
            $config = new Ruckusing_Config($this->_all, false);

            $this->assertFalse(isset($config->notarealkey));
            $this->assertTrue(isset($config->hostname)); // top level
            $this->assertTrue(isset($config->db->name)); // one level down
        }
    }

    public function testNoModifications()
    {
        $config = new Ruckusing_Config($this->_all);
        try {
            $config->hostname = 'test';
        } catch (Ruckusing_Exception_Config $expected) {
            $this->assertContains('is read only', $expected->getMessage());
            return;
        }
        $this->fail('An expected Ruckusing_Exception_Config has not been raised');
    }

    public function testNoNestedModifications()
    {
        $config = new Ruckusing_Config($this->_all);
        try {
            $config->db->host = 'test';
        } catch (Ruckusing_Exception_Config $expected) {
            $this->assertContains('is read only', $expected->getMessage());
            return;
        }
        $this->fail('An expected Ruckusing_Exception_Config has not been raised');
    }

    public function testNumericKeys()
    {
        $data = new Ruckusing_Config($this->_numericData);
        $this->assertEquals('test', $data->{1});
        $this->assertEquals(34, $data->{0});
    }

    public function testCount()
    {
        $data = new Ruckusing_Config($this->_menuData1);
        $this->assertEquals(3, count($data->button));
    }

    public function testIterator()
    {
        // top level
        $config = new Ruckusing_Config($this->_all);
        $var = '';
        foreach ($config as $key=>$value) {
            if (is_string($value)) {
                $var .= "\nkey = $key, value = $value";
            }
        }
        $this->assertContains('key = name, value = thisname', $var);

        // 1 nest
        $var = '';
        foreach ($config->db as $key=>$value) {
            $var .= "\nkey = $key, value = $value";
        }
        $this->assertContains('key = host, value = 127.0.0.1', $var);

        // 2 nests
        $config = new Ruckusing_Config($this->_menuData1);
        $var = '';
        foreach ($config->button->b1 as $key=>$value) {
            $var .= "\nkey = $key, value = $value";
        }
        $this->assertContains('key = L1, value = button1-1', $var);
    }

    public function testArray()
    {
        $config = new Ruckusing_Config($this->_all);

        ob_start();
        print_r($config->toArray());
        $contents = ob_get_clean();

        $this->assertContains('Array', $contents);
        $this->assertContains('[hostname] => all', $contents);
        $this->assertContains('[user] => username', $contents);
    }

    public function testErrorWriteToReadOnly()
    {
        $config = new Ruckusing_Config($this->_all);
        try {
            $config->test = '32';
        } catch (Ruckusing_Exception_Config $expected) {
            $this->assertContains('read only', $expected->getMessage());
            return;
        }

        $this->fail('An expected Ruckusing_Exception_Config has not been raised');
    }

    public function testZF343()
    {
        $config_array = array(
            'controls' => array(
                'visible' => array(
                    'name' => 'visible',
                    'type' => 'checkbox',
                    'attribs' => array(), // empty array
                ),
            ),
        );
        $form_config = new Ruckusing_Config($config_array, true);
        $this->assertSame(array(), $form_config->controls->visible->attribs->toArray());
    }

    public function testZF402()
    {
        $configArray = array(
            'data1'  => 'someValue',
            'data2'  => 'someValue',
            'false1' => false,
            'data3'  => 'someValue'
            );
        $config = new Ruckusing_Config($configArray);
        $this->assertTrue(count($config) === count($configArray));
        $count = 0;
        foreach ($config as $key => $value) {
            if ($key === 'false1') {
                $this->assertTrue($value === false);
            } else {
                $this->assertTrue($value === 'someValue');
            }
            $count++;
        }
        $this->assertTrue($count === 4);
    }

    public function testZf1019_HandlingInvalidKeyNames()
    {
        $config = new Ruckusing_Config($this->_leadingdot);
        $array = $config->toArray();
        $this->assertContains('dot-test', $array['.test']);
    }

    public function testZF1019_EmptyKeys()
    {
        $config = new Ruckusing_Config($this->_invalidkey);
        $array = $config->toArray();
        $this->assertContains('test', $array[' ']);
        $this->assertContains('test', $array['']);
    }

    public function testZF1417_DefaultValues()
    {
        $config = new Ruckusing_Config($this->_all);
        $value = $config->get('notthere', 'default');
        $this->assertTrue($value === 'default');
        $this->assertTrue($config->notThere === null);

    }

    public function testUnsetException()
    {
        // allow modifications is off - expect an exception
        $config = new Ruckusing_Config($this->_all, false);

        $this->assertTrue(isset($config->hostname)); // top level

        try {
            unset($config->hostname);
        } catch (Ruckusing_Exception_Config $expected) {
            $this->assertContains('is read only', $expected->getMessage());
            return;
        }
        $this->fail('Expected read only exception has not been raised.');
    }

    /**
     * Ensures that toArray() supports objects of types other than Ruckusing_Config
     *
     * @return void
     */
    public function testToArraySupportsObjects()
    {
        $configData = array(
            'a' => new stdClass(),
            'b' => array(
                'c' => new stdClass(),
                'd' => new stdClass()
                )
            );
        $config = new Ruckusing_Config($configData);
        $this->assertEquals($config->toArray(), $configData);
        $this->assertInstanceOf('stdClass', $config->a);
        $this->assertInstanceOf('stdClass', $config->b->c);
        $this->assertInstanceOf('stdClass', $config->b->d);
    }

    public function testZF6995_toArrayDoesNotDisturbInternalIterator()
    {
        $config = new Ruckusing_Config(range(1,10));
        $config->rewind();
        $this->assertEquals(1, $config->current());

        $config->toArray();
        $this->assertEquals(1, $config->current());
    }

    public function testClone()
    {
        $parent = new Ruckusing_Config(array('key' => array('nested' => 'parent')));
        $newConfig = clone $parent;
        $this->assertEquals($parent, $newConfig);
        $this->assertNotSame($parent, $newConfig);
    }
}


/* vim: set expandtab tabstop=4 shiftwidth=4: */
