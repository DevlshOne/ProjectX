#!/usr/bin/php
<?php
	session_start();

	$basedir = "/var/www/dev/";



	$cluster_id = 5;

	include_once($basedir."site_config.php");
	include_once($basedir."db.inc.php");

	include_once($basedir."utils/db_utils.php");


	if($argv[1]){

		$stime = strtotime($argv[1]);


	}else{

		$stime = mktime (0,0,0);

	}

	$etime = $stime + 86399;

//	$stime = mktime (0,0,0);
//	$etime = $stime + 86399;

	echo "Processing for ".date("m/d/Y", $stime)." ...\n";


	connectPXDB();


//	$stime = mktime(0,0,0, 		3,11,2018);
//	$etime = mktime(23,59,59, 	3,11,2018);

	$sql = "SELECT * FROM `sales` WHERE `transfer_id`=0 AND agent_cluster_id=".intval($cluster_id)." AND `sale_time` BETWEEN '$stime' AND '$etime' ";

	echo $sql. "\n";

	$res = query($sql , 1);

	$rowarr = array();
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		$rowarr[] = $row;

	}

	$dbidx = getClusterIndex($cluster_id);

	connectViciDB($dbidx);

	foreach($rowarr as $row){


		connectViciDB($dbidx);


		$sql = "SELECT `lead_id` FROM `vicidial_carrier_log` WHERE `channel` LIKE '%".$row['phone']."%' ORDER BY call_date DESC LIMIT 1";

		//echo $sql."\n";

		list($most_recent_lead_id) = queryROW($sql);

		$most_recent_lead_id = intval($most_recent_lead_id);











		if($most_recent_lead_id > 0 && $row['agent_lead_id'] != $most_recent_lead_id){

			echo "Alternate Recent lead ID# ".$most_recent_lead_id."\n";

			$sql = "SELECT `user` FROM asterisk.recording_log ".

								" WHERE ".
									(($most_recent_lead_id > 0 && $row['agent_lead_id'] != $most_recent_lead_id)?
										" (lead_id='".$most_recent_lead_id."' OR lead_id='".$row['agent_lead_id']."')":
										" lead_id='".$row['agent_lead_id']."' "
									).

								" AND start_epoch BETWEEN '$stime' AND '$etime' ".
								" ORDER BY recording_id DESC ".
								" LIMIT 1 ";
			//echo $sql."\n";
		}else{

			$sql = "SELECT `user` FROM asterisk.recording_log ".

								" WHERE ".
									(($most_recent_lead_id > 0 && $row['agent_lead_id'] != $most_recent_lead_id)?
										" (lead_id='".$most_recent_lead_id."' OR lead_id='".$row['agent_lead_id']."')":
										" lead_id='".$row['agent_lead_id']."' "
									).

								" AND start_epoch BETWEEN '$stime' AND '$etime' ".
								" ORDER BY recording_id DESC ".
								" LIMIT 1 ";

		}
		//echo $sql."\n";

		list($fronter_user) = queryROW($sql);

		echo "Lead Tracking #".$row['lead_tracking_id'].' - Fronter lead #'.$row['agent_lead_id'].'/'.$most_recent_lead_id.' Listed as '.$row['agent_username']." - ";

		if(trim($fronter_user) && trim(strtoupper($fronter_user)) != trim(strtoupper($row['agent_username']))){

			//echo "Lead Tracking #".$row['lead_tracking_id'].' - Fronter lead #'.$row['agent_lead_id'].' Listed as '.$row['agent_username']." - ";
			echo 'Detected as: '.$fronter_user;


			connectPXDB();

			list($group,$office) = queryROW("SELECT user_group,office FROM `users` WHERE username='".mysqli_real_escape_string($_SESSION['db'],$fronter_user)."' AND priv <= 2");


			$dat = array(

				'agent_username'=> $fronter_user,
				'agent_name' => $fronter_user,

				'call_group' => $group,
				'office' => $office
			);

print_r($dat);

			//aedit($row['id'], $dat, 'sales');
			//echo " - UPDATED\n";
			echo "\n";

		}else{

		//	echo " - Skipped.\n";
			echo "\n";
		}

	}




