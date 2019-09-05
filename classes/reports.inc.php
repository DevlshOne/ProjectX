<?	/***************************************************************
	 *	Reports - The replacement report system for "Andrews Labryinth" (tm)
	 *	Written By: Jonathan Will
	 ***************************************************************/


// INITIALIZE THE REPORT OBJECT IN SESSION, SO IT IS ACCESSABLE BY THE REST OF THE SYSTEM/CODE
$_SESSION['reports'] = new Reports();


class Reports{

	var $vici_db_name = "asterisk";

	// ARRAY OF DATABASE CONNECTIONS, TO VICI
	// POPULATED BY THE connectViciDBServers() function
	var $vicidbs = array();

	var $ccidb = array();




	function Reports(){

		// ASSUMES ALREADY CONNECTED TO PX DB VIA DBAPI
		// $_SESSION['dbapi']->query()/querySQL() functions

		// HANDLE FORM SUBMITS HERE
		$this->handlePOST();

	}


	function handlePOST(){


	}

	/**
	 * MAIN FLOW FUNCTION
	 */
	function handleFLOW(){





	}


















































	// I'M ALL THE WAY DOWN HERE, HIDING FROM THE MANOTAURS IN ANDREWS LABYRINTH

	/**
	 * Generates reports from each vicidial cluster for the day
	 */
	function generateDailyReports(){

		$start_time = mktime(0,0,0); // hours, minutes, seconds (month day year default to today)
		$end_time = mktime(23,59,59); // END OF THE DAY, 11:59:59pm

		// CONNECT TO THE POOL OF VICI SERVERS
		$this->connectViciDBServers();


		$output = "";

		foreach($this->vicidbs as $vicidb){

			$output .= $this->generateSalesAnalysis($vicidb, $start_time, $end_time);

		}



		return $output;
	}



	function generateSalesAnalysis($vicidb, $start_time, $end_time){
		$output = "";





		return $output;
	}


	/**
	 * Connect the array of vici connections
	 */
	function connectViciDBServers(){

		$res = $_SESSION['dbapi']->query(
						"SELECT * FROM vici_clusters ".
						" WHERE `status`='enabled' ".
						" ORDER BY name ASC "
						);
		$x=0;

		// DISCONNECT/CLEAN UP OLD ONES, IF THEY ARE THEIR
		if(count($this->vicidbs) > 0){

			$this->disconnectViciDBServers();

		}

		// (RE)INIT THE VICIDBS ARRAY
		$this->vicidbs = array();

		// LOOP THROUGH THE QUERY RESULTS
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			// COMBINE HOST AND PORT, FOR REUSE LATER
			$mysql_host_port = $row['ip_address'].":".$row['port'];

			$this->vicidbs[$x] = mysqli_connect(
									$mysql_host_port, // HOST:PORT
									$row['db_user'],// USERNAME
									$row['db_pass'],// PASSWORD
									$this->vici_db_name
								 );

			if(!$this->vicidbs[$x]){

				echo "Failed to connect on #"+$x+" to ".$mysql_host_port;
				continue;
			}

//			// SELECT THE DATABASE "asterisk" usually
//			if(!mysql_select_db($this->vici_db_name)){
//
//				echo "Failed to select DB "+$this->vici_db_name+" on conn #"+$x+" to ".$mysql_host_port;
//				continue;
//			}


			$x++;
		}


	}



	function disconnectViciDBServers(){

		foreach($this->vicidbs as $vicidb){

			mysqli_close($vicidb);

		}


	}



	/**
	 * Grabs an array of usernames from the specified vici db connection
	 * @param $vicidb 	A connected vici database connection, usually from $this->vicidbs array()
	 */
	function getViciUsers($vicidb){

		$output = array();

		$res = mysqli_query(

					// FROM THIS DB CONNECTION
					$vicidb,

					"SELECT LEFT(user, 3) AS user FROM vicidial_users ".
					" WHERE user_group LIKE '%SYSTEM%'"

//					"SELECT LEFT(vicidial_users.user, 3) AS user, COUNT(vicidial_agent_log.agent_log_id) as total_calls  FROM vicidial_agent_log ".
//					" RIGHT JOIN vicidial_users ON vicidial_agent_log.user = vicidial_users.user ".
//					" WHERE vicidial_users.user_group LIKE '%SYSTEM%'",


				);


	}











}
