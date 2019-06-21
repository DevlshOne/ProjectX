<?	/***************************************************************
	 *	Answering Machines - Tools to handle recalling them/list ninjitsu
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['answering_machines'] = new AnsweringMachines;



class AnsweringMachines{



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
	 */
	function generateLeadStack($dispo, $num_times, $stime, $etime, $timeframe = 'AM'){

		$out = array();

		$res = querySQL(
				"SELECT `lead_id` FROM `lead_tracking` ".
				" WHERE `time` BETWEEN '$stime' AND '$etime' ".
				" AND `dispo`='".mysqli_real_escape_string($_SESSION['db'], $dispo)."' ".
				""
			);




		return $out;
	}






	function handlePOST(){

	}

	function handleFLOW(){

	}



}
