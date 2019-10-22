#!/usr/bin/php
<?

	$basedir = "/var/www/html/reports/";

	include_once($basedir."db.inc.php");
	include_once($basedir."util/microtime.php");
	include_once($basedir."util/format_phone.php");
	include_once($basedir."util/db_utils.php");


	$stime = mktime(0,0,0);// TODAY
	//$stime = mktime(0,0,0,1,9,2017);
	//$stime = mktime(0,0,0, 7, 20,2015);


	$etime = $stime + 86399;
	//$etime = mktime(23,59,59, 1, 12, 2017);



	function endsWith($haystack, $needle) {return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);}




	connectPXDB();



	// GET ALL OF THE "SALE" DISPO LEAD TRACKING RECORDS
	$res = query("SELECT * FROM lead_tracking ".
				" WHERE `time` BETWEEN '$stime' AND '$etime' ".
				" AND `dispo`='SALE'"
				, 1);

	$fix_array = array();
	$verifier_list = array();
	$cnt = 0;
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		// SKIP THE ONES THAT HAVE TRANSFER RECORDS AND SALE RECORDS
		list($xfer_id) = queryROW("SELECT id FROM transfers WHERE lead_tracking_id='".$row['id']."' ");
		if($xfer_id > 0){
			continue;
		}

		if(!$verifier_list[$row['verifier_username']]){
			$verifier_list[$row['verifier_username']] = 1;
		}else{
			$verifier_list[$row['verifier_username']]++;
		}

		//print_r($row);



		$fix_array[$cnt] = $row;



		$cnt++;

	}

//	print_r($verifier_list);



//print_r($fix_array);exit;
//	$output = "";
//	$nl = "\r\n";
//	$sep = "\t";
//
//	$mobile_designator = "-M";


//connectViciDB(0); // CONNECT COLD1

	foreach($fix_array as $row){

		list($call_group, $office) = queryROW("SELECT user_group, office FROM users WHERE id='".$row['user_id']."' ");

		// SKIP BLANK RECORDS
		if(!$row['phone_num']){
			continue;
		}


echo "ID: #".$row['id']."\n";
echo "Agent cluster ID: ".$row['vici_cluster_id']."\n";
echo "Call group: ".$call_group."\n";

		$dat = array();
		$dat['lead_tracking_id'] = $row['id'];
		$dat['campaign_id'] = $row['campaign_id'];
		$dat['xfer_time'] = time();
		$dat['sale_time'] = time();
		$dat['agent_username'] = $row['agent_username'];
		$dat['agent_lead_id'] = $row['lead_id'];

		$dat['agent_cluster_id'] = $row['vici_cluster_id'];
		$dat['agent_amount'] = $dat['verifier_amount'] = $row['amount'];

		$dat['verifier_username'] = $row['verifier_username'];
		$dat['verifier_lead_id'] = $row['verifier_lead_id'];
		$dat['verifier_cluster_id'] = $row['verifier_vici_cluster_id'];

		$dat['verifier_dispo'] = $row['dispo'];// SHOULD BE SALE ANYWAY
		$dat['call_group'] = $call_group;


		// ADD TEH XFER FIRST
		aadd($dat, "transfers");
		$transfer_id = mysqli_insert_id($_SESSION['db']);

		$xfer = querySQL("SELECT * FROM `transfers` WHERE id='".intval($transfer_id)."' ");


// CREATE SALE RECORD
			$dat = array();
			$dat['lead_tracking_id'] = $row['id'];
			$dat['transfer_id'] = $xfer['id'];
			$dat['agent_lead_id'] = $row['lead_id'];
			$dat['agent_cluster_id'] = $row['vici_cluster_id'];
			$dat['verifier_lead_id'] = $row['verifier_lead_id'];
			$dat['verifier_cluster_id'] = $row['verifier_vici_cluster_id'];
			$dat['campaign_id'] = $row['campaign_id'];


			$dat['sale_time'] = $xfer['sale_time'];

			//$dat['vici_last_call_time'] = '';


			// sale_datetime not really used atm
			$dat['phone'] = $row['phone_num'];

			$dat['agent_username'] = $row['agent_username'];
			$dat['agent_name'] = $row['agent_username'];//$agent_user['first_name'].(($agent_user['last_name'])?' '.$agent_user['last_name']:'');

			$dat['verifier_username'] = $row['verifier_username'];
			$dat['verifier_name'] = $row['verifier_username'];//$verifier_user['first_name'].(($verifier_user['last_name'])?' '.$verifier_user['last_name']:'');



			$dat['first_name'] = $row['first_name'];
			$dat['last_name'] = $row['last_name'];
			$dat['contact'] = ($row['contact'])?$row['contact']:$row['first_name'];


			$dat['address1'] = $row['address1'];
			$dat['address2'] = $row['address2'];
			$dat['city'] = $row['city'];
			$dat['state'] = $row['state'];
			$dat['zip'] = $row['zip_code'];
			$dat['campaign'] = $row['campaign'];
			$dat['campaign_code'] = $row['campaign_code'];
			$dat['amount'] = $xfer['verifier_amount'];
			$dat['office'] = $office;//$agent_user['office'];


			//$dat['call_group'] = $agent_user['user_group'];

			$dat['call_group'] = $call_group;//lookupUserGroup($agent_user['id'], $row['vici_cluster_id']);

			$dat['comments'] = $row['comments'];

			//$dat['server_ip'] = $row['city'];

//print_R($dat);
			$sale_id = aadd($dat, 'sales');
			echo "Added sale #".$sale_id."\n";

			$cnt++;
		//list($real_phone) = queryROW("SELECT phone_number FROM vicidial_list WHERE last_name='".addslashes($row['last_name'])."' AND postal_code='".$row['zip_code']."'");

//
//
//		$output .= $row['phone_num'].$sep;
//		$output .= $row['agent_username'].$sep;
//		$output .= $row['agent_name'].$sep;
//
//		$date = date("m/d/Y", $row['time']);
//		$time = date("g:ia", $row['time']);
//
//		$output .= $date.$sep;
//		$output .= $date.$sep;
//		$output .= $time.$sep;
//
//
//		$output .= $row['last_name'].$sep;
//		$output .= $row['first_name'].$sep;
//
//		// NAMS FORMAT - 2 BLANKS
//		$output .= $sep; // SALUTATION
//		$output .= $sep; // COMPANY
//
//		// PERSON CONTACTED
//		$output .= $row['first_name'].$sep;
//
//
//		$output .= $row['address1'].$sep;
//		$output .= $row['address2'].$sep;
//		$output .= $row['city'].$sep;
//		$output .= $row['state'].$sep;
//		$output .= $row['zip_code'].$sep;
//
//
//		// NAMS FORMAT - 2 BLANKS
//		$output .= $sep;  // SOURCE
//		$output .= $sep;  // RECTYPE (Renew Code or List Code)  Must start with C S or T
//
//
//		// CAMPAIGN
//		$output .= $row['campaign'].$sep;
//
//		// NAMS FORMAT - 2 BLANKS
//		$output .= $sep; // LIST ID (can be used as optional ID field, but talk to nams before using!)
//
//
//		// MOB - MOBILE DESIGNATION
//		if(endsWith($row['campaign_code'], $mobile_designator)){
//			$output .= "MOB".$sep; // TYPE SALE
//		}else{
//			$output .= $sep; // TYPE SALE
//		}
//
//
//
//
//
//
//		// SALE AMOUNT
//		$output .= $row['amount'].$sep;
//
//
//		// NAMS FORMAT - 4 BLANKS
//		$output .= $sep; // SIZE CODE
//		$output .= $sep; // NUMBER (ticket/decal)
//		$output .= $sep; // DELIVERY (pickup, mail, other)
//		$output .= $sep; // SPEC INSTRUCTIONS
//
//		// VERIFIER
//		$output .= $row['verifier_username'].$sep;
//
//		// OFFICE
//		$output .= "90".$sep; // 0 FOR NO LOCATION
//
//
//
//		// NAMS FORMAT - 7 more BLANKS
//		$output .= $sep; // PAYMENT TYPE(CC, Credit, CK, Check, PD, PAID, Decline)
//		$output .= $sep; // ETS FIELD
//		$output .= $sep; // ETS FIELD
//		$output .= $sep; // ETS FIELD
//		$output .= $sep; // ETS FIELD
//		$output .= $sep; // ETS FIELD
//		$output .= $sep; // PREVIOUS NAMS invoice number
//
//		$output .= $nl; // END NEW LINE
//
//
//		if(!$campaign_totals[$row['campaign']]){
//			$campaign_totals[$row['campaign']] = array();
//			$campaign_totals[$row['campaign']]['total'] = 0;
//			$campaign_totals[$row['campaign']]['count'] = 0;
//		}
//
//		$campaign_totals[$row['campaign']]['total'] += $row['amount'];
//		$campaign_totals[$row['campaign']]['count'] ++;



	}


	//echo "\n".$output."\n\n";

	//print_r($campaign_totals);



	echo "Completed, $cnt total broken sales.\n";
