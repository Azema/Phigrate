<?php

function getFilesInDir($dir, &$files, $racine) {
    $fp = opendir($dir);
    while(false !== ($entry = readdir($fp))) {
        if ('.php' == substr($entry, -4)) {
            $files[] = $racine . '/' . $entry;
        } elseif ('.' != $entry && '..' != $entry && is_dir($dir.'/'.$entry)) {
            getFilesInDir($dir.'/'.$entry, $files, $racine . '/' . $entry);
        }
    }
}
$files = array();
getFilesInDir(dirname(__FILE__).'/library', $files, 'library');
getFilesInDir(dirname(__FILE__).'/bin', $files, 'bin');
$spec = Pearfarm_PackageSpec::create(array(Pearfarm_PackageSpec::OPT_BASEDIR => dirname(__FILE__)))
             ->setName('Ruckusing')
             ->setChannel('Quazar.pearfarm.org')
             ->setSummary('Migrations SQL with PHP as ActiveRecord Migrations')
             ->setDescription('Ruckusing is a framework written in PHP5 for generating and managing a set of "database
             migrations". Database migrations are declarative files which represent the state of a DB (its tables,
             columns, indexes, etc) at a particular state of time. By using database migrations, multiple developers can
             work on the same application and be guaranteed that the application is in a consistent state across all
             remote developer machines.')
             ->setReleaseVersion('0.9.1')
             ->setReleaseStability('alpha')
             ->setApiVersion('0.0.1')
             ->setApiStability('alpha')
             ->setLicense(array('name' => 'GPLv2', 'uri' => 'http://www.gnu.org/licenses/gpl-2.0.html'))
             ->setNotes('Initial release.')
             ->addMaintainer('lead', 'Manuel HERVO', 'Quazar', 'manuel.hervo@gmail.com')
             //->addGitFiles()
             ->addFilesSimple($files)
             ->addExecutable('bin/main.php', 'ruckusing')
             ->addExecutable('bin/generate.php', 'ruckusing-generate')
             ;
