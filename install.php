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
}
$GLOBALS['db_conn'] = new mysqli($dbHost, $dbUser, $dbPassword, $database); 


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
	$localfilenameBIS=$externaldata['localfilename'];
	$localfilename = __DIR__ . '/temp/' . $externaldata['localfilename'];
	
	echo "Downloading " . $externaldata['url'] . " to $localfilename\n";
	if(strpos($localfilename,"rpg")||strpos($localfilename,"RPG"))
		echo "This file might take a while to download...\n";
	if(!file_exists($localfilename)){
		try{
			if(!copy($externaldata['url'], $localfilename)){
				if($localfilenameBIS==$GLOBALS['external_data']['urlSoilsShpFile']['localfilename']){
					throw new Exception('The file could not be downloaded. Please go on https://data.inra.fr/dataset.xhtml?persistentId=doi:10.15454/BPN57S and download 30169_L93.zip and the following files as .tab : smu.tab, stu.tab, stuorg.tab.');
				}
			}
		}catch(Exception $e){
			echo "An exception occured : ". $e->getMessage()."\n";
		}
	}

	// Create the tables in the database
	
	list($filename,$format)=explode('.',$localfilename);
	$tablename=$externaldata['tablename'];

	switch ($format) {
		case 'zip':
			if(!(mysqli_query($GLOBALS['db_conn'],"SELECT 1 FROM $tablename LIMIT 1"))){
				if(file_exists($localfilename))
					echo "Please unzip the zip files in the temp directory\n";
				while((!(is_dir($filename))));
				if($localfilenameBIS==$GLOBALS['external_data']['urlSoilsShpFile']['localfilename']){
					while(!file_exists($filename."/30169_L93.shp"));
					$shapefile=$filename."/30169_L93.shp";
					
				}elseif($localfilenameBIS==$GLOBALS['external_data']['urlRPG2017']['localfilename']){
					while(!(file_exists($filename."/RPG_2-0__SHP_LAMB93_FR-2017_2017-01-01/RPG/1_DONNEES_LIVRAISON_2017/RPG_2-0_SHP_LAMB93_FR-2017/PARCELLES_GRAPHIQUES.shp")));
					$shapefile=$filename."/RPG_2-0__SHP_LAMB93_FR-2017_2017-01-01/RPG/1_DONNEES_LIVRAISON_2017/RPG_2-0_SHP_LAMB93_FR-2017/PARCELLES_GRAPHIQUES.shp";
				}else{
					echo "Error : no shapefile found...\n";
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
					elseif($localfilenameBIS==$GLOBALS['external_data']['urlRPG2017']['localfilename']){
						$structureQuery="ALTER TABLE $tablename 
							ADD PRIMARY KEY (id_parcel);";
						if(!mysqli_query($GLOBALS['db_conn'],$structureQuery))
							printf("Error: %s\n", mysqli_error($GLOBALS['db_conn']));
					}

				}else
					echo "Please download OSGeo4W and configure the right path to access the folder of ogr2ogr.exe in config.php \n";
			}
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
							$fieldValue=mysqli_real_escape_string($GLOBALS['db_conn'],iconv("Windows-1252","UTF-8",$row[$c]));
							if($c==3){
								$insertQuery=$insertQuery."\"$fieldValue\")";
							}else{
								$insertQuery=$insertQuery."\"$fieldValue\",";
							}
						}
						$checkExistsQuery="SELECT * FROM $tableCSV WHERE Code = \"$row[0]\"";
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
						if($row[0]<>$data[0][0]){
							if($row==end($data)) continue;
							$insertQuery="INSERT INTO $tableCSV(Code, Label, Code_Groupe, Label_groupe) VALUES (";
							for($c=0;$c<4;$c++){
								if($c<2){
									$fieldValue=mysqli_real_escape_string($GLOBALS['db_conn'],iconv("Windows-1252","UTF-8",$row[$c]));
									$insertQuery=$insertQuery."\"$fieldValue\",";
								}elseif($c==2){
									$insertQuery=$insertQuery."null,";
								}else{
									$insertQuery=$insertQuery."null)";
								}
							}
							$checkExistsQuery="SELECT * FROM $tableCSV WHERE Code = \"$row[0]\"";
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
				}
			  	fclose($h);
			}
			break;

		default:
			break;
	}
}

$createSoilDescri="CREATE TABLE IF NOT EXISTS soil_description (
  soil85 varchar(4) DEFAULT NULL,
  soil90 varchar(4) DEFAULT NULL,
  soil varchar(30) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

if(!mysqli_query($GLOBALS['db_conn'],$createSoilDescri))
	printf("Error: %s\n", mysqli_error($GLOBALS['db_conn']));

$insertSoilDescri="INSERT INTO soil_description (soil85, soil90, soil) VALUES
('A', 'AC', 'Acrisol'),
('Af', 'ACf', 'Ferric Acrisol'),
('Ag', 'ACg', 'Gleyic Acrisol'),
('', 'ACh', 'Haplic Acrisols'),
('', 'AChn', 'Niti-haplic Acrisols'),
('Ah', 'ACu', 'Humic Acrisol'),
('', 'ACua', 'Alumi-humic Acrisols'),
('Ao', '', 'Orthic Acrisol'),
('Ap', 'ACp', 'Plinthic Acrisol'),
('', 'AL', 'Alisol'),
('', 'ALf', 'Ferric Alisols'),
('', 'ALg', 'Gleyic Alisols'),
('', 'ALh', 'Haplic Alisols'),
('', 'ALj', 'Stagnic Alisols'),
('', 'ALp', 'Plinthic Alisols'),
('', 'ALu', 'Humic Alisols'),
('', 'ALuu', 'Umbri-humic Alisols'),
('', 'AN', 'Andosols'),
('', 'ANg', 'Gleyic Andosols'),
('', 'ANh', 'Haplic Andosols'),
('', 'ANhe', 'Eutri-aplic Andosols'),
('', 'ANi', 'Gelic Andosols'),
('', 'ANm', 'Mollic Andosols'),
('', 'ANu', 'Umbric Andosols'),
('', 'ANz', 'Vitric Andosols'),
('Q', 'AR', 'Arenosols'),
('Qa', 'ARa', 'Albic Arenosols'),
('Qc', 'ARb', 'Cambic Arenosols'),
('Qcc', '', 'Calcaro-Cambic Arenosol'),
('Qcd', '', 'Dystri-Cambic Arenosol'),
('Qcg', '', 'Gleyo-Cambic Arenosol'),
('Qcs', '', 'Spodo-Cambic Arenosol'),
('Ql', '', 'Luvic Arenosol'),
('Qld', '', 'Dystri-Luvic Arenosol'),
('Qlg', '', 'Gleyo-Luvic Arenosol'),
('', 'ARc', 'Calcaric Arenosols'),
('', 'ARg', 'Gleyic Arenosols'),
('', 'ARh', 'Haplic Arenosols'),
('', 'ARl', 'Luvic Arenosols'),
('', 'ARo', 'Ferralic Arenosols'),
('', 'AT', 'Anthrosols'),
('', 'ATa', 'Aric Anthrosols'),
('', 'ATc', 'Cumulic Anthrosols'),
('', 'ATf', 'Fimic Anthrosols'),
('', 'ATu', 'Urbic Anthrosols'),
('B', '', 'Cambisol'),
('Ba', '', 'Calcaric Cambisol'),
('Bc', '', 'Chromic Cambisol'),
('Bcc', '', 'Calcaro-Chromic Cambisol'),
('Bch', '', 'Humo-Chromic Cambisol'),
('Bck', '', 'Calci-Chromic Cambisol'),
('Bd', '', 'Dystric Cambisol'),
('Bda', '', 'Ando-Dystric Cambisol'),
('Bdg', '', 'Gleyo-Dystric Cambisol'),
('Bds', '', 'Spodo-Dystric Cambisol'),
('Be', '', 'Eutric Cambisol'),
('Bea', '', 'Ando-Eutric Cambisol'),
('Bec', '', 'Calcaro-Eutric Cambisol'),
('Bef', '', 'Fluvi-Eutric Cambisol'),
('Beg', '', 'Gleyo-Eutric Cambisol'),
('Bev', '', 'Verti-Eutric Cambisol'),
('Bg', '', 'Gleyic Cambisol'),
('Bgc', '', 'Calcaro-Gleyic Cambisol'),
('Bge', '', 'Eutri-Gleyic Cambisol'),
('Bgg', '', 'Stagno-Gleyic Cambisol'),
('Bgs', '', 'Spodo-Gleyic Cambisol'),
('Bh', '', 'Humic Cambisol'),
('Bhc', '', 'Calcaro-Humic Cambisol'),
('Bk', '', 'Calcic Cambisol'),
('Bkf', '', 'Fluvi-Calcic Cambisol'),
('Bkh', '', 'Humo-Calcic Cambisol'),
('Bkv', '', 'Verti-Calcic Cambisol'),
('Bv', '', 'Vertic Cambisol'),
('Bvc', '', 'Calcaro-Vertic Cambisol'),
('Bvg', '', 'Gleyo-Vertic Cambisol'),
('Bvk', '', 'Calci-Vertic Cambisol'),
('Bx', '', 'Gelic Cambisol'),
('Bxs', '', 'Spodo-Gelic Cambisol'),
('C', 'CH', 'Chernozem'),
('', 'CHg', 'Gleyic Chernozems'),
('Ch', 'CHh', 'Haplic Chernozem'),
('Chp', '', 'Pachi-Haplic Chernozem'),
('Chv', '', 'Verti-Haplic Chernozem'),
('Ck', 'CHk', 'Calcic Chernozem'),
('Ckb', '', 'Vermi-Calcic Chernozem'),
('Ckc', '', 'Calcaro-Calcic Chernozem'),
('Ckcb', '', 'Vermi-Calcaro-Calcic Chernozem'),
('Ckp', '', 'Pachi-Calcic Chernozem'),
('Cl', 'CHl', 'Luvic Chernozem'),
('', 'CHw', 'Glossic Chernozems'),
('', 'CL', 'Calcisols'),
('', 'CLh', 'Haplic Calcisols'),
('', 'CLhd', 'Duri-haplic Calcisols'),
('', 'CLl', 'Luvic Calcisols'),
('', 'CLp', 'Petric Calcisols'),
('', 'CM', 'Cambisols'),
('', 'CMc', 'Calcaric Cambisols'),
('', 'CMch', 'Hyper-calcaric Cambisols'),
('', 'CMd', 'Dystric Cambisols'),
('', 'CMe', 'Eutric Cambisols'),
('', 'CMg', 'Gleyic Cambisols'),
('', 'CMi', 'Gelic Cambisols'),
('', 'CMo', 'Ferralic Cambisols'),
('', 'CMu', 'Humic Cambisols'),
('', 'CMv', 'Vertic Cambisols'),
('', 'CMx', 'Chromic Cambisols'),
('D', '', 'Podzoluvisol'),
('Dd', '', 'Dystric Podzoluvisol'),
('De', '', 'Eutric Podzoluvisol'),
('Dg', '', 'Gleyic Podzoluvisol'),
('Dgd', '', 'Dystric Gleyic Podzoluvisol'),
('Dge', '', 'Eutric Gleyic Podzoluvisol'),
('Dgs', '', 'Stagno-Gleyic Podzoluvisol'),
('E', '', 'Rendzina'),
('Ec', '', 'Cambic Rendzina'),
('Eh', '', 'Histic Rendzina'),
('Eo', '', 'Orthic Rendzina'),
('J', 'FL', 'Fluvisol'),
('Jc', 'FLc', 'Calcaric Fluvisol'),
('Jcf', '', 'Fluvi-Calcaric Fluvisol'),
('Jcg', '', 'Gleyo-Calcaric Fluvisol'),
('Jd', 'FLd', 'Dystric Fluvisol'),
('Jdf', '', 'Fluvi-Dystric Fluvisol'),
('Jdg', 'FLdg', 'Gleyo-Dystric Fluvisol'),
('Je', 'Fle', 'Eutric Fluvisol'),
('Jef', '', 'Fluvi-Eutric Fluvisol'),
('Jeg', '', 'Gleyo-Eutric Fluvisol'),
('Jm', 'FLm', 'Mollic Fluvisol'),
('Jmg', '', 'Gleyo-Mollic Fluvisol'),
('Jmv', '', 'Verti-Mollic Fluvisol'),
('Jt', 'FLt', 'Thionic Fluvisol'),
('', 'FLs', 'Salic Fluvisols'),
('', 'FLu', 'Umbric Fluvisols'),
('F', 'FR', 'Ferralsol'),
('Fo', '', 'Orthic Ferralsol'),
('', 'FRg', 'Geric Ferralsols'),
('', 'FRh', 'Haplic Ferralsols'),
('', 'FRhv', 'Veti-haplic Ferralsols'),
('', 'FRp', 'Plinthic Ferralsols'),
('', 'FRr', 'Rhodic Ferralsols'),
('', 'FRu', 'Humic Ferralsols'),
('', 'FRua', 'Andi-humic Ferralsols'),
('', 'FRx', 'Xanthic Ferralsols'),
('', 'FRxl', 'Acri-xanthic Ferralsols'),
('G', 'GL', 'Gleysol'),
('', 'GLa', 'Andic Gleysols'),
('Gc', '', 'Calcaric Gleysol'),
('Gcf', '', 'Fluvi-Calcaric Gleysol'),
('Gcs', '', 'Stagno-Calcaric Gleysol'),
('Gd', 'GLd', 'Dystric Gleysol'),
('Gdf', '', 'Fluvi-Dystric Gleysol'),
('Gds', '', 'Stagno-Dystric Gleysol'),
('Ge', 'GLe', 'Eutric Gleysol'),
('Gef', '', 'Fluvi-Eutric Gleysol'),
('Ges', '', 'Stagno-Eutric Gleysol'),
('Gev', '', 'Verti-Eutric Gleysol'),
('Gf', '', 'Fluvic Gleysol'),
('Gfm', '', 'Molli-Fluvic Gleysol'),
('Gh', '', 'Humic Gleysol'),
('Ghf', '', 'Fluvi-Humic Gleysol'),
('Ghh', '', 'Histo-Humic Gleysol'),
('Ght', '', 'Thioni-Humic Gleysol'),
('', 'GLi', 'Gelic Gleysols'),
('Gi', '', 'Histic Gleysol'),
('Gih', '', 'Humo-Histic Gleysol'),
('', 'GLk', 'Calcic Gleysols'),
('Gl', '', 'Luvic Gleysol'),
('Gls', '', 'Stagno-Luvic Gleysol'),
('Gm', 'GLm', 'Mollic Gleysol'),
('Gmc', '', 'Calcaro-Mollic Gleysol'),
('Gmf', '', 'Fluvi-Mollic Gleysol'),
('Gmv', '', 'Verti-Mollic Gleysol'),
('Gs', '', 'Stagnic Gleysol'),
('Gt', 'GLt', 'Thionic Gleysol'),
('', 'GLu', 'Umbric Gleysols'),
('', 'GR', 'Greyzems'),
('', 'GRg', 'Gleyic Greyzems'),
('', 'GRh', 'Haplic Greyzems'),
('', 'GY', 'Gypsisols'),
('', 'GYh', 'Haplic Gypsisols'),
('', 'GYk', 'Calcic Gypsisols'),
('', 'GYl', 'Luvic Gypsisols'),
('', 'GYp', 'Petric Gypsisols'),
('H', '', 'Phaeozem'),
('Hc', '', 'Calcaric Phaeozem'),
('Hcf', '', 'Fluvi-Calcaric Phaeozem'),
('Hcn', '', 'Alkalino-Calcaric Phaeozem'),
('Hcs', '', 'Saline-Calcaric Phaeozem'),
('Hg', '', 'Gleyic Phaeozem'),
('Hgc', '', 'Calcaro-Gleyic Phaeozem'),
('Hgf', '', 'Fluvi-Gleyic Phaeozem'),
('Hgs', '', 'Stagno-Gleyic Phaeozem'),
('Hgv', '', 'Verti-Gleyic Phaeozem'),
('Hh', '', 'Haplic Phaeozem'),
('Hhv', '', 'Verti-Haplic Phaeozem'),
('Hl', '', 'Luvic Phaeozem'),
('Hlv', '', 'Verti-Luvic Phaeozem'),
('Ho', '', 'Orthic Phaeozem'),
('O', 'HS', 'Histosol'),
('Od', '', 'Dystric Histosol'),
('Odp', '', 'Placi-Dystric Histosol'),
('Oe', '', 'Eutric Histosol'),
('', 'HSf', 'Fibric Histosols'),
('', 'HSi', 'Gelic Histosols'),
('', 'HSl', 'Folic Histosols'),
('', 'HSs', 'Terric Histosols'),
('', 'HSt', 'Thionic Histosols'),
('', 'HSts', 'Sulfi-thionic Histosols'),
('I', '', 'Lithosol'),
('Ic', '', 'Calcaric Lithosol'),
('Ich', '', 'Humo-Calcaric Lithosol'),
('Id', '', 'Dystric Lithosol'),
('Ie', '', 'Eutric Lithosol'),
('K', 'KS', 'Kastanozem'),
('Kh', 'KSh', 'Haplic Kastanozem'),
('Khb', '', 'Vermi-Haplic Kastanozem'),
('Kk', 'KSk', 'Calcic Kastanozem'),
('Kkb', '', 'Vermi-Calcic Kastanozem'),
('Kkv', '', 'Verti-Calcic Kastanozem'),
('Kl', 'KSl', 'Luvic Kastanozem'),
('Ko', '', 'Orthic Kastanozem'),
('', 'KSy', 'Gypsic Kastanozems'),
('', 'LP', 'Leptosols'),
('', 'LPd', 'Dystric Leptosols'),
('', 'LPe', 'Eutric Leptosols'),
('', 'LPi', 'Gelic Leptosols'),
('', 'LPk', 'Rendzic Leptosols'),
('', 'LPm', 'Mollic Leptosols'),
('', 'LPq', 'Lithic Leptosols'),
('', 'LPu', 'Umbric Leptosols'),
('L', 'LV', 'Luvisol'),
('La', 'LVa', 'Albic Luvisol'),
('Lap', '', 'Plano-Albic Luvisol'),
('Lc', '', 'Chromic Luvisol'),
('Lcp', '', 'Plano-Chromic Luvisol'),
('Lcr', '', 'Rhodo-Chromic Luvisol'),
('Lcv', '', 'Verti-Chromic Luvisol'),
('Ld', '', 'Dystric Luvisol'),
('Ldg', '', 'Gleyo-Dystric Luvisol'),
('Lf', 'LVf', 'Ferric Luvisol'),
('', 'LVff', 'Fragi-ferric Luvisols'),
('Lg', 'LVg', 'Gleyic Luvisol'),
('Lga', '', 'Albo-Gleyic Luvisol'),
('Lgp', '', 'Plano-Gleyic Luvisol'),
('Lgs', 'LVgj', 'Stagno-Gleyic Luvisol'),
('', 'LVh', 'Haplic Luvisols'),
('Lh', '', 'Humic Luvisol'),
('', 'LVj', 'Stagnic Luvisols'),
('Lk', 'LVk', 'Calcic Luvisol'),
('Lkc', '', 'Chromo-Calcic Luvisol'),
('Lkcr', '', 'Rhodo-Chromo-Calcic Luvisol'),
('Lkv', '', 'Verti-Calcic Luvisol'),
('Lo', '', 'Orthic Luvisol'),
('Lop', '', 'Plano-Orthic Luvisol'),
('Lp', '', 'Plinthic Luvisol'),
('Ls', '', 'Spodic Luvisol'),
('Lv', 'LVv', 'Vertic Luvisol'),
('Lvc', '', 'Chromo-Vertic Luvisol'),
('Lvcr', '', 'Rhodo-Chromo-Vertic Luvisol'),
('Lvk', '', 'Calci-Vertic Luvisol'),
('', 'LVx', 'Chromic Luvisols'),
('', 'LX', 'Lixisols'),
('', 'LXa', 'Albic Lixisols'),
('', 'LXaa', 'Areni-albic Lixisols'),
('', 'LXaf', 'Ferri-albic Lixisols'),
('', 'LXf', 'Ferric Lixisols'),
('', 'LXg', 'Gleyic Lixisols'),
('', 'LXh', 'Haplic Lixisols'),
('', 'LXj', 'Stagnic Lixisols'),
('', 'LXp', 'Plinthic Lixisols'),
('M', '', 'Greyzem'),
('Mo', '', 'Orthic Greyzem'),
('', 'NT', 'Nitisols'),
('', 'NTh', 'Haplic Nitisols'),
('', 'NTr', 'Rhodic Nitisols'),
('', 'NTu', 'Humic Nitisols'),
('', 'PD', 'Podzoluvisols'),
('', 'PDd', 'Dystric Podzoluvisols'),
('', 'PDe', 'Eutric Podzoluvisols'),
('', 'PDg', 'Gleyic Podzoluvisols'),
('', 'PDi', 'Gelic Podzoluvisols'),
('', 'PDj', 'Stagnic Podzoluvisols'),
('', 'PH', 'Phaeozems'),
('', 'PHc', 'Calcaric Phaeozems'),
('', 'PHcv', 'Vertic-calcaric Phaeozems'),
('', 'PHg', 'Gleyic Phaeozems'),
('', 'PHh', 'Haplic Phaeozems'),
('', 'PHj', 'Stagnic Phaeozems'),
('', 'PHl', 'Luvic Phaeozems'),
('', 'PL', 'Planosols'),
('', 'PLd', 'Dystric Planosols'),
('', 'PLdh', 'Hyper-dystric Planosols'),
('', 'PLe', 'Eutric Planosols'),
('', 'PLi', 'Gelic Planosols'),
('', 'PLm', 'Mollic Planosols'),
('', 'PLu', 'Umbric Planosols'),
('', 'PT', 'Plinthosols'),
('', 'PTa', 'Albic Plinthosols'),
('', 'PTd', 'Dystric Plinthosols'),
('', 'PTe', 'Eutric Plinthosols'),
('', 'PTu', 'Humic Plinthosols'),
('P', 'PZ', 'Podzol'),
('', 'PZb', 'Cambic Podzols'),
('', 'PZc', 'Carbic Podzols'),
('', 'PZge', 'Epi-gleyic Podzols'),
('Pf', 'PZf', 'Ferric Podzol'),
('Pg', 'PZg', 'Gleyic Podzol'),
('Pgh', '', 'Histo-Gleyic Podzol'),
('Pgs', '', 'Stagno-Gleyic Podzol'),
('', 'PZh', 'Haplic Podzols'),
('', 'PZi', 'Gelic Podzols'),
('Ph', '', 'Humic Podzol'),
('Phf', '', 'Ferro-Humic Podzol'),
('Pl', '', 'Leptic Podzol'),
('Plh', '', 'Humo-Leptic Podzol'),
('Po', '', 'Orthic Podzol'),
('Pof', '', 'Ferro-Orthic Podzol'),
('Poh', '', 'Humo-Orthic Podzol'),
('Pol', '', 'Lepto-Orthic Podzol'),
('Pp', '', 'Placic Podzol'),
('Pph', '', 'Humo-Placic Podzol'),
('R', 'RG', 'Regosol'),
('Rc', 'RGc', 'Calcaric Regosol'),
('', 'RGcr', 'Rudi-calcaric Regosols'),
('Rd', 'RGd', 'Dystric Regosol'),
('Re', 'RGe', 'Eutric Regosol'),
('', 'RGi', 'Gelic Regosols'),
('', 'RGu', 'Umbric Regosols'),
('', 'RGy', 'Gypsic Regosols'),
('', 'SC', 'Solonchaks'),
('', 'SCg', 'Gleyic Solonchaks'),
('', 'SCh', 'Haplic Solonchaks'),
('', 'SCi', 'Gelic Solonchaks'),
('', 'SCk', 'Calcic Solonchaks'),
('', 'SCm', 'Mollic Solonchaks'),
('', 'SCn', 'Sodic Solonchaks'),
('', 'SCy', 'Gypsic Solonchaks'),
('S', 'SN', 'Solonetz'),
('Sg', 'SNg', 'Gleyic Solonetz'),
('', 'SNh', 'Haplic Solonetz'),
('', 'SNj', 'Stagnic Solonetz'),
('', 'SNja', 'Antraqui-stagnic Solonetz'),
('', 'SNk', 'Calcic Solonetz'),
('Sm', 'SNm', 'Mollic Solonetz'),
('', 'SNmk', 'Calci-mollic Solonetz'),
('So', '', 'Orthic Solonetz'),
('Sof', '', 'Fluvi-Orthic Solonetz'),
('', 'SNy', 'Gypsic Solonetz'),
('T', '', 'Andosol'),
('Th', '', 'Humic Andosol'),
('Tm', '', 'Mollic Andosol'),
('To', '', 'Ochric Andosol'),
('Tv', '', 'Vitric Andosol'),
('U', '', 'Ranker'),
('Ud', '', 'Dystric Ranker'),
('Ul', '', 'Luvic Ranker'),
('V', 'VR', 'Vertisol'),
('Vc', '', 'Chromic Vertisol'),
('Vcc', '', 'Calcaro-Chromic Vertisol'),
('', 'VRd', 'Dystric Vertisols'),
('', 'VRdp', 'Pelli-dystric Vertisols'),
('', 'VRe', 'Eutric Vertisols'),
('', 'VReg', 'Grumi-eutric Vertisols'),
('', 'VRem', 'Mazi-eutric Vertisols'),
('Vg', '', 'Gleyic Vertisol'),
('', 'VRk', 'Calcic Vertisols'),
('Vp', '', 'Pellic Vertisol'),
('Vpc', '', 'Calcaro-Pellic Vertisol'),
('Vpg', '', 'Gleyo-Pellic Vertisol'),
('Vpn', '', 'Sodi-Pellic Vertisol'),
('', 'VRy', 'Gypsic Vertisols'),
('W', '', 'Planosol'),
('Wd', '', 'Dystric Planosol'),
('Wdv', '', 'Verti-Dystric Planosol'),
('We', '', 'Eutric Planosol'),
('Wev', '', 'Verti-Eutric Planosol'),
('Wm', '', 'Mollic Planosol'),
('X', '', 'Xerosol'),
('Xk', '', 'Calcic Xerosol'),
('Xl', '', 'Luvic Xerosol'),
('Xy', '', 'Gypsic Xerosol'),
('Z', '', 'Solonchak'),
('Zg', '', 'Gleyic Solonchak'),
('Zgf', '', 'Fluvi-Gleyic Solonchak'),
('Zo', '', 'Orthic Solonchak'),
('Zt', '', 'Takyric Solonchak'),
('g', '', 'Glacier'),
('p', '', 'Plaggensol'),
('r', '', 'Rock Outcrop'),
('Gtz', '', 'Undefined code'),
('Rds', '', 'Undefined code'),
('Vgs', '', 'Undefined code');";

if(!mysqli_query($GLOBALS['db_conn'],$insertSoilDescri))
	printf("Error: %s\n", mysqli_error($GLOBALS['db_conn']));
