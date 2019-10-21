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

	/**
	 * Gets list of running servers by cluster ID
	 * @param $cluster_id	The value of the cluster id search drop down
	 */
	function getRunningServers($cluster_id){

		# CHECK IF [ALL] IS SELECTED
		if($cluster_id > -1){
				
			# BUILD CLUSTER ID SEARCH
			$sql_cluster = " AND cluster_id='".$_SESSION['site_config']['db'][$cluster_id]['cluster_id']."' ";
			
		} else {

			# LEAVE BLANK BECAUSE WE WANT ALL SERVERS
			$sql_cluster = "";

		}

		# GENERATE FULL SQL STATEMENT AND EXECUTE
		$sql = "SELECT * FROM `servers` WHERE `running`='yes' ".$sql_cluster." ORDER BY `name` ASC";

		$res = $_SESSION['dbapi']->query($sql);

		# BUILD ARRAY TO OUTPUT
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

		# CREATE CURL RESOURCE
		$ch = curl_init();

        # SET URL
        curl_setopt($ch, CURLOPT_URL, "http://".$server['ip_address'].":".$this->infopass_port."/Status?xml_mode=true");

        # SET OPTIONS AND RETURN TRANSFER AS STRING
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        # GRAB OUTPUT STRING
		$output = curl_exec($ch);

        # CLOSE CURL CONNECTION AND RETURN OUTPUT
        curl_close($ch);

		return $output;
	}

	function makeClusterDD($name, $selected, $css, $onchange) {
		
		$out = '<select name="'.$name.'" id="'.$name.'" ';

		$out .= ($css)?' class="'.$css.'" ':'';
		$out .= ($onchange)?' onchange="'.$onchange.'" ':'';
		$out .= '>';

		$out .= '<option value="-1" '.(($selected == '-1')?' SELECTED ':'').'>[All]</option>';


		foreach($_SESSION['site_config']['db'] as $dbidx=>$db){

			$out .= '<option value="'.$dbidx.'" ';
			$out .= ($selected == $dbidx)?' SELECTED ':'';
			$out .= '>'.htmlentities($db['name']).'</option>';
		}

		$out .= '</select>';

		return $out;

	}

	
	function makeHTMLTable($data_array){

		## LIST THROUGH THE PASSED ARRAY AND CREATE THE HTML FOR THE TABLE
		if (count($data_array) < 1) {
            return null;
        }

        # ACTIVATE OUTPUT BUFFERING AND GENERATE HTML TABLE
        ob_start();
		ob_clean(); ?><table id="user_status_report_table" style="width:100%" border="0"  cellspacing="1">
		<thead>
		<tr>
			<th align="left" width="150">Server Name</th>
			<th align="left" width="150">User Group</th>
			<th align="left" width="150">Username</th>
			<th align="left" width="150">Campaign</th>
			<th align="left" width="150">Voice</th>
			<th align="left" width="150">Login Duration</th>
		</tr>
		</thead>
		<tbody><?


		# LOOP THROUGH EACH ARRAY ENTRY AND DISPLAY DATA
		foreach($data_array as $pxuser){

			?><tr>			
			<td><?=htmlentities($pxuser['server_name'])?></td>
			<td><?=htmlentities($pxuser['user_group'])?></td>
			<td><?=htmlentities($pxuser['username'])?></td>
			<td><?=htmlentities($_SESSION['dbapi']->campaigns->getName($pxuser['campaign_id']))?></td>
			<td><?=htmlentities($_SESSION['dbapi']->voices->getName($pxuser['voice_id']))?></td>
			<td><?=htmlentities($pxuser['login_duration'])?></td>
			</tr><?

		}

		?></tbody>
		</table><?

		# GRAB DATA FROM BUFFER
		$data = ob_get_contents();

		# TURN OFF OUTPUT BUFFERING, WITHOUT OUTPUTTING
		ob_end_clean();

		# RETURN HTML
		return $data;

	}


	function makeUserStatusReport(){

		## CREATE SEARCH FORM
		?><form id="user_status_report" method="POST" action="<?=$_SERVER['PHP_SELF']?>?area=user_status_report&no_script=1" onsubmit="return genReport(this, 'user_status_report')">

		<input type="hidden" name="generate_report">

		<table border="0" width="100%">
			<tr>
				<td height="40" class="pad_left ui-widget-header">User Status Report</td>
			</tr>
			<tr>
				<td colspan="2">
					<table border="0">
						<tr>
							<th>Agent Cluster:</th>
							<td><?

								echo $this->makeClusterDD("agent_cluster_id", (!isset($_REQUEST['agent_cluster_id']) || intval($_REQUEST['agent_cluster_id']) < 0)?-1:$_REQUEST['agent_cluster_id'], '', ""); ?></td>
						</tr>
						<tr>
							<th>User Group:</th>
							<td><?

								echo makeViciUserGroupDD("user_group[]", $_REQUEST['user_group'], '', "", 7)

							?></td>
						</tr>
						<tr>
							<th colspan="2">

								<span id="user_status_report_loading_plx_wait_span" class="nod"><img src="images/ajax-loader.gif" border="0" /> Loading, Please wait...</span>
								<span id="user_status_report_submit_report_button">
									<input type="submit" value="Generate">
								</span>
							</th>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</form><br /><br /><?

		##
		## GENERATE REPORT BY CONNECTING TO SERVERS AND GRABBING USER XML BASED ON SEARCH FIELDS
		##

		if (isset($_POST['generate_report'])) {

            # SET AGENT CLUSTER ID FROM POST AND GET LIST OF SERVERS
			$agent_cluster_id = intval($_REQUEST['agent_cluster_id']);
			$servers = $this->getRunningServers($agent_cluster_id);

			# SET USER GROUP FROM POST
			$user_group = $_REQUEST['user_group'];
			
			# INITIALIZE XML ARRAYS
			$xml_array = array();
			$xml_data = array();
			$xml_user = array();
			$px_user = array();
			$user_data = array();
			$i=0;

			# LIST THROUGH EACH RUNNING SERVER AND GRAB XML
			foreach($servers as $idx=>$server){
			
				# GET XML FROM SERVER AS ARRAY
				$xml_array[$idx] = $this->grabServerXML($server);

				# CHECK IF WE RECEIVED DATA BACK FROM THE SERVER GET XML REQUEST
				if($xml_array[$idx]){

					# GET PXUSERS TAG FROM SERVER XML
					$xml_data[$idx] = $_SESSION['JXMLP']->parseOne($xml_array[$idx], 'PXUsers', false);

					# GET PXUSER ARRAY FROM SERVER XML
					$xml_user = $_SESSION['JXMLP']->grabTagArray($xml_array[$idx], "PXUser", true);

					# LOOP THROUGH EACH ACTIVE USER
					if(is_array($xml_user)){

						foreach($xml_user as $pxuser){

							# GRAB PXUSER ENTRY AND ADD IT TO THE DATA ARRAY
							$px_user = $_SESSION['JXMLP']->parseOne($pxuser, 'PXUser', 1);

							# WE NEED TO CHERRY PICK DATA BECAUSE WE NEED THE SERVER NAME
							$user_data[$i]['username'] = $px_user['username'];
							$user_data[$i]['campaign_id'] = $px_user['campaign_id'];
							$user_data[$i]['voice_id'] = $px_user['voice_id'];
							$user_data[$i]['user_group'] = $px_user['user_group'];
							$user_data[$i]['login_duration'] = $px_user['login_duration'];
							$user_data[$i]['server_name'] = $server['name'];

							$i++;

						}

					} else {

						# NO USERS FOUND FOR THIS SERVER
						
					}

				} else {

					# NO DATA RETURNED FROM SERVER

				}

				
			}

			##
			## USER DATA ARRAY FILTERING BY ALL, SINGLE OR MULTIPLE USER GROUPS
			##

			# INITIALIZE NEW FILTERED ARRAY SO WE CAN APPEND FILTERED DATA TO IT
			$filtered_array = array();

			# MAKE SURE USER GROUP HAS DATA
			if($user_group){
			
				# CHECK IF USER GROUP IS AN ARRAY	
				if(is_array($user_group)){
					
					# IF ARRAY HAS MORE THAN ONE VALUE AND THE FIRST ENTRY IS NOT [ALL]
					if(count($user_group) > 0 && trim($user_group[0]) != ''){
											
						# LOOP THROUGH SELECTED USER GROUPS AND APPEND TO FILTERED ARRAY IF MATCH
						foreach($user_group as $group){

							# APPEND TO FILTERED ARRAY USING ARRAY FILTER, USING PASSED FUNCTION TO RETURN ON USER GROUP MATCH
							$filtered_array[] = array_filter($user_data, function ($var) use ($group) {

								# USING STRTOLOWER TO STRIP CASE AND IGNORE CASE SENSITIVITY
								return (strtolower($var['user_group']) == strtolower($group));

							});
							
						}
						
					} else {

						# USER GROUP ARRAY HAS DATA BUT [ALL] IS SELECTED, JUST MAKE FILTERED ARRAY ALL DATA
						$filtered_array[] = $user_data;

					}
					
				}

			}

            ## GENERATE HTML TABLE AND DISPLAY DATA
            $html = $this->makeHTMLTable($filtered_array[0]);

            if ($html == null) {
                echo '<span style="font-size:14px;font-style:italic;"><br />No results found for the specified values.</span><br />';
            } else {
                echo $html;
            }

			## DISPLAY DATA TABLE
            if (!isset($_REQUEST['no_nav'])) {
                ?><script>
					$(document).ready( function () {

					    $('#user_status_report_table').DataTable({

							"lengthMenu": [[ -1, 20, 50, 100, 500], ["All", 20, 50, 100,500 ]]


					    });



					} );

				</script><?
            }

		}


	}


}

