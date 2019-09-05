#!/usr/bin/php
<?php

        // PX DATABASE INFO
        $px_db_host             = "127.0.0.1";
        $px_db_port             = "3306";
        $px_db_user     = "projectxdb";
        $px_db_pass     = "hYGjWDAX4LFmdR4C";
        $px_dbname              = "projectx";

        //TEST VICI DB INFO
//      $vici_db_host="192.168.233.116";
//      $vici_db_port="3306";
//      $vici_db_user="viciuser";
//      $vici_db_pass="908sht843h4t";
//      $vici_dbname="asterisk";


        // PRODUCTION
        $vici_db_host="10.101.13.2";
        $vici_db_port="3306";
        $vici_db_user="px";
        $vici_db_pass="FreedomFries666";
        $vici_dbname="asterisk";

	$vici_cluster_id = 9;

	// INCLUDE DATABASE FUNCTIONS
	include("vici_db.inc.php");



	# CONNECT TO VICI DB
    $db	= $_SESSION['vicidb'] = mysql_connect(
    							$vici_db_host.':'.$vici_db_port,
    							$vici_db_user,
    							$vici_db_pass
    						) or die(mysql_error()."Connection to VICI-DB Failed.");

	mysql_select_db($vici_dbname, $db) or die("Could not select vici-database ".$vici_dbname);




	echo "Connected to ViciDB @ ".$vici_db_host.':'.$vici_db_port."\n";



	// GATHER CAMPAIGNS AND DATA
	$campaigns = array();
	$users = array();
	$user_assoc = array();
	$vici_user_groups = array();
	$res = query("SELECT * FROM vicidial_campaigns ",1);
	while($row=mysql_fetch_array($res, MYSQL_ASSOC)){


		$campaigns[] = $row;

		// GATHER USER ASSOCIATIONS
		$re2 = query("SELECT * FROM vicidial_campaign_agents WHERE campaign_id='".addslashes($row['campaign_id'])."'",1);
		while($r2 = mysql_fetch_array($re2, MYSQL_ASSOC)){

			$user_assoc[$row['campaign_id']][] = $r2;

		}

		echo "Loaded ".count($user_assoc[$row['campaign_id']])." user associations for campaign #".$row['campaign_id']."\n";
	}
	echo "Loaded ".count($campaigns)." campaigns.\n";

	$re2 = query("SELECT * FROM vicidial_users WHERE user_level >= 1 ",1 );//AND user_group='SYSTEM-".addslashes($row['campaign_id'])."'
	while($r2 = mysql_fetch_array($re2, MYSQL_ASSOC)){
		$users[] = $r2;
	}

	echo "Loaded ".count($users)." users.\n";


	// GATHER TRAINING USERS
	$training_users = array();
	$res = query("SELECT * FROM vicidial_users WHERE user_level=1 AND (user_group LIKE 'SYSTEM-TRNG%' OR user_group LIKE 'TRAIN%') ", 1);
	while($row = mysql_fetch_array($res, MYSQL_ASSOC)){

		$training_users[] = $row;

	}

	echo "Loaded ".count($training_users)." training users.\n";



	$res = query("SELECT * FROM vicidial_user_groups ", 1);
	while($row = mysql_fetch_array($res, MYSQL_ASSOC)){

		$vici_user_groups[$row['user_group']] = $row['office'];

	}


	echo "Loaded vicidial_user_groups (for office).\n";



	// CLOSE VICI CONNECTION
	mysql_close($db);
	unset($_SESSION['vicidb']);


	// CONNECT TO PROJECT X DATABASE AND SYNC/ADD DATA
    $db	= $_SESSION['pxdb'] = mysql_connect(
    							$px_db_host.':'.$px_db_port,
    							$px_db_user,
    							$px_db_pass
    						) or die(mysql_error()."Connection to PX-DB Failed.");

	mysql_select_db($px_dbname, $db) or die("Could not select px-database ".$px_dbname);


	echo "Connected to PX database @ ".$px_db_host.':'.$px_db_port."\n";

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

			$campaignid = mysql_insert_id();

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

//		// GO THRU USER ASSOC
//		foreach($user_assoc[$campaign['campaign_id']] as $assoc){
//
//			// GATHER THE USERS THAT WILL BE ADDED
//			$cur_user = null;
//			foreach($users as $user){
//
//				//echo strtolower($assoc['user'])." vs ".strtolower($user['user'])."\n";
//
//				if(strtolower($assoc['user']) == strtolower($user['user']) ){
//					$cur_user = $user;
//					break;
//				}
//			}
//
//			// USER NOT FOUND
//			if($cur_user == null){
//				echo "User association for '".$assoc['user']."' NOT FOUND, skipped.\n";
//				continue;
//			}
//
//			// OVERWRITE THE VERBOSE "NPTAC" campaign_id, and replace with integer ID
//			$assoc['campaign_id'] = $campaignid;
//
//
//			// FIND IF USER IS ALREADY ON THE REAL NIGGAS STACK
//			$skipme=false;
//			$ridx=-1;
//			foreach($real_users as $idx=>$user){
//				if($user['user'] == $cur_user['user']){
//					$skipme=true;
//					$ridx = $idx;
//					break;
//				}
//			}
//
//			if($skipme == true){
//
//				echo "User already on the stack to be added, adding assoc only.\n";
//
//				$real_users[$ridx]['associations'][] = $assoc;
//
//				continue;
//
//			}
//
//			// ADD TO THE REAL NIGGAS
//			$real_users[$rp] = $cur_user;
//			$real_users[$rp]['associations'][] = $assoc;
//			$rp++; // INCREMENT POINTER
//		}



		// CAMPAIGN USERS
//		foreach($users[$campaign['campaign_id']] as $vici_user){
//
//
//			list($id) = queryROW("SELECT * FROM users WHERE vici_user_id='".$vici_user['user_id']."' ");
//
//			// USER NOT FOUND, ADD USER, ADD CAMPAIGN ASSOCIATION
//			if(!$id){
//
//				echo "User '".$vici_user['user']."' not found, adding!\n";
//
//				$dat = array();
//				$dat['vici_user_id'] = $vici_user['user_id'];
//				$dat['enabled'] = ($vici_user['active'] == 'Y')?'yes':'no';
//
//				$dat['username'] = $vici_user['user'];
//				$dat['password'] = md5($vici_user['pass']);
//
//				$dat['login_code'] = md5(uniqid('',true).$dat['username'].$dat['password']);
//
//
//				$namearr = preg_split("/\s+/",$vici_user, 2);
//
//				$dat['first_name'] = $namearr[0];
//				$dat['last_name'] = $namearr[1];
//
//				$dat['priv'] = 2; // CALLER LEVEL ACCESS
//
//				$dat['createdby_time'] = time();
//
//
//				aadd($dat, 'users');
//
//
//				$userid = mysql_insert_id();
//
//				$dat = array();
//				$dat['campaign_id'] = $campaignid;
//				$dat['user_id'] = $userid;
//				aadd($dat, 'campaigns_assoc');
//
//
//			// USER FOUND, MAKE SURE THE CAMPAIGN ASSOCATION IS THERE
//			}else{
//
//				echo "User found, syncing status and password\n";
//
//				// UPDATE ACTIVE STATUS
//				$dat = array();
//				$dat['enabled'] = ($vici_user['active'] == 'Y')?'yes':'no';
//				$dat['password'] = md5($vici_user['pass']);
//				aedit($userid, $dat, 'users');
//
//				list($id) = queryROW("SELECT id FROM campaigns_assoc WHERE user_id='".addslashes($userid)."' AND campaign_id='".addslashes($campaignid)."' ");
//
//				if(!$id){
//					$dat = array();
//					$dat['campaign_id'] = $campaignid;
//					$dat['user_id'] = $userid;
//					aadd($dat, 'campaigns_assoc');
//				}
//
//			}
//
//
//		}


	} // END FOREACH CAMPAIGNS





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

		list($userid) = queryROW("SELECT id FROM users WHERE username LIKE '".addslashes($user['user'])."' AND priv <= 2");///vici_user_id='".$user['user_id']."' ");

//echo $user['user'].' USERID #'.$userid."\n";
//continue;


		// USER NOT FOUND, ADD USER, ADD CAMPAIGN ASSOCIATION
		if(!$userid){

			echo "User '".$user['user']."' not found, adding!\n";

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

			$dat['priv'] = 2; // CALLER LEVEL ACCESS

			$dat['createdby_time'] = time();


			aadd($dat, 'users');


			$userid = mysql_insert_id();


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

			$dat['office'] = $vici_user_groups[$user['user_group']];

			aadd($dat, 'user_group_translations');



		// USER FOUND, MAKE SURE THE CAMPAIGN ASSOCATION IS THERE
		}else{

			echo "User '".$user['user']."' found, syncing status and password\n";

			// UPDATE ACTIVE STATUS
			$dat = array();
			$dat['enabled'] = ($user['active'] == 'Y')?'yes':'no';
			$dat['password'] = md5($user['pass']);

			$dat['vici_password'] = $user['pass'];

			 // USER GROUP IS UPDATED AUTOMATICALLY
                        // DEPENDING ON WHICH CLUSTER THEY LOGIN TO
			//$dat['user_group'] = $user['user_group'];
			if($vici_user_groups[$user['user_group']]){
                                $dat['office'] = $vici_user_groups[$user['user_group']];
                        }



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

			if($tmpid){

				// EDIT USERS GROUP
				$dat = array();
				$dat['vici_user_id'] = $user['user_id'];
				$dat['group_name'] = $user['user_group'];
				$dat['office'] = $vici_user_groups[$user['user_group']];
				aedit($tmpid,$dat, 'user_group_translations');

			}else{

				// ADD USERS GROUP
				$dat = array();
				$dat['user_id'] = $userid;
				$dat['vici_user_id'] = $user['user_id'];
				$dat['cluster_id'] = $vici_cluster_id;
				$dat['group_name'] = $user['user_group'];
				$dat['office'] = $vici_user_groups[$user['user_group']];
				aadd($dat, 'user_group_translations');
			}




		}
	}





	// ADD TRAINING USERS
	foreach($training_users as $user){

		list($userid) = queryROW("SELECT * FROM users WHERE username LIKE '".addslashes($user['user'])."' AND priv <= 2");///vici_user_id='".$user['user_id']."' ");


//echo $user['user'].' USERID #'.$userid."\n";
//continue;


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


			$userid = mysql_insert_id();


			// ADD USERS GROUP
			$dat = array();
			$dat['user_id'] = $userid;
			$dat['vici_user_id'] = $user['user_id'];
			$dat['cluster_id'] = $vici_cluster_id;
			$dat['group_name'] = $user['user_group'];
			$dat['office'] = $vici_user_groups[$user['user_group']];
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

			if($vici_user_groups[$user['user_group']]){
                                $dat['office'] = $vici_user_groups[$user['user_group']];
                        }


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
				$dat['office'] = $vici_user_groups[$user['user_group']];
				aedit($tmpid,$dat, 'user_group_translations');

			}else{

				// ADD USERS GROUP
				$dat = array();
				$dat['user_id'] = $userid;
				$dat['vici_user_id'] = $user['user_id'];
				$dat['cluster_id'] = $vici_cluster_id;
				$dat['group_name'] = $user['user_group'];
				$dat['office'] = $vici_user_groups[$user['user_group']];
				aadd($dat, 'user_group_translations');
			}



		}
	}


mysql_close();


