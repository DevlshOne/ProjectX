#!/usr/bin/php
<?php

	require_once("/var/www/html/dev/db.inc.php");




	$old_voice_id = 56;
	$new_voice_id = 137;


	$res = query("SELECT * FROM `names` WHERE voice_id='$old_voice_id'", 1);



	while($row= mysqli_fetch_array($res, MYSQLI_ASSOC)){


		$dat = array();
		foreach($row as $key=>$val){

			if($key == 'id')continue;


			$dat[$key] = $val;


			if($key == 'voice_id'){
				$dat['voice_id'] = $new_voice_id;
			}

		}

		aadd($dat,'names');

	}

