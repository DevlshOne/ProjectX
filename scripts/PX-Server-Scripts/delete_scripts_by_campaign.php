#!/usr/bin/php
<?php

	require_once("/var/www/html/dev/db.inc.php");



	if(!$argv[1]){
		die("Please provide the campaign ID.\nExample: ".$argv[0]." <IDNUMBERHERE>\n");
	}

	$campaign_id = intval($argv[1]);

	if($campaign_id <= 0){
		die("Invalid campaign ID provided.\n");
	}


	// LOOKUP THE SCRIPTS
	$res = query("SELECT * FROM scripts WHERE campaign_id='$campaign_id'", 1 );

	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		// DELETE THE VOICES_FILES FOR THE SCRIPT
		execSQL("DELETE FROM voices_files WHERE script_id='".$row['id']."' ");
		execSQL("DELETE FROM scripts WHERE id='".$row['id']."' ");

	}
