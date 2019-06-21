#!/usr/bin/php
<?php

	require_once("/var/www/html/dev/db.inc.php");



    if(!$argv[1]){
		die("Please provide the voice ID.\nExample: ".$argv[0]." <IDNUMBERHERE>\n");
    }

    $voice_id = intval($argv[1]);

    if($voice_id <= 0){
		die("Invalid voice ID provided.\n");
    }


    // LOOKUP THE SCRIPTS

	$res = query("SELECT * FROM scripts WHERE voice_id='$voice_id'", 1 );

	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		// DELETE THE VOICES_FILES FOR THE SCRIPT
		execSQL("DELETE FROM voices_files WHERE script_id='".$row['id']."' ");
		execSQL("DELETE FROM scripts WHERE id='".$row['id']."' ");

	}
