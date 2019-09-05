#!/usr/bin/php
<?php
	session_start();

	$basedir = "/var/www/dev/";



	include_once($basedir."site_config.php");
	include_once($basedir."db.inc.php");

	include_once($basedir."utils/db_utils.php");


	$stime = mktime (0,0,0);
	$etime = $stime + 86399;



	connectPXDB();
	
	$sql = "UPDATE projectx.sales s ".
		"INNER JOIN ".
		"projectx.users u ON s.agent_username = u.username ".
		"SET s.office = u.office ".
		"WHERE s.office = '' ".
		"AND s.sale_time BETWEEN ".$stime." AND ".$etime;

	echo "Fixing Offices for sales for ".date("m/d/Y", $stime)."\n";

	$cnt = execSQL($sql);

	echo "Updated $cnt records\n";
//	echo $sql."\n";
//	echo $stime.", ".$etime."\n";

