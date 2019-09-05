#!/usr/bin/php
<?php

	require_once("/var/www/html/dev/db.inc.php");




	$copy_from = 161;

	$copy_to = 189;
	$new_voice_id = 165;


	$force_voice_id = -1; // -1 to skip, specify to only do a specific voice

	$include_files = true;

	$separate_files = true;

	$res = query("SELECT * FROM scripts WHERE campaign_id='$copy_from' ".
			(($force_voice_id > -1)?" AND voice_id='$force_voice_id' ":'')
			, 1);



	while($row= mysqli_fetch_array($res, MYSQLI_ASSOC)){

		$re2 = query("SELECT * FROM voices_files WHERE script_id='".$row['id']."'",1);

		$dat = array();
		foreach($row as $key=>$val){

			if($key == 'id')continue;


			$dat[$key] = $val;


			if($key == 'campaign_id'){
				$dat['campaign_id'] = $copy_to;
			}

			if($key == 'voice_id'){
				$dat['voice_id'] = $new_voice_id;
			}

		}

		aadd($dat,'scripts');

		$script_id = mysqli_insert_id($_SESSION['db']);


		// MAKE THE VOICE FOLDER
		$newdir = "/playback/voice-".$new_voice_id.'/';


		if(!is_dir($newdir)){
                	mkdir($newdir);
                }

		// ATTEMPT TO CHANGE OWNERS
		$cmd = "chown www-data $newdir";
		echo `$cmd`;



		if($include_files){


			while($r2 = mysqli_fetch_array($re2, MYSQLI_ASSOC)){
				$dat = array();

				// SEPARATE THE FILEZ?
				if($separate_files){

					$path_parts = pathinfo($r2['file']);

					$newdir = "/playback/voice-".$new_voice_id.'/';


					if(!is_dir($newdir)){
						mkdir($newdir);
					}

					// ATTEMPT TO CHANGE OWNERS
					$cmd = "chown www-data $newdir";
					echo `$cmd`;


	                $newfile = $newdir.$path_parts['basename'];

					if(!copy($r2['file'], $newfile)){
						die("Error: failed to copy ".$r2['file']." to ".$newfile."\n");
					}else{
						$r2['file'] = $newfile;
					}
				} // END SEPARATE FILES IF



				foreach($r2 as $key=>$val){

					if($key == 'id')continue;


					$dat[$key] = $val;

					if($key == 'script_id'){
						$dat['script_id'] = $script_id;
					}

					if($key == 'voice_id'){
						$dat['voice_id'] = $new_voice_id;
					}

				}

				aadd($dat, 'voices_files');

			} // END WHILE

		} // END IF



	} // END WHILE

