#!/usr/bin/php
<?php


/**
 * PX Remove extension
 * Written By: Jonathan Will
 *
 * To remove an extension:
 * 6) DB			- Remove record from DB
 * 7) Asterisk		- Reload asterisk
 *
 */
	include("config.php");
	
	
	// INCLUDE DATABASE CONNECTION
	require_once($basedir."db.inc.php");
/*****/




	function missingArgs($program){
		die("Missing Parameters. Please specify the extension to remove.\nExample: ".$program." 12345\n\n");
	}

	function startsWith($haystack, $needle){return $needle === "" || strpos($haystack, $needle) === 0;}

	function removeExtensionBlock($file, $exten){

		$data = file_get_contents($file);

		$linearr = preg_split("/\r\n|\n/", $data);

		$out = "";
		$cutmode = false;
		foreach($linearr as $line){

			// FIND EITHER A NEW LINE, OR THE NEXT BLOCK, TO TURN CUT BACK OFF
			if(
				////trim($line) == "" || // FUCK THE NEW LINE, NUKE THE WHOLE BLOCK
				startsWith($line, "[")
			){
				$cutmode = false;
			}

			// FIND THE BLOCK
			if(startsWith($line, "[".$exten."]")){
				// START SKIPPING LINES
				$cutmode = true;
			}




			if(!$cutmode){
				$out .= $line."\n";
			}
		}

		if(file_put_contents($file, $out) === FALSE){
			die("ERROR: Couldn't remove extension block in '".$file."': write problem\n");
		}
	}

	function removeLine($needle, $file){

		$data = file_get_contents($file);

		$linearr = preg_split("/\r\n|\n/", $data);
		$out = "";
		foreach($linearr as $line){

			// SKIP THE LINE
			if(startsWith($line, $needle)){
				continue;
			}

			$out .= $line."\n";
		}


		if(file_put_contents($file, $out) === FALSE){
			die("ERROR: Couldn't remove register line in '".$file."': write problem\n");
		}
	}


/** START DOING SHIT HERE **/

	echo "\nProject X - Remove extension NEW AND IMPROVED\nBy: Jonathan Will\n\n";


	if(!isset($argv[1]) || !$argv[1]){

		missingArgs($argv[0]);

	}

	$exten = intval($argv[1]);

	if($exten < 100 || $exten > 999999){

		die("ERROR: Extension must be between 3 and 6 digits long.\n");

	}



	// CHECK DATABASE FOR EXTENSION EXISTANCE
	list($extension_id) = queryROW("SELECT id FROM extensions WHERE `number`=$exten AND `status`='enabled' AND server_id='$server_id' ");

	if(!$extension_id){

		die("ERROR: Extension '$exten' not found in the database for server #".$server_id.".\n");

	}



	// 6) DB			- Remove record from DB
	echo "6) DB - Remove record from DB\n";
	execSQL("DELETE FROM `extensions` WHERE `id`='".$extension_id."' AND `server_id`='$server_id'");


	// RUN COMMAND TO REGENERATE CONFIG
	echo "2) Regenerating configs and reloading asterisk via '$config_gen_cmd'\n";
	echo `$config_gen_cmd`;
	
	echo "DONE!\n\n";
	







