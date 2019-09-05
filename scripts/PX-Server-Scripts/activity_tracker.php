#!/usr/bin/php
<?php
/**
 * ACTIVITY MOTHERFUCKING TRACKER
 * Written By: Jonathan Will, Professor of Computer Science at the Scumlord University of CCI
 *
 * 1) Runs every minute and checks for activity
 * 2) Uses a 15 minute "frame" (0-14, 15-29, 30-44, 45-59)
 * 3) Uses an offset, to prevent slight overlap, like taking a call at 4:01, and getting credit for the whole 15 minutes.
 * 4) ...
 * 5) Profit!
 */







	// NOT REALLY ADJUSTABLE, UNLESS YOU REMAP THE FRAMES!
	$time_frame_offset = 0; // IN MINUTES - OFFSET FROM THE HOUR FRAME TO USE, (so they dont take a call at 4:01 and get credited for 4:00-4:15)
	$time_frame_window = 5; // IN MINUTES - ACTIVITY OCCURS WITHIN THE 15 MINUTES, ADD 15 MINUTES ACTIVITY TIME



    // INCLUDE DB CONFIG FILE
	include("/var/www/html/dev/site_config.php");
	include("/var/www/html/dev/db.inc.php");
//	include("/var/www/html/dev/utils/db_utils.php");

    // INCLUDE DATABASE FUNCTIONS
	//include("/var/www/html/scripts/vici_db.inc.php");





	// BUILD THE CURRENT FRAME WINDOWS
	$stime = mktime(0,0,0, date("m"), date("j"), date("Y") );
	$etime = $stime + 86399;



	// BUILD THE DEFAULT MINUTE START TIME
/**	if($min >= 45)			$min = 45 + $time_frame_offset; // 45 to 59
	else if($min >= (30+$time_frame_offset))		$min = 30 + $time_frame_offset; // 30-44
	else if($min >= (15+$time_frame_offset))		$min = 15 + $time_frame_offset; // 15-29
	else					$min = 0 + $time_frame_offset; // 0 - 14


	// THEN APPLY THE OFFSET
	//$min += $time_frame_offset;
**/


	$hour = date("H");
	$min = date("i");

	$remove = $min % 5;

	$min -= $remove;


//	if($min >= 50 || $min < 5){
//
//		if($min < 5){
//			$hour--;
//		}
//		$min = 50;
//
//
//	}else if($min >= 35){
//		$min = 35;
//	}else if($min >= 20){
//		$min = 20;
//	}else{
//		$min = 5;
//	}



	// GENERATE THE TIMESTAMPS FOR THE TIMEFRAMES
	$frame_start = mktime($hour, $min, 0);
	$frame_end = $frame_start + ($time_frame_window * 60) - 1;


	echo "Started at ".date("g:i:sa m/d/Y")."\n";
	echo "Frame Start: ".date("g:i:sa m/d/Y", $frame_start)."\n";
	echo "Frame End: ".date("g:i:sa m/d/Y", $frame_end)."\n";

//exit;

	foreach($_SESSION['site_config']['db'] as $vidx=>$vicidb){

	    # CONNECT TO VICI DB

	    connectViciDB($vidx);

	    $db = $_SESSION['db'];
//		$db = $_SESSION['vicidb'] = mysql_connect(
//	                                        $vicidb['sqlhost'],
//	                                        $vicidb['sqllogin'],
//	                                        $vicidb['sqlpass']
//	                                );
		if(!$db){

			echo mysqli_error($db)."Connection to VICI-DB (".$vicidb['sqlhost'].") Failed.\n";

			// SKIP IT
			continue;
		}


	  //  mysql_select_db($vicidb['sqldb'], $db) or die("Could not select vici-database ".$vicidb['sqldb']);




	    echo "Connected to ViciDB @ ".$vicidb['sqlhost']."\n";


	    // GRAB THE DATA

		$rowarr = array();

		$res = query("SELECT * FROM vicidial_live_agents", 1);

		// TOSS-N-AND-FLOSSIN, MY STYLE IS AWESOME (also, put data into an array)
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			$rowarr[] = $row;

		}


		echo "Total records: ".count($rowarr)."\n";



		// CLOSE VICI CONNECTION
		mysqli_close($db);
		unset($_SESSION['vicidb']);


		// CONNECT TO PROJECT X DATABASE AND SYNC/ADD DATA
//	    $db	= $_SESSION['pxdb'] = mysql_connect(
//	    							$_SESSION['site_config']['pxdb']['sqlhost'],
//	    							$_SESSION['site_config']['pxdb']['sqllogin'],
//	    							$_SESSION['site_config']['pxdb']['sqlpass']
//	    						) or die(mysql_error()."Connection to PX-DB Failed.\n");
//
//		mysql_select_db($_SESSION['site_config']['pxdb']['sqldb'], $db) or die("Could not select px-database ".$_SESSION['site_config']['pxdb']['sqldb']);

		connectPXDB();

		echo "Connected to PX database @ ".$_SESSION['site_config']['pxdb']['sqlhost']."\n";


		$username_array = array();

		// PREPROCESSING - BUILD ARRAY OF USERS
		foreach($rowarr as $vrow){
			$user = strtolower($vrow['user']);

			$username_array[$user] = $user;
		}

		// GO THROUGH EACH RECORD
		foreach($rowarr as $vrow){

			// INIT/REINIT FLAGS AND SHIT
			$dat = array();
			$is_active = false;
			$is_time_added = false;

			$user = strtolower($vrow['user']);


			// FIND USER RECORD FOR TODAY
			$row = querySQL("SELECT * FROM activity_log ".
						" WHERE username LIKE '".addslashes($user)."' ".
						" AND time_started BETWEEN '$stime' AND '$etime' ".
						""
						//" AND vici_cluster_id='".$vicidb['cluster_id']."'"
						);



			// ADD IF NOT FOUND
			if(!$row){

//
//				// LOOK UP PX USER ID
//				$pxuser = querySQL("SELECT * FROM users WHERE username LIKE '".addslashes($user)."' AND enabled='yes' ORDER BY id ASC");
//
//				// LOOK UP USER GROUP TRANSLATION
//				$group = querySQL("SELECT * FROM user_group_translations WHERE user_id='".$pxuser['id']."' AND cluster_id='".addslashes($vicidb['cluster_id'])."'");




				// START ACTIVITY TIME AT ZERO
				$activity = 0;

				// INIT THE OTHER FIELDS
				$dat['time_started'] = time();
				$dat['username'] = $user;
				$dat['campaign'] = $vrow['campaign_id'];

				if(is_numeric($user[strlen($user)-1]) ){


					// CHECK IF THERE MAIN IS IN THE ARRAY
					//if($username_array[substr($user,0, strlen($user) - 1)]){

						$dat['paid_time'] = 0;

					//}

				}

//				$dat['call_group'] = $group['group_name'];
//				$dat['office'] = $group['office'];

				$dat['vici_cluster_id'] = $vicidb['cluster_id'];

			}else{

				// START AT CURRENT ACTIVITY TIME
				$activity = $row['activity_time'];

			}

			// PATCH MISSING CALL GROUP
//			if(!isset($dat['call_group']) || !trim($dat['call_group'])){

				$pxuser = querySQL("SELECT * FROM `users` WHERE username LIKE '".addslashes($user)."' AND `enabled`='yes' ORDER BY `last_login` DESC");

				// LOOK UP USER GROUP TRANSLATION
				$group = querySQL("SELECT * FROM user_group_translations WHERE `user_id`='".$pxuser['id']."' AND `cluster_id`='".addslashes($vicidb['cluster_id'])."'");

				$dat['call_group'] = $group['group_name'];

				$dat['office'] = intval($group['office']);


				if($dat['office'] <= 0){

					list($dat['office']) = queryROW("SELECT office FROM user_groups_master WHERE user_group='".addslashes($dat['call_group'])."'");

				}


				$dat['office'] = intval($dat['office']);

//			}


			// UPDATE CALL COUNTER FOR TODAY
			$dat['calls_today'] = $vrow['calls_today'];



			// DETERMINE IF ACTIVE OR NOT
			switch($vrow['status']){

			// ALWAYS ASSUMING ANY NEW STATUS THAT IS ADDED, IS CONSIDERED ACTIVE
			default:

				echo "Potentially unknown status: ".$vrow['status']."\n";

			/// VICISTATUS - // IS CONSIDERED ACTIVE(yes/no) / DESCRIPTION
			case 'READY': // YES / READY FOR A CALL
			case 'INCALL': // YES / ACTIVELY TAKING A CALL
			case 'QUEUE':  // YES / WAITING FOR A CALL
			case 'MQUEUE':	// YES / WAITING FOR A CALL
			case 'CLOSER': // ASSUMING YES / VERIFIERS
				$is_active = true;

				break;
			case 'PAUSED': // NO / NOT TAKING A CALL, PAUSED UP FOR WHATEVER REASON

				$is_active = false;

				break;
			}

			if($is_active){


				// IF RECORD EXISTS
				if($row){


					echo "last tick: ".date("g:i:sa m/d/Y",$row['time_last_tick'])." vs ".date("g:i:sa m/d/Y",$frame_start)."\n";

					// CHECK THE 'time_last_tick' against the timeframes
					if( ($row['time_last_tick'] < $frame_start) || ($row['time_last_tick'] > $frame_end) ){

						$dat['activity_time']	= $activity + $time_frame_window;
						$dat['time_last_tick']	= time();

						$is_time_added = true;
					}



				// NEW RECORD, JUST ADD IT
				}else{
					$dat['activity_time']	= $activity + $time_frame_window;
					$dat['time_last_tick']	= time();


					$is_time_added = true;
				}


			}

			// ADD IF NOT EXIST, OTEHRWISE UPDATE.
			if(!$row){

				aadd($dat, 'activity_log');

				echo "Added '$user' - Active:".$is_active." - Added time:$is_time_added\n";

			}else{

				aedit($row['id'], $dat, 'activity_log');

				echo "Updated '$user' - Active:".$is_active." - Added time:$is_time_added\n";
			}

		} // END FOREACH(user in the record)






	} // END FOREACH (all vici connections)







