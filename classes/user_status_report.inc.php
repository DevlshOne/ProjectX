<?	/***************************************************************
	 *	PX Server Status - Aggrigate data from all PX servers
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['user_status_report'] = new UserStatusReport;


class UserStatusReport{

	var $infopass_port = 2288; // WHAT PORT TO CONNECT TO, TO GRAB THE XML FROM PX SERVER


	function UserStatusReport(){


		## REQURES JXMLP PARSER!
		include_once($_SESSION['site_config']['basedir'].'classes/JXMLP.inc.php');


		$this->handlePOST();
	}



	function handlePOST(){

	}

	function handleFLOW(){


		if(!checkAccess('user_status_report')){


			accessDenied("User Status Report");

			return;

		}else{

			$this->makeUserStatusReport();

		}

	}

	function getRunningServers(){

		$res = $_SESSION['dbapi']->query("SELECT * FROM `servers` WHERE `running`='yes' ORDER BY `name` ASC");

		$rowarr = array();
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
			$rowarr[] = $row;
		}

		return $rowarr;
	}


	/**
	 * Connects to the PX server, and attempts to extract the XML from the report system via INFOPASS port
	 * @param $server	The Associative array of a single record from PX servers table
	 */
	function grabServerXML($server){

		// create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, "http://".$server['ip_address'].":".$this->infopass_port."/Status?xml_mode=true");


		//curl_setopt($ch, CURLOPT_URL, "http://10.100.0.12:".$this->infopass_port."/Status?xml_mode=true");


        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // $output contains the output string
        $output = curl_exec($ch);

        // close curl resource to free up system resources
        curl_close($ch);

		return $output;
	}




	function makeUserStatusReport(){





		?><table border="0" width="900" >
		<tr>
			<td colspan="3" align="center">

				<table border="0" align="center" width="900">
				<tr>
					<th height="30" colspan="2" class="ui-widget-header">User Status Report</th>
				</tr>
				<tr>
					<th height="30">Total Users:</th>
					<td><?=number_Format($total_users)?></td>
				</tr>
				</table>
				<br />

			</td>
		</tr><?
		
		
		
		/*
		// GATHER DATA
		$servers = $this->getRunningServers();
		
		$xml_array = array();
		$xml_data = array();
		$total_users = 0;
		foreach($servers as $idx=>$server){
			
			
			//print_r($server);
			$xml_array[$idx] = $this->grabServerXML($server);
			
			
			$xml_data[$idx] = $_SESSION['JXMLP']->parseOne($xml_array[$idx], 'PXUsers', false);
			
			list($usercnt, $usermax) = preg_split("/\//", $xml_data[$idx]['connections']);
			
			$total_users += $usercnt;
		}*/

// 						$userarr = $_SESSION['JXMLP']->grabTagArray($xml_array[$idx], "PXUser", true);


// 							$pxuser = $_SESSION['JXMLP']->parseOne($pxuserxml, 'PXUser', 1);

// 							$campaign_name = $_SESSION['dbapi']->campaigns->getViciID($pxuser['campaign']);


		?></table><?



	//print_r($xml_data);


	}



}
