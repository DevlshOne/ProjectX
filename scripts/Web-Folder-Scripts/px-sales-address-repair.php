#!/usr/bin/php
<?php

	global $stime;
	global $etime;

	$base_dir = "/var/www/html/dev2/";


	include_once($base_dir."site_config.php");
	include_once($base_dir."db.inc.php");

	include_once($base_dir."utils/rendertime.php");


	include_once($base_dir."classes/address_verification.inc.php");

	global $stime, $etime;

	$started_time = time();

	$dripp_update_url = "https://dripp.advancedtci.com/dripp/pages/update_transaction.php";


	// GRAB TODAY TIMEFRAME
	$stime = mktime(0,0,0);
	$etime = mktime(23,59,59);


	if($argv[1] && ($tmptime = strtotime($argv[1])) > 0){

		$stime = mktime(0,0,0, date("m", $tmptime), date("d", $tmptime), date("Y", $tmptime));
		$etime = mktime(23,59,59, date("m", $stime), date("d", $stime), date("Y", $stime));

	}


	echo date("H:i:s m/d/Y")." - Started, looking for sales from ".date("m/d/Y", $stime)."\n";


	$sql = "SELECT lead_tracking.*, sales.id as sales_id FROM `sales` ".
				" INNER JOIN `lead_tracking` ON `sales`.lead_tracking_id = `lead_tracking`.id ".
				" WHERE `sales`.sale_time BETWEEN '$stime' AND '$etime' ";

//	echo $sql;

	$res = query($sql, 1);

	$total_cnt = mysqli_num_rows($res);

	echo date("H:i:s m/d/Y")." - Processing ".number_format($total_cnt)." records...\n";

	$updatecnt = 0;
	$drippfixes = 0;
//	$actualfixes = 0;
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){


		$new_row = $_SESSION['address_verification']->getCleanAddressFromRow($row);

		if($new_row != $row){

			$dat = array();
			$notjustcase = false;
			foreach($row as $key=>$val){



				if(strtolower($val) != strtolower($new_row[$key])){

					$dat[$key] = $new_row[$key];

					echo "Old $key = $val\n";
					echo "New $key = ".$new_row[$key]."\n\n";
				}
			}


			if(count($dat) > 0){

				//print_r($dat);

				echo date("H:i:s m/d/Y")." - Updating Lead #".$row['id'].' Sale#'.$row['sales_id']."\n";

				aedit($row['id'], $dat, 'lead_tracking');
//
				try{
					if($row['sales_id']){

						$sdat = $dat;

						if(isset($dat['zip_code'])){
							$sdat['zip'] = $dat['zip_code'];
							unset($sdat['zip_code']);
						}

						aedit($row['sales_id'], $sdat, 'sales');
					}
				}catch(Exception $ex){
					echo "Error updating sales record: ".mysqli_error($_SESSION['db'])."\nException: ".$ex;
				}


				switch($row['dispo']){
				// ANYTHING NOT SPECIFIED HERE, SKIP/DONT ATTEMPT TO UPDATE DRIPP
				default:


					break;
				case 'SALECC':
				case 'PAIDCC':

					echo date("H:i:s m/d/Y")." - ".$row['dispo']." Detected, pushing update to DRIPP as well...\n";


					$dat['phone'] = $row['phone_num'];
					$dat['project_id'] = $row['campaign'];

					$ch = curl_init($dripp_update_url);

//echo "Posting to dripp: ".$dripp_update_url."\n";
//
//print_r($dat);

					//return the transfer as a string
			        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $dat);

			        // $output contains the output string
			        $output = curl_exec($ch);

//					echo "DRIPP OUTPUT: ".$output."\n";

					// close the connection, release resources used
					curl_close($ch);

					$drippfixes++;

					break;
				}












				$updatecnt++;

			}
//echo $row['id']."\n";

//			print_r($dat);


//print_r($row);
//print_r($new_row);
//exit;



		}



	} // END WHILE LOOP

	echo date("H:i:s m/d/Y")." - DONE, Updated ".number_format($updatecnt)." records out of ".number_format($total_cnt)."\n";
	echo date("H:i:s m/d/Y")." - DRIPP fixes: ".number_format($drippfixes)."\n";

	$elapsed = time() - $started_time;

	echo "Elapsed time: ".rendertime($elapsed).' ('.$elapsed.' seconds)';


