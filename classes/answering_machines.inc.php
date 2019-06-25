<?	/***************************************************************
	 *	Answering Machines - Tools to handle recalling them/list ninjitsu
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['answering_machines'] = new AnsweringMachines;



class AnsweringMachines{


	var $call_attempts_move_limit = 10;
	var $call_attempts_dnc_limit = 15;

	var $dnc_default_duration = 15768000; // (15768000 = Half a year)


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
				"SELECT `lead_id`,`vici_cluster_id`, `phone_num` FROM `lead_tracking` ".
				" WHERE `time` BETWEEN '$stime' AND '$etime' ".
				" AND `dispo`='".mysqli_real_escape_string($_SESSION['db'], $dispo)."' ".

				(($timeframe == 'AM' || $timeframe == 'PM')?" AND FROM_UNIXTIME(time,'%p')='".$timeframe."'":'').

				$ignore_list_sql.

				"", 1);


		echo date("h:i:s m/d/Y")." - ".number_format(mysqli_num_rows($res))." records to be processed.\n";

		$z=0;
		while($data = mysqli_fetch_row($res)){

			list($lead_id, $vici_cluster_id, $phone) = $data;

			$re2 = query(
					"SELECT `id`,`lead_id`,`vici_cluster_id`,`time`,`dispo` FROM `lead_tracking` ".
					" WHERE `phone_num`='".mysqli_real_escape_string($_SESSION['db'], $phone)."' ".
					(($timeframe == 'AM' || $timeframe == 'PM')?" AND FROM_UNIXTIME(time,'%p')='".$timeframe."'":'').
					"",1);

			$bad_dispo_cnt = 0;

			while($row = mysqli_fetch_array($re2, MYSQLI_ASSOC)){

				if(strtoupper($row['dispo']) != strtoupper($dispo)){
					$bad_dispo_cnt++;
					break;
				}



			}

			// IF EVEN A SINGLE BAD DISPO FOUND, REJECT THE LEAD
			if($bad_dispo_cnt > 0){

				continue;
			}

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
		}



		return $out;
	}




	function extractAnswerDispos($timeframe){

			$stime = mktime(0,0,0);
			$etime = mktime(23,59,59);


			$ignore_list_sql = '';




			$arr = $this->generateLeadStack('A', $this->call_attempts_move_limit, $stime, $etime, $timeframe, $ignore_list_sql);



			echo date("h:i:s m/d/Y")." - Successfully extracted ".count($arr)." leads.\n";



	}




	function handlePOST(){

	}

	function handleFLOW(){

	}



}
