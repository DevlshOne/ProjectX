<?php




class API_Sales_Management{
	
	var $xml_parent_tagname = "Sales";
	var $xml_record_tagname = "Sale";
	
	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";
	
	
	
	function getCallGroup($user_id, $cluster_id){
		
		$user_id = intval($user_id);
		$cluster_id = intval($cluster_id);
		
		connectPXDB();
		
		list($group) = queryROW("SELECT group_name FROM user_group_translations WHERE user_id='$user_id' AND cluster_id='$cluster_id'");
		
		return $group;
	}
	
	
	
	
	
	function handleAPI(){
		
		if(!checkAccess('sales_management')){
			
			
			$_SESSION['api']->errorOut('Access denied to Sales Management');
			
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
		/*	case 'delete':
				
				$id = intval($_REQUEST['id']);
				
				//$row = $_SESSION['dbapi']->campaigns->getByID($id);
				
				
				$_SESSION['dbapi']->lead_management->delete($id);
				
				
				logAction('delete', 'lead_management', $id, "");
				
				
				
				$_SESSION['api']->outputDeleteSuccess();
				
				
				break;*/
				
			case 'view':
				
				
				$id = intval($_REQUEST['id']);
				
				$row = $_SESSION['dbapi']->sales_management->getByID($id);
				
				
				
				
				## BUILD XML OUTPUT
				$out = "<".$this->xml_record_tagname." ";
				
				foreach($row as $key=>$val){
					
					
					$out .= $key.'="'.htmlentities($val).'" ';
					
				}
				
				$out .= " />\n";
				
				
				
				
				
				
				///$out .= "</".$this->xml_record_tagname.">";
				
				echo $out;
				
				
				
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
						
						$dat['sale_time'] = array($tmp0, $tmp1);
						
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
				if($_REQUEST['s_lead_tracking_id']){
					
					$dat['lead_tracking_id'] = intval($_REQUEST['s_lead_tracking_id']);
					
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
					
					$dat['phone'] = preg_replace("/[^0-9]/","",trim($_REQUEST['s_phone']));
					
				}
				
				
				// CITY
				if($_REQUEST['s_city']){
					
					$dat['city'] = trim($_REQUEST['s_city']);
					
				}
				
				// STATE
				if($_REQUEST['s_state']){
					
					$dat['state'] = trim($_REQUEST['s_state']);
					
				}
				
				
				
				// DISPO STATUS SEARCH
				if($_REQUEST['s_is_paid']){
					
					
					$dat['is_paid'] = trim($_REQUEST['s_is_paid']);
					
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
					list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->sales_management->getResults($cntdat));
					
					$dat['limit'] = array(
							"offset"=>intval($_REQUEST['index']),
							"count"=>intval($_REQUEST['pagesize'])
					);
					
				}
				
				
				## ORDER BY SYSTEM
				if($_REQUEST['orderby'] && $_REQUEST['orderdir']){
					$dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
				}
				
				
				
				
				
				
				$res = $_SESSION['dbapi']->sales_management->getResults($dat);
				
				
				
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

