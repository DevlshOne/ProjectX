#!/usr/bin/php
<?

	// INCLUDE DATABASE FILE
	include("/var/www/db.inc.php");

	function out($str){	echo date("H:i:s m-d-Y")." - ".$str."\n"; }




	out("Starting problems - info grabber");


	// CONNECT VICI CLUSTERS
	$vici_db_array = array();

	$res = query("SELECT * FROM vici_clusters ".
				" WHERE db_user IS NOT NULL", 1);
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		$vici_db_array[$row['id']] = mysqli_connect(
										$row['ip_address'],
										$row['db_user'],
										$row['db_pass'],
										"asterisk");//$row;
//		mysql_select_db("asterisk", $vici_db_array[$row['id']]);

	}


	out("Loaded ".count($vici_db_array)." VICI databases.");


	if(count($vici_db_array) < 1){

		out("No vici databases, cannot continue.");
		exit;

	}

	// GET THE PROBLEMS THAT NEED UPDATED
	$res = mysqli_query("SELECT * FROM lead_tracking ".
			" WHERE problem='yes' ".
			" AND info_update_attempts < 2 ".
			" AND recording_url IS NULL".
			" ORDER BY vici_cluster_id ASC"
			, $_SESSION['db']);


	$cnt = 0;
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		$dat = array();

		if($row['vici_cluster_id'] > 0){

			$carrier = @mysqli_fetch_array(
							@mysqli_query(
								$vici_db_array[$row['vici_cluster_id']],
								"SELECT channel, server_ip FROM vicidial_carrier_log ".
								"WHERE lead_id='".$row['lead_id']."' AND channel LIKE 'SIP/%' ".
								" ORDER BY uniqueid DESC ".
								" LIMIT 1"
								)
							);
			$recrow = @mysqli_fetch_array(
							@mysqli_query(
								$vici_db_array[$row['vici_cluster_id']],
								"SELECT location FROM recording_log WHERE lead_id='".$row['lead_id']."' ".
								" ORDER BY recording_id DESC ".
								" LIMIT 1"
							)
						);





			if(isset($carrier['channel']) && $carrier['channel'] != '') 	$dat['carrier_channel'] = $carrier['channel'];
			if(isset($carrier['server_ip']) && $carrier['server_ip'] != '')	$dat['server_ip'] = $carrier['server_ip'];
			if(isset($recrow['location']) && $recrow['location'] != '')		$dat['recording_url'] = $recrow['location'];

			out("LTID: ".$row['id']." Lead #".$row['lead_id'].
						(isset($carrier['server_ip'])?" Dialer:".$dat['server_ip']:'').
						(isset($dat['carrier_channel'])?" Channel: ".$dat['carrier_channel']:'').
						(isset($dat['recording_url'])?" RecURL: ".$dat['recording_url']:'').
						" Attempt: ".$row['info_update_attempts']);

		}else{

			out("LTID: ".$row['id']." Lead #".$row['lead_id']." INFO NOT FOUND.");

		}


		if(isset($dat['recording_url']) && $dat['recording_url'] != '' && isset($dat['server_ip']) && $dat['server_ip'] != ''){

		 	$dat['info_update_attempts'] = 2;
		}else{
			$dat['info_update_attempts'] = $row['info_update_attempts'] + 1;
		}





		aedit($row['id'], $dat, 'lead_tracking');

		$cnt++;
//		// QUERY VICI
//		list($channel, $server_ip) = queryROW();




	}


	out("Finished grabbing info. Updated ".number_format($cnt)." records.");

