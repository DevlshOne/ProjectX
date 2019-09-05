#!/usr/bin/php
<?php

	// NOT UPDATED TO MYSQLI/PHP7 due to lack of use/hasn't been needed in years


	$campaign_id = 4;
	$voice_id = 15;


	// PX DEV DB INFO
	$pxdev_db_host		= "10.10.0.64";
	$pxdev_db_port		= "3306";
	$pxdev_db_user  	= "projectxdb";
	$pxdev_db_pass   	= "hYGjWDAX4LFmdR4C";
	$pxdev_dbname 		= "projectx";


	// PRODUCTION
	$px_db_host		="10.100.0.65";
	$px_db_port		="3306";
	$px_db_user		="projectxdb";
	$px_db_pass		="hYGjWDAX4LFmdR4C";
	$px_dbname		="projectx";

	// INCLUDE DATABASE FUNCTIONS
	include("/var/www/scripts/vici_db.inc.php");



	# CONNECT TO PX DEV DB
    $db	= $_SESSION['pxdev'] = mysql_connect(
    							$pxdev_db_host.':'.$pxdev_db_port,
    							$pxdev_db_user,
    							$pxdev_db_pass
    						) or die(mysql_error()."Connection to PX-DEV Failed.");

	mysql_select_db($pxdev_dbname, $db) or die("Could not select px-database ".$pxdev_dbname);




	echo "Connected to PX-DEV @ ".$pxdev_db_host.':'.$pxdev_db_port."\n";

	// GRAB THE CAMPAIGN
	$campaign = querySQL("SELECT * FROM campaigns WHERE id='$campaign_id'");


	// GRAB THE VOICE
	$voice = querySQL("SELECT * FROM voices WHERE id='$voice_id'");


	// EXTRACT THE SCRIPTS
	$script_arr = array();
	$res = query("SELECT * FROM scripts WHERE campaign_id='$campaign_id' AND voice_id='$voice_id'", 1);
	$x=0;
	while($row = mysql_fetch_array($res, MYSQL_ASSOC)){

		$script_arr[$x] = $row;



		// EXTRACT THE VOICE FILES FOR TEH SCIRPTS
		$re2 = query("SELECT * FROM voices_files WHERE script_id='".$row['id']."' AND voice_id='".$voice_id."'  ", 1);
		while($r2 = mysql_fetch_array($re2, MYSQL_ASSOC)){
			$script_arr[$x]['voices_files'][] = $r2;
		}

		$x++;
	}


	// DISCONNECT FROM PX-DEV
	mysql_close($db);
	unset($_SESSION['pxdev']);


	// CONNECT TO PROJECT X DATABASE AND SYNC/ADD DATA
    $db	= $_SESSION['pxdb'] = mysql_connect(
    							$px_db_host.':'.$px_db_port,
    							$px_db_user,
    							$px_db_pass
    						) or die(mysql_error()."Connection to PX-DB Failed.");

	mysql_select_db($px_dbname, $db) or die("Could not select px-database ".$px_dbname);


	echo "Connected to PX database @ ".$px_db_host.':'.$px_db_port."\n";


	// CHECK IF VOICE EXISTS ALREADY
	// IF SO, REMOVE IT,
	// ADD THE VOICE


	// CHECK IF TEH CAMPAIGN EXISTS ARLEADY
	// IF SO, REMOVE IT,
	// ADD THE CAMPAIGN

	// GET THE SCRIPTS
	$res = query("SELECT * FROM scripts WHERE voice_id='$voice_id' AND campaign_id='$campaign_id'", 1);

	while($row = mysql_fetch_array($res, MYSQL_ASSOC)){

		// DELETE TEH VOICE_FILES
		execSQL("DELETE FROM voices_files WHERE script_id='".$row['id']."' AND voice_id='$voice_id'");
	}

	// DELETE ALL EXISTING SCRIPTS
	execSQL("DELETE FROM scripts WHERE voice_id='$voice_id' AND campaign_id='$campaign_id'");


	// ADD TEH SCRIPTS/VOICE FILES
	foreach($script_arr as $idx => $script){

		foreach($script['voices_files'] as $vidx=>$voices_file){

			aadd($voices_file, "voices_files");

		}

		unset($script['voices_files']);

		aadd($script, "scripts");
	}



	// CLOSE THE DB CONNECTION
	mysql_close();



