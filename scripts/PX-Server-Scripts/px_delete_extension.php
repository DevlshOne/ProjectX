#!/usr/bin/php
<?php

	// INCLUDE DATABASE CONNECTION
	require_once("/var/www/html/dev/db.inc.php");

/**
 * PX Remove extension
 * Written By: Jonathan Will
 *
 * To remove an extension:
 * 1) sip-px.conf	- Remove extension block
 * 2) iax-px.conf	- Remove extension block
 * 3) iax.conf		- Remove register line
 * 4) extensions.conf-Remove extensions block
 * 5) meetme.conf	- Remove conference line
 * 6) DB			- Remove record from DB
 * 7) Asterisk		- Reload asterisk
 *
 */

	global $config_dir;
	global $meetme_prefix;
	global $server_id;


	$server_id = 6; // PX SERVERS TABLE ID, CHANGE AS NECESSARY

	$config_dir = "/etc/asterisk/"; // change this if you want to test it first in another dir

	$meetme_prefix = "1024";



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

	echo "\nProject X - Remove extension\nBy: Jonathan Will\n\n";


	if(!isset($argv[1]) || !$argv[1]){

		missingArgs($argv[0]);

	}

	$exten = intval($argv[1]);

	if($exten < 100 || $exten > 99999){

		die("ERROR: Extension must be between 3 and 5 digits long.\n");

	}



	// CHECK DATABASE FOR EXTENSION EXISTANCE
	list($extension_id) = queryROW("SELECT id FROM extensions WHERE `number`=$exten AND status='enabled' ");

	if(!$extension_id){

		die("ERROR: Extension '$exten' not found in the database for server #".$server_id.".\n");

	}




	// 1) sip-px.conf	- Remove extension block
	echo "1) sip-px.conf - Remove extension block\n";
	removeExtensionBlock($config_dir."sip-px.conf", $exten);


	// 2) iax-px.conf	- Remove extension block
	echo "2) iax-px.conf - Remove extension block\n";
	removeExtensionBlock($config_dir."iax-px.conf", $exten);

	// 3) iax.conf		- Remove register line
	echo "3) iax.conf - Remove register line\n";
	removeLine("register => ".$exten.":", $config_dir."iax.conf");



	// 4) extensions.conf-Remove extensions block
	echo "4) extensions.conf - Remove extensions block\n";
	removeExtensionBlock($config_dir."extensions-px.conf", "px-".$exten);


	// 5) meetme.conf	- Remove conference line
	echo "5) meetme.conf - Remove conference line\n";
	removeLine("conf => ".$meetme_prefix.$exten, $config_dir."meetme.conf");

	// 6) DB			- Remove record from DB
	echo "6) DB - Remove record from DB\n";
	execSQL("DELETE FROM `extensions` WHERE `id`='".$extension_id."'");


	 // 7) Asterisk		- Reload asterisk
	$cmd = "asterisk -rx reload";
	echo "7) Reloading Asterisk via '$cmd'\n";
	echo `$cmd`;

	echo "DONE!\n\n";








