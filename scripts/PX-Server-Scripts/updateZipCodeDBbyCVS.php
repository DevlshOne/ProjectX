#!/usr/bin/php
<?php
/**
 * UPDATES THE ZIPCODES TABLE IN PX, used for city/state lookup by zipcode
 * Written by: Jonathan Will
 *
 * DATA SOURCE:
 *  https://zipcodeo.com/download-zip-code-list
 *
 *  https://zipcodeo.com/databases/zip_codes_full.csv
 *
 * DATA EXAMPLE:
_____________________________
id	zipCode	zipType	postOfficeCity	state	stateCode	county	areaCode	longitude	latitude	population	populationDensity	housingUnits	medianHomeValue	occupiedHousingUnits	medianHouseholdIncome	waterArea	landArea	timezone
1	99553	PO Box	Akutan	Alaska	AK	Aleutians East Borough	907	-165.5	54.1	1027	74	44	112500	40	38333	0.1	13.83	America/Nome
2	99571	PO Box	Cold Bay	Alaska	AK	Aleutians East Borough	907	-161	55	160	5	111	59200	68	53500	3.53	29.42	America/Nome
_____________________________


 */

	require_once("/var/www/html/dev/db.inc.php");



	if(count($argv) < 2){

		$filename = "https://zipcodeo.com/databases/zip_codes_full.csv";

//		die("Args required! Specify the CVS file to parse!\n");

	}else{

		$filename = $argv[1];

		if(!file_exists($filename)){

			die("File: $filename does not exist! Exiting!\n");

		}

	}


	// CAN EVENTUALLY CHANGE THIS TO "zipcodes", so its fully automated, once its been tested for a while
	$table = "zipcodes_update";






	$filedata = file_get_contents($filename);
	$linearr = preg_split("/\n|\r/", $filedata, -1, PREG_SPLIT_NO_EMPTY);

	echo "Zip Code CVS Parser - parsing ".count($linearr)." records/lines.\n";

	if(count($linearr) > 1000){

		// FLUSH THE TABLE FIRST
		execSQL("DELETE FROM `".$table."` WHERE 1");


	}else{

		die("ERROR: Zipcode data is less than 1000 lines, something is wrong. (	".count($linearr)." lines)\n\nData:".$filedata);
	}

	foreach($linearr as $idx=>$line){

		if($idx == 0)continue;

		$line = trim($line);

		//echo "line: ".$line;

		$row = str_getcsv($line);


		//print_r($row);
		$x=0;

		$dat = array();

//		if($adding_new){
			// SET THE ID THE FIRST TIME
			$dat['id'] 	= $row[$x++];
			$id = $dat['id'];
//		}else{
//			$id = $row[$x++];
//		}

		$dat['zip'] 	= $row[$x++];
		$dat['type']	= $row[$x++];
		$dat['city']	= $row[$x++];

		$x++; // SKIP THE FULL STATE NAME

		$dat['state']			= $row[$x++];
		$dat['county']			= $row[$x++];
		$dat['area_codes']		= $row[$x++];
		$dat['longitude']		= $row[$x++];
		$dat['latitude']		= $row[$x++];

		$dat['est_population']	= intval($row[$x++]);

		$x++; // SKIP "population density"
		$x++; // SKIP "housing units"
		$x++; // SKIP "median home value"
		$x++; // SKIP "occupied Housing units"
		$x++; // SKIP "medianHouseholdIncome"
		$x++; // SKIP "waterArea"
		$x++; // SKIP "landArea"

		$dat['timezone']= $row[$x++];


//		if($adding_new){
			aadd($dat, $table);
//		}else{
//			aedit($id, $dat, $table);
//		}


	}


	echo "Done.\n";







