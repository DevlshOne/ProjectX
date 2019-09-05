#!/usr/bin/php
<?php
	$basedir = "/var/www/html/dev/";

	include_once($basedir."db.inc.php");
	include_once($basedir."utils/db_utils.php");


//	$stime = mktime(0,0,0);
//	$etime = $stime + 86399;


	connectPXDB();

	$res = query("SELECT * FROM lead_tracking WHERE `dispo`='PAIDCC'");
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){


		execSQL("UPDATE `sales` SET `is_paid`='yes' WHERE `lead_tracking_id`='".$row['id']."'");


	}


