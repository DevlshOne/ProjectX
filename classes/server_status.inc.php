<?	/***************************************************************
	 *	PX Server Status - Aggrigate data from all PX servers
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['server_status'] = new ServerStatus;


class ServerStatus{

	var $table		= 'servers';	## Classes main table to operate on
	var $orderby	= 'name';		## Default Order field
	var $orderdir	= 'ASC';		## Default order direction

	var $infopass_port = 2288; // WHAT PORT TO CONNECT TO, TO GRAB THE XML FROM PX SERVER




	var $soft_max_users = 90,
		$hard_max_users = 100;


	function ServerStatus(){


		## REQURES JXMLP PARSER!
		include_once($_SESSION['site_config']['basedir'].'classes/JXMLP.inc.php');


		$this->handlePOST();
	}


	function makeDD($name,$sel,$class,$onchange,$size){

		$names		= 'name';	## or Array('field1','field2')
		$value		= 'id';
		$seperator	= '';		## If $names == Array, this will be the seperator between fields


		$fieldstring='';
		if(is_array($names)){
			$x=0;
			foreach($names as $name){
				$fieldstring.= $name.',';
			}
		}else{	$fieldstring.=$names.',';}
		$fieldstring	.= $value;

		$sql = "SELECT $fieldstring FROM ".$this->table." WHERE running='yes' ";
		$DD = new genericDD($sql,$names,$value,$seperator);
		return $DD->makeDD($name,$sel,$class,1,$onchange,$size);
	}



	function handlePOST(){

	}

	function handleFLOW(){


		if(!checkAccess('server_status')){


			accessDenied("Server Status");

			return;

		}else{

			$this->makeStatusPage();

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




	function makeStatusPage(){

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
		}



		?>


		<table border="0" width="900" >
		<tr>
			<td colspan="3" align="center">

				<table border="0" align="center" width="900">
				<tr>
					<th height="30" colspan="2" class="ui-widget-header">Server Status</th>
				</tr>
				<tr>
					<th height="30">Total Users:</th>
					<td><?=number_Format($total_users)?></td>
				</tr>
				</table>
				<br />

			</td>
		</tr><?

		$x=0;
		foreach($xml_data as $idx=>$row){

			if($x++%3 == 0) echo '<tr>';


			$is_up = ($row['uptime'] && $row['connections'])? true:false;


			list($usercnt, $usermax) = preg_split("/\//", $row['connections']);

			$percent = round( ($usercnt / $this->hard_max_users) * 100 );

			?><td align="center" width="300">

				<table border="0" width="300" class="lb" >
				<tr>
					<td rowspan="2" width="100"><img src="<?=(($is_up)?"images/LiKLKBdia.png":"images/LiKLKBdia-down.png")?>" width="100" border="0" /></td>
					<td width="200" class="row2"><?=$servers[$idx]['name']?></td>
				</tr>
				<tr>
					<td><table border="0" width="100%">
					<tr>
						<th align="left">Status:</th>
						<td><?=($is_up)?'<span style="background-color:green">ONLINE</span>':'<span style="background-color:red">OFFLINE</span>'?></td>
					</tr><?

					if($is_up){
					?><tr>
						<th align="left">Uptime:</th>
						<td><?=htmlentities($row['uptime'])?></td>
					</tr>
					<tr>
						<th align="left">Users:</th>
						<td><?=$usercnt?>/<?=$this->hard_max_users?></td>
					</tr>

					<tr>
						<td colspan="2" align="center"><?


							?><input type="button" value="View Users" onclick="$('#users_server_<?=$idx?>').dialog({width: 700, height: 480, title: 'Users of <?=$servers[$idx]['name']?>' } );" ><?


						?></td>
					</tr><?

					}

					?></table>

					<div id="users_server_<?=$idx?>" class="nod">

						<table border="0" width="100%">
						<tr>
							<th class="row2" align="left">User</th>
							<th class="row2">Extension</th>
							<th class="row2">Campaign</th>
							<th class="row2">Login Duration</th>
							<th class="row2">Volume Settings</th>
							<th class="row2">Agent Phone</th>
							<th class="row2">Vici Login</th>
							<th class="row2">Script</th>
							<th class="row2">In Live Call?</th>
							<th class="row2">Duration</th>
							<th class="row2">Calls</th>
						</tr><?

						$userarr = $_SESSION['JXMLP']->grabTagArray($xml_array[$idx], "PXUser", true);

						foreach($userarr as $pxuserxml){

							$pxuser = $_SESSION['JXMLP']->parseOne($pxuserxml, 'PXUser', 1);

							$campaign_name = $_SESSION['dbapi']->campaigns->getViciID($pxuser['campaign']);

							?><tr>
								<td><?=$pxuser['username']?></td>
								<td align="center"><?=$pxuser['extension']?></td>
								<td align="center"><?=$campaign_name.' ('.$pxuser['campaign'].')'?></td>
								<td align="center"><?=$pxuser['login_duration']?></td>
								<td align="center"><?=$pxuser['volume_settings']?></td>
								<td align="center"><?

									if($pxuser['has_callers'] == 'Yes'){
										echo $pxuser['has_callers'];
									}else{
										echo '<span class="redbg">'.$pxuser['has_callers'].'</span>';
									}
								?></td>
								<td align="center"><?

									if($pxuser['has_vici'] == 'Yes'){
										echo '<span class="greenbg">'.$pxuser['has_vici'].'</span>';
									}else{
										echo '<span class="yellowbg">'.$pxuser['has_vici'].'</span>';
									}
								?></td>
								<td align="center"><?

									if($pxuser['has_linphone'] == 'Active'){
										echo '<span class="greenbg">'.$pxuser['has_linphone'].'</span>';
									}else{
										echo '<span class="redbg">'.$pxuser['has_linphone'].'</span>';
									}
								?></td>
								<td align="center"><?

									if($pxuser['live_call'] == 'Yes'){
										echo '<span class="greenbg">'.$pxuser['live_call'].'</span>';
									}else{
										echo '<span class="yellowbg">'.$pxuser['live_call'].'</span>';
									}
								?></td>
								<td align="center"><?=$pxuser['call_duration']?></td>
								<td align="center"><?=number_format($pxuser['call_count'])?></td>

							</tr><?
						}

						?></table>

					</div>


					</td>
				</tr>
				</table>

			</td><?


			if($x%3 == 0) echo '</tr>';
		}

		if($x % 3 != 0){
			?><td colspan="<?=(3 - ($x%3))?>">&nbsp;</td></tr><?
		}


		?></table><?



	//print_r($xml_data);


	}



}
