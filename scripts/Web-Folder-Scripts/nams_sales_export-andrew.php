#!/usr/bin/php
<?php

	$base_dir = "/var/www/";
	//Directory where files will be saved
	$outputdir = "/var/log/nams_export_testing/NEW-EXPORT-TEST-";
    //$outputdir = "/var/log/nams_export/";
	$mobile_designator = "-M";

	include_once($base_dir."site_config.php");
	include_once($base_dir."db.inc.php");

	//$debug = 1; 		//Enables debug output
	$debug =0;

	$sale_data=array();
	$campaign_array=array();
	$sales=array();
	$data=array();


	// GRAB TODAY TIMEFRAME
	$stime = mktime(0,0,0,date("m"),date("d"),date("Y"));
	$etime = mktime(23,59,59,date("m"),date("d"),date("Y"));

	if (isset($argv[1])) if($argv[1] && ($tmptime = strtotime($argv[1])) > 0){

		$stime = mktime(0,0,0, date("m", $tmptime), date("d", $tmptime), date("Y", $tmptime));
		$etime = mktime(23,59,59, date("m", $stime), date("d", $stime), date("Y", $stime));
     }

	// tESTING TIME
	/*
	$stime = mktime(0,0,0, 5, 13, 2015);
	$etime = mktime(23,59,59, 5, 13, 2015);
     */

	echo "NAMS Export STARTING - ".date("H:i:s m/d/Y")."\r\n";
	echo "Setting Timeframe to ".date("H:i:s m/d/Y", $stime)." - ".date("H:i:s m/d/Y", $etime)."\r\n";

	$where = " WHERE sale_time BETWEEN '$stime' AND '$etime' AND verifier_cluster_id='9' ";

	// Retrieve ALL sales date ranges for the selected with the global variables $stime and $etime
	// This was changed from the original script to make it easier to change department codes, offices, etc
	$sale_data = getpxsales($where);

	// Take the sales_date. make department code changes per campaign based on specific dates and passes the data back.
	$sale_data = fix_dapartments($sale_data);

	// Take the raw query data in $sales_data and return everything ready to be saved
	$sales=generate_sales_output_data($sale_data);

	// Take the completed sales data and save it.. TODO Nifty return data on this to show results in html format
	save_sales($sales);

	// All done nothing to see here move along....


function save_sales($sales) {
	global $stime,$etime;
	global $outputdir;
	global $debug;
	//Define variables
	$filenames=array();
	$csv_filename="";
	$html_filename="";
	$zip_filename="";
	$written=0;
	$html_data="";

    //Loop through each office stored in the $sales array

	foreach ($sales as $office){
		// CREATE FILENAMES for this office
		$filenames=getfilename($office['id']);
		$csv_filename = $outputdir.$filenames['csv'];
		$html_filename = $outputdir.$filenames['html'];
		$zip_filename = $outputdir.$filenames['zip'];


		if ($debug) {
			echo "\r\nSave_Sales: Dataset:\r\n";
			print_r($office);
			echo "\r\nSave_Sales: Filenames:\r\n";
			print_r($filenames);
			}

		//Save CSV output data for this office and returning the bytes written to disk to $written
		$written = file_put_contents($csv_filename, $office['output']);
		//Check the size of the output against the bytes written to disk and Report Error if not saved correctly
		if($written != strlen(implode($office['output']))) echo("Office ".$office['id']." CSV didn't write enough data!\r\nFile: ".$csv_filename." ".$written." bytes of ".strlen($office['output'])." written..\r\n");

		//Create HTML Report
		$html_data = generateCampaignTotalHTML($office);
		//Save HTML data for this office and returning the bytes written to disk to $written
		$written = file_put_contents($html_filename, $html_data);
		//Check the size of the output against the bytes written to disk and Report Error if not saved correctly
		if($written != strlen($html_data)) echo("Office ".$office['id']." HTML didn't write enough data!\r\nFile: ".$html_filename." ".$written." bytes of ".strlen($html_data)." written..\r\n");


		//Create zip container!
		$zip = new ZipArchive();
		//Create and open new zipfile
		$zip->open($zip_filename, ZIPARCHIVE::CREATE);
		//Add Sales CSV to ZIP
		$zip->addFile($csv_filename);
		//Add HTML to ZIP
		$zip->addFile($html_filename);
		//Close ZIP
		$zip->close();
		echo "File: $zip_filename created.\r\n";

		//DELETE THE OTHER FILES - Report Failure
		if ((!unlink($csv_filename))) echo "Error deleting $csv_filename";
		if ((!unlink($html_filename))) echo "Error deleting $html_filename";
	}
	}

	// Gets all sales per $where statement and returns them as array
function getpxsales($where){
		global $debug, $fake_sales;
		$sale_data=array();
		$res = query("SELECT * FROM sales $where ORDER BY sale_time ASC",1 );
			//if fake_sales>0 is set then we get the 'magic' query instead of hitting the database..
			if ($fake_sales) $res=query(testquery("sales"));
			if ($debug)
			{

				echo("Debug:");
				#echo("PXSALES:".testquery("sales")."/r/n/r/n");
			}

			while($row = mysql_fetch_array($res, MYSQL_ASSOC)){
				$sale_data[]=$row;
			}
			if ($debug) echo("Rows:".count($res)."\r\n");

		return $sale_data;

	}

function fix_dapartments($sale_data) { 	// Fix for the deparment codes
		global $debug,$mobile_designator,$deparment_change;
		$res = query(testquery("departments"), 1);
		$new_array=array();


		foreach ($sale_data as $row) { // Process each sale record
			$row['office'][0]='R'; // If all a true then make the change.. Old_Department_Code=>New_Department_code

			if
			(startsWith($row['office'][0],'R') && endsWith($row['campaign_code'], $mobile_designator)){ // Check if its a mobile and if the office begins with R
				$row['office'][0] = 'N';// If mobile change R to N = $tmp; /
			}
			$new_array[]=$row;}

		IF ($debug)	{
			echo "Fix Departments:";
			print_r($new_array);}

		echo "\r\nDepartments Corrected.\r\n";
		return($new_array);

	}

function generate_sales_output_data($sale_data){
	global $debug,$mobile_designator;
	$output = "";
	$output_array=array();
	$nl = "\r\n";
    $sep = "\t";

    $row=array();
    foreach ($sale_data as $row) {
			// Reset output varible
			$output="";
			// PHONE NUMBER - AGENT USER - AGENT NAME - DATE - DATE - TIME - LAST NAME - FIRST NAME - PERSON CONTACTED - ADDRESS1 - ADDRESS2 - CITY - STATE - ZIP - CAMPAIGN - AMOUNT - VERIFIER - OFFICE

			$output = $row['phone'].$sep;
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
			$output .= $row['office'].$sep;

			// NAMS FORMAT - 7 more BLANKS
			$output .= $sep; // PAYMENT TYPE(CC, Credit, CK, Check, PD, PAID, Decline)
			$output .= $sep; // ETS FIELD
			$output .= $sep; // ETS FIELD
			$output .= $sep; // ETS FIELD
			$output .= $sep; // ETS FIELD
			$output .= $sep; // ETS FIELD
			$output .= $sep; // PREVIOUS NAMS invoice number

			$output .= $nl; // END NEW LINE
			// Build a really big array to stuff everyting into as we iterate through the data
			if(!isset($output_array[$row['office']])){ // Does the current office exist?
				$output_array[$row['office']]=array(); // if not Define the top level array for the Office
				$output_array[$row['office']]['id']=$row['office'];// Add the Office ID
				$output_array[$row['office']]['total']=intval(0);// Define integer variable to track totol deparment/office sales set to 0
				$output_array[$row['office']]['count']=intval(0);// Define integer variable to count total number of sales
				$output_array[$row['office']]['output']=array();// Define array to put the actual sales records in
				$output_array[$row['office']]['campaigns'] = array();// Define array for campaign stats
			 }
			if(!isset($output_array[$row['office']]['campaigns'][$row['campaign']])){ // Do we have stats yet for the current campaign?
				$output_array[$row['office']]['campaigns'][$row['campaign']]=array(); // If not create the top level array to store id, total and count in.
				$output_array[$row['office']]['campaigns'][$row['campaign']]['id']=$row['campaign'];//Set the campaign id
				$output_array[$row['office']]['campaigns'][$row['campaign']]['total'] = intval(0);// Define integer variable to track total number of campaign sales
				$output_array[$row['office']]['campaigns'][$row['campaign']]['count'] = intval(0);// Define integer variable to count total number of campaign sales

			}
			// Add the current data to the array
			$output_array[$row['office']]['output'][]= $output; // Add the CSV line for the sales file
			$output_array[$row['office']]['total'] += $row['amount']; // add the amount to the office/department total
			$output_array[$row['office']]['count'] ++; // increment the total count of sales for the office/department
		        $output_array[$row['office']]['campaigns'][$row['campaign']]['total'] += $row['amount']; // add the amount to the campaign total for this office/department
			$output_array[$row['office']]['campaigns'][$row['campaign']]['count'] ++; // increment the total count of sales for the current campaigns  for the office/department


		} // END FOREACH



		return $output_array;
	}

function generateCampaignTotalHTML($office){
		global $stime, $etime,$debug;
		$filenames=getfilename($office['id']);

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
			<th>Deparment: <?=$office['id']?></th>
		</tr>
		<tr>
			<th>Filename: <?=$filenames['csv']?></th>
		</tr>
		</table>

		<br />

		<table border="1" align="center">
		<tr>
			<th align="left">Campaign</th>
			<th align="right"># of Deals</th>
			<th align="right">Total</th>
		</tr><?

		foreach($office['campaigns'] as $campaign){

			if (isset($campaign['id'])){
			?><tr>
				<td><?=($campaign['id'])?></td>
				<td align="right"><?=number_format($campaign['count'])?></td>
				<td align="right">$<?=number_format($campaign['total'])?></td>
				</tr><?
			}
		}

		?><tr>
			<th>DEPARTMENT TOTAL:</th>
			<th align="right"><?=number_format($office['count'])?></th>
			<th align="right">$<?=number_format($office['total'])?></th>
		</tr>
		</table>

		</body>
		</html><?

		$html = ob_get_contents();

		ob_end_clean();

		return $html;
	}
function startsWith($haystack, $needle) {return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;}
function endsWith($haystack, $needle) {return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);}
function getfilename($office) {
	global $stime,$debug;
	$names=array();
	$names['csv'] = $office."_".date("m-d-Y", $stime)."-PX.csv";
	$names['html'] = $office."_".date("m-d-Y", $stime)."-PX-totals.html";
	$names['zip'] = $office."_".date("m-d-Y", $stime)."-PX.zip";
	return $names;
	}

?>
