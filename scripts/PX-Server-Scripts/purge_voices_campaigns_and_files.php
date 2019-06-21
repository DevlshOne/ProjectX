#!/usr/bin/php
<?php

	require_once("/var/www/html/dev/db.inc.php");


/**
 * SELECT `campaigns`.id,`campaigns`.`name`,`voices`.id,`voices`.`name` FROM `campaigns`
 * LEFT JOIN `voices` ON voices.campaign_id=campaigns.id
 * WHERE `voices`.id > 0
 * order by `campaigns`.name ASC, voices.id ASC
 */



	//$camp_array = array(1,2,43,45,72,77,94,99,103,105,109,111,115,117,119,125,127,129,131,133,141,147,149,153,177,195,249,251);

	// VOICES TO PURGE
	$voice_arr = array(


		68,109,169,66,115,125,129,139,127,111,86,13,33,72,11,50,133,29,46,82,41,60,35,43,



		141,	 // SBV (Chris Dozier)

		7,87, 		// BCRSF
		6, 			// NPTA
		25, 		// IUPA COLD

		27, 161, 	// FFCF

		52, 		// BCSF
		56, 		// ADVF
		78,80,		// USFA
		89,			// BCAF
		93,			// FSF
		95,			// UBCF

		97, 135,	// NEW NPTA

		99,			// USVF

		103,		// AVF

		105,		// NEW ADVF
		107,		// NEW BCAF

		113,121,	// NEW BCRSF (warm too)

		117,		// HTV
		119,		// BCRSF3
		123,		// BCRSF RENEWALS

		131,137,	// SBV
		215,225,	// SBV2

		157,		// UNAVP
		171,		// UNAVP2





	);


	if(!is_dir("/playback/trash/names")){

		echo `mkdir /playback/trash/names`;

	}


	foreach($voice_arr as $voice_id){

		$voice_id = intval($voice_id);

		list($campaign_id) = queryROW("SELECT campaign_id FROM `voices` WHERE id='$voice_id'");


		echo "VOICE #$voice_id : Purging scripts and voices_files...";


	    // LOOKUP THE SCRIPTS
		$res = query("SELECT * FROM scripts WHERE voice_id='$voice_id'", 1 );

		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			// DELETE THE VOICES_FILES FOR THE SCRIPT
			execSQL("DELETE FROM voices_files WHERE script_id='".$row['id']."' ");
			execSQL("DELETE FROM scripts WHERE id='".$row['id']."' ");

		}




		$pb_folder = "/playback/voice-".intval($voice_id);
		$name_folder="/playback/names/voice-".intval($voice_id);

		echo "\nChecking for Scripts folder: ".$pb_folder."... ";

		// MOVE PLAYBACK FOLDER IF ITS FOUND
		if(is_dir($pb_folder)){

			echo "Found, moving to trash.\n";

			$cmd = "mv ".escapeshellarg($pb_folder)." /playback/trash/";
			echo `$cmd`;

		}else{
			echo "Not found.\n";
		}




//		echo "Checking for Names folder: ".$name_folder."... ";
//
//		// MOVE NAMES FOLDER IF ITS FOUND
//		if(is_dir($name_folder)){
//
//			echo "Found, moving to trash.\n";
//
//			$cmd = "mv ".escapeshellarg($name_folder)." /playback/trash/names/";
//			echo `$cmd`;
////			echo $cmd."\n";
//
//		}else{
//
//			echo "Not found.\n";
//		}


		echo "Deleting voice #".$voice_id." from db.\n";
		execSQL("DELETE FROM voices WHERE id='$voice_id' ");


		if($campaign_id > 0){

			echo "Checking if campaign has any voices left: ";


			list($cnt) = queryROW("SELECT COUNT(*) FROM `voices` WHERE campaign_id='$campaign_id'");

			if($cnt > 0){
				echo "Yes, skip the campaign delete.\n";
			}else{
				echo "No, removing Campaign ID# $campaign_id\n";

				execSQL("DELETE FROM campaigns WHERE id='$campaign_id' ");

			}
		}

		echo "\n";


	}
