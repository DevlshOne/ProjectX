#!/usr/bin/php
<?php

    require_once("/var/www/html/dev/db.inc.php");

	$duration_script = "/ProjectX-Server/scripts/parseDuration.sh";


	$res = query("SELECT * FROM voices_files ",1);

	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		// SKIP NONEXISTANT FILES
		if(!file_exists($row['file'])){
			echo "File: ".$row['file']." does not exist, skipping.\n";
			continue;
		}

		$cmd = $duration_script.' '.escapeshellarg($row['file']);

//echo $cmd."\n";
//echo `$cmd`;
//continue;

		$output = `$cmd`;

		//echo $output;
		/**
		 *  1.300250 /playback/names/voice-7/caron.wav
			1.277125 /playback/names/voice-7/carrie.wav

		 */
		$arr = preg_split("/ /", $output, 2);

		$duration = $arr[0];

		if(!trim($duration)){

			echo "Invalid duration, command returned: ".$output."\n";
			$duration = "0.0";
		}


		$duration = round($duration, 3);

		//echo $duration.' '.$row['file']."\n";
		//continue;


		execSQL("UPDATE voices_files SET duration='".addslashes($duration)."' WHERE id='".$row['id']."' ");

	}


