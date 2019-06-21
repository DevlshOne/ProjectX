#!/usr/bin/php
<?php
/****
 * FIX MISSING ADDRESSES IN VICIDIAL, BASED ON TASK RECORDS FROM PX LIST TOOL
 * Written By: Jonathan Will - 11-29-2017
 *
 *
 */
	$basedir = "/var/www/dev2/"; // FOR PRODUCTION

    include_once($basedir."db.inc.php");
    include_once($basedir."utils/db_utils.php");
 	include_once($basedir."classes/JXMLP.inc.php");



	$stime = mktime(0,0,0, date("m"), date("j")-2, date("Y") );
	$etime = time();//$stime + 86399;



	echo "Looking up Tasks created between '".date("m/d/Y H:i:s", $stime)."' and '".date("m/d/Y H:i:s", $etime)."'\n";
	// CONNECT TO TEH LIST TOOL
	connectListDB();

	// LOAD THE TASKS IN QUESTION
	$tres = query("SELECT * FROM `tasks` WHERE `time_created` BETWEEN '$stime' AND '$etime' AND `status`='finished'", 1);

	while($task = mysqli_fetch_array($tres, MYSQLI_ASSOC)){

		$taskid = $task['id'];

		echo "Processing Task #".$taskid."\n";

		$config = $_SESSION['JXMLP']->parseOne($task['config_xml'],"Config", 1);

		$cluster_id = $config['target_vici_cluster_id'];

		$cidx = getClusterIndex($cluster_id);


		if($cidx < 0){

			die("ERROR: Cannot find vici cluster ID# ".$cluster_id."\n");

		}

		echo "Extracting leads_pulled for Task #$taskid\n";

		// GO THROUGH ALL THE "leads_pulls" TO FIND THE AFFECTED LEADS
		$res = query("SELECT `leads`.`address`,`leads`.`phone` FROM `leads` ".
				"INNER JOIN `leads_pulls` ON `leads_pulls`.`phone`=`leads`.`phone` ".
				" WHERE `leads_pulls`.task_id='$taskid' "
				,1);
		$address_arr = array();
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			$address_arr[$row['phone']] = $row['address'];

		}

		echo "Found ".count($address_arr)." leads, patching address...\n";


		// CONNECT TO VICI CLUSTER
		connectViciDB($cidx);

		// UPDATE THE LEADS BY PHONE NUMBER
		foreach($address_arr as $phone=>$address){

			if(!trim($address))continue;

			echo("UPDATE `vicidial_list` SET `address1`='".mysqli_real_escape_string($_SESSION['db'],$address)."' WHERE `phone_number`='".mysqli_real_escape_string($_SESSION['db'],$phone)."'\n");

		}

		connectListDB();


	}