#!/usr/bin/php
<?php

	$basedir = "/var/www/html/dev/";

	include_once($basedir."db.inc.php");
	include_once($basedir."utils/microtime.php");
	include_once($basedir."utils/format_phone.php");
	include_once($basedir."utils/db_utils.php");


	$stime = mktime(0,0,0);
	$etime = $stime + 86399;



	$res = query("SELECT * FROM activity_log ".
						" WHERE time_started BETWEEN '$stime' AND '$etime' ".
						" AND (`call_group` IS NULL OR `call_group`='') ", 1);


	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){


		$user = querySQL("SELECT * FROM `users` WHERE username='".addslashes($row['username'])."' AND `enabled`='yes' ");



		echo "Setting User ".$user['username']." to group ".$user['user_group']."\n";



		execSQL("UPDATE `activity_log` SET call_group='".addslashes($user['user_group'])."' WHERE id='".$row['id']."' ");



	}


