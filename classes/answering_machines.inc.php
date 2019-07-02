<?	/***************************************************************
	 *	Answering Machines - Tools to handle recalling them/list ninjitsu
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['answering_machines'] = new AnsweringMachines;



class AnsweringMachines{


	var $call_attempts_move_limit = 10;
	var $call_attempts_dnc_limit = 15;

	var $dnc_default_duration = 15768000; // (15768000 = Half a year)


	var $AM_type_code = "700";
	var $PM_type_code = "710";

	function AnsweringMachines(){


		## REQURES DB CONNECTION!
		$this->handlePOST();



	}




	/**
	 *  generateAnsweringMachineLeads($num_times, $stime, $etime, $timeframe = 'AM')
	 *
	 * $dispo		The dispo to search for (A,DC, etc etc)
	 * $num_times	A count of how many times the event had to occur, for it to be considered
	 * $stime/$etime	The start and end timestamps to search within
	 * $timeframe	AM/PM or blank/anything else, to include all
	 * $ignore_list_sql	 An SQL statement to ignore the list(s) that indicate the lead has already been moved
	 */
	function generateLeadStack($dispo, $num_times, $stime, $etime, $timeframe = 'AM', $ignore_list_sql = ''){

		$stime = intval($stime);
		$etime = intval($etime);

		echo date("h:i:s m/d/Y")." - Extracting '".$dispo."' Leads from ".date("H:i:s m/d/Y", $stime).' to '.date("H:i:s m/d/Y", $etime)."\n";


		$out = array();

		$timeframe = ($timeframe)?strtoupper($timeframe):$timeframe;



		$res = query(
				"SELECT DISTINCT(`phone_num`) FROM `lead_tracking` ".
				" WHERE `time` BETWEEN '$stime' AND '$etime' ".
				" AND `dispo`='".mysqli_real_escape_string($_SESSION['db'], $dispo)."' ".

				(($timeframe == 'AM' || $timeframe == 'PM')?" AND FROM_UNIXTIME(time,'%p')='".$timeframe."'":'').

				$ignore_list_sql.

				"", 1);


		echo date("h:i:s m/d/Y")." - ".number_format(mysqli_num_rows($res))." records to be processed.\n";

		$phone_arr = array();

		$z=0;

		// GO THROUGH EACH DISTINCT PHONE NUMBER
		while($data = mysqli_fetch_row($res)){

			list($phone) = $data;

			// GRAB EVERY CALL WE'VE MADE TO THEM IN THE LAST 30 DAYS
			$re2 = query(
					"SELECT `id`,`lead_id`,`list_id`,`vici_cluster_id`,`time`,`dispo`,`campaign` FROM `lead_tracking` ".
					" WHERE `phone_num`='".mysqli_real_escape_string($_SESSION['db'], $phone)."' ".
					(($timeframe == 'AM' || $timeframe == 'PM')?" AND FROM_UNIXTIME(time,'%p')='".$timeframe."'":'').
					"",1);

			$bad_dispo_cnt = 0;



			while($row = mysqli_fetch_array($re2, MYSQLI_ASSOC)){

				// IF ANY OTHER DISPO FOUND, KICK OUT AND SKIP THE NUMBER
				if(strtoupper($row['dispo']) != strtoupper($dispo)){
					$bad_dispo_cnt++;
					break;
				}

				if(!array_key_exists($phone, $phone_arr)){
					$phone_arr[$phone] = array();
				}

				$phone_arr[$phone][] = $row;
			}


			// IF EVEN A SINGLE BAD DISPO FOUND, REJECT THE LEAD
			if($bad_dispo_cnt > 0){

				unset($phone_arr[$phone]);

				continue;
			}


			if(count($phone_arr[$phone]) < $num_times){

				unset($phone_arr[$phone]);

			}else{


				if(count($phone_arr[$phone]) >= $this->call_attempts_dnc_limit){
					echo "PHONE $phone - DNC LIMIT HIT!\n";
				}else{

					echo "PHONE $phone - Pushed for rotation!\n";
				}

			}






		} // END WHILE(distinct phone numbers)



		return $phone_arr;
//
//			// GET COUNT OF ANY DISPO BESIDES THE ONE SPECIFIED
//			list($notcnt) = queryROW(
//							"SELECT COUNT(*) FROM `lead_tracking` ".
//							" WHERE `phone_num`='".mysqli_real_escape_string($_SESSION['db'], $row['phone_num'])."' ".
//							" AND `dispo` != '".mysqli_real_escape_string($_SESSION['db'], $dispo)."'".
//							(($timeframe == 'AM' || $timeframe == 'PM')?" AND FROM_UNIXTIME(time,'%p')='".$timeframe."'":'')
//					);
//
//			// IF THEY HAVE ANY OTHER DISPO THAT THE ONE SPECIFIED, SKIP THEM
//			if($notcnt > 0){
//				continue;
//			}
//
//
//			$sql = "SELECT COUNT(*) FROM `lead_tracking` ".
//							" WHERE `phone_num`='".mysqli_real_escape_string($_SESSION['db'], $row['phone_num'])."' ".
//							" AND `dispo`='".mysqli_real_escape_string($_SESSION['db'], $dispo)."'".
//							(($timeframe == 'AM' || $timeframe == 'PM')?" AND FROM_UNIXTIME(time,'%p')='".$timeframe."'":'');
//
//echo $sql;
//			// GET COUNT OF ALL ANSWERING MACHINE (SPECIFIED DISPO) FOR THE ENTIRE LEAD TRACKING TABLE (30 day window)
//			list($cnt) = queryROW(
//							$sql
//					);
//
//
//			if($cnt > $num_times){
//
//				$out[$z] = $row;
//
//				$out[$z]['dispo_count'] = $cnt;
//
//				echo date("h:i:s m/d/Y")." - Pushing ".$row['phone_num']." to the stack with $cnt instances.\n";
//
//				$z++;
//			}
//		}
//
//
//
//		return $out;
	}




	function extractAnswerDispos($timeframe){

			$stime = mktime(0,0,0);
			$etime = mktime(23,59,59);

			// MOVE IT BACK 1 DAY!
			$stime = $stime - 86400;
			$etime = $etime - 86400;

			$ignore_list_sql = " AND `list_id` NOT LIKE '7%'";

			connectPXDB();

			$PXDB = $_SESSION['db'];

			$VICIDB = array();

			$arr = $this->generateLeadStack('A', $this->call_attempts_move_limit, $stime, $etime, $timeframe, $ignore_list_sql);

			echo date("h:i:s m/d/Y")." - Successfully extracted ".count($arr)." leads.\n";

			// GO THROUGH EACH PHONE NUMBER
			foreach($arr as $phone=>$rowarr){


				if(count($rowarr) >= $this->call_attempts_dnc_limit){

					echo $phone." - PUSH TO THE TIMED DNC HERE, WHEN ITS FINISHED REVAMP.\n";


					continue;
				}

				echo "Rotating $phone to ".(($timeframe == 'AM')?"PM":"AM")." list\n";

				foreach($rowarr as $row){

					// EACH ROW:
					//`id`,`lead_id`,`vici_cluster_id`,`time`,`dispo`,`campaign`

					if(array_key_exists($row['vici_cluster_id'], $VICIDB)){

						$_SESSION['db'] = $VICIDB[$row['vici_cluster_id']];

					}else{

						$vidx = getClusterIndex($row['vici_cluster_id']);

						if($vidx < 0){
							echo "ERROR LOCATING VICI CLUSTER ID# ".$row['vici_cluster_id']."\n";
							continue;
						}

						// CONNECT TO VICI TO MAKE THE CHANGE
						connectViciDB($vidx);
						$VICIDB[$row['vici_cluster_id']] = $_SESSION['db'];

					}


					// FIGURE OUT WHICH LIST WE'RE PUTTING IT IN, CREATE IT IF NECESSARY

					// AM LIST - MOVE TO PM LIST
					if($timeframe == 'AM'){

						$list_id = $this->findOrAddViciListID($this->PM_type_code, $row);

					// PM - MOVE THEM TO THE AM LIST
					}else{

						$list_id = $this->findOrAddViciListID($this->AM_type_code, $row);

					}

					// UPDATE THE LIST ID IN VICIDIAL
					echo ("UPDATE `vicidial_list` SET list_id='".intval($list_id)."' WHERE lead_id='".intval($row['lead_id'])."'");


					// UPDATE THE LIST ID IN PX
					//connectPXDB();
					$_SESSION['db'] = $PXDB;
					echo ("UPDATE `lead_tracking` SET list_id='".intval($list_id)."' WHERE id='".mysqli_real_escape_string($_SESSION['db'],$row['id'])."'");


				}


			}



	}



	function findOrAddViciListID($type_code, $row){

		$campaign = $row['campaign'];

		$base_list_id = intval($row['list_id']);

		$new_list_id = $type_code. $base_list_id;

		if($type_code == $this->AM_type_code){
			$time_mode = "AM";
		}else{
			$time_mode = "PM";
		}

		list($list_id) = queryROW("SELECT list_id FROM `vicidial_lists` ".
							" WHERE `list_id`='".mysqli_real_escape_string($_SESSION['db'], $new_list_id)."'"); // AND campaign_id='".mysqli_real_escape_string($_SESSION['db'],$campaign)."'

		if($list_id)return $list_id;


		// FIND THE NEXT OPEN ID IN OUR RANGE
//		list($last_id) = queryROW("SELECT MAX(list_id) FROM vicidial_lists ".
//								" WHERE list_id REGEXP '^".mysqli_real_escape_string($_SESSION['db'], $type_code)."[[:digit:]]{2}".mysqli_real_escape_string($_SESSION['db'],$base_list_id)."$' ");
//
//		echo "Highest List ID in use: ".$last_id."\n";
//
//		$new_list_id = $last_id;
//		$new_list_id[2] = intval($new_list_id[2])+1;
//
//		// CREATE THE LIST ID
//		echo "New List ID to create: ".$new_list_id."\n";

		// RETURN THE NEW LIST ID

		$dat['list_id'] = $new_list_id;
		$dat['list_name'] = "AnsMach-".$campaign.'-'.$time_mode;
		$dat['campaign_id'] = $campaign;
		$dat['active'] = 'N';
		$dat['list_description'] = "Answering Machine generated ".$time_mode." rotation list";
		$dat['list_changedate'] = date("Y-m-d H:i:s");


		aadd($dat, "vicidial_lists");

		$list_id = mysqli_insert_id($_SESSION['db']);

		echo "Created LIST ID $list_id on ".$row['vici_cluster_id'].", copying custom fields...\n";


		execSQL(
			"INSERT INTO vicidial_lists_fields(`list_id`,`field_label`,`field_name`,`field_description`,`field_rank`,`field_help`,`field_type`,`field_options`,`field_size`,`field_max`,`field_default`,`field_cost`,`field_required`,`name_position`,`multi_position`, `field_order`) ".
			"SELECT $list_id,`field_label`,`field_name`,`field_description`,`field_rank`,`field_help`,`field_type`,`field_options`,`field_size`,`field_max`,`field_default`,`field_cost`,`field_required`,`name_position`,`multi_position`, `field_order` FROM vicidial_lists_fields ".
				"WHERE list_id='".$base_list_id."' "
		);



		return $list_id;
	}


	function handlePOST(){

	}

	function handleFLOW(){

	}



}
