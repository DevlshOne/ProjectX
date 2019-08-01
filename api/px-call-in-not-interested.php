<?php
/**
* PX - Call in - Not interested
* Written By: Jonathan Will
*
* A tool to mark a phone number as not interested, on all available dialers
*/

	$base_dir = "/var/www/html/staging/";

	$dispo_code = "NI";

	$ignore_dispo_codes = "'SALE','SALECC','PAIDCC','XFER','DNC','VDNC','VOID'";


	$use_log_file = true;
	$log_file_path = "/var/log/px-call-in-not-interested.log";

	

	if(!isset($_REQUEST['phone'])){
		die("Error: number not provided.");
	}

	$phone = preg_replace("/[^0-9]/", '', $_REQUEST['phone']);

	if(strlen($phone) < 10){
		die("Error: number not long enough");
	}


	// NETWORK/IP ADDRESS RESTRICTIONS?


	// AUTH CODE/SECURITY RESTRICTIONS?

	include_once($base_dir."site_config.php");
	include_once($base_dir."db.inc.php");
	include_once($base_dir."utils/db_utils.php");


	
	




	// CONNECT PX DB
	connectPXDB();


	$res = query("SELECT * FROM vici_clusters WHERE status='enabled' ",1);
	$clusters = array();
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		$clusters[$row['id']] = $row;

	}

	


	echo date("g:i:sa m/d/Y")." - Marking Phone # ".$phone." as ".$dispo_code." on ALL clusters.<br />\n";

	$cnt = 0;

	// LOOP THROUGH STACK OF VICIDIAL SERVERS
	foreach($clusters as $cluster_id => $vicirow ){

		echo "Processing Vici Cluster #".$cluster_id." - ".$vicirow['name'].' - '.$vicirow['ip_address']."...<br />\n";

		// LOCATE WHICH DB INDEX IT IS
		$dbidx = getClusterIndex($cluster_id);

		// CONNECT TO VICIDIAL DB
		connectViciDB($dbidx);


		// MARK ALL LEADS TO THE PROPER DISPO FOR THE SPECIFIED PHONE NUMBER, IGNORING ANY DISPOS THAT SHOULDN'T BE TOUCHED
		$cnt += execSQL("UPDATE `vicidial_list` SET `status`='".mysqli_real_escape_string($_SESSION['db'],$dispo_code)."' ".
				" WHERE `phone_number`='$phone' ".
				" AND `status` NOT IN($ignore_dispo_codes) ");


	}


	if($use_log_file){
		
		$str = date("g:i:sa m/d/Y")."\t".time()."\t".$phone."\t".$cnt."\n";
		
		file_put_contents($log_file_path, $str, FILE_APPEND);
	}
	
	echo date("g:i:sa m/d/Y")." - DONE, updated $cnt Leads<br />\n";



