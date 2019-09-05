#!/usr/bin/php
<?php

	// INCLUDE DATABASE CONNECTION
	require_once("/var/www/html/dev/db.inc.php");

/**
 * PX Add extension
 * Written By: Jonathan Will
 *
 * To add an extension to PX:
 * 1) sip-px.conf	- Add zoiper/grandstream users
 * 2) iax-px.conf	- Add phone users
 * 3) iax.conf		- Register line to vici
 * 4) extensions.conf-Add the extensions dialplan
 * 5) meetme.conf	- Add the conference room
 * 6) DB			- Add extensions to the database
 * 7) Asterisk		- Reload asterisk
 *
 *
 */
 	global $config_dir;
	global $meetme_prefix;
	global $server_id;

	$server_id = 8; // CHANGE AS NECESSARY

	$config_dir = "/etc/asterisk/"; // change this if you want to test it first in another dir

	$sip_password = "aakeeG4eeh4moge8";
	$iax_password = "LfIetSBrW70I1ZD";
	$px_sip_pass = "t1g3rstyl3";	// THE PASSWORD PX LINPHONE USES TO REGISTER (as px-system user)



	$meetme_prefix = "1024";



/** SHOULDNT NEED TO EDIT BELOW HERE (UNLESS YOU ARE A FUCKING BLACKBELT NINJA)***/

	function missingArgs($program){
		die("Missing Parameters. Please specify the extension and host.\nExample: ".$program." 12345 10.100.0.123\n\n");
	}

	function appendFile($file, $str){if(file_put_contents($file, $str, FILE_APPEND) === FALSE){ die("ERROR: Unable to write to '$file'\n");}}
	function startsWith($haystack, $needle){return $needle === "" || strpos($haystack, $needle) === 0;}


	function addDBEntry($exten, $px_sip_pw){

		global $server_id;


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
			"register_as"	=> "px-system",
			"register_pass"	=> $px_sip_pw
		);


		aadd($dat, "extensions");


	}

	function makeExtensionConfig($exten){
		global $meetme_prefix;

		$out = "[px-".$exten."]\n".
				"include => px-volume-control\n".
				"exten => s,1,Answer()\n".
				"exten => s,n,SET(AGC(rx)=20000)\n".
				"exten => s,n,Meetme(".$meetme_prefix.$exten.",q)\n".
				"exten => s,n,Hangup\n\n";

		return $out;
	}

	function makeIAXConfig($exten, $password){

		$out = "[".$exten."]\n".
				"type=user\n".
				"username=".$exten."\n".
				"secret=".$password."\n".
				"host=dynamic\n".
				"callbackextension=".$exten."\n".
				"insecure=port,invite\n".
				"context=px-vici\n".
				"peercontext=px-vici\n".
				"qualify=yes\n".
				"disallow=all\n".
				"allow=ulaw\n".
				"canreinvite=no\n\n";
		return $out;
	}

	function makeSipConfig($exten, $password){

		$out = "[".$exten."]\n".
				"type=friend\n".
				"host=dynamic\n".
				"defaultuser=".$exten."\n".
				"secret=".$password."\n".
				"accountcode=".$exten."\n".
				"callerid=\"\" <".$exten.">\n".
				"mailbox=".$exten."\n".
				"context=px-sip\n".
				"qualify=yes\n\n";

		return $out;
	}



	function addIAXRegister($exten, $password, $host){
		global $config_dir;

		$regline = "register => ".$exten.":".$password."@".$host."\n";

		$iaxdata = file_get_contents($config_dir."iax.conf");

		// FIND THE [general] TAG
		$linearr = preg_split("/\r\n|\n/", $iaxdata);

		$out = "";

		foreach($linearr as $line){

			if(startsWith($line, "[general]")){

				// FOUND GENERAL TAG,
				$out .= $line."\n";

				// INJECT THE REG-LINE TO THE NEXT LINE
				$out .= $regline;

			}else{
				// KEEP APPENDING
				$out .= $line."\n";
			}
		}


		if(!file_put_contents($config_dir."iax.conf", $out)){
			die("ERROR: Unable to write the IAX register to '".$config_dir."iax.conf'\n");
		}


	}



	echo "\nProject X - Add extension\nBy: Jonathan Will\n\n";


	if(!isset($argv[1]) || !$argv[1]){

		missingArgs($argv[0]);

	}


	if(!isset($argv[2]) || !$argv[2]){

		missingArgs($argv[0]);

	}


	$exten = intval($argv[1]);
	$host = trim($argv[2]);

	if($exten < 100 || $exten > 99999){

		die("ERROR: Extension must be between 3 and 5 digits long.\n");

	}



	// CHECK DATABASE FOR EXTENSION EXISTANCE
	list($test) = queryROW("SELECT id FROM extensions WHERE `number`=$exten AND status='enabled' AND server_id='$server_id'");

	if($test > 0){

		die("ERROR: Extension '$exten' already exists in the database.\n");

	}





	// #1 - SIP config
	echo "1) Adding SIP config\n";
	appendFile($config_dir."sip-px.conf", makeSipConfig($exten, $sip_password) );

	// #2 - IAX config
	echo "2) Adding IAX config\n";
	appendFile($config_dir."iax-px.conf", makeIAXConfig($exten, $iax_password) );

	// #3 - iax.conf - REGISTER VICI PHONE
	echo "3) Adding VICI IAX register\n";
	addIAXRegister($exten, $iax_password, $host);

	// #4 - extensions.conf - ADD DIALPLAN

	// SHOULDNT NEED EXTENSIONS CHANGES NOW
	echo "4) Adding Extension config -- SKIPPED - no longer needed\n";
//	appendFile($config_dir."extensions-px.conf", makeExtensionConfig($exten) );

	// #5 - meetme.conf
	echo "5) Adding MeetMe config\n";
	appendFile($config_dir."meetme.conf", "conf => ".$meetme_prefix.$exten."\n");


	// #6 - DB - add record to the database
	echo "6) Adding to the Database\n";
	addDBEntry($exten, $px_sip_pass);


	// RELOAD ASTERISK
	$cmd = "asterisk -rx reload";
	echo "7) Reloading Asterisk via '$cmd'\n";
	echo `$cmd`;

	echo "DONE!\n\n";

