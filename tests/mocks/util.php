<?php

require_once 'Ruckusing/Adapter/Mysql/Adapter.php';
/**
 * Mock class adapter RDBMS
 *
 * @category   RuckusingMigrations
 * @package    Mocks
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/azema/ruckusing-migrations
 */
class utilAdapterMock extends adapterMock
{
    public $versions = array();

    public $currentVersion;

    public $removeVersion;

    public function selectAll($query)
    {
        return $this->versions;
    }

    public function setCurrentVersion($version)
    {
        $this->currentVersion = $version;
    }

    public function removeVersion($version)
    {
        $this->removeVersion = $version;
    }
}

