#!/usr/bin/php
<?php
/**
 * FIX USER GROUP OFFICES
 * Written By: Jonathan Will
 *
 *   This script will read the list of groups from "user_groups_master" MASTER LIST,
 *   then check the "user_group_translations" table and/or fix the records where the office isn't set right, for that specific group name.
 *
 */



	$read_only_mode = false;



	$basedir = "/var/www/html/dev/";

	include_once($basedir."db.inc.php");
	include_once($basedir."util/microtime.php");
	include_once($basedir."util/format_phone.php");
	include_once($basedir."util/db_utils.php");



	echo "Starting Repairs at ".date("H:i:s m/d/Y")."...\n";

//exit;

	// CONNECT PX DB
	connectPXDB();


	$res = query("SELECT * FROM user_groups_master", 1);
	$cnt = 0;
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		if($read_only_mode){
			$check_sql = "SELECT user_group_translations.*, users.username FROM `user_group_translations` ".
						" INNER JOIN `users` ON users.id=user_group_translations.user_id ".
						" WHERE user_group_translations.`office` != '".$row['office']."' AND user_group_translations.`group_name` = '".mysqli_real_escape_string($_SESSION['db'], $row['user_group'])."' ";



			$re2 = query($check_sql, 1);

		//	echo $check_sql."\n";

			while($r2 = mysqli_fetch_array($re2, MYSQLI_ASSOC)){



				print_r($r2);

				echo "Office Should be ".$row['office'].' instead of '.$r2['office']."\n";
			}
		}else{

			$cnt += execSQL("UPDATE `user_group_translations` SET `office`='".$row['office']."' WHERE `group_name` = '".mysqli_real_escape_string($_SESSION['db'], $row['user_group'])."' ");

		}

	}

	if($read_only_mode){
		echo "DONE!\n";
	}else{
		echo "DONE, Updated $cnt records\n";
	}
