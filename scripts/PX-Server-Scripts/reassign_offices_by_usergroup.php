#!/usr/bin/php
<?php

	require_once("/var/www/html/dev/db.inc.php");
	require_once("/var/www/html/dev/utils/db_utils.php");

	connectPXDB();


	$do_sales = false;
	$do_users = true;


	$timeframe = 90; // DAYS TO GO BACK

	$office_groups = array(




		60 => array(

			'DIALER3-HN-AM',
			'DIALER3-HN-PM',
			'DIALER5-HN-AM',
			'DIALER5-HN-PM',
			'DIALER9-HN-AM',
			'DIALER9-HN-PM',
			'TRAINING-SOUTH-AM',
			'TRAINING-SOUTH2-AM',
			'TRAINIG-SOUTH-PM',
			'TRAINING-SOUTH-PM',
			'TRAINING-SOUTH2-PM',
			'MANAGERS-SOUTH',
			'SYSTEM-TAPS',
			'SYSTEM-TAPS-AM',


		),

		62 => array(

			'DIALER9-TG-AM',
			'DIALER9-TG-PM',
			'TRAINING-TG-AM',
			'TRAINING-TG-PM',

			'Verifier-L-T1-TG-AM',
			'Verifier-Live-TG-AM',
			'Verifier-Live-TG-PM',


		),


		70 => array(

			'DIALER4-GT-AM',
			'DIALER4-GT-PM',
			'DIALER6-GT-AM',
			'DIALER6-GT-PM',
			'DIALER7-GT-AM',
			'DIALER7-GT-PM',
			'DIALER9-GT-AM',
			'DIALER9-GT-PM',
			'SYSTEM-TAPS-GT-AM',
			'SYSTEM-TAPS-GT-PM',
			'TRAINIG-GUAT-AM',
			'TRAINING-GUAT-AM',
			'TRAINING-GUAT2-AM',
			'TRAINING-GUAT-PM',
			'TRAINING-GUAT2-PM',
			'MANAGERS-GT',


			'Verifier-GT-AM',
			'Verifier-GT-MGR',
			'Verifier-GT-PM',
			'Verifier-L-RST-GT-AM',
			'Verifier-L-RST-GT-PM',
			'Verifier-L-T22-GT-AM',
			'Verifier-L-T23-GT-AM',
			'Verifier-L-T24-GT-PM',
			'Verifier-L-T25-GT-PM',
			'Verifier-L-T26- GT-PM',
			'Verifier-Live-GT-AM',
			'Verifier-Live-GT-PM',
			'Verifier-L-T26-GT-PM',


		),


		80 => array(

			'DIALER6-LV-AM',
			'DIALER6-LV-PM',
			'SYSTEM-TAPS-LV-AM',
			'SYSTEM-TAPS-LV-PM',
			'TRAINING-LV-AM',
			'TRAINING-LV-PM',
			'MANAGERS-LV',

			'Verifier-Live-LV-AM',
			'Verifier-Live-LV-PM',

		),


		82 => array(
			'DIALER1-AL-AM',
			'DIALER1-AL-PM',
			'DIALER9-AL-AM',
			'DIALER9-AL-PM',
			'SYSTEM-TAPS-AL-AM',
			'SYSTEM-TAPS-AL-PM',
			'TRAINING-AL-AM',
			'TRAINING-AL-PM',
			'MANAGERS-AL',
		),



		90 => array(
//
//			'SYSTEM-TAPS',
//			'SYSTEM-TAPS-AM',

		),


		92 => array(

			'92A-TAP',
		),


		94 => array(

			'94A-TAP-AM',
			'94A-TAP-PM',

			'Verifier-Live-94-AM',
			'Verifier-Live-94-PM',


		),



		98 => array(

			'98A-TAP',

			'Verifier-Live-98',
			'Verifier-Live-98-PM',


		),





	);







/************************/

	$start_time = time() - ($timeframe * 86400);


	$res = query("SELECT * FROM vici_clusters WHERE status='enabled' ",1);
	$clusters = array();
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		$clusters[$row['id']] = $row;

	}




/************************/




	foreach($office_groups as $office_id => $group_arr){

		echo "Processing Office $office_id...\n";

		$user_group_str = '';
		$x=0;
		foreach($group_arr as $user_group){

			$user_group_str .= ($x++ > 0)?",":'';

			$user_group_str .= "'".mysqli_real_escape_string($_SESSION['db'], $user_group)."'";

		} // END foreach($group array)


		// SKIP ONES WITHOUT GROUPS
		if($x == 0)continue;


		if($do_sales){

			echo "Processing Sales...\n";
			$sql = "UPDATE `sales` SET `office`='$office_id' WHERE call_group IN (".$user_group_str.") AND sale_time > '$start_time'";
			execSQL($sql);
//			//echo $sql."\n";
//
			echo "Processing Transfers...\n";
			$sql = "UPDATE `transfers` SET `office`='$office_id' WHERE call_group IN (".$user_group_str.") AND xfer_time > '$start_time'";
			execSQL($sql);
		//	echo $sql."\n";

//			echo "Processing Lead Tracking...\n";
//			$sql = "UPDATE `lead_tracking` SET `office`='$office_id' WHERE user_group IN (".$user_group_str.")";
//			execSQL($sql);
		//	echo $sql."\n";
		}



		if($do_users){

			echo "Processing Users...\n";
			$sql = "UPDATE `users` SET `office`='$office_id' WHERE user_group IN (".$user_group_str.")";
			echo execSQL($sql)."\n";
			//echo $sql."\n";

			echo "Processing User Groups...\n";
			$sql = "UPDATE `user_groups` SET `office`='$office_id' WHERE user_group IN (".$user_group_str.")";
			echo execSQL($sql)."\n";
			//echo $sql."\n";

			echo "Processing User Groups Master...\n";
			$sql = "UPDATE `user_groups_master` SET `office`='$office_id' WHERE user_group IN (".$user_group_str.")";
			echo execSQL($sql)."\n";
			//echo $sql."\n";

			echo "Processing User Group Translations...\n";
			$sql = "UPDATE `user_group_translations` SET `office`='$office_id' WHERE group_name IN (".$user_group_str.")";
			echo execSQL($sql)."\n";
			//echo $sql."\n";


			// CONNECT TO VICI AND UPDATE IT AS WELL
			foreach($clusters as $cluster_id=>$cluster){

				// LOCATE WHICH DB INDEX IT IS
				$dbidx = getClusterIndex($cluster_id);

				// CONNECT TO VICIDIAL DB
				connectViciDB($dbidx);

				echo "Processing Vici Cluster '".$cluster['name']."' User groups...\n";
				$sql = "UPDATE `vicidial_user_groups` SET `office`='$office_id' WHERE user_group IN (".$user_group_str.")";
				echo $sql."\n";
				echo execSQL($sql)."\n";
				//echo $sql."\n";
			}

			//echo "\n";
			// RECONNECT BACK TO PX DB WHEN FINISHED
			connectPXDB();
		}




	} // END foreach($office/groups array)




















	echo "Done.\n";


