#!/usr/bin/php
<?php

	$base_dir = "/var/www/";
	$tmpdir = "/var/log/nams_export/";

	include_once($base_dir."site_config.php");
	include_once($base_dir."db.inc.php");


	global $stime, $etime;
	global $campaign_array;
	global $offices;

	global $campaign_totals;


	// GRAB TODAY TIMEFRAME
	$stime = mktime(0,0,0);
	$etime = mktime(23,59,59);


	if($argv[1] && ($tmptime = strtotime($argv[1])) > 0){

		$stime = mktime(0,0,0, date("m", $tmptime), date("d", $tmptime), date("Y", $tmptime));
		$etime = mktime(23,59,59, date("m", $stime), date("d", $stime), date("Y", $stime));

	}

	// tESTING TIME
	//$stime = mktime(0,0,0, 5, 13, 2015);
	//$etime = mktime(23,59,59, 5, 13, 2015);



	function endsWith($haystack, $needle) {return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);}



	echo "NAMS Export STARTING - ".date("H:i:s m/d/Y")."\n";




	echo " - Setting Timeframe to ".date("H:i:s m/d/Y", $stime)." - ".date("H:i:s m/d/Y", $etime)."\n";





	$where = " WHERE sale_time BETWEEN '$stime' AND '$etime' AND verifier_cluster_id='9' ";




	/**
	 * GATHER THE LIST OF CAMPAIGNS
	 */
	$campaign_array = array();
	$res = query("SELECT DISTINCT(campaign) AS campaign FROM sales $where ORDER BY campaign ASC",1 );
	while($row = mysql_fetch_array($res, MYSQL_ASSOC)){
		$campaign_array[] = $row['campaign'];
	}


	/**
	 * Gather list of OFFICES
	 */
	$offices = array();
	$res = query("SELECT DISTINCT(office) AS office FROM sales $where",1 );
	while($row = mysql_fetch_array($res, MYSQL_ASSOC)){
		$offices[] = $row['office'];
	}


	$campaign_totals = array();


	/**
	* GET ALL SALES
	*/


	function generateOfficeSales($where, $office){

		global $campaign_totals;

		$where .= " AND office='".addslashes($office)."' ";

		$output = "";
		$nl = "\r\n";
		$sep = "\t";

		$mobile_designator = "-M";

		$res = query("SELECT * FROM sales $where ORDER BY sale_time ASC",1 );

		if(mysql_num_rows($res) == 0){

			return null;

		}

		while($row = mysql_fetch_array($res, MYSQL_ASSOC)){

			// PHONE NUMBER - AGENT USER - AGENT NAME - DATE - DATE - TIME - LAST NAME - FIRST NAME - PERSON CONTACTED - ADDRESS1 - ADDRESS2 - CITY - STATE - ZIP - CAMPAIGN - AMOUNT - VERIFIER - OFFICE

			$output .= $row['phone'].$sep;
			$output .= $row['agent_username'].$sep;
			$output .= $row['agent_name'].$sep;

			// NEEDS FIXED! USE VICI LAST LOCAL CALL TIME


			if($row['vici_last_call_time']){

				list($date, $time) = preg_split("/\s/", $row['vici_last_call_time'], 2);

				// REFORMAT DATE FOR NAMS SICK PLEASURE


				$date = date("m/d/Y", strtotime($row['vici_last_call_time']));
				$time = date("g:ia", strtotime($row['vici_last_call_time']));

			// EMERGENCY FALLBACK
			}else{

				$date = date("m/d/Y", $row['sale_time']);
				$time = date("g:ia", $row['sale_time']);
			}

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
			$output .= $row['zip'].$sep;


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


/**90 -> R0
94 -> R4
M0 -> N0
M4 -> N4*/

			$office_code = "";
			if(endsWith($row['campaign_code'], $mobile_designator)){

				$tmp = $row['office'];

				$tmp[0] = 'M';


				//$output .= $tmp.$sep; // 0 FOR NO LOCATION
				$office_code = $tmp;
			}else{
				//$output .= $row['office'].$sep; // 0 FOR NO LOCATION

				$office_code = $row['office'];
			}


			switch($office_code){
			default:
				$output .= $office_code.$sep;
				break;
			case '90':
				$output .= 'R0'.$sep;
				break;
			case '94':
				$output .= 'R4'.$sep;
				break;
			case 'M0':
				$output .= 'N0'.$sep;
				break;
			case 'M4':
				$output .= 'N4'.$sep;
				break;
			}



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


		} // END WHILE





		//echo $output;

		return $output;
	}


	function generateCampaignTotalHTML($office){




		global $stime, $etime;
		global $campaign_totals;



		if(count($campaign_totals) < 1){
			return null;
		}

		ob_start();
		ob_clean();

		?><html>
		<head>
			<title>Campaign Totals - <?=date("m/d/Y", $stime)?></title>
		</head>
		<body>


		<table border="0" align="center">
		<tr>
			<th><h1>Summary Report</h1></th>
		</tr>
		<tr>
			<th>CCI Office: <?=$office?></th>
		</tr>
		<tr>
			<th>Filename: <?=getFilename($office)?></th>
		</tr>
		</table>

		<br />

		<table border="1" align="center">
		<tr>
			<th align="left">Campaign</th>
			<th align="right"># of Deals</th>
			<th align="right">Total</th>
		</tr><?

		$totalcount=0;
		$totalamount=0;
		foreach($campaign_totals as $code=>$data){


			?><tr>
				<td><?=$code?></td>
				<td align="right"><?=number_format($data['count'])?></td>
				<td align="right">$<?=number_format($data['total'])?></td>
			</tr><?

			$totalcount += $data['count'];
			$totalamount += $data['total'];
		}

		?><tr>
			<th>OFFICE TOTAL:</th>
			<th align="right"><?=number_format($totalcount)?></th>
			<th align="right">$<?=number_format($totalamount)?></th>
		</tr>
		</table>

		</body>
		</html><?

		$html = ob_get_contents();

		ob_end_clean();

		return $html;
	}





	function getFilename($office){
		global $stime;
		return $office."_".date("m-d-Y", $stime)."-PX.csv";
	}

	function getHTMLFilename($office){
		global $stime;
		return $office."_".date("m-d-Y", $stime)."-PX-totals.html";
	}

	function getZIPFilename($office){
		global $stime;
		return $office."_".date("m-d-Y", $stime)."-PX.zip";
	}



//print_r($offices);


	foreach($offices as $office){




	// GENERATE THE FULL TSV REPORT

		// GENERATE TEH FILENAME
		$output_filename = getFilename($office);

		// GENERATE TEH REPORT
		$data = generateOfficeSales($where, $office);


		if($data == null){

			echo "Skipping office $office: no sales.\n";
			continue;
		}

		$tsv_filename = $tmpdir.$output_filename;

		// WRITE THE FILE
		$written = file_put_contents($tsv_filename, $data);

		// PISS PANTS IF IT DIDNT WORK RIGHT
		if($written != strlen($data)){
			echo "Office $office CSV didn't write enough data!\n";
		}



	// GENERATE HTML TOTALS

		// GENERATE FILENAME
		$output_filename = getHTMLFilename($office);

		// USE CAMPAIGN TOTAL ARRAY TO MAKE HTML REPORT
		$data = generateCampaignTotalHTML($office);

		$html_filename = $tmpdir.$output_filename;

		// WRITE IT TO FILE
		$written = file_put_contents($html_filename, $data);

		// SHIT PANTS IF IT DIDN'T WRITE RIGHT
		if($written != strlen($data)){
			echo "Office $office HTML didn't write enough data!\n";
		}



	// ZIP THEM TOGETHER!

		$output_filename = $tmpdir.getZIPFilename($office);

		$zip = new ZipArchive();
		$zip->open($output_filename, ZIPARCHIVE::CREATE);


		$zip->addFile($tsv_filename, getFilename($office));
		$zip->addFile($html_filename, getHTMLFilename($office));

		$zip->close();



		// DELETE THE OTHER FILES

		unlink($tsv_filename);
		unlink($html_filename);


	}











