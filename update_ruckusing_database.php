<?php

/* 
  The table for keeping track of Ruckusing Migrations has changed so we need to alter the schema and migrate
  over existing migrations.
*/

define('RUCKUSING_BASE', dirname(__FILE__) );

echo "\n\nStarting upgrade process.\n";
$main = new Ruckusing_FrameworkRunner($argv);
$main->updateSchemaForTimestamps();
echo "\n\nSuccesfully completed upgrade!\n";
$notice = <<<NOTICE
Ruckusing Migrations now uses the table '%s' to keep track of migrations.
The old table '%s' can be removed at your leisure.
NOTICE;

printf("\n$notice\n\n", RUCKUSING_TS_SCHEMA_TBL_NAME, RUCKUSING_SCHEMA_TBL_NAME);

?>
