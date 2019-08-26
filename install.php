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

//
//
//
//
// Download the tables that need to be loaded in our DB and put them in the temp directory

foreach ($GLOBALS['external_data'] as $externaldata)
{
	$localfilenameBIS=$externaldata['localfilename'];
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

			$dir_handle = opendir($filename); 
			$shapefile="";
			if(is_resource($dir_handle)){  
			     while(($file = readdir($dir_handle)) == true) { 
			     	if(substr($file,-3)=="shp") 
			    		$shapefile=$filename."/".$file; 
			    }
			    closedir($dir_handle); 
			}

			echo "Loading $tablename to the database...\n";
			
			if(is_dir($GLOBALS['ogr2ogrpass'])){
				exec($GLOBALS['ogr2ogrpass']."\ogr2ogr -f MySQL MySQL:$database,host=$dbHost,user=$dbUser,password= $shapefile -nln $tablename -update -overwrite -lco engine=MYISAM");
				if($localfilenameBIS==$GLOBALS['external_data']['urlSoilsShpFile']['localfilename']){
					$structureQuery="ALTER TABLE $tablename
					  ADD PRIMARY KEY (soil_id),
					  ADD KEY smu (smu);";
					  if(!mysqli_query($GLOBALS['db_conn'],$structureQuery))
					  	printf("Error: %s\n", mysqli_error($GLOBALS['db_conn']));
				}
				//elseif($localfilenameBIS==$GLOBALS['external_data']['urlRPG2017']['localfilename']){
				//	$structureQuery="ALTER TABLE $tablename 
						//ADD PRIMARY KEY (id_parcel), ADD SPATIAL KEY SHAPE (SHAPE);";
					//if(!mysqli_query($GLOBALS['db_conn'],$structureQuery))
					//		printf("Error: %s\n", mysqli_error($GLOBALS['db_conn']));
				//}

			}else
				echo "Please download OSGeo4W and configure the right path to access the folder of ogr2ogr.exe in config.php \n";
			break;

		case 'tab':
			echo "Loading $tablename to the database...\n";
			$rows=file($localfilename);
			$firstRow=explode(';',$rows[0]);
			$nbColumns=count($firstRow);

			switch($localfilenameBIS){
				case $GLOBALS['external_data']['urlSMU']['localfilename']:
					$createQuery="CREATE TABLE IF NOT EXISTS $tablename (
					  smu varchar(24) DEFAULT NULL,
					  nb_polys varchar(10) DEFAULT NULL,
					  area varchar(10) DEFAULT NULL
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
					if(!mysqli_query($GLOBALS['db_conn'],$createQuery))
					  	printf("Error: %s\n", mysqli_error($GLOBALS['db_conn']));
					break;
				case $GLOBALS['external_data']['urlSTU']['localfilename']:
					$createQuery="CREATE TABLE IF NOT EXISTS $tablename (
					  stu int(7) NOT NULL,
					  nb_polys int(3) DEFAULT NULL,
					  nb_smu int(1) DEFAULT NULL,
					  area varchar(13) DEFAULT NULL,
					  soil varchar(3) DEFAULT NULL,
					  soil90 varchar(3) DEFAULT NULL,
					  text1 int(1) DEFAULT NULL,
					  text2 int(1) DEFAULT NULL,
					  slope1 int(1) DEFAULT NULL,
					  slope2 int(1) DEFAULT NULL,
					  aglim1 int(2) DEFAULT NULL,
					  aglim2 int(2) DEFAULT NULL,
					  mat1 varchar(3) DEFAULT NULL,
					  mat2 varchar(3) DEFAULT NULL,
					  zmin int(4) DEFAULT NULL,
					  zmax int(4) DEFAULT NULL,
					  use1 int(2) DEFAULT NULL,
					  use2 int(2) DEFAULT NULL,
					  dt int(1) DEFAULT NULL,
					  td1 int(1) DEFAULT NULL,
					  td2 int(1) DEFAULT NULL,
					  roo int(1) DEFAULT NULL,
					  il int(1) DEFAULT NULL,
					  wr int(1) DEFAULT NULL,
					  wm1 int(1) DEFAULT NULL,
					  wm2 int(1) DEFAULT NULL,
					  wm3 int(1) DEFAULT NULL,
					  cfl varchar(1) DEFAULT NULL
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
					if(!mysqli_query($GLOBALS['db_conn'],$createQuery))
					  	printf("Error: %s\n", mysqli_error($GLOBALS['db_conn']));
					break;
				case $GLOBALS['external_data']['urlSTUORG']['localfilename']:
					$createQuery="CREATE TABLE stuorg (
					  smu int(7) DEFAULT NULL,
					  stu int(7) NOT NULL,
					  pcarea int(3) DEFAULT NULL
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
					if(!mysqli_query($GLOBALS['db_conn'],$createQuery))
					  	printf("Error: %s\n", mysqli_error($GLOBALS['db_conn']));
					break; 
				default:
					break;
			}
			
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

				$fieldValue[$row]=array();

				if($rowArray[$row]<>$firstRow){
					for($c=0;$c<$nbColumns;$c++){
						$fieldValue[$row][$c] = mysqli_real_escape_string($GLOBALS['db_conn'],$rowArray[$row][$c]);
						if(!is_null($fieldValue)){
							if($c==$nbColumns-1){
								$insertQuery=$insertQuery."'".$fieldValue[$row][$c]."')";
							}else{
								$insertQuery=$insertQuery."'".$fieldValue[$row][$c]."',";
							}
						}
					}
				}

				if($rowArray[$row]<>$firstRow){
					$checkExistsQuery="SELECT * FROM $tablename WHERE ";
					switch ($localfilenameBIS) {
						case $GLOBALS['external_data']['urlSMU']['localfilename']:
							$checkExistsQuery=$checkExistsQuery."smu = ".$fieldValue[$row][0];
							break;
						
						case $GLOBALS['external_data']['urlSTU']['localfilename']:
							$checkExistsQuery=$checkExistsQuery."stu = ".$fieldValue[$row][0];
							break;


						case $GLOBALS['external_data']['urlSTUORG']['localfilename']:
							$checkExistsQuery=$checkExistsQuery."smu = ".$fieldValue[$row][0]." AND stu = ".$fieldValue[$row][1];
							break;

						default:
							break;
					}

					if($result=mysqli_query($GLOBALS['db_conn'],$checkExistsQuery)){
						if(mysqli_num_rows($result)==0){
							if(!mysqli_query($GLOBALS['db_conn'],$insertQuery))
					  			printf("Error: %s\n", mysqli_error($GLOBALS['db_conn']));
						}
					}else{
						printf("Error: %s\n", mysqli_error($GLOBALS['db_conn']));
					}
				}
			}	

			$structureQuery="ALTER TABLE $tablename
								ADD ";
			switch ($localfilenameBIS) {
				case $GLOBALS['external_data']['urlSTU']['localfilename']:
					$structureQuery=$structureQuery."PRIMARY KEY (stu);";
					if(!mysqli_query($GLOBALS['db_conn'],$structureQuery))
					  	printf("Error: %s\n", mysqli_error($GLOBALS['db_conn']));
					break;

				case $GLOBALS['external_data']['urlSTUORG']['localfilename']:
					$structureQuery=$structureQuery."PRIMARY KEY (stu), ADD KEY smu (smu);";
					if(!mysqli_query($GLOBALS['db_conn'],$structureQuery))
					  	printf("Error: %s\n", mysqli_error($GLOBALS['db_conn']));
					break;
				
				default:
					break;
			}			
			break;

		case 'csv':
			echo "Loading $tablename to the database...\n";

			$tableCSV=$GLOBALS['CSVtables'];
			// Open the file for reading
			if (($h = fopen($localfilename, "r")) !== FALSE) 
			{
			  	while (($data[] = fgetcsv($h, 1000, ";")) !== FALSE);

				if($localfilenameBIS==$GLOBALS['external_data']['urlCodificationMainCrops']['localfilename']){
					$createQuery="CREATE TABLE IF NOT EXISTS $tableCSV(
						Code varchar(3) DEFAULT NULL,
						  Label varchar(136) DEFAULT NULL,
						  Code_groupe int(2) DEFAULT NULL,
						  Label_groupe varchar(37) DEFAULT NULL
						) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
					mysqli_query($GLOBALS['db_conn'],$createQuery);

					$structureQuery="ALTER TABLE $tableCSV
		  				ADD UNIQUE KEY `Code` (`Code`)";
		  			if(!mysqli_query($GLOBALS['db_conn'],$structureQuery))
					  	printf("Error: %s\n", mysqli_error($GLOBALS['db_conn']));
				
					foreach ($data as $row) {
						if($row==$data[0]) continue;
						if($row==end($data)) continue;
						$insertQuery="INSERT INTO $tableCSV (Code, Label, Code_Groupe, Label_groupe) VALUES (";
						for($c=0;$c<4;$c++){
							$fieldValue=mysqli_real_escape_string($GLOBALS['db_conn'],utf8_encode($row[$c]));
							if($c==3){
								$insertQuery=$insertQuery."'".$fieldValue."')";
							}else{
								$insertQuery=$insertQuery."'".$fieldValue."',";
							}
						}
						$checkExistsQuery="SELECT * FROM $tableCSV WHERE Code = '".$row[0]."'";
						if($result=mysqli_query($GLOBALS['db_conn'],$checkExistsQuery)){
							if(mysqli_num_rows($result)==0){
								if(!mysqli_query($GLOBALS['db_conn'],$insertQuery))
				  					printf("Error: %s\n", mysqli_error($GLOBALS['db_conn']));
							}
						}else{
							printf("Error: %s\n", mysqli_error($GLOBALS['db_conn']));
						}
					}
				}

				if($localfilenameBIS==$GLOBALS['external_data']['urlCodificationCatchCrops']['localfilename']){
					foreach ($data as $row) {
						if($row==$data[0]) continue;
						if($row==end($data)) continue;
						$insertQuery="INSERT INTO $tableCSV(Code,Label,Code_Groupe,Label_groupe) VALUES (";
						for($c=0;$c<4;$c++){
							if($c<2){
								$fieldValue=mysqli_real_escape_string($GLOBALS['db_conn'],utf8_encode($row[$c]));
								$insertQuery=$insertQuery."'".$fieldValue."',";
							}elseif($c==2){
								$insertQuery=$insertQuery."null,";
							}else{
								$insertQuery=$insertQuery."null)";
							}
						}						
						$checkExistsQuery="SELECT * FROM $tableCSV WHERE Code = '".$row[0]."'";
						if($result=mysqli_query($GLOBALS['db_conn'],$checkExistsQuery)){
							if(mysqli_num_rows($result)==0){
								if(!mysqli_query($GLOBALS['db_conn'],$insertQuery))
				  					printf("Error: %s\n", mysqli_error($GLOBALS['db_conn']));
							}
						}else{
							printf("Error: %s\n", mysqli_error($GLOBALS['db_conn']));
						}					
					}
				}
			  	fclose($h);
			}
			break;

		default:
			break;
	}
}