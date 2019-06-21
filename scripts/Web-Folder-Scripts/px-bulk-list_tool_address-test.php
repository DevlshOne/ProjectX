#!/usr/bin/php
<?php

	global $stime;
	global $etime;

	$base_dir = "/root/address_verification/";//"/var/www/html/dev2/";

	$batch_size = 100;

	include_once($base_dir."site_config.php");
	include_once($base_dir."db.inc.php");

	include_once($base_dir."utils/rendertime.php");


	include_once($base_dir."classes/address_verification.inc.php");

	global $stime, $etime;

	$started_time = time();
	$updatecnt = 0;

	$skip_imports = array(41, 42, 46, 55, 56);

	$import_id = 0;

	if($argv[1]){

//		$skip_imports =

		$import_id = intval($argv[1]);


	}





	echo date("H:i:s m/d/Y")." - Started\n";


	connectListDB();

	if($import_id){


		$extrasql = " AND `id`='".intval($import_id)."' ";
		$x=1;

	}else{

		$extrasql = " AND `id` NOT IN (";
		$x=0;
		foreach($skip_imports as $iid){
			$extrasql .= ($x++ > 0)?',':'';
			$extrasql .= $iid;
		}

		$extrasql .= ")";

	}


	$re2 = query("SELECT * FROM `imports` WHERE status='active' ".(($x > 0)?$extrasql:''),1);
	while($import = mysqli_fetch_array($re2, MYSQLI_ASSOC)){

		echo date("H:i:s m/d/Y")." - Processing Import ID#". $import['id']." - ".$import['name']." - Imported ".date("m/d/Y", $import['time'])."\n";

		list($list_total_count) = queryROW("SELECT COUNT(*) FROM `leads` WHERE `import_id`='".intval($import['id'])."' ");
		$cnt = 0;

		$import_update_cnt = 0;

		echo date("H:i:s m/d/Y")." - Processing Import ID#". $import['id']." - ".$import['name']." - Total Leads:".number_format($list_total_count)."\n";

		while($cnt < $list_total_count){


			$pcent = ($cnt > 0)?round(($import_update_cnt / $cnt)*100, 2):0;

			echo "Processed $cnt out of $list_total_count. Updated $import_update_cnt (".$pcent."%) - Total updates $updatecnt\n";

			$addr_array = array();

			$res = query("SELECT phone,address,city,state,zip FROM `leads` WHERE `import_id`='".intval($import['id'])."' LIMIT ".$cnt.",".($batch_size*20), 1);


			if(mysqli_num_rows($res) <= 0){
				echo "No more records found.";
				continue 2;
			}

			while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

				$addr_array[] = array(
					'phone'			=> $row['phone'],
					'address1' 		=> $row['address'],
					'address2' => '',
					'city' 			=> $row['city'],
					'state' 		=> $row['state'],
					'zip_code' 		=> $row['zip']

					);
				$cnt++;
			}




			// PROCESS IT
			$new_rows = $_SESSION['address_verification']->getCleanAddressesFromRows($addr_array);

			foreach($addr_array as $idx=>$row){

				if($new_rows[$idx] != $row){

					$dat = array();

					foreach($row as $key=>$val){



						if(strtolower($val) != strtolower($new_rows[$idx][$key])){

							$dat[$key] = $new_rows[$idx][$key];

							echo "Old $key = $val\n";
							echo "New $key = ".$new_rows[$idx][$key]."\n\n";
						}
					}


					if(count($dat) > 0){

						//print_r($dat);

						echo date("H:i:s m/d/Y")." - Updating Phone #".$row['phone']."\n";

						try{

							$sdat = $dat;

							if(isset($dat['zip_code'])){
								$sdat['zip'] = $dat['zip_code'];
								unset($sdat['zip_code']);
							}

							if(isset($dat['address1'])){
								$sdat['address'] = $dat['address1'];
								unset($sdat['address1']);
							}

							if(isset($dat['address2'])){
								$sdat['address'] .= ' '.$dat['address2'];
								unset($sdat['address2']);
							}


//							print_r($sdat);

							aeditByField('phone',$row['phone'],$sdat, 'leads');

//exit;

						}catch(Exception $ex){
							echo "Error updating record: ".mysqli_error($_SESSION['db'])."\nException: ".$ex;
						}



						$updatecnt++;
						$import_update_cnt++;

					}

				}

			}






		}


		$pcent = round(($import_update_cnt / $cnt)*100, 2);

			//echo "Processed $cnt out of $list_total_count. Updated $import_update_cnt (".$pcent."%) - Total updates $updatecnt\n";

		// IMPORT FINISHED PROCESSING
		echo date("H:i:s m/d/Y")." - Import ID#". $import['id']." DONE, Updated $import_update_cnt (".$pcent."%) - Total updates $updatecnt\n";

		$elapsed = time() - $started_time;

		echo "Elapsed time: ".rendertime($elapsed).' ('.$elapsed.' seconds)'."\n";

//		exit;


	}



	echo date("H:i:s m/d/Y")." - DONE, Updated ".number_format($updatecnt)." records out of ".number_format($cnt)."\n";

	$elapsed = time() - $started_time;

	echo "Elapsed time: ".rendertime($elapsed).' ('.$elapsed.' seconds)'."\n";
//

