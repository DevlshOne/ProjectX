<?	/***************************************************************
	 *	Names - Handles list/search/import names
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['ringing_calls'] = new RingingCalls;


class RingingCalls{


	function RingingCalls(){


		## REQURES DB CONNECTION!
		$this->handlePOST();
	}


	function handlePOST(){


	}

	function handleFLOW(){


	}



	function grabData($starttime, $endtime){

		connectCCIDB();

		$res = query("SELECT MID(agent_id,1,3), `date`, CAST(hours as decimal(5,2)) FROM employee_hours ".
				" WHERE `date` BETWEEN '".date("Y-m-d", $starttime)."' AND '".date("Y-m-d", $endtime)."' ".
				" AND hours != 0 AND MID(agent_id,1,3) IN ".
					"(SELECT DISTINCT MID(agent_id,1,3) FROM employees WHERE RIGHT(call_group,5) LIKE 'south')");

		$rowarr = array();

		$x=0;
		while($row = mysqli_fetch_row($res)){

			$rowarr[$x] = array();
			$rowarr[$x]['user'] = $row[0];
			$rowarr[$x]['date'] = $row[1];
			$rowarr[$x]['hours'] = $row[2];

			$x++;
		}

		return $rowarr;
	}


	/*function makeBilledHours(){



		?><form method="POST" action="<?//fuck it going to bed
	}*/


}
