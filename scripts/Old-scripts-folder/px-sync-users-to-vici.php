#!/usr/bin/php
<?php
/* PX - SYNC/IMPORT USERS FROM PX TO VICI (REVERSE)
 * Written By: Jonathan Will
 * Created on Feb 12, 2016
 */



	$basedir = "/var/www/dev/";

	include_once($basedir."db.inc.php");
	include_once($basedir."dbapi/dbapi.inc.php");
	include_once($basedir."utils/db_utils.php");
	include_once($basedir."classes/vici_templates.inc.php");


	// LOOP THROUGH CLUSTERS
	foreach($_SESSION['site_config']['db'] as $idx=>$db){


		$user_arr = array();
		// LOAD ALL THE USERS TAHT SHOULD BE ON THIS VICI CLUSTER
		$res = $_SESSION['dbapi']->query("SELECT * FROM user_group_translations WHERE cluster_id='".$db['cluster_id']."' ");
		while($row = mysql_fetch_array($res, MYSQL_ASSOC)){

			$user = $_SESSION['dbapi']->users->getByID($row['user_id']);

			$user_arr[$row['user_id']] = array($user, $row);

		}

		echo "PROCESSING CLUSTER : ".$db['name']."\n";
		#print_r($user_arr);


		//$db['cluster_id'] $db['name'];



		foreach($user_arr as $user_id=>$datarr){

			// CONNECT TO VICI DB (moved inside the user loop, because vici template function juggles connections too)
			connectViciDB($idx);


			// BREAK IT APART AGAIN
			list($user,$grouptrans) = $datarr;

			if(!$user){

				echo "DELETING REFERENCE TO INVALID USER ID $user_id group trans #".$grouptrans['id']."\n";

				$_SESSION['dbapi']->execSQL("DELETE FROM user_group_translations WHERE id='".$grouptrans['id']."'");

				continue;
			}

			// DO WE HAVE THE VICI USER ID?
			if($grouptrans['vici_user_id'] == 0){

				echo $user['username'].": No vici user_id for user, attempting to locate...\n";

				// ATTEMPT TO FIND THE USER BY USERNAME
				list($vici_user_id) = queryROW("SELECT user_id FROM vicidial_users WHERE user='".addslashes($user['username'])."'");

				if($vici_user_id > 0){

					echo $user['username'].": found user at user_id: $vici_user_id\n";

					// FIX MISSING VICI USER ID

					$_SESSION['dbapi']->execSQL("UPDATE user_group_translations SET vici_user_id='$vici_user_id' WHERE id='".$grouptrans['id']."' ");
					$grouptrans['vici_user_id'] = $vici_user_id;

				}

			// WE HAVE THE VICI USER ID ALREADY, CHECK TO SEE IF THEY EXIST
			}else{


				echo $user['username'].": We think vici user_id is #".$grouptrans['vici_user_id'].", checking...\n";

				// CHECK TO SEE IF THEY EXIST
				list($vici_user_id) = queryROW("SELECT user_id FROM vicidial_users WHERE user_id='".addslashes($grouptrans['vici_user_id'])."'");

				if($vici_user_id <= 0){
					echo $user['username'].": No vici user_id for user, attempting to locate...\n";

					// ATTEMPT TO FIND THE USER BY USERNAME
					list($vici_user_id) = queryROW("SELECT user_id FROM vicidial_users WHERE user='".addslashes($user['username'])."'");

					if($vici_user_id > 0){

						echo $user['username'].": found user at user_id: $vici_user_id\n";

						// FIX MISSING VICI USER ID

						$_SESSION['dbapi']->execSQL("UPDATE user_group_translations SET vici_user_id='$vici_user_id' WHERE id='".$grouptrans['id']."' ");
						$grouptrans['vici_user_id'] = $vici_user_id;

					}
				}

			}





			// USER NOT FOUND AT ALL... ADD
			if($vici_user_id <= 0){


				echo $user['username'].": No vici user_id found at all. Creating new user.\n";

				$dat = array();
				$dat['user'] = $user['username'];
				$dat['pass'] = $user['vici_password'];
				$dat['full_name'] = trim($user['first_name'].' '.$user['last_name']);
				$dat['user_group'] = $grouptrans['group_name'];

				switch($user['priv']){
				case 5: // ADMIN
					$dat['user_level'] = 9;
					break;
				case 4: // MANAGER
					$dat['user_level'] = 8;
					break;
				default:
					$dat['user_level'] = 1;
					break;
				}


				aadd($dat,'vicidial_users');

				$vici_user_id = mysql_insert_id();

				$_SESSION['dbapi']->execSQL("UPDATE user_group_translations SET vici_user_id='$vici_user_id' WHERE id='".$grouptrans['id']."' ");
				$grouptrans['vici_user_id'] = $vici_user_id;

			}else{


				echo $user['username'].": User found/OKAY! Syncing password and reapplying vici settings template\n";



				/// SYNC PASSWORD
				execSQL("UPDATE vicidial_users SET `pass`='".addslashes($user['vici_password'])."' WHERE user_id='".intval($vici_user_id)."' ");


			}



			// APPLY THE VICI TEMPLATE
			if(isset($grouptrans['vici_template_id']) && $grouptrans['vici_template_id'] > 0){

				echo $user['username'].": Re-applying vici template settings to user\n";

				$_SESSION['vici_templates']->applyTemplate($grouptrans['vici_template_id'], $db['cluster_id'], $user['id'], $vici_user_id);

			}








		}

	}








	echo date("g:i:sa m/d/Y")." - Done.\n";











