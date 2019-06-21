#!/usr/bin/php
<?php
	global $code_conversion_arr;
	global $email_results_to;
	global $email_html;

	global $stime;
	global $etime;

	$email_html = null;

	$base_dir = "/var/www/html/dev/";
	$tmpdir = "/var/log/nams_export/";


	$email_results_to = "support@advancedtci.com";


	$send_email_results = true;






	function sendResultEmail(){

		// INCLUDE PEAR FUNCTIONS
		include_once 'Mail.php';
		include_once 'Mail/mime.php' ;

		global $email_results_to;
		global $stime;
		global $email_html;

		$subject = "NAMS Sales Generation Results - ".date("m/d/Y", $stime);

		$headers   = array(
						"From"		=> "PX NAMS Sales <support@advancedtci.com>",
						"Subject"	=> $subject,
						"X-Mailer"	=> "NAMS Sales Exporter",
						"Reply-To"	=> "PX NAMS Sales <support@advancedtci.com>"
					);

		$mime = new Mail_mime(array('eol' => "\n"));

		//$mime->setTXTBody($textdata);

		$mime->setHTMLBody($email_html);

		$mail_body = $mime->get();
		$mail_header=$mime->headers($headers);

		$mail =& Mail::factory('mail');

		if($mail->send($email_results_to, $mail_header, $mail_body) != TRUE){

			echo date("H:i:s m/d/Y")." - ERROR: Mail::send() call failed sending to ".$email_results_to;

		}else{
			echo date("H:i:s m/d/Y")." - Successfully emailed NAMS RESULTS for ".date("m/d/Y", $stime)." to $email_results_to.\n";
		}



	}

//	$code_conversion_arr = array(
//
//		// ACTIVE 9-1-16
//		'BCOF', 'ABCF', 'ADVF', 'NCA','BCAF', 'NPTA', 'VFBC',
//
//		// Switch on 09-12-16
//		'UBCF',
//
//		// Switch on 09-15-16
//		'USVFF', 'HTV', 'MFOA', 'IUPA',
//
//		// Switch on 09-20-16
//		'ACS', 'AVF', 'FFCF', 'VETN',
//
// 		// Switch on 09-21-16
// 		'BCRSF', 'USFA', 'VEBB',
//
//
//	);



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
	//$stime = mktime(0,0,0, 12, 15, 2016);
	//$etime = mktime(23,59,59, 12, 15, 2016);


	function TSVFilter($input){
		//return preg_replace("/\t/", " ", $input, -1);


		return generalFilter($input);
	}


	function generalFilter($input){
		return preg_replace('/[^a-zA-Z0-9.,-=_ $@#^&:;\'"\?]/','', $input, -1);
	}

	function endsWith($haystack, $needle) {return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);}



	echo "NAMS Export STARTING - ".date("H:i:s m/d/Y")."\n";




	echo " - Setting Timeframe to ".date("H:i:s m/d/Y", $stime)." - ".date("H:i:s m/d/Y", $etime)."\n";





	$where = " WHERE sale_time BETWEEN '$stime' AND '$etime' ".
		" AND ((verifier_cluster_id=9 AND `is_paid`='no') OR (agent_cluster_id=3 AND office='11'))  ";







	/**
	 * GATHER THE LIST OF CAMPAIGNS
	 */
	$campaign_array = array();
	$res = query("SELECT DISTINCT(campaign) AS campaign FROM sales $where ORDER BY campaign ASC",1 );
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
		$campaign_array[] = $row['campaign'];
	}


	/**
	 * Gather list of OFFICES
	 */
	$offices = array();
	$res = query("SELECT DISTINCT(office) AS office FROM sales $where ORDER BY `office` ASC",1 );
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
		$offices[] = $row['office'];
	}


	$campaign_totals = array();


	/**
	* GET ALL SALES
	*/


	function generateOfficeSales($where, $office){

		global $campaign_totals;
		global $code_conversion_arr;
		global $email_html;


		$where .= " AND office='".addslashes($office)."' ";

		$output = "";
		$nl = "\r\n";
		$sep = "\t";

		$mobile_designator = "-M";

		$res = query("SELECT * FROM sales $where ORDER BY sale_time ASC",1 );

		if(mysqli_num_rows($res) == 0){

			$email_html .= "Office $office: Skipped, no sales<br />\n";

			return null;

		}


		$office_total = 0;
		$office_count = 0;

		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){



			$lead = querySQL("SELECT * FROM lead_tracking WHERE id='".$row['lead_tracking_id']."'");

			// PHONE NUMBER - AGENT USER - AGENT NAME - DATE - DATE - TIME - LAST NAME - FIRST NAME - PERSON CONTACTED - ADDRESS1 - ADDRESS2 - CITY - STATE - ZIP - CAMPAIGN - AMOUNT - VERIFIER - OFFICE

			$output .= TSVFilter($row['phone']).$sep;
			$output .= TSVFilter($row['agent_username']).$sep;
			$output .= TSVFilter($row['agent_name']).$sep;

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

			$output .= TSVFilter($date).$sep;
			$output .= TSVFilter($date).$sep;
			$output .= TSVFilter($time).$sep;


			$output .= TSVFilter($row['last_name']).$sep;
			$output .= TSVFilter($row['first_name']).$sep;

			// NAMS FORMAT - 2 BLANKS
			$output .= $sep; // SALUTATION

			//$output .= $sep; // COMPANY
			$output .= TSVFilter($row['company']).$sep;



			// PERSON CONTACTED
			$output .= TSVFilter($row['first_name']).$sep;


			$output .= TSVFilter($row['address1']).$sep;
			$output .= TSVFilter($row['address2']).$sep;
			$output .= TSVFilter($row['city']).$sep;
			$output .= TSVFilter($row['state']).$sep;
			$output .= TSVFilter($row['zip']).$sep;


			// NAMS FORMAT - 2 BLANKS
			$output .= $sep;  // SOURCE
			$output .= $sep;  // RECTYPE (Renew Code or List Code)  Must start with C S or T


			// CAMPAIGN
			$output .= TSVFilter($row['campaign']).$sep;

			// NAMS FORMAT - 2 BLANKS
			$output .= $sep; // LIST ID (can be used as optional ID field, but talk to nams before using!)


			// MOB - MOBILE DESIGNATION
			if(endsWith($row['campaign_code'], $mobile_designator)){
				$output .= "MOB".$sep; // TYPE SALE
			}else{
				$output .= $sep; // TYPE SALE
			}






			// SALE AMOUNT
			$output .= TSVFilter($row['amount']).$sep;


			// NAMS FORMAT - 4 BLANKS

			if($row['package']){

				// EXTRACT PACKAGE CODE (last element in the array, when split by colons)
				$pkgarr = preg_split("/:/", $row['package']);

				$output .= TSVFilter($pkgarr[count($pkgarr)-1]).$sep;

			}else{
				$output .= $sep; // SIZE CODE
			}

			$output .= $sep; // NUMBER (ticket/decal)
			$output .= $sep; // DELIVERY (pickup, mail, other)
			$output .= $sep; // SPEC INSTRUCTIONS

			// VERIFIER
			$output .= TSVFilter($row['verifier_username']).$sep;

/**OLD WAY
 * 90 -> R0
94 -> R4
M0 -> N0
M4 -> N4*/

//			switch($row['office']){
//			default:
				// OFFICE
				$output .= $row['office'].$sep;

//				break;
//			case '11':
//
//				$output .= "BMAG".$sep;
//				break;
//			}

//
//			$office_code = "";
//			if(endsWith($row['campaign_code'], $mobile_designator)){
//
//				$tmp = $row['office'];
//
//				$tmp[0] = 'M';
//
//
//				//$output .= $tmp.$sep; // 0 FOR NO LOCATION
//				$office_code = $tmp;
//			}else{
//				//$output .= $row['office'].$sep; // 0 FOR NO LOCATION
//
//				$office_code = $row['office'];
//			}
//
//
//
//
//
//				switch($office_code){
//				default:
//					$output .= $office_code.$sep;
//					break;
//				case '90':
//					$output .= 'R0'.$sep;
//					break;
//				case '92':
//					$output .= 'R2'.$sep;
//					break;
//				case '94':
//					$output .= 'R4'.$sep;
//					break;
//				case '98':
//					$output .= 'R8'.$sep;
//					break;
//				case 'M0':
//					$output .= 'N0'.$sep;
//					break;
//				case 'M2':
//					$output .= 'N2'.$sep;
//					break;
//				case 'M4':
//					$output .= 'N4'.$sep;
//					break;
//				case 'M8':
//					$output .= 'N8'.$sep;
//					break;
//				}




			// NAMS FORMAT - 7 more BLANKS

			switch($row['is_paid']){
			default:
			case 'no':

				// NOT PAID
				$output .= $sep; // PAYMENT TYPE(CC, Credit, CK, Check, PD, PAID, Decline)
				break;

			case 'yes':
			case 'roustedcc':

				$output .= "CREDIT".$sep; // PAYMENT TYPE(CC, Credit, CK, Check, PD, PAID, Decline)
				break;


			}

			$output .= $sep; // ETS FIELD
			$output .= $sep; // ETS FIELD
			$output .= $sep; // ETS FIELD
			$output .= $sep; // ETS FIELD
			$output .= $sep; // ETS FIELD
			$output .= $sep; // PREVIOUS NAMS invoice number


			$output .= TSVFilter($lead['occupation']).$sep;
			$output .= TSVFilter($lead['employer']).$sep;

			$output .= $nl; // END NEW LINE


			if(!$campaign_totals[$row['campaign']]){
				$campaign_totals[$row['campaign']] = array();
				$campaign_totals[$row['campaign']]['total'] = 0;
				$campaign_totals[$row['campaign']]['count'] = 0;
			}

			$office_total += $row['amount'];
			$office_count++;

			$campaign_totals[$row['campaign']]['total'] += $row['amount'];
			$campaign_totals[$row['campaign']]['count'] ++;


		} // END WHILE


	//	$email_html .= "Office $office: ".number_format($office_count)." deals, $".number_format($office_total)."<br />\n";




		//echo $output;

		return $output;
	}


	function generateCampaignTotalHTML($office){




		global $stime, $etime;
		global $campaign_totals;
		global $email_html;



		if(count($campaign_totals) < 1){
			return null;
		}

		ob_start();
		ob_clean();

		?>
		<table border="0" align="center">
		<tr>
			<th><h1>Summary Report</h1></th>
		</tr>
		<tr>
			<th align="left">Office: <?=$office?></th>
		</tr>
		<tr>
			<th align="left">Filename: <?=getFilename($office)?></th>
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

		<?

		$html = ob_get_contents();

		ob_end_clean();


		$email_html .= $html."<br /><br />\n";


		$html = "<html><head><title>Campaign Totals - ".date("m/d/Y", $stime)."</title></head><body>".
				$html.
				"</body></html>";

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


/** START PROCESSING **/


	foreach($offices as $office){




	// GENERATE THE FULL TSV REPORT

		// GENERATE TEH FILENAME
		$output_filename = getFilename($office);


		$campaign_totals = array();



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




	// SEND THE RESULTS EMAIL
	if($send_email_results == true && $email_html != null){

		sendResultEmail();

	}






