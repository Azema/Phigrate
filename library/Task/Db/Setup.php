<?php

/**
 * Phigrate
 *
 * PHP Version 5.3
 *
 * @category   Phigrate
 * @package    Task
 * @subpackage Db
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */

/**
 * @see Task_Base
 */
require_once 'Task/Base.php';

/**
 * @see Phigrate_Task_ITask
 */
require_once 'Phigrate/Task/ITask.php';
require_once 'Phigrate/Util/Migrator.php';

/**
 * This is a generic task which initializes a table
 * to hold migration version information.
 * This task is non-destructive and will only create the table
 * if it does not already exist, otherwise no other actions are performed.
 *
 * @category   Phigrate
 * @package    Task
 * @subpackage Db
 * @author     Cody Caughlan <codycaughlan % gmail . com>
 * @author     Manuel HERVO <manuel.hervo % gmail .com>
 * @copyright  2007 Cody Caughlan (codycaughlan % gmail . com)
 * @license    GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://github.com/Azema/Phigrate
 */
class Task_Db_Setup extends Task_Base implements Phigrate_Task_ITask
{
    /**
     * Primary task entry point
     *
     * @param mixed $args Arguments to task
     *
     * @return void
     */
    public function execute($args)
    {
        $return = 'Started: ' . date('Y-m-d g:ia T') . "\n\n"
            . "[db:setup]: \n";
        //it doesnt exist, create it
        $tables = $this->_adapter->getTables();
        $getDatabase = false;
        $withData = false;
        if (count($tables) > 1) {
            // La base n'est pas vide, demandé quoi faire.
            $getDatabase = $this->_ask(
                'Your database isn\'t empty. Do you want generate the first migration file (yes/no)? '
            );
            if ($getDatabase) {
                $withData = $this->_ask('Would you get data (yes/no)? ');
                $filePath = $this->_createMigrationImportInitial($withData);
                $return .= "\n\tThe migration file is generated in {$filePath}\n";
                // Suppression des tables de la base de données
                $this->_cleanDatabase();
            }
        }
        if (! $this->_adapter->tableExists(PHIGRATE_TS_SCHEMA_TBL_NAME, true)) {
            $return .= sprintf("\tCreating table: '%s'", PHIGRATE_TS_SCHEMA_TBL_NAME);
            $this->_adapter->createSchemaVersionTable();
            $return .= "\n\tDone.";
        } else {
            $return .= sprintf(
                "\tNOTICE: table '%s' already exists. Nothing to do.",
                PHIGRATE_TS_SCHEMA_TBL_NAME
            );
        }
        $return .= "\n\nFinished: " . date('Y-m-d g:ia T') . "\n\n";
        return $return;
    }

    protected function _ask($ask)
    {
        $response = false;
        echo $ask;
        $handle = fopen ('php://stdin', 'r');
        $line = fgets($handle);
        $response = (trim($line) == 'yes') ? true : false;
        fclose($handle);
        return $response;
    }

    /**
     * Return the usage of the task
     *
     * @return string
     */
    public function help()
    {
        $output =<<<USAGE
Task: \033[36mdb:setup\033[0m

A basic task to initialize your DB for migrations is available. One should
always run this task when first starting out.

This task not take arguments.

USAGE;
        return $output;
    }

    /**
     * get template
     *
     * @param string $codeSql Le code SQL
     * @param string $klass   The class name
     *
     * @return string
     */
    protected function _getTemplate($codeSql, $klass, $withData = false)
    {
        $year = date('Y');
        $tableSchemaMigrations = PHIGRATE_TS_SCHEMA_TBL_NAME;

        $template = <<<TPL
<?php\n
/**
 * Phigrate
 *
 * PHP Version 5
 *
 * @category  Phigrate
 * @package   Migrations
 * @author    Manuel HERVO <manuel.hervo % gmail . com>
 * @author    Cody Caughlan <codycaughlan % gmail . com>
 * @copyright 2007-{$year} Cody Caughlan (codycaughlan % gmail . com)
 * @license   GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/Azema/Phigrate
 */

/**
 * Class migration DB $klass
 *
 * For documentation on the methods of migration
 *
 * @see http://blog.phigrate.org/doc/methodsMigrations
 *
 * @category
 * @package
 * @author
 * @copyright
 * @license
 * @link
 */
class $klass extends Phigrate_Migration_Base
{
    /**
     * up
     *
     * @return void
     */
    public function up()
    {
        \$this->execute('SET FOREIGN_KEY_CHECKS=0;');

TPL;
        $hasViews = false;
        $queries = preg_split('/;\n/', $codeSql, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($queries as $query) {
            $query = addcslashes(trim($query), '"');
            if (empty($query)) {
                continue;
            }
            if (preg_match('/DEFINER/', $query)) {
                $hasViews = true;
                $query = preg_replace('/DEFINER=`\w*`@`\w*`/', 'DEFINER=CURRENT_USER', $query);
                $query = preg_replace('/CREATE (.*) VIEW/', 'CREATE OR REPLACE \1 VIEW', $query);
            }
            $query = preg_replace('/CREATE TABLE/', 'CREATE TABLE IF NOT EXISTS', $query);
            $template .= "\t\t\$this->execute(\"{$query};\");\n";
        }

        if ($withData) {
            $template .= "\n\n";
            $template .= $this->_createQueryForData($withData);
        }
        $template .=<<<TPL

        \$this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * down
     *
     * @return void
     */
    public function down()
    {
        \$database = \$this->_adapter->getDatabaseName();
        \$this->execute(
            "SET FOREIGN_KEY_CHECKS=0;
SET @tables = NULL;
SELECT GROUP_CONCAT(table_schema, '.', table_name) INTO @tables FROM information_schema.tables
    WHERE table_schema = '{\$database}' AND table_name <> '" . PHIGRATE_TS_SCHEMA_TBL_NAME . "';
SET @tables = CONCAT('DROP TABLE IF EXISTS ', @tables);
PREPARE stmt1 FROM @tables;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;
TPL;
        if ($hasViews) {
            $template .=<<<TPL

SET @views = NULL;
SELECT GROUP_CONCAT(table_schema, '.', table_name) INTO @views FROM information_schema.views
    WHERE table_schema = '{\$database}' AND table_name <> '" . PHIGRATE_TS_SCHEMA_TBL_NAME . "';
SET @views = CONCAT('DROP VIEW IF EXISTS ', @views);
PREPARE stmt1 FROM @views;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;
TPL;
        }
        $template .=<<<TPL

SET FOREIGN_KEY_CHECKS=1;"
        );
    }
}
TPL;
            return $template;
    }

    /**
     * Crée les requêtes d'insertions en les découpant par lot de 50 lignes de données par requête d'insertion.
     *
     * @param array $data Le tableau des données par table
     *
     * @return string
     */
    protected function _createQueryForData($data)
    {
        $template = '';
        $adapter = $this->_adapter;
        foreach ($data as $table => $values) {
            if (empty($values)) {
                continue;
            }
            $template .= "\t\t// Insertion des données dans la table " . $table . "\n";
            $query = 'INSERT INTO ' . $table . ' ';
            $keys = array_keys(reset($values));
            array_walk($keys, function(&$item, $key) use($adapter) {
                $item = $adapter->identifier($item);
            });
            $max = round(count($values) / 50);
            for ($i = 0; $i <= $max; $i++) {
                $partialData = array_slice($values, $i*50, 50);
                if (empty($partialData)) {
                    break;
                }
                $query = $this->_createInsert($table, $keys, $partialData);
                $template .= "\t\t\$this->execute(\"{$query}\");\n";
            }
            $template .= "\n\n";
        }
        return $template;
    }

    /**
     * Crée la requête d'insertion de données
     *
     * @param string $table Le nom de la table
     * @param array  $keys  Un tableau des noms de colonnes
     * @param array  $data  Le tableau des données
     *
     * @return string
     */
    protected function _createInsert($table, $keys, $data)
    {
        $adapter = $this->_adapter;
        $query = 'INSERT INTO ' . $table . ' ';
        $query .= '(' . implode(', ', $keys) . ')';
        foreach ($data as $index => $record) {
            array_walk($record, function(&$item, $key) use ($adapter) {
                if (is_numeric($item)) {
                    $item = (int)$item;
                }
                $item = $adapter->quote($item);
            });
            $data[$index] = '(' . implode(',', $record) . ')';
        }
        $query .= " VALUES \n" . implode(",\n", $data) . ';';
        return $query;
    }

    /**
     * Nettoie la base de données en supprimant toutes les tables et les vues
     *
     * @return boolean
     */
    protected function _cleanDatabase()
    {
        // Suppression des tables de la base de données
        $database = $this->_adapter->getDatabaseName();
        $codeSql =<<<EOF
SET FOREIGN_KEY_CHECKS=0;
SET @tables = NULL;
SELECT GROUP_CONCAT(table_schema, '.', table_name) INTO @tables FROM information_schema.tables WHERE table_schema = '{$database}';
SET @tables = CONCAT('DROP TABLE IF EXISTS ', @tables);
PREPARE stmt1 FROM @tables;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;
SET @views = NULL;
SELECT GROUP_CONCAT(table_schema, '.', table_name) INTO @views FROM information_schema.views WHERE table_schema = '{$database}';
SET @views = CONCAT('DROP VIEW IF EXISTS ', @views);
PREPARE stmt1 FROM @views;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;
SET FOREIGN_KEY_CHECKS=1;
EOF;
        $clean = $this->_adapter->execute($codeSql);

        return $clean;
    }

    /**
     * Creer le fichier de migration initial
     *
     * @return string
     */
    protected function _createMigrationImportInitial($withData = false)
    {
        $schema = $this->_adapter->schema();

        // On génère le fichier de migration
        $klass = 'ImportInitial';
        if ($withData) {
            $withData = $this->_getData();
        }
        $template = $this->_getTemplate($schema, $klass, $withData);
        $fileName = Phigrate_Util_Migrator::generateTimestamp() . '_' . $klass . '.php';
        $filePath = $this->_migrationDir . '/' . $fileName;
        file_put_contents($filePath, $template);
        return $filePath;
    }

    /**
     * Recuperation des données
     *
     * @return array Le tableau des données par table
     */
    protected function _getData()
    {
        $database = $this->_adapter->getDatabaseName();
        $tables = $this->_adapter->execute(
            "select table_name from information_schema.tables"
            . " WHERE table_schema = '{$database}' AND table_type = 'BASE TABLE';"
        );
        $data = array();
        foreach ($tables as $table) {
            $tableName = $table['table_name'];
            if ($tableName == PHIGRATE_TS_SCHEMA_TBL_NAME) {
                continue;
            }
            $dataTable = $this->_adapter->selectAll('SELECT * FROM ' . $tableName);
            $data[$tableName] = $dataTable;
        }
        return $data;
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
