<?php

class taskMock implements Ruckusing_Task_ITask
{
    public $dir;

    /**
     * execute the task
     * 
     * @param array $args Argument to the task
     *
     * @return string
     */
    public function execute($args)
    {
        return implode(', ', $args);
    }
    
    /**
     * Return the usage of the task
     * 
     * @return string
     */
    public function help()
    {
        return 'my help task';
    }

    public function setDirectoryOfMigrations($dir)
    {
        $this->dir = $dir;
    }
}
