#!/usr/bin/php
<?php
	global $spreadsheet_id;
	global $sheet_name;
	global $export_dir;

	global $skip_spreadsheet_export;

	$base_dir = "/var/www/html/dev/";

	$spreadsheet_id = "1rku_g4RQ-Wk3420yGKf0Lph6LMZog1Awu3fKVuyRBUA";
	//$spreadsheet_id = "1uGNgyhv7V3pTU860LNTQCMczEBGHzGCFrsQdExDqfK8";//"1oxJEkn1UpPxZkr6sDWozTJJLpp2AX77ipf48RE3kTh4";
	$sheet_name = "CGX Leads";


	$export_dir = "/var/log/ProjectX/";

	$skip_spreadsheet_export = true;


/**** END CONFIGURABLES, SHOULDN'T NEED TO GO PAST HERE ****/
	include_once($base_dir."site_config.php");
	include_once($base_dir."db.inc.php");

	if (php_sapi_name() != 'cli') {
    	throw new Exception('This application must be run on the command line.');
	}

	require __DIR__ . '/google-api-php-client-2.2.2/vendor/autoload.php';

	include_once($base_dir."classes/google_spreadsheets.inc.php");
	include_once($base_dir."classes/JXMLP.inc.php");


/********** END INCLUDES/FUNCTIONS***********/


function writeCSVData($stime, $values){

	global $export_dir;

	if(!is_dir($export_dir)){
		mkdir($export_dir);
	}

	$file = $export_dir . "CGX-export-".date("m-d-Y_H-i-s")."-FOR-Date-".date("m-d-Y", $stime).".csv";


	echo "Writing ".count($values)." records to CSV: ".$file."\n";

	$fh = fopen($file, "a");

	foreach($values as $row){

		$len = fputcsv($fh, $row);

	}

	fclose($fh);


}

function exportData($stime, $etime){


	global $skip_spreadsheet_export;
	global $spreadsheet_id;
	global $sheet_name;

	$rowarr = getCGXLeads($stime, $etime);

	$values = generateExportData($rowarr);

	if(count($values) > 0){

		// WRITE CSV DATA FIRST
		writeCSVData($stime, $values);


		if(!$skip_spreadsheet_export){
			$sheet = new GoogleSpreadSheets($spreadsheet_id, $sheet_name);
			return $sheet->appendValues($values);
		}else{
			return 1;
		}

	}else{

		return "No data for the specified timeframe '".date("H:i:s m/d/Y", $stime)."' to '".date("H:i:s m/d/Y", $etime)."'\n";
	}

}

function getCGXLeads($stime, $etime){

	$sql = "SELECT * FROM `sales` ".
			" WHERE sale_time BETWEEN '".intval($stime)."' AND '".intval($etime)."'  ".
			" AND office='20' ".
			"";

//echo $sql;

	$res = query($sql);
	$rowarr = array();
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		$rowarr[] = $row;

	}
//print_r($rowarr);
	return $rowarr;
}


function generateExportData($rowarr){

	$values = array();

	$valp = 0;
	foreach($rowarr as $row){

		$values[$valp] = array();
//echo $row['xml_data'];
		$xml_data = $_SESSION['JXMLP']->parseOne($row['xml_data'],"Data",1);


		//print_r($xml_data);exit;
/**
 * Status
 * First Name
 * Last Name
 * Date Of Birth
 * Address: Street 1
 * Address: City
 * ADDRESS: State/Region
 * Address: Postal Code
 * Phone Number
 * Member ID #
 * Primary Insurance Name
 * Secondary Insurance Name
 * Secondary Member ID #
 * Ethnicity
 * Gender
 * Patient's Personal History of Cancer	Breast Cancer Age When Diagnosed	Colon/Rectal Adenomas Age When Diagnosed	Colon/Rectal Cancer Age When Diagnosed	Prostate Cancer  Age When Diagnosed	Ovarian Cancer Age When Diagnosed	leukemiaage	cancerOther	cancerOtherText	Other Cancer Age When Diagnosed	Family History Of Cancer	relation1	Side of Family1	Cancer Site or Polyp Site	Age When Each Diagnosed1	relation2	Side of Family2	Cancer Site or Polyp Site2	Age When Each Diagnosed2	relation3	Side of Family3	Cancer Site or Polyp Site3	Age When Each Diagnosed3	relation4	Side of Family4	Cancer Site or Polyp Site4	Age When Each Diagnosed4	Recording Link
 *
 *
 *XML DATA Array
(

    [insurance_claim_dep_city] =>
    [personal_cancer_leukemia_found] => false
    [dob] => 04/11/1953
    [race] => American Indian or Alaska Native
    [personal_cancer_ovarian_found] => false
    [phone] =>
    [insurance_secondary_group_number] =>
    [personal_cancer_other_found] => false
    [cancer_site1] => Breast Cancer
    [cancer_site2] =>
    [cancer_site3] =>
    [cancer_site4] =>
    [diag_age1] => 33
    [diag_age2] =>
    [diag_age3] =>
    [diag_age4] =>
    [relationship4] =>
    [relationship3] =>
    [relationship2] =>
    [relationship1] => kjiuyh
    [family_side4] =>
    [family_side3] =>
    [family_side2] =>
    [family_side1] => Father (Paternal)
    [personal_cancer_colon_age] =>
    [personal_cancer_prostate_age] =>
    [personal_cancer_prostate_found] => true
    [personal_cancer_colon_adenoman_found] => false
    [personal_cancer_colon_adenoman_age] =>
    [personal_cancer_colon_found] => false
    [email] =>
    [insurance_carrier_name] =>
    [personal_cancer_endometrial_found] => false
    [personal_cancer_other_age] =>
    [insurance_claim_dep_state] => AL
    [insurance_secondary_policy_number] =>
    [personal_cancer_ovarian_age] =>
    [insurance_policy_number] =>
    [personal_cancer_leukemia_age] =>
    [insurance_secondary_carrier_name] =>
    [personal_cancer_breast_age] =>
    [personal_cancer_other_site] =>
    [insurance_claim_dep_phone] =>
    [personal_cancer_breast_found] => false
    [personal_cancer_endometrial_age] =>
    [gender] => Male
)

 *
 *
 ***/

		$fidx = 0;

		$values[$valp][$fidx++] = "New PT";
		$values[$valp][$fidx++] = $row['first_name'];
		$values[$valp][$fidx++] = $row['last_name'];
		$values[$valp][$fidx++] = $xml_data['dob'];
		$values[$valp][$fidx++] = $row['address1'];
		$values[$valp][$fidx++] = $row['city'];
		$values[$valp][$fidx++] = $row['state'];
		$values[$valp][$fidx++] = $row['zip'];
		$values[$valp][$fidx++] = $row['phone'];

		$values[$valp][$fidx++] = $xml_data['insurance_policy_number'];
		$values[$valp][$fidx++] = $xml_data['insurance_carrier_name'];

		$values[$valp][$fidx++] = $xml_data['insurance_secondary_carrier_name'];
		$values[$valp][$fidx++] = $xml_data['insurance_secondary_policy_number'];

		$values[$valp][$fidx++] = $xml_data['race'];
		$values[$valp][$fidx++] = $xml_data['gender'][0];



//		$values[$valp][$fidx++] = ($xml_data['personal_cancer_breast_found'] == "false")?"no":"Yes";
//		$values[$valp][$fidx++] = ($xml_data['personal_cancer_colon_adenoman_found'] == "false")?"no":"Yes";

		/***
		 * PERSONAL CANCER HISTORY
		 * COLUMN "P" through "Y"
		 */
		if(
			(strtolower($xml_data['personal_cancer_breast_found']) == "true") ||
			(strtolower($xml_data['personal_cancer_colon_adenoman_found']) == "true") ||
			(strtolower($xml_data['personal_cancer_colon_found']) == "true") ||
			(strtolower($xml_data['personal_cancer_prostate_found']) == "true") ||
			(strtolower($xml_data['personal_cancer_ovarian_found']) == "true") ||
			(strtolower($xml_data['personal_cancer_leukemia_found']) == "true") ||
			(strtolower($xml_data['personal_cancer_other_found']) == "true")
		){
			$values[$valp][$fidx++] = "Yes";
		}else{
			$values[$valp][$fidx++] = "no";

		}

		$values[$valp][$fidx++] = $xml_data['personal_cancer_breast_age'];
		$values[$valp][$fidx++] = $xml_data['personal_cancer_colon_adenoman_age'];
		$values[$valp][$fidx++] = $xml_data['personal_cancer_colon_age'];
		$values[$valp][$fidx++] = $xml_data['personal_cancer_prostate_age'];
		$values[$valp][$fidx++] = $xml_data['personal_cancer_ovarian_age'];
		$values[$valp][$fidx++] = $xml_data['personal_cancer_leukemia_age'];

		$values[$valp][$fidx++] = ($xml_data['personal_cancer_other_found'] == "false")?"no":"Yes";
		$values[$valp][$fidx++] = $xml_data['personal_cancer_other_site'];
		$values[$valp][$fidx++] = $xml_data['personal_cancer_other_age'];


		/**
		 * FAMILY HISTORY OF CANCER
		 *
		 *     [cancer_site1] => Breast Cancer
			    [cancer_site2] =>			    	[cancer_site3] =>			    [cancer_site4] =>
			    [diag_age1] => 33
			    [diag_age2] =>			    		[diag_age3] =>			    [diag_age4] =>
			    [relationship4] =>			    	[relationship3] =>			    [relationship2] =>
			    [relationship1] => kjiuyh
			    [family_side4] =>			    	[family_side3] =>			    [family_side2] =>
			    [family_side1] => Father (Paternal)
		 */

		$values[$valp][$fidx++] = (
									strtolower(trim($xml_data['cancer_site1'])) != '' ||
									strtolower(trim($xml_data['cancer_site2'])) != '' ||
									strtolower(trim($xml_data['cancer_site3'])) != '' ||
									strtolower(trim($xml_data['cancer_site4'])) != ''
								)?"Yes":"no";

		$values[$valp][$fidx++] = trim($xml_data['relationship1']);
		$values[$valp][$fidx++] = trim($xml_data['family_side1']);
		$values[$valp][$fidx++] = trim($xml_data['cancer_site1']);
		$values[$valp][$fidx++] = trim($xml_data['diag_age1']);

		$values[$valp][$fidx++] = trim($xml_data['relationship2']);
		$values[$valp][$fidx++] = trim($xml_data['family_side2']);
		$values[$valp][$fidx++] = trim($xml_data['cancer_site2']);
		$values[$valp][$fidx++] = trim($xml_data['diag_age2']);

		$values[$valp][$fidx++] = trim($xml_data['relationship3']);
		$values[$valp][$fidx++] = trim($xml_data['family_side3']);
		$values[$valp][$fidx++] = trim($xml_data['cancer_site3']);
		$values[$valp][$fidx++] = trim($xml_data['diag_age3']);

		$values[$valp][$fidx++] = trim($xml_data['relationship4']);
		$values[$valp][$fidx++] = trim($xml_data['family_side4']);
		$values[$valp][$fidx++] = trim($xml_data['cancer_site4']);
		$values[$valp][$fidx++] = trim($xml_data['diag_age4']);


		$values[$valp][$fidx++] = "Recording Link Goes here!";


		$valp++;
	}

	return $values;
}


/******* KICK THIS MOTHERF***ER OFF *********/

	// GRAB TODAY TIMEFRAME
	$stime = mktime(0,0,0);
	$etime = mktime(23,59,59);


	if(count($argv) > 1 && ($tmptime = strtotime($argv[1])) > 0){

		$stime = mktime(0,0,0, date("m", $tmptime), date("d", $tmptime), date("Y", $tmptime));
		$etime = mktime(23,59,59, date("m", $stime), date("d", $stime), date("Y", $stime));

	}


	$result = exportData($stime, $etime);

	if(gettype($result) == "string"){

		echo "RESULT: ".$result;

	}else{

		echo "SUCCESS! ";

	}



//	$custom_range = "A:ZZ";
//	$sheet->setRange($custom_range);

//	$values = array(
//	    array( "Lets do some math","", "=SUM(C1:C5)" ),
//	    array( "Hmm", "Lets", "Find", "out", "Now" ),
//	    // Additional rows ...
//	);


// READING EXAMPLE
//$response = $service->spreadsheets_values->get($spreadsheetId, $range);
//$values = $response->getValues();
//
//if (empty($values)) {
//    print "No data found.\n";
//} else {
//   // print "Name, Major:\n";
//    foreach ($values as $row) {
//        // Print columns A and E, which correspond to indices 0 and 4.
//      //  printf("%s, %s\n", $row[0], $row[4]);
//
//      print_r($row);
//    }
//}




//
//$data = new Google_Service_Sheets_ValueRange([
//    'range' => $range,
//    'values' => $values,
//
//]);
//
//
////// Additional ranges to update ...
////$body = new Google_Service_Sheets_BatchUpdateValuesRequest([
////    'valueInputOption' => $valueInputOption,
////    'data' => $data
////]);
//
//// OVERWRITES
////$result = $service->spreadsheets_values->batchUpdate($spreadsheetId, $body);
////printf("%d cells updated.", $result->getTotalUpdatedCells());
//
//$args = array(
//
//		'valueInputOption' => $valueInputOption,
//	);
//
//$response = $service->spreadsheets_values->append($spreadsheet_id, $range, $data, $args);
//
//echo '<pre>', var_export($response, true), '</pre>', "\n";
















