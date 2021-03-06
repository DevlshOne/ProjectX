#!/usr/bin/php
<?php
	$basedir = "/var/www/html/reports/";

	include_once($basedir."db.inc.php");
	include_once($basedir."util/microtime.php");
	include_once($basedir."utils/format_phone.php");
	include_once($basedir."utils/db_utils.php");


	include_once($basedir."dbapi/dbapi.inc.php");
	
	global $process_name;
	
	$process_name = "px_export_all_user_groups_to_vici";
	
	
	$stime = mktime(0,0,0);
	$etime = mktime(23,59,59);


	$procid = $_SESSION['dbapi']->process_tracker->logStartProcess($process_name, 'started', implode(" ", $argv));
	
	
	
	
	echo "Starting Bulk Vici User GROUP EXPORT script on ".date("m/d/Y",$stime)."...\n";


	// CONNECT PX DB
	connectPXDB();


	$res = query("SELECT * FROM vici_clusters WHERE status='enabled' ",1);
	$clusters = array();
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		$clusters[$row['id']] = $row;

	}



	$res = query("SELECT * FROM user_groups_master ORDER BY `user_group` ASC ", 1);

	$master_groups = array();
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		$skipme = false;
		switch($row['agent_type']){
		default:

			// ALLOW IT/DO NOTHING HERE


			break;

		case 'verifier':
		case 'manager':
		case 'monitor':
		case 'admin':

			$skipme = true;
			break;
		}

		if(!$skipme){

			$master_groups[] = $row;

		}

	}


//	print_r($master_groups);
//	exit;

	$process_logs = '';
	
	// LOOP THROUGH STACK OF VICIDIAL SERVERS
	foreach($clusters as $cluster_id => $vicirow ){

		echo "Processing Vici Cluster #".$cluster_id."...\n";

		// LOCATE WHICH DB INDEX IT IS
		$dbidx = getClusterIndex($cluster_id);

		// CONNECT TO VICIDIAL DB
		connectViciDB($dbidx);


		foreach($master_groups as $grouprow){

			list($test) = queryROW("SELECT `user_group` FROM `vicidial_user_groups` WHERE `user_group`='".mysqli_real_escape_string($_SESSION['db'],$grouprow['user_group'])."'");

			// MISSING GROUP, FILL THE VOID
			if(!$test){


				$dat = array();
				$dat2 = array();
				foreach($grouprow as $key=>$val){


					switch($key){

					// IGNORE BECAUSE THEY ARE PX SPECIFIC FIELDS
					case 'company_id':
					case 'time_shift':
					case 'id':
					case 'agent_type':
						break;

					// IGNORE BECAUSE THEY ARE ONLY IN NEWEST VICI SVN
					case 'allowed_custom_reports':
					case 'agent_allowed_chat_groups':
					case 'agent_xfer_park_3way':
					case 'admin_ip_list':
					case 'agent_ip_list':
					case 'api_ip_list':
					case 'webphone_layout':

						$dat2[$key] = $val;

						break;
					default:

						$dat[$key] = $val;

					}
				} // END FOREACH (Group row)

				$str = "Adding group '".$grouprow['user_group']." to Vici Cluster #".$cluster_id."\n";
				
				$process_logs .= $str;

				echo $str;

//print_r($dat);

				aadd($dat, 'vicidial_user_groups');





			}


		}


	}


	
	$_SESSION['dbapi']->process_tracker->logFinishProcess($procid, "completed", $process_logs);
	
	
	

