#!/usr/bin/php
<?

	$basedir = "/var/www/ringreport/dev/";

	include_once($basedir."db.inc.php");
	include_once($basedir."dbapi/dbapi.inc.php");
	include_once($basedir."util/microtime.php");
	include_once($basedir."util/format_phone.php");
	include_once($basedir."util/functions.php");
	include_once($basedir."util/db_utils.php");

	$stime = mktime(0,0,0) ;
	$etime = $stime + 86399;

// CONNECT PX DB
	connectPXDB();

	$res = query("SELECT * FROM transfers ".
			" WHERE verifier_dispo='SALE' ".
			" AND `sale_time` BETWEEN '$stime' AND '$etime'", 1);

	$cnt = 0;
	while($xfer = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		// LOOK FOR THE SALE RECORD


		list($test) = queryROW("SELECT id FROM sales WHERE lead_tracking_id='".$xfer['lead_tracking_id']."'");


		if(!$test){
			$cnt++;
			echo "MISSING SALE FOR TRANSFER #".$xfer['id'].' Lead tracking #'.$xfer['lead_tracking_id']."\n";


			// LOAD THE LEAD RECORD
			$row = querySQL("SELECT * FROM lead_tracking WHERE id='".$xfer['lead_tracking_id']."' ");

			$agent_user = getUserByID($row['user_id']);
			$verifier_user = getUserByID($row['verifier_id']);



			$dbidx = getClusterIndex($row['vici_cluster_id']);
			connectViciDB($dbidx);

			// LOOK UP LAST CALL TIME
			list($calltime) = queryROW("SELECT CAST(last_local_call_time AS char) as last_local_call_time FROM vicidial_list ".
								" WHERE lead_id='".$row['lead_id']."'");

			// CONNECT BACK TO PX DB
			connectPXDB();


			// CREATE SALE RECORD
			$dat = array();
			$dat['lead_tracking_id'] = $xfer['lead_tracking_id'];
			$dat['transfer_id'] = $xfer['id'];
			$dat['agent_lead_id'] = $row['lead_id'];
			$dat['agent_cluster_id'] = $row['vici_cluster_id'];
			$dat['verifier_lead_id'] = $row['verifier_lead_id'];
			$dat['verifier_cluster_id'] = $row['verifier_vici_cluster_id'];
			$dat['campaign_id'] = $row['campaign_id'];


			$dat['sale_time'] = $xfer['sale_time'];

			$dat['vici_last_call_time'] = $calltime;


			// sale_datetime not really used atm
			$dat['phone'] = $row['phone_num'];

			$dat['agent_username'] = $agent_user['username'];
			$dat['agent_name'] = $agent_user['first_name'].(($agent_user['last_name'])?' '.$agent_user['last_name']:'');

			$dat['verifier_username'] = $verifier_user['username'];
			$dat['verifier_name'] = $verifier_user['first_name'].(($verifier_user['last_name'])?' '.$verifier_user['last_name']:'');



			$dat['first_name'] = $row['first_name'];
			$dat['last_name'] = $row['last_name'];
			$dat['contact'] = ($row['contact'])?$row['contact']:$row['first_name'];


			$dat['address1'] = $row['address1'];
			$dat['address2'] = $row['address2'];
			$dat['city'] = $row['city'];
			$dat['state'] = $row['state'];
			$dat['zip'] = $row['zip_code'];
			$dat['campaign'] = $row['campaign'];
			$dat['campaign_code'] = $row['campaign_code'];
			$dat['amount'] = $xfer['verifier_amount'];
			$dat['office'] = $agent_user['office'];


			//$dat['call_group'] = $agent_user['user_group'];

			$dat['call_group'] = lookupUserGroup($agent_user['id'], $row['vici_cluster_id']);

			$dat['comments'] = $row['comments'];

			//$dat['server_ip'] = $row['city'];

//print_R($dat);
			$sale_id = aadd($dat, 'sales');
			echo "Added sale #".$sale_id."\n";

		}


	}


	echo "Total Missing sales: ".number_format($cnt)."\n";
