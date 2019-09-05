#!/usr/bin/php
<?php
/* PX - Cleanup Playback folder - Deletes/removes files that do not have a PX db reference for them
 * Written By: Jonathan Will
 * Created on March 2, 2018
 */

	$playback_folder = "/playback/";

	$backup_location = "/var/backups/playback_backups/";


	$basedir = "/var/www/dev/";

	include_once($basedir."db.inc.php");
	include_once($basedir."dbapi/dbapi.inc.php");
	include_once($basedir."utils/db_utils.php");


	connectPXDB();



	// GET ALL ACTIVE VOICES
	$res = query("SELECT * FROM voices WHERE status='enabled' ", 1);

	$missing_stack = array();

	echo "Gathing a list of orphaned files...\n";

	echo "Scanning Voice ";

	while($voice = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		echo "#".$voice['id']." ";

		$path = $playback_folder.'voice-'.$voice['id'].'/';

		// LIST CONTENTS OF THAT VOICES FOLDER
		$files = scandir($path);

		foreach($files as $fname){

			// SKIPP ALL FILES THAT START WITH PERIOD
			if($fname[0] == '.')continue;

			$full_filename = $path.$fname;

			//echo $full_filename."\n";
			//echo $fname."\n";

			// COMPARE WITH DB
			list($test) = queryROW("SELECT id FROM voices_files WHERE `file` LIKE '".mysqli_real_escape_string($_SESSION['db'],$full_filename)."'");

			if(!$test){
				// MAKE A LIST OF THE ONES THAT ARE MISSING DB RECORDS
				$missing_stack[] = $full_filename;
			}

		}

	}



	echo "\nPreparing to cleanup ".count($missing_stack)." files...\n";


//	print_r($missing_stack);


	// MAKE A DB BACKUP BEFORE YOU DO ANYTHING YOU MAY LATER REGRET
	$backup_filename = $backup_location.'/playback-backup-'.date("H-i-s_m-d-Y").'.tar.gz';

	$cmd = "tar -cz ".escapeshellarg($playback_folder)." > ".escapeshellarg($backup_filename)." ";

	echo $cmd."\n";
	echo `$cmd`;

	// DO NAUGHTY THINGS TO THEM

	foreach($missing_stack as $purgeme){


		echo "Deleting file: ".$purgeme."\n";
		unlink($purgeme);

	}
