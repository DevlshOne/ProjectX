<?	/***************************************************************
	 *	PX LIST HOPPER SYSTEM - A WAY TO SCHEDULE WHAT LISTS TO TURN ON AND OFF, AND WHEN
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['list_hopper'] = new ListHopper;



class ListHopper{


	function ListHopper(){


		## REQURES DB CONNECTION!
		$this->handlePOST();



	}

	function handlePOST(){

	}

	function handleFLOW(){

	}


	function checkHopper(){
		
		
		$process_logs = date("H:i:s m/d/y")." - Starting List Hopper checks\n";
		
		
		// CONNECT PX DB
		// connectPXDB();

		$completed_stack = array();
		
		$curtime = time();
		
		
		// NEWLY CREATED TASKS, OR TASKS THAT HAVE BEEN STARTED THAT NEED FINISHED
		$where = " WHERE (`status`='new' OR `status`='started') ".
				
				// ONCE THE TIME HAS PASSED, TRIGGER IT, THEN CHECK IF WE'RE PAST "time_end" TO SEE IF WE HAVE TO DISABLE INSTEAD?
		" AND `time` <= '".$curtime."'".
		
		//" AND ('".$curtime."' BETWEEN `time` AND `time_end`) ".
		
		"";
		
		$res = query("SELECT DISTINCT(cluster_id) FROM list_hopper $where",1);
		$clusters = array();
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
			$clusters[] = $row['cluster_id'];
		}

		if(count($clusters) == 0){
			
			$process_logs .= date("H:i:s m/d/y")." - No hopper tasks found.\n";
			
		}
		
		// LOOP THROUGH STACK OF VICIDIAL SERVERS
		foreach($clusters as $cluster_id){
			
			$cluster_id = intval($cluster_id);
			
			$process_logs .= date("H:i:s m/d/y")." - Processing Vici Cluster #".$cluster_id."...\n";
			
			// LOCATE WHICH DB INDEX IT IS
			$dbidx = getClusterIndex($cluster_id);
			
			
			$process_logs .= date("H:i:s m/d/y")." - Getting all HOPPER TASKS for Cluster $cluster_id - ".$_SESSION['site_config']['db'][$dbidx]['name']." ...\n";
			
			
			$tasks = $_SESSION['dbapi']->fetchAllAssoc("SELECT * FROM `list_hopper` $where AND cluster_id='$cluster_id' ");
			
			// CONNECT TO VICIDIAL DB
			connectViciDB($dbidx);
			
			foreach($tasks as $task){
				
				$sql = "SELECT `campaign_name`, `active`, `dial_statuses` FROM `vicidial_campaigns` WHERE campaign_id='".mysqli_real_escape_string($_SESSION['db'], $task['campaign'])."'";
				
				//echo $sql;
				
				// GET THE CAMPAIGN RECORD IN VICI
				$vici_campaign = querySQL($sql);
				
				if(!$vici_campaign || $vici_campaign['active'] != 'Y'){
					
					$process_logs .= date("H:i:s m/d/y")." - Skipping task#".$task['id']." - Campaign ".$task['campaign']." - ".$vici_campaign['campaign_name']." is ".((!$vici_campaign)?"NOT FOUND":"NOT ACTIVE")."\n";
					continue;
				}else{
					
					$process_logs .= date("H:i:s m/d/y")." - Task#".$task['id']." - Loaded Campaign ".$task['campaign']." - ".$vici_campaign['campaign_name']." Statuses:".$vici_campaign['dial_statuses']."\n";
				}
				
				
				
				// THE TASK WAS PREVIOUSLY STARTED, AND WE'RE PAST OUR ENDING TIME
				if($task['status'] == 'started'){
					
					## PAST THE ENDING TIME
					if($task['time_end'] <= $curtime){
						
						$action = "disable";
						
					}else{
						
						$process_logs .= date("H:i:s m/d/y")." - Skipping task#".$task['id']." - Not time to turn off yet.\n";
						continue;
					}
					
					
				}else{
					
					
					
					$action = "enable";
					
				}
				
				
				
				
				// PARSE OUT THE DIALABLE STATUSES
				$dialarr = preg_split('/\s+/', $vici_campaign['dial_statuses'], -1, PREG_SPLIT_NO_EMPTY);
				$dialable_status_sql = "AND `status` IN ('";
				$y=0;
				foreach($dialarr as $status){
					if(!trim($status)) continue;
					
					if($y++ > 0)$dialable_status_sql.= "','";
					
					$dialable_status_sql .= mysqli_real_escape_string($_SESSION['db'],$status);
				}
				
				if($y > 0)$dialable_status_sql .= "') ";
				
				
				$run_task = false;
				$lead_count = 0;
				
				// "lead_count_trigger" set to 0, means enable immediately, dont count.
				if($task['lead_count_trigger'] == 0){
					
					$run_task = true;
					
				}else{
					// COUNT FIRST
					//
					
					// GET ALL ACTIVE LISTS FOR THE CAMPAIGN
					$lists = fetchAllAssoc("SELECT list_id FROM `vicidial_lists` WHERE `campaign_id`='".mysqli_real_escape_string($_SESSION['db'], $task['campaign'])."' AND `active`='Y' ");
					
					if(count($lists) > 0){
						$listsql = " AND `list_id` IN(";
						$x=0;
						foreach($lists as $list){
							
							$listsql .= ($x++ > 0)?',':'';
							
							$listsql .= $list['list_id'];
						}
						
						if($x > 0){
							$listsql.= ')';
							
							$sql = "SELECT COUNT(*) FROM `vicidial_list` WHERE called_since_last_reset='N' $listsql $dialable_status_sql";
							// 					echo $sql;
							// 					exit;
							list($lead_count) = queryROW($sql);
							
						}
						
					}
					
					if($lead_count <= $task['lead_count_trigger']){
						$run_task = true;
					}else{
						
						$process_logs .= date("H:i:s m/d/y")." - Skipping task#".$task['id']." - Lead count $lead_count is above its trigger value: ".$task['lead_count_trigger']."\n";
						continue;
					}
					
					
				}
				
				
				if($run_task == true){
					
					$process_logs .= date("H:i:s m/d/y")." - Running task#".$task['id']." - ".$action." ".$task['list_ids']." ".(($task['lead_count_trigger'] > 0)?"(Lead count: $lead_count / Trigger: ".$task['lead_count_trigger'].")":'')."\n";
					
					// continue;
					
					
					
					switch($action){
						default:
							$process_logs .= date("H:i:s m/d/y")." - Skipping task#".$task['id']." - Action '".$action."' Unknown\n";
							break;
						case 'enable':
							
							
							$process_logs .= date("H:i:s m/d/y")." - Enabling Lists(".$task['list_ids'].") for cluster: ".$_SESSION['site_config']['db'][$dbidx]['name']."\n";
							
							
							$sql = "UPDATE `vicidial_lists` SET `active`='Y' WHERE `list_id` IN(".mysqli_real_escape_string($_SESSION['db'],$task['list_ids']).")";
							
							//echo $sql."\n";
							execSQL($sql);
							
							$status = ($task['time_end'] > 0)?"started":"done";
							
							$_SESSION['dbapi']->execSQL("UPDATE `list_hopper` SET `status`='$status' WHERE id='".$task['id']."' ");
							
							if($status == 'done'){
								$completed_stack[] = $task;
							}
							
							break;
						case 'disable':
							
							
							$process_logs .= date("H:i:s m/d/y")." - Disabling Lists(".$task['list_ids'].") for cluster: ".$_SESSION['site_config']['db'][$dbidx]['name']."\n";
							
							$sql = "UPDATE `vicidial_lists` SET `active`='N' WHERE `list_id` IN(".mysqli_real_escape_string($_SESSION['db'],$task['list_ids']).")";
							
							//echo $sql."\n";
							execSQL($sql);
							
							$_SESSION['dbapi']->execSQL("UPDATE `list_hopper` SET `status`='done' WHERE id='".$task['id']."' ");
							
							$completed_stack[] = $task;
							
							break;
					}
					
					//echo "\n";
					
				}
				
			}
			
			
			
			
		} // END CLUSTER LOOP
		
		
		
		// LOOP THROUGH THE COMPLETED TASKS, AND RESCHEDULE AS NECESSARY
		foreach($completed_stack as $task){
			
			$tid = $task['id'];
			
			switch($task['repeats']){
			case 'no':
			default:
				continue;
			
			case 'daily':
				
				
				// CREATE A NEW TASK FOR TOMORROW
				unset($task['id']);
				
				$task['time'] = mktime(date("H", $task['time']), date("i", $task['time']), date("s", $task['time'])) + 86400;
				$task['time_end'] = ($task['time_end'] == 0)?0:mktime(date("H", $task['time_end']), date("i", $task['time_end']), date("s", $task['time_end'])) + 86400;
				
				$task['status'] = 'new';
				
				$_SESSION['dbapi']->aadd($task, 'list_hopper');
				
				
				$process_logs .= date("H:i:s m/d/y")." - Repeat Daily Scheduled for task #$tid - Cluster: ".$task['cluster_id']." on ".date("H:i:s m/d/y", $task['time'])."\n";
				
				break;
			case 'weekly':
				
				// CREATE A NEW TASK FOR NEXT WEEK
				unset($task['id']);
				
// 				$task['time'] += 604800;//mktime(date("H", $task['time']), date("i", $task['time']), date("s", $task['time']))
// 				$task['time_end'] = ($task['time_end'] == 0)?0:$task['time_end'] + 604800;
				
				$task['time'] = mktime(date("H", $task['time']), date("i", $task['time']), date("s", $task['time'])) + 604800;
				$task['time_end'] = ($task['time_end'] == 0)?0:mktime(date("H", $task['time_end']), date("i", $task['time_end']), date("s", $task['time_end'])) + 604800;
				
				$task['status'] = 'new';
				
				$_SESSION['dbapi']->aadd($task, 'list_hopper');
				
				$process_logs .= date("H:i:s m/d/y")." - Repeat Weekly Scheduled for task #$tid - Cluster: ".$task['cluster_id']." on ".date("H:i:s m/d/y", $task['time'])."\n";
				
				break;
			}
			
		}
		
		$process_logs .= date("H:i:s m/d/y")." - checkHopper() Finished.\n";
		
		return $process_logs;
		
	} // END checkHopper() function

	
	
	
	
} // END ListHopper Class
