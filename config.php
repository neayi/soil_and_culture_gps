<?php

$database="dbtest";
$dbHost="localhost";
$dbUser="root";
$dbPassword="";

$GLOBALS['TOKEN_IP']='64a05c25e31c0a';
$GLOBALS['urlTOKEN']="https://ipinfo.io/";

// Path to the folder containing ogr2ogr.exe
$GLOBALS['ogr2ogrpass']="C:\OSGeo4W64\bin";


// The soils datatables can be downloaded from this website: https://data.inra.fr/dataset.xhtml?persistentId=doi:10.15454/BPN57S
$GLOBALS['external_data']['urlSoilsShpFile'] = array('url' => "https://data.inra.fr/api/access/datafile/8787?gbrecs=true", 'localfilename' => 'soils.zip', 'tablename'=>'soils');

//$GLOBALS['external_data']['urlAttricod'] = array('url' => "https://data.inra.fr/api/access/datafile/8791?gbrecs=true", 'localfilename' => 'attricod.txt', 'tablename'=>'');

$GLOBALS['external_data']['urlSMU'] = array('url' => "https://data.inra.fr/api/access/datafile/8793?format=tab&gbrecs=true", 'localfilename' => 'smu.tab', 'tablename'=>'smu');

$GLOBALS['external_data']['urlSTU'] = array('url' => "https://data.inra.fr/api/access/datafile/8794?format=tab&gbrecs=true", 'localfilename' => 'stu.tab','tablename'=>'stu');

$GLOBALS['external_data']['urlSTUORG'] = array('url' => "https://data.inra.fr/api/access/datafile/8795?format=tab&gbrecs=true", 'localfilename' => 'stuorg.tab','tablename'=>'stuorg');



$GLOBALS['CSVtables']="cultures";

// Data tables for Crops codification : main crops and catch crops (cultures principales et cultures derobees)

$GLOBALS['external_data']['urlCodificationMainCrops'] = array('url' => "https://www.data.gouv.fr/fr/datasets/r/18658e27-e7e5-4dee-a3c8-ee2f9c840f8c",'localfilename'=>'maincrops.csv','tablename'=>'cultures');

$GLOBALS['external_data']['urlCodificationCatchCrops'] = array('url' => "https://www.data.gouv.fr/fr/datasets/r/939387de-d184-43d9-b88d-5bb3a34db6db",'localfilename'=>'catchcrops.csv','tablename'=>'');



// The RPG datatable can be downloaded from this website: https://www.data.gouv.fr/fr/datasets/registre-parcellaire-graphique-rpg-contours-des-parcelles-et-ilots-culturaux-et-leur-groupe-de-cultures-majoritaire/#_
$GLOBALS['external_data']['urlRPG2017'] = array('url' => "https://www.data.gouv.fr/fr/datasets/r/debf37f0-fac5-48a1-b70d-dcdee462219c",'localfilename'=>'rpg2017.zip', 'tablename'=>'rpg2017');
//http://professionnels.ign.fr/rpg#tab-3



