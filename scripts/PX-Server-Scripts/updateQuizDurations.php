#!/usr/bin/php
<?php

	require_once("/var/www/html/dev/db.inc.php");


	$data = file_get_contents("/root/quiz-sound-file-lengths.txt");


	$lines = preg_split("/\r\n|\n|\r/",$data, -1, PREG_SPLIT_NO_EMPTY);


	foreach($lines as $line){

		$arr = preg_split("/ /", $line, 2, PREG_SPLIT_NO_EMPTY);

		$duration = $arr[0];
                $duration = round($duration, 3);


		//print_r($arr);
		 execSQL("UPDATE quiz_questions SET duration=".addslashes( $duration)." WHERE `file` LIKE '/playback/quiz/".addslashes($arr[1])."'");
	}

