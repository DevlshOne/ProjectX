#!/usr/bin/php
<?php
/**
 * Add Names - Takes the names you pass in, and adds or updates them in the database
 * Written By: Jonathan Will
 */


	if(count($argv) < 2){

		die("Error: WAV file arguments not provided.\n".
		"Example:\tadd_names.php ted.wav timothy.wav susan.wav maria.wav\n");

	}


	## INCLUDE DATABASE CONNECTION
	include_once("/var/www/site_config.php");
	include_once($_SESSION['site_config']['basedir']."db.inc.php");





	foreach($argv as $idx=>$filename){

		if($idx==0)continue;


		if(!is_file($filename)){
			echo "Error: ".$filename." not a valid file, skipping.\n";
			continue;
		}

		$path = realpath($filename);
		$path_parts = pathinfo($filename);

		if(strtolower($path_parts['extension']) != 'wav'){
			echo "Error: ".$filename." not a WAV file, skipping.\n";
			continue;
		}

		$name = strtolower(trim($path_parts['filename']));


		// CHECK DATABASE FOR EXISTING NAME
		list($id) = queryROW("SELECT id FROM names WHERE name LIKE '".addslashes($name)."' ");

		// IF FOUND, UPDATE FILE PATH
		if($id > 0){

			echo "Processing: '".$name."' found, EDITING ID# ".$id."\n";

			$dat = array();
			$dat['filename'] = $path;
			aedit($id, $dat, 'names');

		// NOT FOUND, ADD FILE
		}else{

			echo "Processing: '".$name."' not found, Adding ".$path."\n";

			$dat = array();
			$dat['name'] = $name;
			$dat['filename'] = $path;
			aadd($dat, 'names');

		}
	}