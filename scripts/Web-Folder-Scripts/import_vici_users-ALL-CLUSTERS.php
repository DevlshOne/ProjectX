#!/usr/bin/php
<?php

	$base_dir = "/var/www/html/dev2/";


	include_once($base_dir."site_config.php");
	include_once($base_dir."db.inc.php");

	include_once($base_dir."util/db_utils.php");


	connectPXDB();


//	$vici_groups = array();

	$master_groups = array();


	$res = query("SELECT * FROM user_groups_master ",1);
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		$master_groups[$row['user_group']] = $row['office'];

	}



	$res = query("SELECT * FROM vici_clusters WHERE `status`='enabled' AND `sync_users`='yes' ORDER BY `name` ASC",1);
	$clusters = array();
	while($row = mysqli_fetch_array($res)){

		$clusters[$row['id']] = $row;

	}


	// LOOP THROUGH STACK OF VICIDIAL SERVERS
	foreach($clusters as $cluster_id => $vicidb ){

		$vici_cluster_id = $cluster_id;

		// LOCATE WHICH DB INDEX IT IS
		$dbidx = getClusterIndex($cluster_id);

		// CONNECT TO VICIDIAL DB
		connectViciDB($dbidx);


//	// LOOP THROUGH STACK OF VICIDIAL SERVERS
//	foreach($_SESSION['site_config']['db'] as $dbidx=>$vicidb){
//
//		// CONNECT TO VICIDIAL DB
//		connectViciDB($dbidx);


		echo "Connected to ViciDB @ ".$vicidb['ip_address'].' - '.$vicidb['name']."\n";


		// GATHER CAMPAIGNS AND DATA
		$campaigns = array();
		$users = array();
		$user_assoc = array();
		$vici_user_groups = array();
		$res = query("SELECT * FROM vicidial_campaigns WHERE active='Y' ",1);
		while($row=mysqli_fetch_array($res, MYSQLI_ASSOC)){


			$campaigns[] = $row;

			// GATHER USER ASSOCIATIONS
			$re2 = query("SELECT * FROM vicidial_campaign_agents WHERE campaign_id='".mysqli_real_escape_string($_SESSION['db'],$row['campaign_id'])."'",1);
			while($r2 = mysqli_fetch_array($re2, MYSQLI_ASSOC)){

				$user_assoc[$row['campaign_id']][] = $r2;

			}

			echo "Loaded ".count($user_assoc[$row['campaign_id']])." user associations for campaign #".$row['campaign_id']."\n";
		}
		echo "Loaded ".count($campaigns)." campaigns.\n";

		$re2 = query("SELECT * FROM vicidial_users WHERE user_level >= 1 ",1 );//AND user_group='SYSTEM-".addslashes($row['campaign_id'])."'
		while($r2 = mysqli_fetch_array($re2, MYSQLI_ASSOC)){
			$users[] = $r2;
		}

		echo "Loaded ".count($users)." users.\n";


		// GATHER TRAINING USERS
		$training_users = array();
		$res = query("SELECT * FROM vicidial_users WHERE user_level=1 AND (user_group LIKE 'SYSTEM-TRNG%' OR user_group LIKE 'TRAIN%') ", 1);
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			$training_users[] = $row;

		}

		echo "Loaded ".count($training_users)." training users.\n";



		$res = query("SELECT * FROM vicidial_user_groups ", 1);
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			$vici_user_groups[$row['user_group']] = $row['office'];

		}



	//print_r($vici_user_groups);
	//exit;

		echo "Loaded vicidial_user_groups (for office).\n";


		connectPXDB();

		echo "Connected to PX database @ ".$_SESSION['site_config']['pxdb']['sqlhost']."\n";

		$real_users = array();
		$rp = 0;

		// SYNC CAMPAIGNS TO PX
		foreach($campaigns as $campaign){


			// WHERE active='Y'
			list($campaignid, $status) = queryROW("SELECT id,status FROM campaigns WHERE vici_campaign_id='".addslashes($campaign['campaign_id'])."' ");

			// CAMPAIGN NOT FOUND, ADD IT
			if(!$campaignid){

				echo "Campaign ".$campaign['campaign_id']." not found, adding!\n";

				$dat = array();
				$dat['status'] = ($campaign['active'] == 'Y')?'active':'suspended';
				$dat['name'] = $campaign['campaign_name'];

				$dat['transfer_group'] = $campaign['default_xfer_group'];

				$dat['vici_campaign_id'] = $campaign['campaign_id'];
				$dat['time_created'] = time();

				aadd($dat, 'campaigns');

				$campaignid = mysqli_insert_id($_SESSION['db']);

			// SYNC STATUS
			}else{

				echo "Campaign ".$campaign['campaign_id']." found @ id#".$campaignid."\n";

				if($status == 'locked'){

					echo "Campaign ".$campaign['campaign_id']." Skipped: Campaign is locked/blocked from sync.\n";

				}else{
					$dat = array();
					$dat['status'] = ($campaign['active'] == 'Y')?'active':'suspended';
			//		$dat['transfer_group'] = $campaign['default_xfer_group'];
					aedit($campaignid, $dat, 'campaigns');
				}
			}



			// GATHER THE USERS THAT WILL BE ADDED
			// FORCING ALL USERS TO BE REAL USERS, SINCE CAMPAIGN_AGENTS TABLE IS NOT WHAT WE THOUGHT IT WAS
			foreach($users as $user){

				$real_users[$rp++] = $user;
			}

		} // END FOREACH CAMPAIGNS



		echo "Processing Caller users for ".getClusterName($vici_cluster_id)."\n";


		/**
		 * Go through the stack of users that actually have campaign associations
		 */
		foreach($real_users as $user){

			// NOPE OUT COMPLETELY IF MISSING PASSWORD
			if(!$user['pass']){

				echo "User '".$user['user']."' MISSING PASSWORD! Skipping.\n";

				// nope. nope! NOPE!
				continue;
			}


			list($userid) = queryROW("SELECT id FROM users WHERE username LIKE '".mysqli_real_escape_string($_SESSION['db'],$user['user'])."' AND priv <= 2");///vici_user_id='".$user['user_id']."' ");

	//echo $user['user'].' USERID #'.$userid."\n";
	//continue;


			// USER NOT FOUND, ADD USER, ADD CAMPAIGN ASSOCIATION
			if(!$userid){

				echo "Added '".$user['user']."',";

				$dat = array();
				$dat['vici_user_id'] = $user['user_id'];
				$dat['enabled'] = ($user['active'] == 'Y')?'yes':'no';

				$dat['username'] = $user['user'];
				$dat['password'] = md5($user['pass']);


				$dat['vici_password'] = $user['pass'];

				$dat['login_code'] = md5(uniqid('',true).$dat['username'].$dat['password']);

				$dat['user_group'] = $user['user_group'];


				$dat['office'] = intval($vici_user_groups[$user['user_group']]);


				$namearr = preg_split("/\s+/",$user['full_name'], 2);

				$dat['first_name'] = $namearr[0];
				if(count($namearr) > 1)$dat['last_name'] = $namearr[1];

				$dat['priv'] = 2; // CALLER LEVEL ACCESS

				$dat['createdby_time'] = time();


				aadd($dat, 'users');


				$userid = mysqli_insert_id($_SESSION['db']);


	//			foreach($user['associations'] as $assoc){
	//
	//				$dat = array();
	//				$dat['campaign_id'] = $assoc['campaign_id'];
	//				$dat['user_id'] = $userid;
	//				aadd($dat, 'campaigns_assoc');
	//			}



				// ADD USERS GROUP
				$dat = array();
				$dat['user_id'] = $userid;
				$dat['vici_user_id'] = $user['user_id'];
				$dat['cluster_id'] = $vici_cluster_id;
				$dat['group_name'] = $user['user_group'];

				$dat['office'] = intval($vici_user_groups[$user['user_group']]);

				aadd($dat, 'user_group_translations');



			// USER FOUND, MAKE SURE THE CAMPAIGN ASSOCATION IS THERE
			}else{

				echo "Syncing '".$user['user']."',";

				// UPDATE ACTIVE STATUS
				$dat = array();

				$dat['enabled'] = ($user['active'] == 'Y')?'yes':'no';
				$dat['password'] = md5($user['pass']);

				$dat['vici_password'] = $user['pass'];

				// USER GROUP IS UPDATED AUTOMATICALLY
				// DEPENDING ON WHICH CLUSTER THEY LOGIN TO
				//$dat['user_group'] = $user['user_group'];

				$dat['office'] = intval($vici_user_groups[$user['user_group']]);

				$namearr = preg_split("/\s+/",$user['full_name'], 2);

				$dat['first_name'] = $namearr[0];
				if(count($namearr) > 1)$dat['last_name'] = $namearr[1];
				else $dat['last_name'] = '';
	//print_r($dat);
				aedit($userid, $dat, 'users');


	//			foreach($user['associations'] as $assoc){
	//
	//				list($id) = queryROW("SELECT id FROM campaigns_assoc WHERE user_id='".addslashes($userid)."' ".
	//									" AND campaign_id='".addslashes($assoc['campaign_id'])."' ");
	//				if(!$id){
	//					$dat = array();
	//					$dat['campaign_id'] = $assoc['campaign_id'];
	//					$dat['user_id'] = $userid;
	//					aadd($dat, 'campaigns_assoc');
	//				}
	//			}



				// ADD OR EDIT THE GROUP
				list($tmpid) = queryROW("SELECT id FROM `user_group_translations` WHERE user_id='$userid' AND cluster_id='$vici_cluster_id'");


				// IF THE OFFICE FOR THE GROUP ISN'T FOUND, ATTEMPT TO PATCH
				if(!$vici_user_groups[$user['user_group']]){
					//print_r($vici_user_groups);
					$vici_user_groups[$user['user_group']] = $master_groups[$user['user_group']];

					//print_r($vici_user_groups);

					// IF ITS STILL NOT FOUND, THROW AN ERROR MESSAGE
					if(!$vici_user_groups[$user['user_group']]){
						echo "ERROR: Group ".$user['user_group']." was NOT FOUND on ".$vicidb['name']."!\n";
					}
				}

				if($tmpid){



					// EDIT USERS GROUP
					$dat = array();
					$dat['vici_user_id'] = $user['user_id'];
					$dat['group_name'] = $user['user_group'];
					$dat['office'] = intval($vici_user_groups[$user['user_group']]);
					aedit($tmpid,$dat, 'user_group_translations');

				}else{

					// ADD USERS GROUP
					$dat = array();
					$dat['user_id'] = $userid;
					$dat['vici_user_id'] = $user['user_id'];
					$dat['cluster_id'] = $vici_cluster_id;
					$dat['group_name'] = $user['user_group'];
					$dat['office'] = intval($vici_user_groups[$user['user_group']]);
					aadd($dat, 'user_group_translations');
				}




			}

		} // END SYNCING NORMAL CALLER USERS



		echo "\nStarting Training Users...\n";

		// ADD TRAINING USERS
		foreach($training_users as $user){

			list($userid) = queryROW("SELECT * FROM users WHERE username LIKE '".mysqli_real_escape_string($_SESSION['db'],$user['user'])."' AND priv <= 2");///vici_user_id='".$user['user_id']."' ");


	//echo $user['user'].' USERID #'.$userid."\n";
	//continue;


			// IF THE OFFICE FOR THE GROUP ISN'T FOUND, ATTEMPT TO PATCH
			if(!$vici_user_groups[$user['user_group']]){
				//print_r($vici_user_groups);
				$vici_user_groups[$user['user_group']] = $master_groups[$user['user_group']];

				//print_r($vici_user_groups);

				// IF ITS STILL NOT FOUND, THROW AN ERROR MESSAGE
				if(!$vici_user_groups[$user['user_group']]){
					echo "ERROR: Group ".$user['user_group']." was NOT FOUND on ".$vicidb['name']."!\n";
				}
			}

			// USER NOT FOUND
			if(!$userid){

				echo "Training user '".$user['user']."' not found, adding!\n";

				$dat = array();
				$dat['vici_user_id'] = $user['user_id'];
				$dat['enabled'] = ($user['active'] == 'Y')?'yes':'no';

				$dat['username'] = $user['user'];
				$dat['password'] = md5($user['pass']);

				$dat['vici_password'] = $user['pass'];

				$dat['login_code'] = md5(uniqid('',true).$dat['username'].$dat['password']);

				$dat['user_group'] = $user['user_group'];

				$dat['office'] = $vici_user_groups[$user['user_group']];


				$namearr = preg_split("/\s+/",$user['full_name'], 2);

				$dat['first_name'] = $namearr[0];
				if(count($namearr) > 1)$dat['last_name'] = $namearr[1];

				$dat['priv'] = 1; // TRAINING LEVEL ACCESS

				$dat['createdby_time'] = time();


				aadd($dat, 'users');


				$userid = mysqli_insert_id($_SESSION['db']);


				// ADD USERS GROUP
				$dat = array();
				$dat['user_id'] = $userid;
				$dat['vici_user_id'] = $user['user_id'];
				$dat['cluster_id'] = $vici_cluster_id;
				$dat['group_name'] = $user['user_group'];
				$dat['office'] = intval($vici_user_groups[$user['user_group']]);
				aadd($dat, 'user_group_translations');





			// FOUND - UPDATE STATUS
			}else{

				echo "Training user '".$user['user']."' found, syncing status and password\n";

				// UPDATE ACTIVE STATUS
				$dat = array();
				$dat['enabled'] = ($user['active'] == 'Y')?'yes':'no';
				$dat['password'] = md5($user['pass']);

				$dat['vici_password'] = $user['pass'];

				 // USER GROUP IS UPDATED AUTOMATICALLY
	                        // DEPENDING ON WHICH CLUSTER THEY LOGIN TO
				//$dat['user_group'] = $user['user_group'];

				$dat['office'] = $vici_user_groups[$user['user_group']];

				$namearr = preg_split("/\s+/",$user['full_name'], 2);

				$dat['first_name'] = $namearr[0];
				if(count($namearr) > 1)$dat['last_name'] = $namearr[1];
				else $dat['last_name'] = '';

				aedit($userid, $dat, 'users');



				// ADD OR EDIT THE GROUP
				list($tmpid) = queryROW("SELECT id FROM `user_group_translations` WHERE user_id='$userid' AND cluster_id='$vici_cluster_id'");

				if($tmpid){

					// EDIT USERS GROUP
					$dat = array();
					$dat['vici_user_id'] = $user['user_id'];
					$dat['group_name'] = $user['user_group'];
					$dat['office'] = intval($vici_user_groups[$user['user_group']]);
					aedit($tmpid,$dat, 'user_group_translations');

				}else{

					// ADD USERS GROUP
					$dat = array();
					$dat['user_id'] = $userid;
					$dat['vici_user_id'] = $user['user_id'];
					$dat['cluster_id'] = $vici_cluster_id;
					$dat['group_name'] = $user['user_group'];
					$dat['office'] = intval($vici_user_groups[$user['user_group']]);
					aadd($dat, 'user_group_translations');
				}



			}
		}


		echo "Finished Processing Cluster ".$vicidb['ip_address'].' - '.$vicidb['name']."\n";

	}

