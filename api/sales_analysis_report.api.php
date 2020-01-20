<?

##
## NEW API FILE FOR GENERATING SALES ANALYSIS REPORT DATA
## USED BY GUI AND API GET REQUEST 'sales_analysis_report'
##


class API_Sales_Analysis_Report{


	function to_xml(SimpleXMLElement $object, array $data) {   

		## ARRAY TO XML
		foreach ($data as $key => $value) {
			if (is_array($value)) {
				$new_object = $object->addChild($key);
				to_xml($new_object, $value);
			} else {
				// if the key is an integer, it needs text with it to actually work.
				if ($key == (int) $key) {
					$key = "key_$key";
				}

				$object->addChild($key, $value);
			}   
		}   
	}  



    function generateData($stime, $etime, $campaign_code, $agent_cluster_id, $user_team_id, $combine_users, $user_group, $ignore_group, $vici_campaign_code = '', $ignore_arr = NULL, $vici_campaign_id = '', $output_method = 'report') {
		$campaign_id = 0;
		
		connectPXDB();
		
		$sql_campaign = "";
		$sql_vici_campaign = "";
		$sql_cluster = "";
		$sql_agent_cluster = "";
		$sql_user_group = "";
		$sql_ignore_group = "";
		$ofcsql = "";
		
		if(php_sapi_name() != "cli") {
			
			// Not in cli-mode
			
			
			
			
			// OFFICE RESTRICTION/SEARCH ABILITY
			if(
					($_SESSION['user']['priv'] < 5) &&
					($_SESSION['user']['allow_all_offices'] != 'yes')
					){
						
						$ofcsql = " AND `office` IN(";
						$x=0;
						foreach($_SESSION['assigned_offices'] as $ofc){
							
							if($x++ > 0)$ofcsql.= ',';
							
							$ofcsql.= intval($ofc);
						}
						
						$ofcsql.= ") ";
						
			}else{
				
			}
			
		}
		
		
		/**
		 * Campaign SQL Generation
		 */
		$sql_campaign = '';
		if($campaign_code){
			
			if(is_array($campaign_code)){
				
				$sql_campaign = " AND (";
				
				$x=0;
				foreach($campaign_code as $code){
					
					list($campaign_id) = $_SESSION['dbapi']->ROqueryROW("SELECT id FROM campaigns WHERE vici_campaign_id='".mysqli_real_escape_string($_SESSION['db'],$code)."' ");
					
					if($x++ > 0) $sql_campaign .= " OR ";
					
					$sql_campaign .= " campaign_id='".$campaign_id."' ";
				}
				
				
				
				$sql_campaign.= ") ";
				
				// AVOID SQL ERRORS WITH EMPTY QUERY " AND () "
				if($x == 0){
					$sql_campaign = "";
				}
				
				// SINGULAR
			}else{
				
				list($campaign_id) = $_SESSION['dbapi']->ROqueryROW("SELECT id FROM campaigns WHERE vici_campaign_id='".mysqli_real_escape_string($_SESSION['db'],$campaign_code)."' ");
				
				$sql_campaign = " AND campaign_id='".$campaign_id."' ";
				
			}
			
		}
		
		
		if($vici_campaign_code){
			
			$sql_campaign .= " AND `campaign_code`='".mysqli_real_escape_string($_SESSION['db'],$vici_campaign_code)."' ";
			
		}
		
		if($vici_campaign_id){
			
			
			$sql_vici_campaign .= " AND `vici_campaign_id`='".mysqli_real_escape_string($_SESSION['db'],$vici_campaign_id)."' ";
		}
		
		
		if($agent_cluster_id > -1){
			
			
			if(is_array($agent_cluster_id)){
				
				$sql_cluster = " AND ( ";
				$sql_agent_cluster = " AND ( ";
				$x=0;
				foreach($agent_cluster_id as $cidx){
					
					if($x++ > 0)$sql_cluster .= " OR ";
					
					$sql_cluster .= " agent_cluster_id='".$_SESSION['site_config']['db'][$cidx]['cluster_id']."' ";
					$sql_agent_cluster .= " vici_cluster_id='".$_SESSION['site_config']['db'][$cidx]['cluster_id']."' ";
					
				}
				
				$sql_cluster .= ") ";
				$sql_agent_cluster .= ") ";
				
				if($x == 0){
					$sql_cluster .= "";
					$sql_agent_cluster .= "";
				}
				
			}else{
				
				$sql_cluster = " AND agent_cluster_id='".$_SESSION['site_config']['db'][$agent_cluster_id]['cluster_id']."' ";
				$sql_agent_cluster = " AND vici_cluster_id='".$_SESSION['site_config']['db'][$agent_cluster_id]['cluster_id']."' ";
				
			}
			

			
		}
		
		
		$use_team = false;
		$sql_user_team_list = array();
		if($user_team_id) {
			$use_team = true;
			$sql_user_team_list = $_SESSION['dbapi']->user_teams->getTeamMembers($user_team_id);
		}
		
		
		
		$sql_user_group_for_activity_join = '';
		$sql_user_group_lmt = '';
		
		if($user_group){
			
			
			if(is_array($user_group)){
				
				$x=0;
				
				if(count($user_group) > 0 && trim($user_group[0]) != ''){
					
					$sql_user_group = " AND ( ";
					$sql_user_group_for_activity_join = " AND ( ";
					$sql_user_group_lmt = " AND ( ";
					
					foreach($user_group as $group){
						
						if($x++ > 0){
							$sql_user_group .= " OR ";
							$sql_user_group_for_activity_join .= " OR ";
							$sql_user_group_lmt .= " OR ";
						}
						
						$sql_user_group .= " call_group='".mysqli_real_escape_string($_SESSION['db'],$group)."' ";
						$sql_user_group_for_activity_join	.= " transfers.call_group='".mysqli_real_escape_string($_SESSION['db'],$group)."' ";
						$sql_user_group_lmt	.= " user_group='".mysqli_real_escape_string($_SESSION['db'],$group)."' ";
					}
					
					$sql_user_group .= ")";
					$sql_user_group_for_activity_join .= ")";
					$sql_user_group_lmt .= ")";
					
				}
				
				if($x == 0){
					$sql_user_group = "";
					$sql_user_group_for_activity_join = "";
					$sql_user_group_lmt = "";
				}
				
			}else{
				
				$sql_user_group = " AND call_group='".mysqli_real_escape_string($_SESSION['db'],$user_group)."' ";
				$sql_user_group_for_activity_join = " AND transfers.call_group='".mysqli_real_escape_string($_SESSION['db'],$user_group)."' ";
				$sql_user_group_lmt = " AND user_group='".mysqli_real_escape_string($_SESSION['db'],$user_group)."' ";
				
			}
		}
		
		$sql_ignore_group_lmt = '';
		
		if($ignore_group){
			
			
			if(is_array($ignore_group)){
				$x=0;
				
				if(count($ignore_group) > 0 && trim($ignore_group[0]) != ''){
					
					$sql_ignore_group = " AND ( ";
					$sql_ignore_group_lmt = " AND ( ";
					
					foreach($ignore_group as $group){
						
						if($x++ > 0){
							$sql_ignore_group .= " AND ";
							$sql_ignore_group_lmt .= " AND ";
						}
						
						$sql_ignore_group .= " call_group != '".mysqli_real_escape_string($_SESSION['db'],$group)."' ";
						$sql_ignore_group_lmt .= " user_group != '".mysqli_real_escape_string($_SESSION['db'],$group)."' ";
						
					}
					
					$sql_ignore_group .= ")";
					$sql_ignore_group_lmt .= ")";
				}
				
				
				
				if($x == 0){
					$sql_ignore_group = "";
					$sql_ignore_group_lmt = "";
				}
				
			}else{
				
				$sql_ignore_group = " AND call_group != '".mysqli_real_escape_string($_SESSION['db'],$ignore_group)."' ";
				$sql_ignore_group_lmt = " AND user_group != '".mysqli_real_escape_string($_SESSION['db'],$ignore_group)."' ";
				
			}
		}
		
		$where = " WHERE sale_time BETWEEN '$stime' AND '$etime' ".
				
				// EXCLUDE ANYTHING ROUSTING RELATED
		" AND `is_paid` != 'roustedcc' ".
		
		//	" AND `account_id`='".$_SESSION['account']['id']."' ".
		$sql_campaign.
		$sql_vici_campaign.
		$sql_cluster.
		$sql_user_group.
		$sql_ignore_group.
		$ofcsql.
		//	(($verifier_cluster_id > -1)?" AND verifier_cluster_id='".$_SESSION['site_config']['db'][$verifier_cluster_id]['cluster_id']."' ":"").
		"";
			
		
		
		
		
		
		$user_group_array = array();
		
		$user_team_array = array();
		
		
		// DATA ARRAY TO RETURN!
		$output_array = array();
		
		$agent_array = array();
		$cluster_array=array();
		
		
		
		
		$sql = "SELECT DISTINCT(agent_username), lead_tracking.vici_cluster_id FROM `lead_tracking` ".
		
		"WHERE lead_tracking.`time` BETWEEN '$stime' AND '$etime' ".
		
		$sql_vici_campaign.
		
		
		//		$sql_cluster.
		$sql_agent_cluster.
		$sql_campaign.
		
		
		// 		$sql_user_group.
		// 		$sql_user_group_for_activity_join.
		$sql_user_group_lmt.
		
		$sql_ignore_group_lmt.
		// 		$sql_ignore_group.
		
		
		$ofcsql.
		" ORDER BY agent_username ASC";
		"".
		"".
		"";
		
		
		
		$res = $_SESSION['dbapi']->ROquery($sql);
		//$res = query("SELECT DISTINCT(agent_username),agent_cluster_id FROM sales ".$where." ORDER BY agent_username ASC");
		while($row = mysqli_fetch_row($res)){
			
			$tmp =  strtoupper(trim($row[0]));
			
			// SKIP BLANK USERNAME
			if(!$tmp)continue;
			
			// USER GROUP FILTER
			// IGNORE GROUP FILTER
			
			
			if($combine_users){
				
				// JPW vs JPW2
				// SKIPS THE "2" users (IF YOU CAN FIND THERE MAIN USER
				if(endsWith($tmp, "2")){
					
					//					if($this->findAgent($agent_array, substr($tmp,0,strlen($tmp)-1) ) > -1   ){
					//
					//						echo "SKIPPING $tmp : ".substr($tmp,0,strlen($tmp)-1)."<br />\n";
					//
					//						continue;
					//					}else{
					$tmp = substr($tmp,0,strlen($tmp)-1);
					
					//						echo "TRIMMING $tmp<br />\n";
					//					}
				}
				
			}
			
			if($use_team) {

				if(!in_array($tmp, $sql_user_team_list, false)) {
					
					//echo "Skipping " . $tmp . " --> not in selected team.<br />";
					
					continue;
				}
			}
			
			if($ignore_arr != null && is_array($ignore_arr)){
				
				// SKIPP THEM!!!!
				if(in_array($tmp, $ignore_arr)){
					//						echo "Skipping user on ignore list.\n";
					
					continue;
				}
				
				
			}
			
			
			$idx = $this->findAgent($agent_array, $tmp);
			
			if($idx < 0){
				
				//				echo "Pushing user $tmp ".$row[1]."<br />\n";
				
				$agent_array[] = new AgentInfo($tmp, $row[1]);
				
			}else{
				
				// FIND BY CLUSTER
				// SKIP IF CLUSTER ALREADY EXISTS
				if(!$agent_array[$idx]->findCluster($row[1]) ){
					
					//					echo "Pushing cluster ".$row[1]."<br />\n";
					$agent_array[$idx]->addClusterID($row[1]);
					
				}
			}
			
		}
		
		
		//		print_r($agent_array);
		//		exit;
		
		
		
		// INIT TOTALS VARIABLES
		$total_paid_hrs = 0;
		$total_active_hrs = 0;
		$total_calls = 0;
		$total_ni = 0;
		$total_xfer = 0;
		$total_sale_cnt = 0;
		$total_amount = 0;
		
		$total_paid_sale_cnt = 0;
		$total_paid_amount = 0;
		
		$total_closing = 0;
		$total_conversion = 0;
		$total_yes2all = 00;
		$total_avg = 0;
		$total_paid_hr = 0;
		$total_wrkd_hr = 0;
		
		
		$total_AnswerMachines = 0;
		
		$ox = 0;
		
		//print_r($agent_array);
		
		foreach($agent_array as $idx=>$agentobj){
			
			$running_amount = 0;
			$running_salecnt= 0;
			$running_num_NI = 0;
			$running_num_XFER = 0;
			$running_activity_paid = 0;
			$running_activity_wrkd = 0;
			$running_activity_num_calls = 0;
			
			$running_paid_amount = 0;
			$running_paid_salecnt = 0;
			
			
			foreach($agentobj->cluster_array as $cluster_id){
				
				$sql = "SELECT SUM(amount) AS amount,count(id) AS salecount FROM sales ".
						$where.
						
						(($combine_users)?
								// GET USER AND USER2
								" AND (agent_username IN ('".mysqli_real_escape_string($_SESSION['db'],$agentobj->username)."','".mysqli_real_escape_string($_SESSION['db'],$agentobj->username)."2') ) "
								:
								// ELSE JUST GET THE SPECIFIED USER
								" AND agent_username='".mysqli_real_escape_string($_SESSION['db'],$agentobj->username)."' "
								).
								" AND agent_cluster_id='$cluster_id' ";
								
								//	echo "\n".$sql."\n";
								//if(stripos($agentobj->username,"2")> -1){
								//
									//	echo "<br />POOOP(".$agentobj->username.")!<br />\n";
									//}
								
								$paidsql = $sql." AND is_paid='yes' ";
								$unpaidsql = $sql." AND is_paid='no' ";
								
								// GET THE UNPAID DEALS
								list($amount,$salecnt) = $_SESSION['dbapi']->ROqueryROW($unpaidsql);
								
								
								$running_amount += $amount;
								$running_salecnt += $salecnt;
								
								// GET THE PAID DEALS
								list($amount,$salecnt) = $_SESSION['dbapi']->ROqueryROW($paidsql);
								
								// ADDING TO THE MAIN NUMBERS
								$running_amount += $amount;
								$running_salecnt += $salecnt;
								// BUT ALSO TRACKING THEM SEPERATE
								$running_paid_amount += $amount;
								$running_paid_salecnt += $salecnt;
								//
								//				$testsql = "EXPLAIN ".$sql;
								//				$row = querySQL($testsql);
								//
								//				print_r($row);
								
			}
			
			
			//echo $amount.' '.$salecnt."\n";
			
			// TOTAL CALLZ!
			$sql = "SELECT COUNT(id) FROM lead_tracking ".
					" WHERE `time` BETWEEN '$stime' AND '$etime' ".
					
					// EXCLUDE ANYTHING ROUSTING RELATED
			" AND dispo != 'SALECC' ".
			
			//	" AND `account_id`='".$_SESSION['account']['id']."' ".
			" AND lead_id > 0 ".
			(($combine_users)?
					// GET USER AND USER2
					" AND (agent_username IN ('".mysqli_real_escape_string($_SESSION['db'],$agentobj->username)."','".mysqli_real_escape_string($_SESSION['db'],$agentobj->username)."2') ) "
					:
					// ELSE JUST GET THE SPECIFIED USER
					" AND agent_username='".mysqli_real_escape_string($_SESSION['db'],$agentobj->username)."' "
					).
					$ofcsql.
					(($campaign_id )?" AND campaign_id='".$campaign_id."' ":"").
					$sql_agent_cluster.
					$sql_vici_campaign;
					
					//echo $sql."<br />\n\n";
					
					list($num_total_calls_px) = $_SESSION['dbapi']->ROqueryROW($sql);
					
					
					
					$sql = "SELECT COUNT(id) FROM lead_tracking ".
							
							//									" FORCE INDEX (time) ".
					//" USE INDEX (time) ".
					
					" WHERE `time` BETWEEN '$stime' AND '$etime' ".
					//	" AND `account_id`='".$_SESSION['account']['id']."' ".
					
					// EXCLUDE ANYTHING ROUSTING RELATED
					" AND dispo != 'SALECC' ".
					
					" AND lead_id > 0 ".
					(($combine_users)?
							// GET USER AND USER2
							" AND (agent_username IN('".mysqli_real_escape_string($_SESSION['db'],$agentobj->username)."','".mysqli_real_escape_string($_SESSION['db'],$agentobj->username)."2') )"
							:
							// ELSE JUST GET THE SPECIFIED USER
							" AND agent_username='".mysqli_real_escape_string($_SESSION['db'],$agentobj->username)."' "
							).
							" AND (`dispo`='NI') ".
							$ofcsql.
							(($campaign_id )?" AND campaign_id='".$campaign_id."' ":"").
							$sql_agent_cluster.
							$sql_vici_campaign;
							
							//echo "\n".$sql."\n";
							
							
							// NOT INTERESTED STATS
							list($num_NI) = $_SESSION['dbapi']->ROqueryROW($sql
									);
							
							
							
							// ANSWERING MACHINE STATS
							$sql = "SELECT COUNT(id) FROM lead_tracking ".
									
									//									" FORCE INDEX (time) ".
							//" USE INDEX (time) ".
							
							" WHERE `time` BETWEEN '$stime' AND '$etime' ".
							//	" AND `account_id`='".$_SESSION['account']['id']."' ".
							
							
							// EXCLUDE ANYTHING ROUSTING RELATED
							" AND dispo != 'SALECC' ".
							
							" AND lead_id > 0 ".
							(($combine_users)?
									// GET USER AND USER2
									" AND (agent_username IN ('".mysqli_real_escape_string($_SESSION['db'],$agentobj->username)."','".mysqli_real_escape_string($_SESSION['db'],$agentobj->username)."2') ) "
									:
									// ELSE JUST GET THE SPECIFIED USER
									" AND agent_username='".mysqli_real_escape_string($_SESSION['db'],$agentobj->username)."' "
									).
									" AND (`dispo`='A') ".
									$ofcsql.
									(($campaign_id )?" AND campaign_id='".$campaign_id."' ":"").
									$sql_agent_cluster.
									$sql_vici_campaign;
									
									
									//echo "\n".$sql."\n";
									// ANSWERING MACHINE STATS
									list($num_AnswerMachines) = $_SESSION['dbapi']->ROqueryROW($sql);
									
									
									//if($num_AnswerMachines > $num_total_calls_px)echo $sql."<br />\n\n";
									//echo $agentobj->username.' '.$agent_cluster_id.' Calls: '.$num_total_calls_px." A: ".$num_AnswerMachines."<br />\n";
									
									
									$sql = "SELECT COUNT(id) FROM transfers ".
											" WHERE xfer_time BETWEEN '$stime' AND '$etime' ".
											//	" AND `account_id`='".$_SESSION['account']['id']."' ".
									" AND (verifier_dispo IS NOT NULL  AND verifier_dispo != 'DROP' AND `verifier_dispo` != 'SALECC') ".
									$ofcsql.
									(($combine_users)?
											// GET USER AND USER2
											" AND (agent_username IN ('".mysqli_real_escape_string($_SESSION['db'],$agentobj->username)."','".mysqli_real_escape_string($_SESSION['db'],$agentobj->username)."2') ) "
											:
											// ELSE JUST GET THE SPECIFIED USER
											" AND agent_username='".mysqli_real_escape_string($_SESSION['db'],$agentobj->username)."' "
											).
											(($campaign_id > 0)?" AND campaign_id='".intval($campaign_id)."' ":"").
											$sql_vici_campaign;
											
											
											
											//			echo $sql."<br />\n";
											
											list($num_XFER) = $_SESSION['dbapi']->ROqueryROW($sql
													);
											
											
											
											$activity_paid = 0;
											$activity_wrkd = 0;
											$activity_num_calls = 0;
											
											
											if($combine_users){
												
												list($activity_paid,$activity_wrkd,$activity_num_calls)  =
												$_SESSION['dbapi']->ROqueryROW("SELECT SUM(paid_time), SUM(activity_time),SUM(calls_today) FROM activity_log ".
														"WHERE `time_started` BETWEEN '$stime' AND '$etime' ".
														//	" AND `account_id`='".$_SESSION['account']['id']."' ".
														" AND `username`='".mysqli_real_escape_string($_SESSION['db'],strtolower($agentobj->username))."' "
														//" AND `vici_cluster_id`='".$cluster_array[$idx]."' ".
														//(($campaign_code)?" AND campaign='".mysqli_real_escape_string($_SESSION['db'],$campaign_code)."' ":"")
														
														);
														
														//" AND (username='".mysql_real_escape_string($agent)."' OR username='".mysql_real_escape_string($agent)."2') "
														list($activity_paid2,$activity_wrkd2,$activity_num_calls2)  =
														$_SESSION['dbapi']->ROqueryROW("SELECT SUM(paid_time), SUM(activity_time),SUM(calls_today) FROM activity_log ".
																"WHERE `time_started` BETWEEN '$stime' AND '$etime' ".
																//	" AND `account_id`='".$_SESSION['account']['id']."' ".
																" AND `username`='".mysqli_real_escape_string($_SESSION['db'],strtolower($agentobj->username))."2' "
																//" AND `vici_cluster_id`='".$cluster_array[$idx]."' ".
																//(($campaign_code)?" AND campaign='".mysqli_real_escape_string($campaign_code)."' ":"")
																
																);
																
																// PER STEVE, DONT COMBINE HOURS WORKED
																//$activity_paid += $activity_paid2;
																//$activity_wrkd += $activity_wrkd2;
																$activity_num_calls += $activity_num_calls2;
																
											}else{
												// GET AGENT ACTIVITY TIMER
												list($activity_paid,$activity_wrkd,$activity_num_calls)  =
												$_SESSION['dbapi']->ROqueryROW("SELECT SUM(paid_time), SUM(activity_time),SUM(calls_today) FROM activity_log ".
														"WHERE `time_started` BETWEEN '$stime' AND '$etime' ".
														//" AND `account_id`='".$_SESSION['account']['id']."' ".
														" AND `username`='".mysqli_real_escape_string($_SESSION['db'],strtolower($agentobj->username))."' ".
														//" AND `vici_cluster_id`='".$cluster_array[$idx]."' ".
														(($campaign_code)?" AND campaign='".mysqli_real_escape_string($_SESSION['db'],$campaign_code)."' ":"")
														
														);
											}
											
											
											
											
											
											/// $activity_num_calls
											
											
											
											
											
											
											$paid_hrs = $avtivity_paid = $activity_paid/60;
											$active_hrs= $activity_worked = $activity_wrkd/60;
											
											
											$closing_percent = ($num_XFER <= 0)?0:(($running_salecnt / $num_XFER) * 100);
											
											$conversion_percent = (($num_NI + $running_salecnt) <= 0)?0: (($running_salecnt / ($num_NI + $running_salecnt)) * 100);
											
											
											$avg_sale = ($running_salecnt <= 0)?0:($running_amount / $running_salecnt);
											
											
											$yes2all = ($activity_num_calls <= 0)?0: ($running_salecnt / $activity_num_calls) * 100;
											
											$paid_hr = ($paid_hrs <= 0)?0:($running_amount / $paid_hrs);
											
											$wrkd_hr = ($active_hrs <= 0)?0:($running_amount / $active_hrs);
											
											
											
											
											$contacts_hr = ($paid_hrs <= 0)?0:(($num_NI + $num_XFER)/$avtivity_paid);
											$calls_hr = ($paid_hrs <= 0)?0:(($activity_num_calls)/$avtivity_paid);
											
											
											$worked_contacts_hr = ($activity_worked <= 0)?0:(($num_NI + $num_XFER)/$activity_worked);
											$worked_calls_hr = ($activity_worked <= 0)?0:(($activity_num_calls)/$activity_worked);
											
											
											$output_array[$ox++] = array(
													
													'agent_username'=>$agentobj->username,
													'cluster_id'=>$agentobj->cluster_array,
													
													'activity_paid'=>$avtivity_paid,
													'activity_wrkd'=>$activity_worked,
													'calls_today'=>$num_total_calls_px, //$activity_num_calls,
													
													'num_NI'		=> $num_NI,
													'num_XFER'	=> $num_XFER,
													
													'num_AnswerMachines' => $num_AnswerMachines,
													
													'contacts_per_paid_hour' => $contacts_hr,
													'calls_per_paid_hour' => $calls_hr,
													
													'contacts_per_worked_hour' => $worked_contacts_hr,
													'calls_per_worked_hour' => $worked_calls_hr,
													
													'sale_cnt'		=> $running_salecnt,
													'closing_percent'=> $closing_percent,
													'conversion_percent'=>$conversion_percent,
													'yes2all_percent'	=> $yes2all,
													'sales_total'		=> $running_amount,
													'paid_sales_total'	=> $running_paid_amount,
													'paid_sale_cnt'		=> $running_paid_salecnt,
													'avg_sale'			=> $avg_sale,
													
													'paid_hr'=>$paid_hr,
													'wrkd_hr'=>$wrkd_hr,
											);
											
											
											
											
											
											
											
											// TOTALS ADDUP
											$total_paid_hrs += $paid_hrs;
											$total_active_hrs += $active_hrs;
											$total_calls += $num_total_calls_px;//$activity_num_calls;
											$total_ni += $num_NI;
											$total_xfer += $num_XFER;
											
											$total_AnswerMachines += $num_AnswerMachines;
											
											$total_sale_cnt += $running_salecnt;
											$total_amount += $running_amount;
											
											$total_paid_sale_cnt += $running_paid_salecnt;
											$total_paid_amount += $running_paid_amount;
											
		}
		
		
		//print_r($output_array);
		
		if(count($output_array) > 0){
			
			$total_closing = ($total_xfer <= 0)?0: (($total_sale_cnt / $total_xfer) * 100);
			
			$total_conversion = (($total_ni + $total_sale_cnt) <= 0)?0: (($total_sale_cnt / ($total_ni + $total_sale_cnt)) * 100);
			
			$total_yes2all = ($total_calls <= 0)?0:(($total_sale_cnt / $total_calls) * 100);
			
			$total_avg = ($total_sale_cnt <= 0)?0:($total_amount / $total_sale_cnt);
			
			$total_paid_hr = ($total_paid_hrs <= 0)?0:($total_amount / $total_paid_hrs);
			
			$total_wrkd_hr = ($total_active_hrs <= 0)?0:($total_amount / $total_active_hrs);
			
			
			////
			$total_contacts_hr = ($total_paid_hrs <= 0)?0:(($total_ni + $total_xfer)/$total_paid_hrs);
			$total_calls_hr = ($total_paid_hrs <= 0)?0:(($total_calls)/$total_paid_hrs);
			//
			$total_worked_contacts_hr = ($total_active_hrs <= 0)?0:(($total_ni + $total_xfer)/$total_active_hrs);
			$total_worked_calls_hr = ($total_active_hrs <= 0)?0:(($total_calls)/$total_active_hrs);
			////
		}
		
		$totals = array(
				
				// ADDED IN THE LOOP
				'total_activity_paid_hrs' => $total_paid_hrs,
				'total_activity_wrkd_hrs' => $total_active_hrs,
				'total_calls' => $total_calls,
				'total_NI' => $total_ni,
				'total_XFER' => $total_xfer,
				
				'total_AnswerMachines' => $total_AnswerMachines,
				
				'total_contacts_per_paid_hour' => $total_contacts_hr,
				'total_calls_per_paid_hour' => $total_calls_hr,
				
				'total_contacts_per_worked_hour' => $total_worked_contacts_hr,
				'total_calls_per_worked_hour' => $total_worked_calls_hr,
				
				'total_sale_cnt' => $total_sale_cnt,
				'total_sales' => $total_amount,
				
				'total_paid_sale_cnt' => $total_paid_sale_cnt,
				'total_paid_sales' => $total_paid_amount,
				
				// MATH GENERATED FROM ABOVE DATA
				'total_closing' => $total_closing,
				'total_conversion' => $total_conversion,
				'total_yes2all' => $total_yes2all,
				'total_avg' => $total_avg,
				'total_paid_hr' => $total_paid_hr,
				'total_wrkd_hr' => $total_wrkd_hr,
		);
			
		
		// SORTING MOTHERFUCKER
		uasort($output_array, 'paidSorter');//($a, $b)
		
		## RETURN THE CORRECTLY FORMATTED DATA BASED ON OUTPUT METHOD
		switch ($output_method){

			default:
			case 'report':

				## DEFAULT OUTPUT METHOD
				return array($output_array, $totals);

				break;

			case 'xml':

				## OUTPUT REPORT DATA IN XML FORMAT
				$xml = new SimpleXMLElement();
				to_xml($xml, $output_array);

				return $xml->asXML();

				break;

			case 'json':

				## OUTPUT REPORT DATA IN JSON FORMAT
				return json_encode($output_array)."\n";
				break;


		}

	}















}