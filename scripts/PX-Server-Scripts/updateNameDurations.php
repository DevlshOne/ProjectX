#!/usr/bin/php
<?php

    require_once("/var/www/html/dev/db.inc.php");

	$duration_script = "/ProjectX-Server/scripts/parseDuration.sh";


	$res = query(
			"SELECT `names`.* FROM `names` ".
			"INNER JOIN `voices` ON `voices`.id=`names`.voice_id ".
			" WHERE `voices`.`status`='enabled'",1);

	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		$cmd = $duration_script.' "'.$row['filename'].'"';

		$output = `$cmd`;

		//echo $output;
		/**
		 *  1.300250 /playback/names/voice-7/caron.wav
			1.277125 /playback/names/voice-7/carrie.wav

		 */
		$arr = preg_split("/ /", $output, 2);

		echo "Setting Duration ".addslashes($arr[0])." for ".$row['filename']."\n";

		execSQL("UPDATE names SET duration='".addslashes($arr[0])."' WHERE id='".$row['id']."' ");

	}


