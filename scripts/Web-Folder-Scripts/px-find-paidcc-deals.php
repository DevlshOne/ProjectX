#!/usr/bin/php
<?php
	$basedir = "/var/www/dev/";

	include_once($basedir."db.inc.php");
	include_once($basedir."util/db_utils.php");




	$source_file = "/root/dripp_phones/phones.csv";

	$data = file_get_contents($source_file);


	$arr = preg_split("/\r\n|\r|\n/", $data, -1, PREG_SPLIT_NO_EMPTY);


	foreach($arr as $phonenum){


		$row = querySQL("SELECT * FROM sales WHERE `phone` = '".addslashes($phonenum)."'");

		if($row['is_paid'] != 'yes'){
			echo "Lead tracking #".$row['lead_tracking_id']." NOT MARKED IS_PAID=YES\n";

			execSQL("UPDATE `sales` SET is_paid='yes' WHERE id='".$row['id']."'");
			execSQL("UPDATE `lead_tracking` SET dispo='PAIDCC' WHERE id='".$row['lead_tracking_id']."'");

		}

	}

