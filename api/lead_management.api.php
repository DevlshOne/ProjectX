<?



class API_Lead_Management{
	
	var $xml_parent_tagname = "Leads";
	var $xml_record_tagname = "Lead";
	
	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";
	
	
	
	function deleteSale($lead_tracking_id){
		
		$lead_tracking_id = intval($lead_tracking_id);
		
		list($sale_time) = queryROW("SELECT `sale_time` FROM `sales` WHERE transfer_id='$transfer_id' AND lead_tracking_id='$lead_tracking_id' ORDER BY id DESC LIMIT 1");
		
		$this->removeSaleFromCCIData($lead_tracking_id, $sale_time);
		
		execSQL("DELETE FROM `sales` WHERE lead_tracking_id='$lead_tracking_id' ORDER BY id DESC LIMIT 1");
	}
	
	function deleteSaleByXFER($lead_tracking_id,$transfer_id){
		
		
		$lead_tracking_id = intval($lead_tracking_id);
		$transfer_id = intval($transfer_id);
		
		list($sale_time) = queryROW("SELECT `sale_time` FROM `sales` WHERE transfer_id='$transfer_id' AND lead_tracking_id='$lead_tracking_id' ORDER BY id DESC LIMIT 1");
		
		$this->removeSaleFromCCIData($lead_tracking_id, $sale_time);
		
		execSQL("DELETE FROM `sales` WHERE transfer_id='$transfer_id' AND lead_tracking_id='$lead_tracking_id' ORDER BY id DESC LIMIT 1");
	}
	
	
	/**
	 * createSaleOnCCIData($row)
	 *
	 * I created this function, for incase they created a sale with the wrong date, so it didn't sync to CCIData.
	 *
	 * We later discovered that it was using "vici_last_local_call_time" field, which is why it was sending the older date
	 *
	 * Going to skip hooking this function up to anything, until its determined that we actually need it for a valid purpose
	 *
	 * -Jon - 8/9/2019
	 *
	 * @param unknown $row SALE ROW/RECORD
	 */
	function createSaleOnCCIData($row){
		
		
		
		$cluster = getClusterRow($row['agent_cluster_id']);
		
		$cluster_ip = $cluster['ip_address'];
		
		$cluster = null; /// CLEAN IT UP
		
		// PART OF CCI-DATA'S INDEX, IS THE SALE DATE, SO THIS HAS TO MATCH UP, OR WE GET DUPES
		$date = date("m/d/Y", $row['sale_time']);
		$time = date("H:i:s", $row['sale_time']);
		
		
		$start_sql = "REPLACE INTO `leads` (`lead_id`,`phone`,`agent_id`,`agent_name`,`sales_date`,`sales_time`,".
				"`last_name`,`first_name`,`contact`,`address1`,`address2`,`city`,`state`,`zip`,`campaign`,`list_id`,".
				"`sale_amount`,`verifier`,`office`,`call_group`,`server`) VALUES ";
		
		$sql = $start_sql;
		
		$sql .= "('".addslashes($row['agent_lead_id'])."',".
				"'".addslashes($row['phone'])."',".
				"'".addslashes(strtoupper($row['agent_username']))."',".
				"'".addslashes(strtoupper($row['agent_name']))."',".
				"'".addslashes($date)."',".
				"'".addslashes($time)."',".
				"'".addslashes(strtoupper($row['last_name']))."',".
				"'".addslashes(strtoupper($row['first_name']))."',".
				"'".addslashes(strtoupper($row['first_name']))."',".
				"'".addslashes(strtoupper($row['address1']))."',".
				"'".addslashes(strtoupper($row['address2']))."',".
				"'".addslashes(strtoupper($row['city']))."',".
				"'".addslashes(strtoupper($row['state']))."',".
				"'".addslashes($row['zip'])."',".
				"'".addslashes(strtoupper($row['campaign']))."',".
				"'".addslashes(strtoupper($row['campaign_code']))."',".
				"'".addslashes($row['amount'])."',".
				"'".addslashes(strtoupper($row['verifier_username']))."',".
				"'".(($row['office'])?addslashes(strtoupper($row['office'])) : "90")."',".
				"'".addslashes(strtoupper($row['call_group']))."',".
				"'".addslashes($cluster_ip)."')";
		
		// CONNECT TO CCIDATA
		connectCCIDB();
		
		$cnt = execSQL($sql);
		
		// CONNECT BACK TO PX
		connectPXDB();
		
		return $cnt;
	}
	
	
	function removeSaleFromCCIData($lead_tracking_id, $sale_time){
		
		// LOAD THE LEAD INFORMATION
		$row = $_SESSION['dbapi']->lead_management->getByID($lead_tracking_id);
		
		
		// FALL BACK TO LEAD TIME, JUST IN CASE
		if(!$sale_time){
			$sale_time = $row['time'];
		}
		
		// CONNECT TO CCI DB
		connectCCIDB();
		
		// REMOVE THE SALE RECORD
		
		$sql = "DELETE FROM `leads` ".
				" WHERE `phone`='".mysqli_real_escape_string($_SESSION['db'], $row['phone_num'])."' ".
				" AND `lead_id`='".intval($row['lead_id'])."' ".
				" AND `sales_date`='".date("m/d/Y", $sale_time)."' ".
				" AND `campaign`='".mysqli_real_escape_string($_SESSION['db'], $row['campaign'])."' ".
				" AND `office`='".intval($row['office'])."' ".
				" LIMIT 1"; //mysqli_real_escape_string($_SESSION['db'], $row['phone_num'])
		
		//		echo $sql."\n";exit;
		
		execSQL($sql);
		
		
		// CONNECT BACK TO PX WHEN WE'RE DONE
		connectPXDB();
	}
	
	function getCallGroup($user_id, $cluster_id){
		
		$user_id = intval($user_id);
		$cluster_id = intval($cluster_id);
		
		connectPXDB();
		
		list($group) = queryROW("SELECT group_name FROM user_group_translations WHERE user_id='$user_id' AND cluster_id='$cluster_id'");
		
		return $group;
	}
	
	
	
	function syncDataChangesToDRIPP($lead_tracking_id){
	
		
// PRODUCTION URL, DISABLED FOR TESTING A BUG 		
//		$url = "https://dripp.advancedtci.com/dripp/pages/update_transaction.php";

// TESTING URL		
//		//$url = "http://10.101.15.101/dripp/pages/update_transaction.php";
		
		
		$url = "https://dripp.advancedtci.com/dripp/pages/update_transaction.php";
		
		
		
		$lead_tracking_id = intval($lead_tracking_id);
		
		// CONNECT PX DB
		connectPXDB();
		
		// LOAD THE LEAD RECORD
		$row = querySQL("SELECT * FROM lead_tracking WHERE id='$lead_tracking_id' ");
		
		
		
		switch($row['dispo']){
			// ANYTHING NOT SPECIFIED HERE, SKIP/DONT ATTEMPT TO UPDATE DRIPP
			default:
				
				// NON SALE - DELETE FROM DRIPP??
				
				return;
				
				break;
			case 'SALECC':
			case 'PAIDCC':
				
				// DO NOTHING/ ALLOW IT TO CONTINUE
				break;
		}
		
		
		
		$dat = array();
		$dat['phone'] = $row['phone_num'];
		$dat['project_id'] = $row['campaign'];
		
		$dat['first_name'] = $row['first_name'];
		$dat['last_name'] = $row['last_name'];
		$dat['address1'] = $row['address1'];
		$dat['address2'] = $row['address2'];
		$dat['city'] = $row['city'];
		$dat['state'] = $row['state'];
		$dat['zip'] = $row['zip_code'];
		
		
		$dat['comments'] = $row['comments'];
		
		$dat['occupation'] = $row['occupation'];
		$dat['employer'] = $row['employer'];
		
		
		$ch = curl_init($url);
		
		//return the transfer as a string
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $dat);
		
		// $output contains the output string
		$output = curl_exec($ch);
		
		// close the connection, release resources used
		curl_close($ch);
		
		
		//echo "DRIPP UPDATE OUTPUT: <br />\n";
		//print_r($output);
		
		return $output;
	}
	
	function syncDataChangesToSales($lead_tracking_id){
		
		$lead_tracking_id = intval($lead_tracking_id);
		
		// CONNECT PX DB
		connectPXDB();
		
		// LOAD THE LEAD RECORD
		$row = querySQL("SELECT * FROM lead_tracking WHERE id='$lead_tracking_id' ");
		
		$dat = array();
		$dat['first_name'] = $row['first_name'];
		$dat['last_name'] = $row['last_name'];
		$dat['contact'] = ($row['contact'])?$row['contact']:$row['first_name'];
		
		$dat['address1'] = $row['address1'];
		$dat['address2'] = $row['address2'];
		$dat['city'] = $row['city'];
		$dat['state'] = $row['state'];
		$dat['zip'] = $row['zip_code'];
		
		
		// SHOULD ONLY HAVE 1 RECORD, BUT JUST IN CASE
		$res = query("SELECT id FROM sales WHERE lead_tracking_id='$lead_tracking_id'", 1);
		
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
			
			
			aedit($row['id'], $dat, 'sales');
			
		}
		
	}
	
	
	function syncDataToViciCluster($lead_tracking_id){
		
		$lead_tracking_id = intval($lead_tracking_id);
		
		// CONNECT PX DB
		connectPXDB();
		
		// LOAD THE LEAD RECORD
		$row = querySQL("SELECT * FROM lead_tracking WHERE id='$lead_tracking_id' ");
		
		
		// CHECK FOR XFERS/SALES
		/// IF SO, LOAD THAT DATA TOO
		$xfer = querySQL("SELECT * FROM transfers WHERE lead_tracking_id='$lead_tracking_id' ORDER BY id DESC LIMIT 1");
		$sale = ($xfer)? querySQL("SELECT * FROM sales WHERE lead_tracking_id='$lead_tracking_id' AND transfer_id='".$xfer['id']."' ")  :  null;
		
		
		// PREP THE "vicidial_list" DATA!
		$dat = array();
		$dat['first_name'] = $row['first_name'];
		$dat['middle_initial'] = $row['middle_initial'];
		$dat['last_name'] = $row['last_name'];
		
		$dat['address1'] = $row['address1'];
		$dat['address2'] = $row['address2'];
		//$dat['address3'] = $row['address3'];
		
		$dat['city'] = $row['city'];
		$dat['state'] = $row['state'];
		$dat['postal_code'] = $row['zip_code'];
		
		
		
		
		
		
		// JUST UPDATE 1
		if($row['vici_cluster_id'] == $row['verifier_vici_cluster_id'] || $row['verifier_vici_cluster_id'] <= 0){
			
			
			$cluster_idx = getClusterIndex($row['vici_cluster_id']);
			
			
			/// CONNECT TO VICI
			connectViciDB($cluster_idx);
			
			
			// EDIT "vicidial_list" TABLE	$field,$id,$assoarray,$table)
			$affected = aeditByField('lead_id',$row['lead_id'], $dat, "vicidial_list");
			
			
			// YOU SNEAKY FUCK
			// RE-ENABLING 7/18/2019
			$dat['status'] = $row['dispo'];
			
			
			// UPDATE BOTH
		}else{
			
			// UPDATE AGENT CLUSTER
			$cluster_idx = getClusterIndex($row['vici_cluster_id']);
			
			/// CONNECT TO VICI
			connectViciDB($cluster_idx);
			
			// EDIT "vicidial_list" TABLE	$field,$id,$assoarray,$table)
			$affected = aeditByField('lead_id',$row['lead_id'], $dat, "vicidial_list");
			
			
			// UPDATE VERIFIER CLUSTER
			
			
			$cluster_idx = getClusterIndex($row['verifier_vici_cluster_id']);
			
			
			/// CONNECT TO VICI
			connectViciDB($cluster_idx);
			
			// YOU SNEAKY FUCK
			// RE-ENABLING 7/18/2019
			$dat['status'] = $row['dispo'];
			
			// EDIT "vicidial_list" TABLE	$field,$id,$assoarray,$table)
			$affected = aeditByField('lead_id',$row['verifier_lead_id'], $dat, "vicidial_list");
			
			
			
		}
		
		
		// ADD TEH SALE RECORD INFO (custom_xxx
		if($xfer && $sale){
			
			
			
			
			
		}
		
		
	}
	
	
	
	
	
	
	
	
	function handleAPI(){
		
		if(!checkAccess('lead_management')){
			
			
			$_SESSION['api']->errorOut('Access denied to Lead Management');
			
			return;
		}
		
		
		
		//		if($_SESSION['user']['priv'] < 5){
		//
		//
		//			$_SESSION['api']->errorOut('Access denied to non admins.');
		//
		//			return;
		//		}
		
		switch($_REQUEST['action']){
			case 'delete':
				
				$id = intval($_REQUEST['id']);
				
				//$row = $_SESSION['dbapi']->campaigns->getByID($id);
				
				
				$_SESSION['dbapi']->lead_management->delete($id);
				
				
				logAction('delete', 'lead_management', $id, "");
				
				
				
				$_SESSION['api']->outputDeleteSuccess();
				
				
				break;
				
			case 'view':
				
				
				$id = intval($_REQUEST['id']);
				
				$row = $_SESSION['dbapi']->lead_management->getByID($id);
				
				
				
				
				## BUILD XML OUTPUT
				$out = "<".$this->xml_record_tagname." ";
				
				foreach($row as $key=>$val){
					
					
					$out .= $key.'="'.htmlentities($val).'" ';
					
				}
				
				$out .= " />\n";
				
				
				
				
				
				
				///$out .= "</".$this->xml_record_tagname.">";
				
				echo $out;
				
				
				
				break;
			case 'edit':
				
				$id = intval($_POST['editing_lead']);
				
				if($id > 0){
					$row = $_SESSION['dbapi']->lead_management->getByID($id);
				}
				
				
				unset($dat);
				
				
				$dat['first_name'] = trim($_POST['first_name']);
				$dat['last_name'] = trim($_POST['last_name']);
				
				$dat['address1'] = trim($_POST['address1']);
				$dat['address2'] = trim($_POST['address2']);
				$dat['city'] = trim($_POST['city']);
				$dat['state'] = trim($_POST['state']);
				$dat['zip_code'] = trim($_POST['zip_code']);
				
				$dat['comments'] = trim($_POST['comments']);
				
				if(isset($_POST['occupation'])){
					$dat['occupation'] = trim($_POST['occupation']);
				}
				
				if(isset($_POST['employer'])){
					$dat['employer'] = trim($_POST['employer']);
				}
				// NO PHONE CONTROL!
				///$dat['phone_num'] = trim($_POST['phone_num']);
				
				// NO DISPO CONTROL HERE - TOO MUCH ATTACHED
				//$dat['status'] = trim($_POST['status']);
				
				
				if($id){
					
					$_SESSION['dbapi']->aedit($id,$dat,$_SESSION['dbapi']->lead_management->table);
					
					// UPDATE NAME/ADDRESS INFO ON THE SALES RECORDS, IF THEY EXIST
					$this->syncDataChangesToSales($id);
					
					
					// SYNC CHANGES TO VICI
					$this->syncDataToViciCluster($id);
					
					// SYNC CHANGES TO DRIPP
					$this->syncDataChangesToDRIPP($id);
					
					
					$newrow = $_SESSION['dbapi']->lead_management->getByID($id);
					
					logAction('edit', 'lead_management', $id, "", $row, $newrow);
				}
				
				
				
				$_SESSION['api']->outputEditSuccess($id);
				
				
				
				break;
				
			case 'change_dispo':
				
				
				$id = intval($_POST['editing_lead']);
				
				//			$row = $_SESSION['dbapi']->lead_management->getByID($id);
				
				
				$dat = array();
				$dat['dispo'] = $_REQUEST['dispo'];
				
				
				$row = $_SESSION['dbapi']->lead_management->getByID($id);
				
				
				// MUST'VE BEEN A SALE, BUT IS OVVVERRRR NOWWWW
				if(($row['dispo'] == 'SALE' || $row['dispo'] == 'PAIDCC' || $row['dispo'] == 'SALECC') && $dat['dispo'] != $row['dispo']){
					
					
					// CHANGING FROM SALE TO PAIDCC
					if(strtoupper($dat['dispo']) == 'PAIDCC'){
						
						execSQL("UPDATE `sales` SET `is_paid`='yes' WHERE lead_tracking_id='$id' ORDER BY id DESC LIMIT 1");
						
						// CHANGING FROM PAIDCC TO SALE
					}else if(strtoupper($dat['dispo']) == 'SALE'){
						
						execSQL("UPDATE `sales` SET `is_paid`='no' WHERE lead_tracking_id='$id' ORDER BY id DESC LIMIT 1");
						
						// CHANGING FROM (SALE/PAIDCC) to SALECC
					}else if(strtoupper($dat['dispo']) == 'SALECC'){
						
						execSQL("UPDATE `sales` SET `is_paid`='roustedcc' WHERE lead_tracking_id='$id' ORDER BY id DESC LIMIT 1");
						
					}else{
						
						// BEELETED - FIND THE SALE AND AXE IT ( NO AXIN QUESTIONS )
						$this->deleteSale($id);
						
						
					}
					
					
				}
				
				
				if($row['verifier_vici_cluster_id'] && intval($row['verifier_lead_id']) > 0){
					
					// CONNECT TO VICI AND SET DISPO STATUS VIA DB?
					// OR POST TO VICI VIA API?
					
					$vidx = getClusterIndex($row['verifier_vici_cluster_id']);
					
					if($vidx > -1){
						
						// CONNECT TO VICI TO MAKE THE CHANGE
						connectViciDB($vidx);
						
						// CHANGE DISPO IN VICI
						execSQL("UPDATE `vicidial_list` SET `status`='".mysqli_real_escape_string($_SESSION['db'], $dat['dispo'])."' WHERE lead_id=".intval($row['verifier_lead_id'])." ");
						
						// CONNECT BACK TO PX DB
						connectPXDB();
					}
					
				}
				
				
				
				
				$_SESSION['dbapi']->aedit($id,$dat,$_SESSION['dbapi']->lead_management->table);
				
				
				$newrow = $_SESSION['dbapi']->lead_management->getByID($id);
				
				
				logAction('change_dispo', 'lead_management', $id, "Dispo:".$dat['dispo'], $row, $newrow);
				
				
				
				// SYNC CHANGES TO DRIPP
				$this->syncDataChangesToDRIPP($id);
				
				
				
				
				$_SESSION['api']->outputEditSuccess($id);
				
				
				break;
				
			case 'resend_sale':
				$id = intval($_REQUEST['editing_lead']);
				
				$sale_id = intval($_REQUEST['editing_sale_id']);
				
				$reason = trim($_REQUEST['resend_reason']);
				
				execSQL("UPDATE `sales` SET `sale_time`=UNIX_TIMESTAMP(),resend_reason='".mysqli_real_escape_string($_SESSION['db'], $reason)."' ".
						" WHERE id='$sale_id' AND lead_tracking_id='$id'");
				
				
				$_SESSION['api']->outputEditSuccess($id);
				
				
				break;
			case 'create_sale':
				
				$id = intval($_REQUEST['editing_lead']);
				
				$xfer_id = intval($_REQUEST['creating_sale']);
				
				
				
				
				// LOOK UP XFER
				if($xfer_id){
					
					$xfer =	$_SESSION['dbapi']->querySQL("SELECT * FROM transfers WHERE id='$xfer_id'");
					
					
					$sale = $_SESSION['dbapi']->querySQL("SELECT * FROM sales WHERE transfer_id='$xfer_id'");
					
				}
				//print_r($_REQUEST);
				//print_r($xfer);
				//print_r($sale);
				
				$row = $_SESSION['dbapi']->lead_management->getByID($id);
				
				
				// SALE TIME SHIT
				$hour = intval($_REQUEST['sale_hour']);
				$min = intval($_REQUEST['sale_min']);
				$month = intval($_REQUEST['sale_month']);
				$day = intval($_REQUEST['sale_day']);
				$year = intval($_REQUEST['sale_year']);
				
				if($_REQUEST['sale_timemode'] == 'am'){
					
					if($hour == 12)$hour = 0;
					
					$sale_time = mktime($hour, $min, 0 , $month, $day, $year);
					
					// PM, ADD 12 HOURS FOR MILITARY TIME
				}else{
					
					if($hour != 12 )$hour += 12;
					
					$sale_time = mktime($hour, $min, 0 , $month, $day, $year);
				}
				
				// XFER TIME SHIT
				$hour = intval($_REQUEST['xfer_hour']);
				$min = intval($_REQUEST['xfer_min']);
				$month = intval($_REQUEST['xfer_month']);
				$day = intval($_REQUEST['xfer_day']);
				$year = intval($_REQUEST['xfer_year']);
				
				if($_REQUEST['xfer_timemode'] == 'am'){
					
					if($hour == 12)$hour = 0;
					
					$xfer_time = mktime($hour, $min, 0 , $month, $day, $year);
					
					// PM, ADD 12 HOURS FOR MILITARY TIME
				}else{
					
					if($hour != 12 )$hour += 12;
					
					$xfer_time = mktime($hour, $min, 0 , $month, $day, $year);
				}
				
				
				
				$dispo = trim($_REQUEST['dispo']);
				
				
				// LAST LOCAL CALL TIME LOOKUP
				
				// CONNECT TO VICI-CLUSTER FOR AGENT_CLUSTER_ID
				$dbidx = getClusterIndex($row['vici_cluster_id']);
				connectViciDB($dbidx);
				
				// LOOK UP LAST CALL TIME
				list($calltime) = queryROW("SELECT CAST(last_local_call_time AS char) as last_local_call_time FROM vicidial_list ".
						" WHERE lead_id='".$row['lead_id']."'");
				
				
				
				if($row['verifier_vici_cluster_id'] && intval($row['verifier_lead_id']) > 0){
					
					// CONNECT TO VICI AND SET DISPO STATUS VIA DB?
					// OR POST TO VICI VIA API?
					
					$vidx = getClusterIndex($row['verifier_vici_cluster_id']);
					
					if($vidx > -1){
						
						// CONNECT TO VICI TO MAKE THE CHANGE
						connectViciDB($vidx);
						
						// CHANGE DISPO IN VICI
						execSQL("UPDATE `vicidial_list` SET `status`='".mysqli_real_escape_string($_SESSION['db'], $dispo)."' WHERE lead_id=".intval($row['verifier_lead_id'])." ");
						
					}
					
				}
				
				
				
				
				// CONNECT BACK TO PX DB
				connectPXDB();
				
				//			$hour = intval($_REQUEST['last_hour']);
				//			$min = intval($_REQUEST['last_min']);
				//			$month = intval($_REQUEST['last_month']);
				//			$day = intval($_REQUEST['last_day']);
				//			$year = intval($_REQUEST['last_year']);
				//
				//			if($_REQUEST['last_timemode'] == 'am'){
				//
				//				if($hour == 12)$hour = 0;
				//			}else{
				//				if($hour != 12 )$hour += 12;
				//			}
				
				
				
				$agent_user_id = intval($_REQUEST['agent_user_id']);
				$verifier_user_id = intval($_REQUEST['verifier_user_id']);
				
				
				$agent_user = getUserByID($agent_user_id);
				$verifier_user = getUserByID($verifier_user_id);
				
				
				
				$verifier_cluster_id = (intval($row['verifier_vici_cluster_id']) > 0)?$row['verifier_vici_cluster_id']:999999;
				
				
				
				//$agent_user = $_SESSION['dbapi']->lead_management->getUserByID();
				
				
				
				// NO XFER RECORD EXISTS AT ALL
				if( (!$xfer_id || !$xfer) ){
					
					
					
					// CREATE XFER RECORD
					$dat = array();
					$dat['lead_tracking_id'] = $id;
					$dat['campaign_id'] = $row['campaign_id'];
					$dat['xfer_time'] = $xfer_time;// set to SALE TIME
					$dat['sale_time'] = $sale_time;
					
					
					
					$dat['vici_last_call_time'] = $calltime;
					
					// vici_last_call_time
					// vici_gmt_offset
					
					$dat['agent_username'] = $agent_user['username']; //$row['agent_username'];
					$dat['agent_lead_id'] = $row['lead_id'];
					$dat['agent_cluster_id'] = $row['vici_cluster_id'];
					$dat['agent_amount'] = intval($_REQUEST['agent_amount']);
					
					$dat['verifier_username'] = $verifier_user['username'];
					$dat['verifier_lead_id'] = $row['verifier_lead_id'];
					$dat['verifier_cluster_id'] = $verifier_cluster_id;
					$dat['verifier_amount'] = intval($_REQUEST['verifier_amount']);
					$dat['verifier_dispo'] = $dispo;
					
					
					
					$dat['call_group'] = $this->getCallGroup($agent_user['id'], $row['vici_cluster_id']);//
					
					$_SESSION['dbapi']->aadd($dat, 'transfers');
					
					$xfer_id = mysqli_insert_id($_SESSION['dbapi']->db);
					
					
					// CREATE SALE RECORD
					$dat = array();
					$dat['lead_tracking_id'] = $id;
					$dat['transfer_id'] = $xfer_id;
					$dat['agent_lead_id'] = $row['lead_id'];
					$dat['agent_cluster_id'] = $row['vici_cluster_id'];
					$dat['verifier_lead_id'] = $row['verifier_lead_id'];
					$dat['verifier_cluster_id'] = $verifier_cluster_id;
					$dat['campaign_id'] = $row['campaign_id'];
					$dat['sale_time'] = $sale_time;
					
					// sale_datetime not really used atm
					$dat['phone'] = $row['phone_num'];
					
					$dat['agent_username'] = $agent_user['username'];
					$dat['agent_name'] = $agent_user['first_name'].(($agent_user['last_name'])?' '.$agent_user['last_name']:'');
					
					$dat['verifier_username'] = $verifier_user['username'];
					$dat['verifier_name'] = $verifier_user['first_name'].(($verifier_user['last_name'])?' '.$verifier_user['last_name']:'');
					
					
					
					$dat['first_name'] = $row['first_name'];
					$dat['last_name'] = $row['last_name'];
					$dat['contact'] = ($row['contact'])?$row['contact']:$row['first_name'];
					
					
					$dat['address1'] = $row['address1'];
					$dat['address2'] = $row['address2'];
					$dat['city'] = $row['city'];
					$dat['state'] = $row['state'];
					$dat['zip'] = $row['zip_code'];
					$dat['campaign'] = $row['campaign'];
					$dat['campaign_code'] = $row['campaign_code'];
					$dat['amount'] = intval($_REQUEST['verifier_amount']);
					$dat['office'] = $_REQUEST['office'];
					
					$dat['call_group'] = lookupUserGroup($agent_user['id'], $row['vici_cluster_id']);//$agent_user['user_group'];
					
					
					$dat['comments'] = trim($_REQUEST['comments']);//$row['comments'];
					//$dat['server_ip'] = $row['city'];
					
					
					if($_REQUEST['dispo'] == 'PAIDCC'){
						
						$dat['is_paid'] = 'yes';
						
					}else if($_REQUEST['dispo'] == 'SALECC'){
						
						$dat['is_paid'] = 'roustedcc';
						
					}else{
						
						$dat['is_paid'] = 'no';
						
					}
					
					$sale_id = $_SESSION['dbapi']->aadd($dat, 'sales');
					
					
					// EDIT THE lead_tracking record, to set new user, amount, butts
					$dat = array();
					$dat['user_id'] = $agent_user['id'];
					$dat['verifier_id'] = $verifier_user['id'];
					$dat['agent_username'] = $agent_user['username'];
					$dat['verifier_username'] = $verifier_user['username'];
					
					$dat['amount'] = intval($_REQUEST['agent_amount']);
					
					$dat['comments'] = trim($_REQUEST['comments']);
					
					$_SESSION['dbapi']->aedit($row['id'], $dat, 'lead_tracking');
					
					
					
					
					
					
					// DELETING THE SALE
				}else if($xfer &&
						
						// IF IT WAS A SALE, BUT CHANGING TO NOT-A-SALE
						(($xfer['verifier_dispo'] == 'SALE' || $xfer['verifier_dispo'] == 'PAIDCC' || $xfer['verifier_dispo'] == 'SALECC') && ($_REQUEST['dispo'] != 'SALE' && $_REQUEST['dispo'] != 'PAIDCC' && $_REQUEST['dispo'] != 'SALECC')) ||
						
						// IF IT WAS A REVIEW, CHANGING TO NON-SALE
						(($xfer['verifier_dispo'] == 'REVIEW' || $xfer['verifier_dispo'] == 'REVIEWCC')  && ($_REQUEST['dispo'] != 'REVIEW' && $_REQUEST['dispo'] != 'SALE' && $_REQUEST['dispo'] != 'PAIDCC' && $_REQUEST['dispo'] != 'SALECC'))
						){
							
							// SAVE NEW DISPO
							$dat = array();
							$dat['dispo'] = $dispo;
							$_SESSION['dbapi']->aedit($row['id'], $dat, 'lead_tracking');
							
							
							
							
							// DELETE SALE RECORD
							$this->deleteSaleByXFER($row['id'], $xfer['id']);
							//				$_SESSION['dbapi']->execSQL("DELETE FROM sales WHERE transfer_id='".$xfer['id']."'");
							
							
							
							//				if($_REQUEST['dispo'] != 'REVIEW'){
							//
							//					// DELETE XFER RECORD
							////					$_SESSION['dbapi']->execSQL("DELETE FROM transfers WHERE id='".$xfer['id']."'");
							//					/// NO! BAD! DONT DELETE TEH TRANSFER RECORD, WE NEED IT FOR REPORTING THAT THE AGENT MADE TRANSFERS, THEY JUST DIDNT CLOSE
							//
							//
							//				}else{
							
							// EDIT TRANSFER DISPO
							$dat = array();
							$dat['verifier_dispo'] = $dispo;
							$_SESSION['dbapi']->aedit($xfer['id'], $dat, 'transfers');
							
							//				}
							
							
							// DEFAULT TO MODIFY/EDIT TRANSFER/SALE DATA
				}else{
					
					// EDIT THE XFER RECORD
					$dat = array();
					$dat['agent_username'] = $agent_user['username'];
					$dat['agent_amount'] = intval($_REQUEST['agent_amount']);
					
					$dat['verifier_username'] = $verifier_user['username'];
					$dat['verifier_amount'] = intval($_REQUEST['verifier_amount']);
					$dat['verifier_dispo'] = $dispo;
					
					$dat['vici_last_call_time'] = $calltime;
					
					$dat['xfer_time'] = $xfer_time;// set to SALE TIME
					
					if($dispo == 'SALE' || $dispo == 'PAIDCC' || $dispo == 'SALECC'){
						
						$dat['sale_time'] = $sale_time;
					}
					
					$dat['call_group'] = $this->getCallGroup($agent_user['id'], $row['vici_cluster_id']);
					
					
					$_SESSION['dbapi']->aedit($xfer['id'], $dat, 'transfers');
					
					$xfer_id = $xfer['id'];
					
					if($dispo == 'SALE' || $dispo == 'PAIDCC' || $dispo == 'SALECC'){
						
						
						if(intval($sale['id']) > 0){
							
							
							
							// EDIT THE SALE RECORD
							$dat = array();
							
							$dat['agent_username'] = $agent_user['username'];
							$dat['agent_name'] = $agent_user['first_name'].(($agent_user['last_name'])?' '.$agent_user['last_name']:'');
							
							$dat['verifier_username'] = $verifier_user['username'];
							$dat['verifier_name'] = $verifier_user['first_name'].(($verifier_user['last_name'])?' '.$verifier_user['last_name']:'');
							$dat['amount'] = intval($_REQUEST['verifier_amount']);
							
							
							
							// IF IT WAS A SALE, BUT CHANGING TO PAIDCC
							if($_REQUEST['dispo'] == 'PAIDCC'){
								
								$dat['is_paid'] = 'yes';
								
							}else if($_REQUEST['dispo'] == 'SALECC'){
								
								$dat['is_paid'] = 'roustedcc';
								
							}else{
								
								$dat['is_paid'] = 'no';
								
							}
							
							$dat['sale_time'] = $sale_time;
							
							
							//$dat['call_group'] = $agent_user['user_group'];
							$dat['call_group'] = lookupUserGroup($agent_user['id'], $row['vici_cluster_id']);
							
							$dat['comments'] = trim($_REQUEST['comments']);//$row['comments'];
							
							$_SESSION['dbapi']->aedit($sale['id'], $dat, 'sales');
							
							// ADD NEW SALE RECORD!!!!
						}else{
							// CREATE SALE RECORD
							$dat = array();
							$dat['lead_tracking_id'] = $id;
							$dat['transfer_id'] = $xfer_id;
							$dat['agent_lead_id'] = $row['lead_id'];
							$dat['agent_cluster_id'] = $row['vici_cluster_id'];
							$dat['verifier_lead_id'] = $row['verifier_lead_id'];
							$dat['verifier_cluster_id'] = $verifier_cluster_id;
							$dat['campaign_id'] = $row['campaign_id'];
							
							
							$dat['sale_time'] = $sale_time;
							
							$dat['vici_last_call_time'] = $calltime;
							
							
							// IF IT WAS A SALE, BUT CHANGING TO PAIDCC
							if($_REQUEST['dispo'] == 'PAIDCC'){
								
								$dat['is_paid'] = 'yes';
								
							}else if($_REQUEST['dispo'] == 'SALECC'){
								
								$dat['is_paid'] = 'roustedcc';
							}else{
								
								$dat['is_paid'] = 'no';
								
							}
							
							// sale_datetime not really used atm
							$dat['phone'] = $row['phone_num'];
							
							$dat['agent_username'] = $agent_user['username'];
							$dat['agent_name'] = $agent_user['first_name'].(($agent_user['last_name'])?' '.$agent_user['last_name']:'');
							
							$dat['verifier_username'] = $verifier_user['username'];
							$dat['verifier_name'] = $verifier_user['first_name'].(($verifier_user['last_name'])?' '.$verifier_user['last_name']:'');
							
							
							
							$dat['first_name'] = $row['first_name'];
							$dat['last_name'] = $row['last_name'];
							$dat['contact'] = ($row['contact'])?$row['contact']:$row['first_name'];
							
							
							$dat['address1'] = $row['address1'];
							$dat['address2'] = $row['address2'];
							$dat['city'] = $row['city'];
							$dat['state'] = $row['state'];
							$dat['zip'] = $row['zip_code'];
							$dat['campaign'] = $row['campaign'];
							$dat['campaign_code'] = $row['campaign_code'];
							$dat['amount'] = intval($_REQUEST['verifier_amount']);
							$dat['office'] = $_REQUEST['office'];
							
							
							//$dat['call_group'] = $agent_user['user_group'];
							
							$dat['call_group'] = lookupUserGroup($agent_user['id'], $row['vici_cluster_id']);
							
							$dat['comments'] = trim($_REQUEST['comments']);//$row['comments'];
							
							//$dat['server_ip'] = $row['city'];
							
							
							$sale_id = $_SESSION['dbapi']->aadd($dat, 'sales');
							
							
						}
						
						
					}
					
					
					// IF AGENT AMOUNT CHANGES, UPDATE lead_tracking record?? Maybe
					// EDIT THE lead_tracking record, to set new user, amount, butts
					$dat = array();
					$dat['user_id'] = $agent_user['id'];
					$dat['verifier_id'] = $verifier_user['id'];
					$dat['agent_username'] = $agent_user['username'];
					$dat['verifier_username'] = $verifier_user['username'];
					
					$dat['amount'] = intval($_REQUEST['agent_amount']);
					
					$dat['comments'] = trim($_REQUEST['comments']);
					
					$_SESSION['dbapi']->aedit($row['id'], $dat, 'lead_tracking');
					
				}
				
				
				// CHECKBOX TO SYNC DISPO
				//if($_REQUEST['update_lead_dispo']){
				
				// UPDATE LEAD TRACKING DISPO TOO
				$dat = array();
				$dat['dispo'] = $_REQUEST['dispo'];
				$_SESSION['dbapi']->aedit($id,$dat,$_SESSION['dbapi']->lead_management->table);
				
				//}
				
				if($xfer_id > 0){
					$new_xfer =	$_SESSION['dbapi']->querySQL("SELECT * FROM transfers WHERE id='$xfer_id'");
					$new_sale = $_SESSION['dbapi']->querySQL("SELECT * FROM sales WHERE transfer_id='$xfer_id'");
					
					$additional_changes_tracked = "";
					
					$differences = recordCompare($xfer, $new_xfer);
					if($differences != null && strlen(trim($differences)) > 0){
						$additional_changes_tracked .= "XFER Compare:\n".$differences;
					}
					
					$differences = recordCompare($sale, $new_sale);
					if($differences != null && strlen(trim($differences)) > 0){
						$additional_changes_tracked .= "SALE Compare:\n".$differences;
					}
					
				}
		
				$new_row = $_SESSION['dbapi']->lead_management->getByID($id);
				
				

				
				logAction('create_sale', 'lead_management', $id,"", $row, $new_row, $additional_changes_tracked);
				
				// SYNC CHANGES TO DRIPP
				$this->syncDataChangesToDRIPP($id);
				
				
				$_SESSION['api']->outputEditSuccess($id);
				
				break;
				
			default:
			case 'list':
				
				
				
				$dat = array();
				$totalcount = 0;
				$pagemode = false;
				
				
				// RESERVED FOR TIME SEARCH STUFF
				
				
				// RESERVED FOR TIME SEARCH STUFF
				if($_REQUEST['s_date_mode']){
					
					if($_REQUEST['s_date_mode'] != 'any'){
						
						if($_REQUEST['s_date_mode'] == 'daterange'){
							
							$tmp0 = strtotime($_REQUEST['s_date_month'].'/'.$_REQUEST['s_date_day'].'/'.$_REQUEST['s_date_year']);
							$tmp1 = strtotime($_REQUEST['s_date2_month'].'/'.$_REQUEST['s_date2_day'].'/'.$_REQUEST['s_date2_year']);
							
							
							$tmp0 = mktime(0,0,0, date("m", $tmp0), date("d", $tmp0), date("Y", $tmp0));
							$tmp1 = mktime(23,59,59, date("m", $tmp1), date("d", $tmp1), date("Y", $tmp1));
							
						}else if($_REQUEST['s_date_mode'] == 'datetimerange'){
							
							
							$tmp0 = strtotime($_REQUEST['s_date_month'].'/'.$_REQUEST['s_date_day'].'/'.$_REQUEST['s_date_year'].' '.$_REQUEST['s_date_hour'].':'.zeropad($_REQUEST['s_date_min'],2).$_REQUEST['s_date_timemode'] );
							$tmp1 = strtotime($_REQUEST['s_date2_month'].'/'.$_REQUEST['s_date2_day'].'/'.$_REQUEST['s_date2_year'].' '.$_REQUEST['s_date2_hour'].':'.zeropad($_REQUEST['s_date2_min'],2).$_REQUEST['s_date2_timemode']);
							
							
							$tmp0 = mktime(date("H", $tmp0),date("i", $tmp0),0, date("m", $tmp0), date("d", $tmp0), date("Y", $tmp0));
							$tmp1 = mktime(date("H", $tmp1),date("i", $tmp1),59, date("m", $tmp1), date("d", $tmp1), date("Y", $tmp1));
							
							//echo date("H:i:s m/d/Y",$tmp0).' '.date("H:i:s m/d/Y",$tmp1);
							
							
						}else{
							
							$tmp0 = strtotime($_REQUEST['s_date_month'].'/'.$_REQUEST['s_date_day'].'/'.$_REQUEST['s_date_year']);
							$tmp0 = mktime(0,0,0, date("m", $tmp0), date("d", $tmp0), date("Y", $tmp0));
							
							$tmp1 = $tmp0 + 86399;
							
							//					$tmp0 = strtotime($_REQUEST['s_date']);
							//					$tmp1 = $tmp0 + 86399;
							
						}
						//echo date("g:i:s m/d/Y", $tmp0).' ';
						//echo date("g:i:s m/d/Y", $tmp1).' ';
						
						$dat['time'] = array($tmp0, $tmp1);
						
					}
				}else{
					
					
					
					//$dat['time'] = array(mktime(0,0,0), mktime(23,59,59));
					
					
				}
				/*			if($_REQUEST['s_date']){
				
				
				$tmp0 = strtotime($_REQUEST['s_date']);
				$tmp1 = $tmp0 + 86399;
				
				//echo date("g:i:s m/d/Y", $tmp0).' ';
				//echo date("g:i:s m/d/Y", $tmp1).' ';
				
				$dat['time'] = array($tmp0, $tmp1);
				
				}else{
				
				
				
				//$dat['time'] = array(mktime(0,0,0), mktime(23,59,59));
				
				
				}*/
				
				
				## STATUS SEARCH
				//			if($_REQUEST['s_status']){
				//
				//				if($_REQUEST['s_status'] == -1){
				//					// SHOW ALL STATUS
				//					unset($dat['status']);
				//
				//				}else{
				//					$dat['status'] = trim($_REQUEST['s_status']);
				//				}
				//
				//			/// DEFAULT STATUS IS "review"
				//			}else{
				//
				//				$dat['status'] = 'review';
				//			}
				
				
				
				## ID SEARCH
				if($_REQUEST['s_id']){
					
					$dat['id'] = intval($_REQUEST['s_id']);
					
				}
				
				
				if($_REQUEST['s_lead_id']){
					
					$dat['lead_id'] = intval($_REQUEST['s_lead_id']);
					
				}
				
				
				if($_REQUEST['s_campaign_id']){
					
					$dat['campaign_id'] = intval($_REQUEST['s_campaign_id']);
					
				}
				
				// AGENT USERNAME
				if($_REQUEST['s_agent_username']){
					
					$dat['agent_username'] = trim($_REQUEST['s_agent_username']);
					
				}
				
				// VERIFIER USERNAME
				if($_REQUEST['s_verifier_username']){
					
					$dat['verifier_username'] = trim($_REQUEST['s_verifier_username']);
					
				}
				
				
				
				if($_REQUEST['s_phone']){
					
					$dat['phone_number'] = preg_replace("/[^0-9]/","",trim($_REQUEST['s_phone']));
					
				}
				
				
				// CITY
				if($_REQUEST['s_city']){
					
					$dat['city'] = trim($_REQUEST['s_city']);
					
				}
				
				// STATE
				if($_REQUEST['s_state']){
					
					$dat['state'] = trim($_REQUEST['s_state']);
					
				}
				
				
				if($_REQUEST['s_vici_list_id']){
					
					$dat['vici_list_id'] = intval($_REQUEST['s_vici_list_id']);
					
				}
				
				
				// DISPO STATUS SEARCH
				if($_REQUEST['s_status']){
					
					
					switch(trim($_REQUEST['s_status'])){
						default:
							
							$dat['status'] = trim($_REQUEST['s_status']);
							break;
						case 'SALE/PAIDCC':
							
							$dat['status'] = array('SALE','PAIDCC','SALECC');
							
							break;
					}
					
				}
				
				
				## FIRSTNAME
				if($_REQUEST['s_firstname']){
					
					$dat['firstname'] = trim($_REQUEST['s_firstname']);
					
				}
				## LAST NAME
				if($_REQUEST['s_lastname']){
					
					$dat['lastname'] = trim($_REQUEST['s_lastname']);
					
				}
				
				
				//			if($_REQUEST['s_status']){
				//
				//				$dat['status'] = trim($_REQUEST['s_status']);
				//
				//			}
				
				/*if($_REQUEST['s_phone']){
				
				$dat['phone_number'] = trim($_REQUEST['s_phone']);
				
				}*/
				if($_REQUEST['s_cluster_id']){
					
					$dat['cluster_id'] = trim($_REQUEST['s_cluster_id']);
					
				}
				
				
				
				
				// OFFICE RESTRICTION/SEARCH ABILITY
				if(
						($_SESSION['user']['priv'] < 5) &&
						($_SESSION['user']['allow_all_offices'] != 'yes')
						
						){
							
							
							if(count($_SESSION['assigned_offices']) > 0){
								$tmpofc = intval($_REQUEST['s_office_id']);
								
								if($tmpofc > 0){
									
									if(in_array($tmpofc, $_SESSION['assigned_offices'])){
										
										$dat['office'] = $tmpofc;
										
									}else{
										
										$dat['office'] = $_SESSION['assigned_offices'];
										
									}
									
								}else{
									
									$dat['office'] = $_SESSION['assigned_offices'];
									
								}
							}else{
								// DISABLE ALL OFFICE ACCESS (BASICALLLY SHOW NO DATA?)
								//$dat['office'] = -1;
							}
							
							
				}else{
					$tmpofc = intval($_REQUEST['s_office_id']);
					
					if($tmpofc > 0){
						
						$dat['office'] = $tmpofc;
						
					}
					
				}
				
				
				
				
				
				
				
				## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
				if(isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])){
					
					$pagemode = true;
					
					$cntdat = $dat;
					$cntdat['fields'] = 'COUNT(id)';
					list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->lead_management->getResults($cntdat));
					
					$dat['limit'] = array(
							"offset"=>intval($_REQUEST['index']),
							"count"=>intval($_REQUEST['pagesize'])
					);
					
				}
				
				
				## ORDER BY SYSTEM
				if($_REQUEST['orderby'] && $_REQUEST['orderdir']){
					$dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
				}
				
				
				
				
				
				
				$res = $_SESSION['dbapi']->lead_management->getResults($dat);
				
				
				
				## OUTPUT FORMAT TOGGLE
				switch($_SESSION['api']->mode){
					default:
					case 'xml':
						
						
						## GENERATE XML
						
						if($pagemode){
							
							$out = '<'.$this->xml_parent_tagname." totalcount=\"".intval($totalcount)."\">\n";
						}else{
							$out = '<'.$this->xml_parent_tagname.">\n";
						}
						
						$out .= $_SESSION['api']->renderResultSetXML($this->xml_record_tagname,$res);
						
						$out .= '</'.$this->xml_parent_tagname.">";
						break;
						
						## GENERATE JSON
					case 'json':
						
						$out = '['."\n";
						
						$out .= $_SESSION['api']->renderResultSetJSON($this->json_record_tagname,$res);
						
						$out .= ']'."\n";
						break;
				}
				
				
				## OUTPUT DATA!
				echo $out;
				
		}
	}
	
	
	
	function handleSecondaryAjax(){
		
		
		
		$out_stack = array();
		
		//print_r($_REQUEST);
		
		foreach($_REQUEST['special_stack'] as $idx => $data){
			
			$tmparr = preg_split("/:/",$data);
			
			//print_r($tmparr);
			
			
			switch($tmparr[1]){
				default:
					
					## ERROR
					$out_stack[$idx] = -1;
					
					break;
					
				case 'campaign_name':
					
					// vici_cluster_id
					
					if($tmparr[2] <= 0){
						$out_stack[$idx] = '-';
					}else{
						
						//echo "ID#".$tmparr[2];
						
						$out_stack[$idx] = $_SESSION['dbapi']->lead_management->getCampaignName($tmparr[2]);
					}
					
					break;
					
				case 'cluster_name':
					
					// vici_cluster_id
					
					if($tmparr[2] <= 0){
						$out_stack[$idx] = '-';
					}else{
						
						//echo "ID#".$tmparr[2];
						
						$out_stack[$idx] = getClusterName($tmparr[2]);
					}
					//
					
					
					
					break;
					//			case 'voice_name':
					//
					//				// COULD BE REPLACED LATER WITH A CUSOMIZABLE SCREEN DB TABLE
					//				if($tmparr[2] <= 0){
					//					$out_stack[$idx] = '-';
					//				}else{
					//
					//					//echo "ID#".$tmparr[2];
					//
					//					$out_stack[$idx] = $_SESSION['dbapi']->voices->getName($tmparr[2]);
					//				}
					//
					//				break;
					
			}## END SWITCH
			
			
			
			
		}
		
		
		
		$out = $_SESSION['api']->renderSecondaryAjaxXML('Data',$out_stack);
		
		//print_r($out_stack);
		echo $out;
		
	} ## END HANDLE SECONDARY AJAX
	
	
}

