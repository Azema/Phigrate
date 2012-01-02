<?php


//----------------------------
// DATABASE CONFIGURATION
//----------------------------
$ruckusing_db_config = array(
	
    'development' => array(
        'type'      => 'mysql',
        'host'      => 'localhost',
        'port'      => 3306,
        'database'  => 'ruckusing_migrations',
        'user'      => 'rucku',
        'password'  => 'rucku'
    ),

	'test' 					=> array(
        'type' 			=> 'mysql',
        'host' 			=> 'localhost',
        'port'			=> 3306,
        'database' 	=> 'ruckusing_migrations_test',
        'user' 			=> 'rucku_test',
        'password' 	=> 'rucku'
	)
	
);


?>
