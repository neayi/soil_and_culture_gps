<?php
include 'config.php';

// To run the project : cd c:\xampp\htdocs\soil_and_culture_gps 
// -------------------- c:\xampp\php\php.exe install.php

// Create a working directory temp if it doesn't already exist
if(!(is_dir(__DIR__.'/temp'))){
	mkdir(__DIR__.'/temp');
}

// First lets create a DB:
$GLOBALS['db_conn'] = new mysqli($dbHost, $dbUser, $dbPassword); 

if ($GLOBALS['db_conn']->connect_errno) {
    printf("Connection failure: %s\n", $GLOBALS['db_conn']->connect_error);
    exit();
}

if(!(mysqli_select_db($GLOBALS['db_conn'],$database))){
	$sql= "CREATE DATABASE $database";
	if ($GLOBALS['db_conn']->query($sql) === TRUE) {
	    echo "Database created successfully with the name $database\n";
	} else {
	    echo "Error creating database: " . $GLOBALS['db_conn']->error ."\n";
	}
}else{
	$GLOBALS['db_conn'] = new mysqli($dbHost, $dbUser, $dbPassword, $database); 
}

// Set some DB parameters
if (!$GLOBALS['db_conn']->set_charset("utf8")) 
{
    printf("Error when loading utf8 character set: %s\n", $GLOBALS['db_conn']->error);
    exit();
}
$GLOBALS['db_conn']->query("SET NAMES utf8 COLLATE utf8mb4_unicode_ci");
mb_internal_encoding("UTF-8");


// Download the tables that need to be loaded in our DB and put them in the temp directory

foreach ($GLOBALS['external_data'] as $externaldata)
{
	$localfilename = __DIR__ . '/temp/' . $externaldata['localfilename'];
	
	echo "Downloading " . $externaldata['url'] . " to $localfilename\n";
	if(strpos($localfilename,"rpg")||strpos($localfilename,"RPG"))
		echo "This file might take a while to download...";
	copy($externaldata['url'], $localfilename);

	// Create the tables in the database
	
	list($filename,$format)=explode('.',$localfilename);
	$path=explode('/',$filename);
	$tablename=end($path);

	switch ($format) {
		case 'zip':
			echo "Please unzip the zip files in the temp directory\n";
			while((!(is_dir($filename))));

			echo "Loading $tablename to the database...\n";
			//
			//
			break;

		case 'tab':
			echo "Loading $tablename to the database...\n";
			$rows=file($localfilename);
			$firstRow=explode(';',$rows[0]);
			$nbColumns=count($firstRow);
			$createQuery="CREATE TABLE IF NOT EXISTS $tablename (";

			for($c=0;$c<$nbColumns;$c++){
				if($c==$nbColumns-1){
					$createQuery=$createQuery.$firstRow[$c]." VARCHAR(100))"; // LE TYPE EST A ADAPTER ....
				}else{
					$createQuery=$createQuery.$firstRow[$c]." VARCHAR(100),"; // LE TYPE EST A ADAPTER ....				
				}
			}
			mysqli_query($GLOBALS['db_conn'],$createQuery);
			
			// Insert the values into the table

			foreach($rows as $row) {
				
				if($row<>$rows[0]){
					$row=substr($row, 1,-1);
					$row=str_replace("\\","",$row);
					$row=str_replace("\"","",$row);
				}

				$rowArray[$row]=explode(';',$row);

				$insertQuery="INSERT INTO $tablename (";

				for($c=0;$c<$nbColumns;$c++){
					if($c==$nbColumns-1){
						$insertQuery=$insertQuery.$firstRow[$c].") VALUES (";
					}else{
						$insertQuery=$insertQuery.$firstRow[$c].",";
					}
				}

				if($rowArray[$row]<>$firstRow){
					for($c=0;$c<$nbColumns;$c++){
						$fieldValue = mysqli_real_escape_string($GLOBALS['db_conn'],$rowArray[$row][$c]);
						if(!is_null($fieldValue)){
							if($c==$nbColumns-1){
								$insertQuery=$insertQuery."'".$fieldValue."')";
							}else{
								$insertQuery=$insertQuery."'".$fieldValue."',";
							}
						}
					}
				}
				mysqli_query($GLOBALS['db_conn'],$insertQuery);

			}
			break;

		case 'csv':
			echo "Loading $tablename to the database...\n";
			//
			// fusionner les 2 fichiers cultures en 1 table
			break;

		default:
			break;
	}


}






//fonction unzip
// https://www.php.net/manual/fr/ref.zip.php
// https://stackoverflow.com/questions/8889025/unzip-a-file-with-php



