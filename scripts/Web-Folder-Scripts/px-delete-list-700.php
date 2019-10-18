#!/usr/bin/php
<?php
/**
 * EXPORT PX's CAMPAIGN SPECIFIC DNC TO EVERY VICI CLUSTER
 * Written By: Jonathan Will
 */
	$base_dir = "/var/www/html/staging/";


	include_once($base_dir."site_config.php");
	include_once($base_dir."db.inc.php");

	include_once($base_dir."util/db_utils.php");
	include_once($base_dir."util/microtime.php");


	// CONNECT PX DB FIRST
	connectPXDB();


	/**
	 * CONNECT TO COLD CLUSTERS AND PUSH NULL-CAMPAIGN DNC AND THE PER-CAMPAIGN DNC
	 * (taps will be done seperately,below this stuff)
	 */
	$res = query("SELECT * FROM vici_clusters WHERE `status`='enabled' ".
				(($cold_only_mode == true)?" AND `cluster_type` IN ('cold','coldtaps','all')":"").

			//	" AND id='1' ".

				" ORDER BY `name` ASC",1);
	$clusters = array();
	while($row = mysqli_fetch_array($res)){

		$clusters[$row['id']] = $row;

	}

	// LOOP THROUGH STACK OF VICIDIAL SERVERS
	foreach($clusters as $cluster_id => $vicidb ){

		$all_campaigns = array();

		$vici_cluster_id = $cluster_id;

		// LOCATE WHICH DB INDEX IT IS
		$dbidx = getClusterIndex($cluster_id);


		if($dbidx < 0){
			echo "ERROR WITH CLUSTER ID#".$cluster_id." - ".$vicidb['ip_address']." - Cannot locate cluster on site_config/cluster stack, SKIPPING\n";
			continue;
		}

		// CONNECT TO VICIDIAL DB
		connectViciDB($dbidx);


		echo "Connected to CLUSTER ID#".$cluster_id." - ".$vicidb['ip_address']."\n";


		execSQL("DELETE FROM vicidial_lists WHERE list_id LIKE '700%' AND list_name LIKE 'AnsMach-%'");
		execSQL("DELETE FROM vicidial_lists WHERE list_id LIKE '710%' AND list_name LIKE 'AnsMach-%'");
	}// END FOREACH(vici cluster)



