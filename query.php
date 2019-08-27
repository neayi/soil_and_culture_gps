<?php
include 'config.php';

// First lets create a connection to the DB:
$GLOBALS['db'] = new mysqli($dbHost, $dbUser, $dbPassword, $database); 

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

// Analysing the command line
if (isset($argc)) {
	$parameter_type=$argv[1];
	switch ($parameter_type) {
		case 'GPS':
			echo $argv[1]." : " . $argv[2]." ". $argv[3]."\n";
			$long=$argv[2];
			$lat=$argv[3];	
			getInfoFromGPSCoord($long,$lat);
			break;

		case 'PC':
			echo $argv[1]." : " . $argv[2]."\n";
			$pc=$argv[2];
			//recuperer les coordonnees GPS depuis un code postal
			getInfoFromPostalCode($pc);
			break;

		case 'IP':
			echo $argv[1]." : " . $argv[2]."\n";
			$ip=$argv[2]; //recuperer IP depuis appareil ?
			//recuperer les coordonnees GPS depuis adresse IP
			getInfoFromIPAddress($ip);		
			break;
		
		default:
			break;
	}
}
else {
	echo "argc and argv disabled\n";
}

// Definitions of functions

function convertWGS84ToLambert93($xWGS84,$yWGS84)
{	
	// constants definition
	    $c= 11754255.426096; //projection constant
	    $e= 0.0818191910428158; //first eccentricity of ellipsoid
	    $n= 0.725607765053267; //exponent of the projection
	    $xs= 700000; //projected coordinates of the pole
	    $ys= 12655612.049876; //projected coordinates of the pole

	// pre-calculation
	    $lat_rad= $xWGS84/180*PI(); //latitude in rad
	    $lat_iso= atanh(sin($lat_rad))-$e*atanh($e*sin($lat_rad)); //isometric latitude 

	// conversion
	    $x= (($c*exp(-$n*($lat_iso)))*sin($n*($yWGS84-3)/180*PI())+$xs);
	    echo "xL93 : $x\n";
	    $y= ($ys-($c*exp(-$n*($lat_iso)))*cos($n*($yWGS84-3)/180*PI()));
	    echo "yL93 : $y\n";

	    $result[]=$x;
	    $result[]=$y;
	    return $result;
}

function getInfoFromGPSCoord($long,$lat)
{
	//constante d'une distance de 10 km en Lambert 93
	$L93_10km=10000;

	list($longL93,$latL93)=convertWGS84ToLambert93($lat,$long);

	echo "Execution de la 1e requete...\n";
	//recuperer resultat requete SQL
	$rpg=$GLOBALS['external_data']['urlRPG2017']['tablename'];
	$cultures=$GLOBALS['CSVtables'];
	$sql= "SELECT DISTINCT code_cultu, label, label_groupe
			FROM $rpg
			INNER JOIN $cultures ON rpg2017.code_cultu=cultures.code
			WHERE ST_DISTANCE(ST_GeomFromText('POINT($longL93 $latL93)',2154),SHAPE)<=$L93_10km
			ORDER BY ST_DISTANCE(ST_GeomFromText('POINT($longL93 $latL93)',2154),SHAPE) ASC";
	echo $sql . "\n";
	$parcels = array();
	if($query=$GLOBALS['db']->query($sql))
	{
		while($row=$query->fetch_assoc())
			$parcels[$row['code_cultu']]=$row;
	}

	$info['parcels']=$parcels;

	echo "Execution de la 2e requete...\n";
	//Pour que le resultat sur le terminal soit plus lisible, demandez plutot stu.soil
	$stu=$GLOBALS['external_data']['urlSTU']['tablename'];
	$stuorg=$GLOBALS['external_data']['urlSTUORG']['tablename'];
	$soilsTable=$GLOBALS['external_data']['urlSoilsShpFile']['tablename'];
	$sql="SELECT stu.soil, stu.stu, soil.smu, stuorg.pcarea,soil_description.soil 
			FROM $stu
			INNER JOIN $stuorg ON stu.stu = stuorg.stu
			INNER JOIN $soilsTable ON soil.smu = stuorg.smu 
			INNER JOIN soil_description on soil_description.soil85=stu.soil
			WHERE ST_DISTANCE(ST_GeomFromText('POINT($longL93 $latL93)',2154),SHAPE)<=$L93_10km";
			// OR soil_description.soil90=stu.soil90
	echo $sql . "\n";
	$soils=array();
	if($query=$GLOBALS['db']->query($sql))
	{
		while($row=$query->fetch_assoc())
			$soils[$row['soil']]=$row;
	}

	$info['soils']=$soils;

	echo "Resultat...\n";
	var_dump($info);

	return $info;
}


function getInfoFromIPAddress($ip)
{
	$info = "";

  	$TOKEN=$GLOBALS['TOKEN_IP'];
  	$urltoken=$GLOBALS['urlTOKEN'];

  	if($TOKEN==''){
  		echo "Please enter a valid token in config.php to get cultures and soil information from an IP address.\n";
  		echo "You can get a token from this website : $urltoken . \n";
  	}else{
		$json  = file_get_contents("http://ipinfo.io/$ip/geo?token=$TOKEN");
		$json  = json_decode($json, true);

		// "loc": "43.6043,1.4437",
		if(array_key_exists('loc', $json)){
			$gps = $json['loc'];	

			// We want to return the GPS in the format '1.4437 43.6043'
			list($long, $lat) = array_reverse(explode(',', $gps));
			echo "lg : $long\n";
			echo "lt : $lat\n";	

			$info = getInfoFromGPSCoord($long, $lat);
		}else{
			echo "This IP address does not correspond to any location. Please choose a different IP.\n";
		}
  	}
  	return $info;	
}

function getInfoFromPostalCode($pc)
{
	$info="";

	$json = file_get_contents("https://api-adresse.data.gouv.fr/search/?q=$pc");
	$json = json_decode($json,true);
	list($long,$lat) = $json['features'][1]['geometry']['coordinates'];
	echo "lg : $long\n";
	echo "lt : $lat\n";	

	$info = getInfoFromGPSCoord($long,$lat);
	return $info;
}