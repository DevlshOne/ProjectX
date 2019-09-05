#!/usr/bin/php
<?php

	require_once("/var/www/html/dev/db.inc.php");




    $campaign_id = 31;

    $new_voice_id = 13;


    $new_path = "/playback/voice-".$new_voice_id."/";

    // MAKE DIR IF NOT EXISTS
    if(!is_dir($new_path)){

    	$cmd = "mkdir $new_path ; chown www-data.www-data $new_path";

    	echo `$cmd`;

    }


    ## GET ALL SCRIPTS
    $res = query("SELECT * FROM scripts WHERE campaign_id='$campaign_id' ", 1);


    while($script = mysqli_fetch_array($res, MYSQLI_ASSOC)){


		## FIX VOICE_ID FIELD
	    $dat = array();
	    $dat['voice_id'] = $new_voice_id;
	    aedit($script['id'], $dat, 'scripts');

	    ## GET ALL VOICES_FILES
		$re2 = query("SELECT * FROM voices_files WHERE script_id='".$script['id']."' ", 1);

		while($file = mysqli_fetch_array($re2, MYSQLI_ASSOC)){

			## FORCE IT TO BE THE CORRECT ID
			$dat = array();
			$dat['voice_id'] = $new_voice_id;
			aedit($file['id'], $dat, 'voices_files');


	    	### MOVE FILES TO CORRECT VOICE FOLDER, IF THEY EXIST
	    	if(file_exists($file['file'])){

	    		$parts = pathinfo($file['file']);

	    		$newname = $new_path.$parts['basename'];


	    		if(!rename($file['file'], $newname)){

	    			echo "ERROR: Couldn't move file '".$file['file']."' to '".$newname."'\n";
	    			continue;
	    		}

	    		$dat = array();
	    		$dat['file'] = $newname;
	    		aedit($file['id'], $dat, 'voices_files');

	    	}


		} // END WHILE(voice files)

    }// END WHILE (scripts)










