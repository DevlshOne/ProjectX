#!/usr/bin/php
<?php
/**
 * PX Add extension - NEW AND IMPROVED!
 * Written By: Jonathan Will
 *
 * To add an extension to PX:
 * 1) DB			- Add extensions to the "extensions" database
 * 2) Config Files	- Run command to re-write the config files
 * 3) Asterisk		- Reload asterisk (done automatically when writing config files)
 *
 *
 *
 */

	


	include("config.php");

	

	
	
	

	
	
	
	
	
	// INCLUDE DATABASE CONNECTION
	require_once($basedir."db.inc.php");


	




/** SHOULDNT NEED TO EDIT BELOW HERE (UNLESS YOU ARE A FUCKING BLACKBELT NINJA)***/

	function missingArgs($program){
		die("Missing Parameters. Please specify the extension and host.\nExample: ".$program." 12345 10.100.0.123\n\n");
	}

	function appendFile($file, $str){if(file_put_contents($file, $str, FILE_APPEND) === FALSE){ die("ERROR: Unable to write to '$file'\n");}}
	function startsWith($haystack, $needle){return $needle === "" || strpos($haystack, $needle) === 0;}


	function addDBEntry($exten, $px_sip_pw){

		global $server_id;

		global $sip_password;
		global $iax_password;
		global $iax_host;
		
		list($max_port) = queryROW("SELECT MAX(port_num) FROM extensions WHERE `status`='enabled' AND server_id='$server_id'");

		// NO PORT FOUND FOR SERVER
		if(!$max_port){

			// START AT PORT 5200 FOR NEW SERVERS
			$max_port = 5200;

		// ADD 2 TO MAX
		}else{
			$max_port += 2;
		}

		$dat = array(
			"status"		=> "enabled",
			"server_id"		=> $server_id,
			"number"		=> $exten,
			"port_num"		=> $max_port,
			"station_id"	=> $exten,
				
				"sip_password" => $sip_password,
				"iax_password" => $iax_password,
				"iax_host" => $iax_host,
				
			"register_as"	=> "px-system",
			"register_pass"	=> $px_sip_pw
		);


		aadd($dat, "extensions");


	}

	echo "\nProject X - NEW AND IMPROVED Add extension\nBy: Jonathan Will\n\n";

	
	if($server_id <= 0){
		
		die("ERROR: Server ID File not set (".$server_id_file.")");
	}
	

	if(!isset($argv[1]) || !$argv[1]){

		missingArgs($argv[0]);

	}


	if(!isset($argv[2]) || !$argv[2]){

		missingArgs($argv[0]);

	}


	$exten = intval($argv[1]);
	$iax_host = trim($argv[2]);

	
	if($exten < 100 || $exten > 999999){

		die("ERROR: Extension ($exten) must be between 3 and 6 digits long.\n");

	}



	// CHECK DATABASE FOR EXTENSION EXISTANCE
	list($test) = queryROW("SELECT id FROM extensions WHERE `number`=$exten AND `status`='enabled' AND `server_id`='$server_id'");

	if($test > 0){

		die("ERROR: Extension '$exten' already exists in the database for server ID# $server_id.\n");

	}


	// #6 - DB - add record to the database
	echo "1) Adding to the Database\n";
	addDBEntry($exten, $px_sip_pass);


	// RUN COMMAND TO REGENERATE CONFIG
	echo "2) Regenerating configs and reloading asterisk via '$config_gen_cmd'\n";
	echo `$config_gen_cmd`;

	echo "DONE!\n\n";

