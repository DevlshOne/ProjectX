#!/usr/bin/php
<?

	require_once("/var/www/html/dev/db.inc.php");


	if(count($argv) < 2){

		die("Args required! Specify the CVS file to parse!\n");

	}

	$filename = $argv[1];

	if(!file_exists($filename)){

		die("File: $filename does not exist! Exiting!\n");

	}


//"00606","STANDARD","Maricao",,"Urb San Juan Bautista","PR","Maricao","America/Puerto_Rico","787,939","18.18","-66.98","NA","US","0","0",
//"00501","UNIQUE","Holtsville",,"I R S Service Center","NY","Suffolk County","America/New_York","631","40.81","-73.04","NA","US","0","384",


	$filedata = file_get_contents($filename);

	$linearr = preg_split("/\n|\r/", $filedata, -1, PREG_SPLIT_NO_EMPTY);

	echo "Zip Code CVS Parser - parsing ".count($linearr)." records/lines.\n";


	foreach($linearr as $line){
		$line = trim($line);

		//echo "line: ".$line;

		$row = str_getcsv($line);


		//print_r($row);


		$dat = array();
		$dat['zip'] 	= $row[0];
		$dat['type']	= $row[1];
		$dat['city']	= $row[2];
		$dat['city_alt']= $row[3];
		$dat['unacceptable_cities']=$row[4];
		$dat['state']	= $row[5];
		$dat['county']	= $row[6];
		$dat['timezone']= $row[7];
		$dat['area_codes']=$row[8];
		$dat['latitude']	= $row[9];
		$dat['longitude']	= $row[10];
		$dat['world_region']= $row[11];
		$dat['country']		= $row[12];
		$dat['decommissioned']=$row[13];
		$dat['est_population']=$row[14];


		aadd($dat, 'zipcodes');


	}


	echo "Done.\n";







