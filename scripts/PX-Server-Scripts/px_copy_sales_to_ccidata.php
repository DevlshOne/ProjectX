#!/usr/bin/php
<?php
	$basedir = "/var/www/dev/html/";

	include_once($basedir."db.inc.php");
	include_once($basedir."util/microtime.php");
	include_once($basedir."util/format_phone.php");
	include_once($basedir."util/db_utils.php");



	$stime = mktime(0,0,0);
	$etime = mktime(23,59,59);


	echo "Starting Sales Copy for ".date("m/d/Y",$stime)."...\n";


	// CONNECT PX DB
	connectPXDB();

	$res = query("SELECT * FROM sales ".
				" WHERE `sale_time` BETWEEN '$stime' AND '$etime' ".
				"".
				"");

	$rowarr = array();
	while($row=mysqli_fetch_array($res, MYSQLI_ASSOC)){
		$rowarr[] = $row;
	}


	$res = query("SELECT id, ip_address FROM vici_clusters ",1);
	$clusters = array();
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		$clusters[$row['id']] = $row['ip_address'];

	}



	echo "Grabbed ".count($rowarr)." sales, pushing to ccidata.leads...\n";


	// CONNECT TO ANDREWS LABYRINTH
	connectCCIDB();

	// SHOVE DATA INTO THE DB
	// rEPLACE INTO

	$start_sql = "REPLACE INTO `leads` (`lead_id`,`phone`,`agent_id`,`agent_name`,`sales_date`,`sales_time`,".
					"`last_name`,`first_name`,`contact`,`address1`,`address2`,`city`,`state`,`zip`,`campaign`,`list_id`,".
					"`sale_amount`,`verifier`,`office`,`call_group`,`server`) VALUES ";


	foreach($rowarr as $row){


		if($row['vici_last_call_time'] && $row['vici_last_call_time'] != null){

			list($date, $time) = preg_split("/\s/", $row['vici_last_call_time']);
			$date = date("m/d/Y", strtotime($date) );

		}else{

			$date = date("m/d/Y", $row['sale_time']);

			$time = date("H:i:s", $row['sale_time']);

		}


		$sql = $start_sql;

		$sql .= "('".addslashes($row['agent_lead_id'])."',".
				"'".addslashes($row['phone'])."',".
				"'".addslashes(strtoupper($row['agent_username']))."',".
				"'".addslashes(strtoupper($row['agent_name']))."',".
				"'".addslashes($date)."',".
				"'".addslashes($time)."',".
				"'".addslashes(strtoupper($row['last_name']))."',".
				"'".addslashes(strtoupper($row['first_name']))."',".
				"'".addslashes(strtoupper($row['first_name']))."',".
				"'".addslashes(strtoupper($row['address1']))."',".
				"'".addslashes(strtoupper($row['address2']))."',".
				"'".addslashes(strtoupper($row['city']))."',".
				"'".addslashes(strtoupper($row['state']))."',".
				"'".addslashes($row['zip'])."',".
				"'".addslashes(strtoupper($row['campaign']))."',".
				"'".addslashes(strtoupper($row['campaign_code']))."',".
				"'".addslashes($row['amount'])."',".
				"'".addslashes(strtoupper($row['verifier_username']))."',".
				"'".(($row['office'])?addslashes(strtoupper($row['office'])) : "90")."',".
				"'".addslashes(strtoupper($row['call_group']))."',".
				"'".addslashes($clusters[$row['agent_cluster_id']])."')";



		execSQL($sql);

		//echo $sql."\n";
	}


	echo "Done.\n";


