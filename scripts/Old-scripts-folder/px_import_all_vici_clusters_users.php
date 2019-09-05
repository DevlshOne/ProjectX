#!/usr/bin/php
<?php
	$basedir = "/var/www/dev/";

	include_once($basedir."db.inc.php");
	include_once($basedir."util/microtime.php");
	include_once($basedir."util/format_phone.php");
	include_once($basedir."util/db_utils.php");



	$stime = mktime(0,0,0);
	$etime = mktime(23,59,59);


	echo "Starting Bulk Vici User Import script on ".date("m/d/Y",$stime)."...\n";


	// CONNECT PX DB
	connectPXDB();


	$res = query("SELECT * FROM vici_clusters ",1);
	$clusters = array();
	while($row = mysql_fetch_array($res, MYSQL_ASSOC)){

		$clusters[$row['id']] = $row;

	}


	// LOOP THROUGH STACK OF VICIDIAL SERVERS
	foreach($clusters as $cluster_id => $vicirow ){

		// LOCATE WHICH DB INDEX IT IS
		$dbidx = getClusterIndex($cluster_id);

		// CONNECT TO VICIDIAL DB
		connectViciDB($dbidx);



	}



