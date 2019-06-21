#!/usr/bin/php
<?php

	require_once("/var/www/html/dev/db.inc.php");


	$group_arr = array();


	$res = query("SELECT * FROM user_group_translations", 1);
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		if(!isset($group_arr[$row['user_id']]) || !is_array($group_arr[$row['user_id']]) ){
			$group_arr[$row['user_id']] = array();
		}

		$group_arr[ $row['user_id'] ][ $row['cluster_id'] ] = $row['group_name'];
	}

//print_R($group_arr);
//exit;



	$res = query("SELECT id, lead_tracking_id,transfer_id,agent_cluster_id FROM transfers WHERE call_group IS NULL",1);

	echo "Fixing ".mysqli_num_rows($res)." transfers records...\n";

	while($xfer = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		// look up user id

		list($agent_user_id) = queryROW("SELECT user_id FROM lead_tracking WHERE id='".$xfer['lead_tracking_id']."' ");

		if(!$agent_user_id){
			echo "Lead tracking(".$xfer['lead_tracking_id'].") not found, skipping...\n";
			continue;
		}

//		list($group) = queryROW("SELECT group_name FROM user_group_translations WHERE user_id='$agent_user_id' AND cluster_id='".$xfer['agent_cluster_id']."'");
		$group = $group_arr[$agent_user_id][$xfer['agent_cluster_id']];

//echo $agent_user_id.' '.$group;
//continue;

		$dat = array();
		$dat['call_group'] = $group;
		aedit($xfer['id'], $dat, 'transfers');

		## ATTEMPT OT PATCH THE SALES AS WELL

		execSQL("UPDATE sales SET call_group='".addslashes($group)."' WHERE lead_tracking_id='".$xfer['lead_tracking_id']."' AND transfer_id='".$xfer['transfer_id']."'");

	}

	echo "Done.\n";






