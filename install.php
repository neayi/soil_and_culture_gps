<?php
include 'config.php';

// Create a working directory temp if it doesn't already exist
if(!(is_dir(__DIR__.'/temp'))){
	mkdir(__DIR__.'/temp');
}

// First lets create a connection to the DB:
$GLOBALS['db'] = new mysqli($dbHost, $dbUser, $dbPassword, $database); // VERIFIER

if ($GLOBALS['db']->connect_errno) {
    printf("Connection failure: %s\n", $GLOBALS['db']->connect_error);
    exit();
}

if (!$GLOBALS['db']->set_charset("utf8")) 
{
    printf("Error when loading utf8 character set: %s\n", $GLOBALS['db']->error);
    exit();
}
$GLOBALS['db']->query("SET NAMES utf8 COLLATE utf8mb4_unicode_ci");
mb_internal_encoding("UTF-8");

foreach ($GLOBALS['external_data'] as $externaldata)
{
	// Download the tables that need to be loaded in our DB and put them in the temp directory
	$localfilename = __DIR__ . '/temp/' . $externaldata['localfilename'];
	
	echo "Downloading " . $externaldata['url'] . " to $localfilename\n";
	copy($externaldata['url'], $localfilename);
}

//fonction unzip
// https://www.php.net/manual/fr/ref.zip.php
// https://stackoverflow.com/questions/8889025/unzip-a-file-with-php



// pour chq table stu, smu etc: lire le fichier (avec file), et pour chq ligne, explode en plusieurs cellules => tableau qu'on insere ensuite dans la base
//$names = file(__DIR__ . '/namesE.txt');