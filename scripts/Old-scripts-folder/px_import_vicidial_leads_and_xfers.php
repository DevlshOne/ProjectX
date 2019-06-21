#!/usr/bin/php
<?php
	$basedir = "/var/www/html/dev2/";

	include_once($basedir."db.inc.php");
	include_once($basedir."util/microtime.php");
	include_once($basedir."util/format_phone.php");
	include_once($basedir."util/db_utils.php");


	$vici_cluster_id = 3; // TAPS VICI DATABASE - based on PX DB - "vici_clusters" table.


	$stime = mktime(0,0,0, date("m"), date("j"), date("Y") );
	$etime = $stime + 86399;


	$campaign_arr = array();

	connectPXDB();

	$res = query("SELECT * FROM campaigns WHERE `status`='ACTIVE'", 1);
	while($row = mysql_fetch_array($res, MYSQL_ASSOC)){

		$campaign_arr[$row['vici_campaign_id']] = $row['id'];
	}




	$dbidx = getClusterIndex($vici_cluster_id);

	connectViciDB($dbidx);




	$res = query("SELECT * FROM vicidial_list ".
			" WHERE `last_local_call_time` >= '".date("Y-m-d", $stime)." 00:00:00' AND `last_local_call_time` < '".date("Y-m-d", $etime+1)." 00:00:00' ".
			" AND `user` != 'VDAD' ".
			" ORDER BY `last_local_call_time` ASC", 1);


	while($row = mysql_fetch_array($res, MYSQL_ASSOC)){

		// WE HAVE TODAYS LEADS SO FAR

		if(intval($row['entry_list_id']) <= 0){
			echo "Skipping Lead #".$row['lead_id'].", entry list is invalid: '".$row['entry_list_id']."'\n";
			continue;
		}


		// CHECK THE XFER LOG TO SEE IF IT GOT TRANSFERRED TO VERIFIERS
		$xfer = querySQL("SELECT * FROM `vicidial_xfer_log` WHERE `lead_id`='".$row['lead_id']."'");


		// LOOK UP RECORDINGS (for duration)
		$recording = querySQL("SELECT * FROM recording_log WHERE `lead_id`='".$row['lead_id']."' AND user='".$row['user']."' ORDER BY recording_id DESC LIMIT 1");


		// PULL THE CUSTOM FIELDS DATA, TO GET CAMPAIGN
		$custom = querySQL("SELECT * FROM `custom_".$row['entry_list_id']."` WHERE `lead_id`='".$row['lead_id']."'");

		// DETERMINE WHICH CAMPAIGN ID IT IS



		// ADD LEAD TRACKING RECORD
		$dat = array();
		$dat['campaign_id'] = $campaign_arr[$custom['campaign']];

		$dat['extension'] = 0;
		$dat['px_server_id'] = 0;
		$dat['vici_cluster_id'] = $vici_cluster_id;

		$dat['lead_id'] = $row['lead_id'];

		//$dat['call_id'] = '';
		//$dat['verifier_call_id'] = '';
		//$dat['recording_id'] = 0;

		$dat['list_id'] = $row['entry_list_id'];

		$dat['user_id'] = 0;
		$dat['verifier_id'] = 0;

		$dat['time'] = strtotime($row['last_local_call_time']);


		if(intval($xfer['xfercallid']) > 0){


			$dat['verifier_vici_cluster_id'] = $vici_cluster_id;
			$dat['verifier_lead_id'] = $row['lead_id'];
		}


	}








