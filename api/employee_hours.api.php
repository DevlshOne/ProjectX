<?



class API_Employee_Hours{

	var $xml_parent_tagname = "Emps";
	var $xml_record_tagname = "Emp";

	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";


	function handleAPI(){


		if(!checkAccess('employee_hours')){


			$_SESSION['api']->errorOut('Access denied to Employee Hours');

			return;
		}



		switch($_REQUEST['action']){
		case 'delete':

			$id = intval($_REQUEST['id']);

			//$row = $_SESSION['dbapi']->campaigns->getByID($id);


			$_SESSION['dbapi']->employee_hours->delete($id);


			logAction('delete', 'employee_hours', $id, "");

			$_SESSION['api']->outputDeleteSuccess();


			break;

		case 'view':


			$id = intval($_REQUEST['id']);

			$row = $_SESSION['dbapi']->employee_hours->getByID($id);




			## BUILD XML OUTPUT
			$out = "<".$this->xml_record_tagname." ";

			foreach($row as $key=>$val){


				$out .= $key.'="'.htmlentities($val).'" ';

			}

			$out .= " />\n";






			///$out .= "</".$this->xml_record_tagname.">";

			echo $out;



			break;


		case 'add':

			//print_r($_POST);





			//agent_id[]
			//$dat['username'] = trim(strtolower($_POST['agent_id']));

			foreach($_POST['agent_id'] as $username){

				$username = trim($username);

				// SKIP BLANKS
				if(!$username)continue;


				$dat = array();

				$dat['username'] = $username;

				$dat['time_started'] = mktime(0,0,0, $_POST['date_month'], $_POST['date_day'], $_POST['date_year']);

				$dat['vici_cluster_id'] = intval($_POST['cluster_id']);
				$dat['campaign'] = trim($_POST['campaign_id']);

				$dat['call_group'] = trim($_POST['user_group']);

				$dat['office'] = $_POST['office_id'];

				$dat['paid_time'] = floatval($_POST['hours']) * 60;

				$dat['notes'] = trim($_POST['notes']);


				aadd($dat, 'activity_log');

				$id = mysqli_insert_id($_SESSION['db']);

				logAction('add', 'employee_hours', $id, "");
			}

			$_SESSION['api']->outputEditSuccess(1);

			///print_r($dat);

			break;

		case 'edit':

			//$id = intval($_POST['editing_emp']);


			if(!checkAccess('employee_hours_edit')){

				$_SESSION['api']->errorOut('Access denied to EDIT Employee Hours');

				return;
			}

/// DO STUFF

			$id_array = preg_split("/\t/", $_POST['activity_ids'], -1);

			$hours_array = preg_split("/\t/", $_POST['activity_hours'], -1);

			$notes_array = preg_split("/\|\|/", $_POST['activity_notes'], -1);


			foreach($id_array as $idx=>$activity_id){

				$activity_id = intval($activity_id);

				// SKIP ANY ACTIVITYS THAT LACK AN ID
				if($activity_id <= 0) continue;

				$notes = $notes_array[$idx];
				$hours = $hours_array[$idx];


				list($hrs,$min) = preg_split("/\:/", $hours);

				$min += ($hrs * 60);

				$dat = array();
				$dat['paid_time'] = $min;//floatval($hours) * 60;
				$dat['notes'] = $notes;

				aedit($activity_id, $dat, 'activity_log');


				logAction('edit', 'employee_hours', $activity_id, "");
			}



			$_SESSION['api']->outputEditSuccess(1);



			break;


		default:
		case 'list':
			$dat = array();
			$totalcount = 0;
			$pagemode = false;
			$dat['date_mode'] = trim($_REQUEST['s_date_mode']);
			if($dat['date_mode'] == 'daterange'){
				$dat['date1'] = $_REQUEST['stime_year'].'-'.$_REQUEST['stime_month'].'-'.$_REQUEST['stime_day'];
				$dat['date2'] = $_REQUEST['etime_year'].'-'.$_REQUEST['etime_month'].'-'.$_REQUEST['etime_day'];
			}else{
				// RESERVED FOR TIME SEARCH STUFF
				if($_REQUEST['stime_month']){
					$dat['date'] = $_REQUEST['stime_year'].'-'.$_REQUEST['stime_month'].'-'.$_REQUEST['stime_day'];
				}else{
					$dat['date'] = date("Y-m-d");
				}
			}
			## ID SEARCH
			if($_REQUEST['s_id']){

				$dat['id'] = intval($_REQUEST['s_id']);

			}

			// AGENT USERNAME
			if($_REQUEST['s_username']){

				$dat['username'] = trim($_REQUEST['s_username']);

			}

			if($_REQUEST['s_show_problems'] == "true"){

				$dat['show_problems'] = 1;
			}

			if($_REQUEST['s_main_users'] == "true"){

				$dat['main_users'] = 1;

			}



//			if($_REQUEST['s_office_id']){
//
//				$dat['office_id'] = intval($_REQUEST['s_office_id']);
//
//			}



// OFFICE RESTRICTION/SEARCH ABILITY
			if(
				($_SESSION['user']['priv'] < 5) &&
				($_SESSION['user']['allow_all_offices'] != 'yes')

				){


				if(count($_SESSION['assigned_offices']) > 0){
					$tmpofc = intval($_REQUEST['s_office_id']);

					if($tmpofc > 0){

						if(in_array($tmpofc, $_SESSION['assigned_offices'])){

							$dat['office_id'] = $tmpofc;

						}else{

							$dat['office_id'] = $_SESSION['assigned_offices'];

						}

					}else{

						$dat['office_id'] = $_SESSION['assigned_offices'];

					}
				}else{
					// DISABLE ALL OFFICE ACCESS (BASICALLLY SHOW NO DATA?)
					//$dat['office'] = -1;
				}


			}else{
				$tmpofc = intval($_REQUEST['s_office_id']);

				if($tmpofc > 0){

					$dat['office_id'] = $tmpofc;

				}

			}










			if($_REQUEST['s_user_group']){

				if(strpos($_REQUEST['s_user_group'], "|") > -1){

					$dat['call_group'] = preg_split("/\|/", trim($_REQUEST['s_user_group']), -1, PREG_SPLIT_NO_EMPTY);

				}else{

					$dat['call_group'] = trim($_REQUEST['s_user_group']);

				}

			}


			if(intval($_REQUEST['report_mode']) > 0){

				$dat['report_mode'] = intval($_REQUEST['report_mode']);

			}else{
				$dat['report_mode'] = 0;
			}

		// CSV MODE - IGNORE PAGE SYSTEM!
		if($_SESSION['api']->mode != "csv"){

			## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
			if(isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])){

				$pagemode = true;

				$cntdat = $dat;
				$cntdat['fields'] = 'COUNT(id)';
				list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->employee_hours->getResults($cntdat));

				$dat['limit'] = array(
									"offset"=>intval($_REQUEST['index']),
									"count"=>intval($_REQUEST['pagesize'])
								);

			}

		}




			## ORDER BY SYSTEM
			if($_REQUEST['orderby'] && $_REQUEST['orderdir']){
				
				if($_REQUEST['orderby'] == 'time_started'){

					$dat['fields'] = '*, FROM_UNIXTIME(time_started, \'%Y-%m-%d\') as date_started ';
					
					$dat['order'] = array(
										"date_started"=>$_REQUEST['orderdir'],
										'username' => 'ASC'
									);
					
				}else if($_REQUEST['orderby'] == 'username'){
					
					$dat['fields'] = '*, FROM_UNIXTIME(time_started, \'%Y-%m-%d\') as date_started ';
					
					$dat['order'] = array(
							$_REQUEST['orderby']=>$_REQUEST['orderdir'],
							'date_started' => 'ASC'
					);
					
				}else{
					$dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
				}
				
// 				print_r($dat);
				
			}else if($dat['report_mode']){
				
				switch($dat['report_mode']){
				default:
					break;
				case 5:
					
					$dat['order'] = array(
										'username' => 'ASC',
										'time_started' => 'ASC'
									);
					
					break;
				}
				
			}







			$res = $_SESSION['dbapi']->employee_hours->getResults($dat);



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


		## GENERATE CSV
			case 'csv':


				if($dat['date_mode'] == 'daterange'){
					$filename = "Hours_Data-".$dat['date1']."-to-".$dat['date2'].".csv";

					$stime = strtotime($dat['date1']);
					$stime = mktime(0,0,0,date("m",$stime), date("d", $stime), date("Y", $stime));

					$etime = strtotime($dat['date2']);
					$etime = mktime(23,59,59,date("m",$etime), date("d", $etime), date("Y", $etime));

				}else{
					$filename = "Hours_Data-".$dat['date'].".csv";

					$stime = strtotime($dat['date']);
					$stime = mktime(0,0,0,date("m",$stime), date("d", $stime), date("Y", $stime));
					$etime = $stime + 86399;
				}

//echo date("g:i:sa m/d/Y", $stime).' - '.date("g:i:sa m/d/Y", $etime)."\n";


				switch($dat['report_mode']){
				case 1:

					$rowarr = array();
					$total_activity = 0;
					$total_paid = 0;
					$total_incall = 0;
					$total_dispo=0;
					while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
						$user = strtolower($row['username']);

//echo $user.' '.$row['paid_time']." minutes, in hours (".($row['paid_time']/60).") rounds to ".round($row['paid_time']/60,2)."\n";

						// ADD FRESH
						if(!isset($rowarr[$user]) || !$rowarr[$user]){

							$rowarr[$user] = $row;

							$rowarr[$user]['date'] = date("m/d/Y", $row['time_started']);

						// APPEND
						}else{

							//echo $user.' '.$row['paid_time'].' vs '.((float)$row['paid_time']

							$rowarr[$user]['activity_time'] += $row['activity_time'];
							$rowarr[$user]['paid_time'] += $row['paid_time'];

							$rowarr[$user]['seconds_INCALL'] += $row['seconds_INCALL'];
							$rowarr[$user]['seconds_READY'] += $row['seconds_READY'];
							$rowarr[$user]['seconds_QUEUE'] += $row['seconds_QUEUE'];
							$rowarr[$user]['seconds_PAUSED'] += $row['seconds_PAUSED'];
							$rowarr[$user]['seconds_DISPO'] += $row['seconds_DISPO'];




							$rowarr[$user]['date'] .= " - ".date("m/d/Y", $row['time_started']);

							$rowarr[$user]['notes'] .= " | ".$row['notes'];
						}





						$sql = "SELECT `id`,`verifier_dispo_time`,`time` FROM `lead_tracking` ".
															"WHERE `verifier_username`='".mysqli_real_escape_string($_SESSION['dbapi']->db, $user )."' ".
															" AND `time` BETWEEN '$stime' AND '$etime' ".
															" AND verifier_dispo_time > 0";
						//echo $sql;

						$res2 = $_SESSION['dbapi']->query($sql, 1) ; //or die("Mysql error: ".mysqli_error())
						$total_agent_handle_time=0;

						if($res2 !== FALSE && mysqli_num_rows($res2) > 0){

							while($row2 = mysqli_fetch_array($res2, MYSQLI_ASSOC)){


								$total_agent_handle_time += intval($row2['verifier_dispo_time'] - $row2['time']);

							}

						}


						$rowarr[$user]['t_handle_time'] = $total_agent_handle_time;


						$total_incall += $row['seconds_INCALL'];
						$total_ready += $row['seconds_READY'];
						$total_queue += $row['seconds_QUEUE'];
						$total_paused += $row['seconds_PAUSED'];
						$total_dispo += $row['seconds_DISPO'];



						$total_activity += $row['activity_time'];


//echo $row['paid_time']."<br >\n";

						// ROUNDING THE PISS OUT OF THINGS TO MAKE THEM APPEAR HOW MANAGEMENT WOULD LIKE IT. 2/21/2017 -Jon
						//$total_paid += round($row['paid_time']/60,2);
						//$total_paid += round($row['paid_time']/60,3);


						$total_paid += round($row['paid_time']/60, 2);

					}



					$out = "Date,Agent,Group,Office,Activity,Activity(New),Activity(New-Min),In-Call Time,In-Call Time(Min),Handle Time,Handle Time(Min),Ready Time,Queue Time,Paused Time,Dispo Time,Paid Hours,Notes\r\n";


					foreach($rowarr as $row){

						$new_activity = $row['seconds_INCALL'] + $row['seconds_READY'] + $row['seconds_QUEUE'] + $row['seconds_DISPO'];





						$out .= $row['date'].",".
								$row['username'].",".
								$row['call_group'].",".
								$row['office'].",".
								round($row['activity_time']/60,2).",".

								renderTimeFormattedSTD($new_activity).",".
								round($new_activity/60,1).",".


								renderTimeFormattedSTD($row['seconds_INCALL']).",".
								round($row['seconds_INCALL']/60,1).",".

								renderTimeFormattedSTD($row['t_handle_time']).",".
								round($row['t_handle_time']/60,1).",".

								renderTimeFormattedSTD($row['seconds_READY']).",".
								renderTimeFormattedSTD($row['seconds_QUEUE']).",".
								renderTimeFormattedSTD($row['seconds_PAUSED']).",".
								renderTimeFormattedSTD($row['seconds_DISPO']).",".


								round($row['paid_time']/60, 2).",".
								//renderTimeFormattedSTD($row['paid_time'] * 60).",".

								preg_replace("/[,\"']/",'',$row['notes'])."\r\n";

						$total_act += $new_activity;

						$t_handle += $row['t_handle_time'];
						$t_incall += $row['seconds_INCALL'];
						$t_ready += $row['seconds_READY'];
						$t_queue += $row['seconds_QUEUE'];
						$t_paused+= $row['seconds_PAUSED'];

					} // END FOR LOOP

					$out .= "TOTALS,".count($rowarr).",,,".round($total_activity/60,2).",".renderTimeFormattedSTD($total_act).",".round($total_act/60,2).",".
							renderTimeFormattedSTD($t_incall).",".round($t_incall/60,2).",".
							renderTimeFormattedSTD($t_handle).",".round($t_handle/60,2).",".


							renderTimeFormattedSTD($t_ready).",".renderTimeFormattedSTD($t_queue).",".renderTimeFormattedSTD($t_paused).",".
							renderTimeFormattedSTD($total_dispo).",".
							round($total_paid,2).",\r\n";
							//renderTimeFormattedSTD($total_paid * 60).",\r\n";


					break;


				case 2:

$rowarr = array();
					$total_activity = 0;
					$total_paid = 0;
					$total_incall = 0;
					$total_dispo=0;
					while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
						$user = strtolower($row['username']);

						// ADD FRESH
						if(!$rowarr[$user]){

							$rowarr[$user] = $row;

							$rowarr[$user]['date'] = date("m/d/Y", $row['time_started']);

						// APPEND
						}else{

							$rowarr[$user]['activity_time'] += $row['activity_time'];
							$rowarr[$user]['paid_time'] += $row['paid_time'];

							$rowarr[$user]['seconds_INCALL'] += $row['seconds_INCALL'];
							$rowarr[$user]['seconds_READY'] += $row['seconds_READY'];
							$rowarr[$user]['seconds_QUEUE'] += $row['seconds_QUEUE'];
							$rowarr[$user]['seconds_PAUSED'] += $row['seconds_PAUSED'];
							$rowarr[$user]['seconds_DISPO'] += $row['seconds_DISPO'];




							$rowarr[$user]['date'] .= " - ".date("m/d/Y", $row['time_started']);

							$rowarr[$user]['notes'] .= " | ".$row['notes'];
						}





						$sql = "SELECT `id`,`verifier_dispo_time`,`time` FROM `lead_tracking` ".
															"WHERE `verifier_username`='".mysqli_real_escape_string($_SESSION['dbapi']->db, $user )."' ".
															" AND `time` BETWEEN '$stime' AND '$etime' ".
															" AND verifier_dispo_time > 0";
						//echo $sql;

						$res2 = $_SESSION['dbapi']->query($sql, 1);
						$total_agent_handle_time=0;
						while($row2 = mysqli_fetch_array($res2, MYSQLI_ASSOC)){


							$total_agent_handle_time += intval($row2['verifier_dispo_time'] - $row2['time']);

						}


						$rowarr[$user]['t_handle_time'] = $total_agent_handle_time;


						$total_incall += $row['seconds_INCALL'];
						$total_ready += $row['seconds_READY'];
						$total_queue += $row['seconds_QUEUE'];
						$total_paused += $row['seconds_PAUSED'];
						$total_dispo += $row['seconds_DISPO'];



						$total_activity += $row['activity_time'];
						$total_paid += $row['paid_time'];

					}




					$out = "Date,Agent,Group,Office,Activity,Activity(Hours),INACTIVE Time,INACTIVE Time(Hours),Paid Hours,Notes\r\n";


					$t_dispo = 0;

					foreach($rowarr as $row){

						$new_activity = $row['seconds_INCALL'] + $row['seconds_READY'] + $row['seconds_QUEUE'] + $row['seconds_dispo'];





						$out .= $row['date'].",".
								$row['username'].",".
								$row['call_group'].",".
								$row['office'].",".

								//round($row['activity_time']/60,2).",".
								//renderTimeFormattedSTD(($row['activity_time'] * 60)).",".

								renderTimeFormattedSTD($new_activity).",".
								round($new_activity/3600,1).",".



								renderTimeFormattedSTD(($row['seconds_PAUSED'] - $row['seconds_DISPO'])).",".
								round(($row['seconds_PAUSED'] - $row['seconds_DISPO'])/3600,2).",".


								round($row['paid_time']/60,2).",".
								preg_replace("/[,\"']/",'',$row['notes'])."\r\n";

						$total_act += $new_activity;


						$t_dispo += $row['seconds_DISPO'];

						$t_handle += $row['t_handle_time'];
						$t_incall += $row['seconds_INCALL'];
						$t_ready += $row['seconds_READY'];
						$t_queue += $row['seconds_QUEUE'];
						$t_paused+= $row['seconds_PAUSED'];

					} // END FOR LOOP

					$out .= "TOTALS,".count($rowarr).",,,".

							renderTimeFormattedSTD($total_act).",".
							round($total_act/3600,2).",".

							//renderTimeFormattedSTD($total_activity * 60,2).","

							renderTimeFormattedSTD(($t_paused-$t_dispo)).",".
							round(($t_paused-$t_dispo)/3600,2).",".

							round($total_paid/60,2).",\r\n";



					break;

					
				case 5:
					
					if($dat['date_mode'] == 'daterange'){
						$filename = "Agent-Totals-".$dat['date1']."-to-".$dat['date2'].".csv";
						
						
					}else{
						$filename = "Agent-Totals-".$dat['date'].".csv";
						
						
					}
					
					
					
					
					$out = "Agent,Office,";

					$date_totals = array();
					for($x = $stime;$x < $etime;$x += 86400){
						$curdate = date("m/d/y", $x);
						$out .= $curdate.',';
						
						$date_totals[$curdate] = 0;
						
					}
					
					$out .= "Total\r\n";
					
					
					$agent_arr = array();
					
					while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
						
						$row['username'] = strtoupper($row['username']);
						
						if(!$agent_arr[$row['username']]){
							$agent_arr[$row['username']] = array();
							$agent_arr[$row['username']]['total_paid_hours'] = 0;
						}
						
						$new_activity = $row['seconds_INCALL'] + $row['seconds_READY'] + $row['seconds_QUEUE'];
						
						$curdate = date("m/d/y", $row['time_started']);
						
						$agent_arr[$row['username']]['office'] = $row['office'];
						
						$agent_arr[$row['username']][$curdate] = $row;
						
						//if(!isset($agent_arr[$row['username']][$curdate]['total_paid_hours'])){
						//	$agent_arr[$row['username']][$curdate]['total_paid_hours'] = 0;						
						//}
						
						
						$agent_arr[$row['username']][$curdate]['paid_hours'] = round($row['paid_time']/60,2);
						
						//$agent_arr[$row['username']][$curdate]['total_paid_hours'] += $agent_arr[$row['username']][$curdate]['paid_hours'];
						$agent_arr[$row['username']]['total_paid_hours'] += $agent_arr[$row['username']][$curdate]['paid_hours'];
						
// 						$out .= date("m/d/Y", $row['time_started']).",".
// 								$row['username'].",".
// 								$row['call_group'].",".
// 								$row['office'].",".
// 								round($row['activity_time']/60,2).",".
								
// 								renderTimeFormattedSTD($new_activity).",".
								
// 								renderTimeFormattedSTD($row['seconds_INCALL']).",".
// 								renderTimeFormattedSTD($row['seconds_READY']).",".
// 								renderTimeFormattedSTD($row['seconds_QUEUE']).",".
// 								renderTimeFormattedSTD($row['seconds_PAUSED']).",".
								
// 								round($row['paid_time']/60,4).",".
// 								preg_replace("/[,\"']/",'',$row['notes'])."\r\n";
								
								
					} // END WHILE LOOP (data prep)
					
					
					$grand_total = 0;
					foreach($agent_arr as $username => $data){
						
						$out .= $username.','.$data['office'].',';
						
						
						for($x = $stime;$x < $etime;$x += 86400){
							$curdate = date("m/d/y", $x);
							
							$out .= $data[$curdate]['paid_hours'].',';
							
							
							$date_totals[$curdate] += $data[$curdate]['paid_hours'];
						}
						
						$out .= $data['total_paid_hours']."\r\n";
						
						$grand_total += $data['total_paid_hours'];
					}
					
					$out .= ",,";
					
					for($x = $stime;$x < $etime;$x += 86400){
						$curdate = date("m/d/y", $x);
						
						
						$out .= $date_totals[$curdate].',';
					}
					
					$out .= $grand_total."\r\n";
					
					header("Content-Type: text/csv");
					
					header('Content-Disposition: attachment; filename="'.$filename.'"');
					
					
					echo $out;
					
					exit;
					
					
					break;
					
				default:

					//$out = "Date,Agent,Group,Office,Activity,Paid Hours,Notes\r\n";
					$out = "Date,Agent,Group,Office,Activity,Activity(New),In-Call Time,Ready Time,Queue Time,Paused Time,Paid Hours,Notes\r\n";


					while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

						$new_activity = $row['seconds_INCALL'] + $row['seconds_READY'] + $row['seconds_QUEUE'];

						$out .= date("m/d/Y", $row['time_started']).",".
								$row['username'].",".
								$row['call_group'].",".
								$row['office'].",".
								round($row['activity_time']/60,2).",".

								renderTimeFormattedSTD($new_activity).",".

								renderTimeFormattedSTD($row['seconds_INCALL']).",".
								renderTimeFormattedSTD($row['seconds_READY']).",".
								renderTimeFormattedSTD($row['seconds_QUEUE']).",".
								renderTimeFormattedSTD($row['seconds_PAUSED']).",".

								round($row['paid_time']/60,4).",".
								preg_replace("/[,\"']/",'',$row['notes'])."\r\n";


					}



					break;
				}






				header("Content-Type: text/csv");

				header('Content-Disposition: attachment; filename="'.$filename.'"');


				echo $out;

				exit;



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
