#!/usr/bin/php
<?

	$basedir = "/var/www/dev/";

	include_once($basedir."db.inc.php");
	include_once($basedir."utils/microtime.php");
	include_once($basedir."utils/format_phone.php");
	include_once($basedir."utils/db_utils.php");



	$cluster_index = 2; // REFER TO site_config.php in the $basedir


	// FIRST TIME IT WAS EVER USED
	//$stime = mktime(0,0,0, 7, 20,2015);
	$stime = mktime(0,0,0, 1,14,2017);
	$etime = $stime + 86399;


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

	print_r($verifier_list);


	$output = "";
	$nl = "\r\n";
	$sep = "\t";

	$mobile_designator = "-M";


connectViciDB($cluster_index); // CONNECT COLD1



	foreach($fix_array as $row){

		//list($call_group, $office) = query("SELECT user_group, office FROM users WHERE id='".$row['user_id']."' ");

		// SKIP BLANK RECORDS
		if(!$row['phone_num']){
			continue;
		}




		list($real_phone) = queryROW("SELECT phone_number FROM vicidial_list WHERE last_name='".addslashes($row['last_name'])."' AND postal_code='".$row['zip_code']."'");



		$output .= $row['phone_num'].' (REAL: '.$real_phone.')'.$sep;
		$output .= $row['agent_username'].$sep;
		$output .= $row['agent_name'].$sep;

		$date = date("m/d/Y", $row['time']);
		$time = date("g:ia", $row['time']);

		$output .= $date.$sep;
		$output .= $date.$sep;
		$output .= $time.$sep;


		$output .= $row['last_name'].$sep;
		$output .= $row['first_name'].$sep;

		// NAMS FORMAT - 2 BLANKS
		$output .= $sep; // SALUTATION
		$output .= $sep; // COMPANY

		// PERSON CONTACTED
		$output .= $row['first_name'].$sep;


		$output .= $row['address1'].$sep;
		$output .= $row['address2'].$sep;
		$output .= $row['city'].$sep;
		$output .= $row['state'].$sep;
		$output .= $row['zip_code'].$sep;


		// NAMS FORMAT - 2 BLANKS
		$output .= $sep;  // SOURCE
		$output .= $sep;  // RECTYPE (Renew Code or List Code)  Must start with C S or T


		// CAMPAIGN
		$output .= $row['campaign'].$sep;

		// NAMS FORMAT - 2 BLANKS
		$output .= $sep; // LIST ID (can be used as optional ID field, but talk to nams before using!)


		// MOB - MOBILE DESIGNATION
		if(endsWith($row['campaign_code'], $mobile_designator)){
			$output .= "MOB".$sep; // TYPE SALE
		}else{
			$output .= $sep; // TYPE SALE
		}






		// SALE AMOUNT
		$output .= $row['amount'].$sep;


		// NAMS FORMAT - 4 BLANKS
		$output .= $sep; // SIZE CODE
		$output .= $sep; // NUMBER (ticket/decal)
		$output .= $sep; // DELIVERY (pickup, mail, other)
		$output .= $sep; // SPEC INSTRUCTIONS

		// VERIFIER
		$output .= $row['verifier_username'].$sep;

		// OFFICE
		$output .= "90".$sep; // 0 FOR NO LOCATION



		// NAMS FORMAT - 7 more BLANKS
		$output .= $sep; // PAYMENT TYPE(CC, Credit, CK, Check, PD, PAID, Decline)
		$output .= $sep; // ETS FIELD
		$output .= $sep; // ETS FIELD
		$output .= $sep; // ETS FIELD
		$output .= $sep; // ETS FIELD
		$output .= $sep; // ETS FIELD
		$output .= $sep; // PREVIOUS NAMS invoice number

		$output .= $nl; // END NEW LINE


		if(!$campaign_totals[$row['campaign']]){
			$campaign_totals[$row['campaign']] = array();
			$campaign_totals[$row['campaign']]['total'] = 0;
			$campaign_totals[$row['campaign']]['count'] = 0;
		}

		$campaign_totals[$row['campaign']]['total'] += $row['amount'];
		$campaign_totals[$row['campaign']]['count'] ++;



	}


	echo "\n".$output."\n\n";

	print_r($campaign_totals);



	echo "Completed, $cnt total broken sales.\n";
