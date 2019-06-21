#!/usr/bin/php
<?php

	require_once("/var/www/html/dev/db.inc.php");



	$copy_campaign = 64;

	$voice_id = 44;
	$new_voice_id = 72;


	$res = query("SELECT * FROM scripts WHERE campaign_id='$copy_campaign' AND voice_id='$voice_id'", 1);



	while($row= mysqli_fetch_array($res, MYSQLI_ASSOC)){

		$dat = array();
		foreach($row as $key=>$val){

			if($key == 'id')continue;


			$dat[$key] = $val;


			if($key == 'voice_id'){
				$dat['voice_id'] = $new_voice_id;
			}

		}

		aadd($dat,'scripts');

	}

