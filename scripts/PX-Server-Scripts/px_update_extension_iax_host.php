#!/usr/bin/php
<?php

	$base_dir = "/var/www/html/reports/";
	
	include_once($base_dir."site_config.php");
	include_once($base_dir."db.inc.php");
	include_once($base_dir."utils/db_utils.php");
	
	connectPXDB();
	
	
	
	$res = query("SELECT * FROM vici_clusters WHERE status='enabled' ",1);
	$clusters = array();
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
		
		$clusters[$row['id']] = $row;
		
	}
	
	$tcnt =0;
	// LOOP THROUGH STACK OF VICIDIAL SERVERS
	foreach($clusters as $cluster_id => $vicirow ){
		
		## GET THE PX SERVER ID's FOR THIS CLUSTER
		$pxservers = fetchAllAssoc("SELECT id FROM `servers` WHERE cluster_id='$cluster_id'");
		$serverstr='';
		$x=0;
		foreach($pxservers as $row){
			if($x++ > 0)$serverstr .= ",";
			$serverstr .= $row['id'];
		}
		
		if($x == 0){
			
			echo "ERROR: Cluster not attached to any PX servers - Vici Cluster #".$cluster_id." - ".$vicirow['name'].' - '.$vicirow['ip_address']."\n";
			continue;
			
		}
		
		
		echo "Processing Vici Cluster #".$cluster_id." - ".$vicirow['name'].' - '.$vicirow['ip_address']."...<br />\n";
		
		// LOCATE WHICH DB INDEX IT IS
		$dbidx = getClusterIndex($cluster_id);
		
		// CONNECT TO VICIDIAL DB
		connectViciDB($dbidx);

		$phones = fetchAllAssoc("SELECT `server_ip`, `extension` FROM `phones` WHERE `status`='ACTIVE'");
		
		connectPXDB();
		
		foreach($phones as $phone){
			
			$ext = $phone['extension'];
			
			if(!is_numeric($ext))continue;
			
			$sql = "UPDATE `extensions` SET iax_host='".mysqli_real_escape_string($_SESSION['db'], $phone['server_ip'])."' ".
					" WHERE `number`='".mysqli_real_escape_string($_SESSION['db'], $ext)."' ".
					" AND server_id IN ($serverstr)";
			$tcnt += execSQL($sql);
			//echo $sql."\n";
		}

		

		
	}
	
	
	echo "DONE! Total phones updated $tcnt\n";