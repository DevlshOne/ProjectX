#!/usr/bin/php
<?
	$base_dir = "/var/www/ringreport/dev/";


	include_once($base_dir."site_config.php");
	include_once($base_dir."db.inc.php");

	include_once($base_dir."util/db_utils.php");






	$vici_groups = array();


	// LOOP THROUGH STACK OF VICIDIAL SERVERS
	foreach($_SESSION['site_config']['db'] as $dbidx=>$vicidb){

		// CONNECT TO VICIDIAL DB
		connectViciDB($dbidx);

		// PULL LIST OF GROUPS
		$res = query("SELECT * FROM vicidial_user_groups", 1);
		while($row = mysql_fetch_array($res, MYSQL_ASSOC)){

			// INIT ARRAY
			if(!isset($vici_groups[$dbidx]))$vici_groups[$dbidx] = array();

			// STORE IN ARRAY FOR USE BELOW
			$vici_groups[$dbidx][] = $row;

		}



	}

	// CONNECT TO PX DB
	connectPXDB();

	// EDIT OR ADD THE GROUPS

	$edit_cnt = 0;
	$add_cnt = 0;

	foreach($vici_groups as $dbidx=>$groups){

		$vici_cluster_id = $_SESSION['site_config']['db'][$dbidx]['cluster_id'];

		foreach($groups as $idx=>$user_group){

			// LOOK UP GROUP IN TABLE, BY USER_GROUP AND VICI CLUSTER ID

			list($tmpid) = queryROW("SELECT id FROM `user_groups` WHERE vici_cluster_id='$vici_cluster_id' AND user_group='".$user_group['user_group']."'");

			$dat = array();

			// EDIT IF IT EXISTS
			if($tmpid > 0){

				$dat['name'] = $user_group['group_name'];

				aedit($tmpid, $dat, 'user_groups');

				$edit_cnt++;

			}else{
			// ELSE ADD NEW

				$dat['vici_cluster_id']	= $vici_cluster_id;
				$dat['user_group']		= $user_group['user_group'];
				$dat['name']			= $user_group['group_name'];

				aadd($dat, 'user_groups');

				$add_cnt++;
			}
		}


	}





	echo "Done, $edit_cnt User Groups Edited, $add_cnt Added.\n";








