<?
/**
 * Employee hours mgt tool db functions
 */



class EmployeeHoursAPI{

	var $table = "activity_log";

	var $week_field_arr = null;

	
	var $shift_flex_time = 300; // IN SECONDS, 5 minutes of flexible time before considered late
	
	function __construct() {
		
		// THE WEEK ARRAY, THE WEEK ARRAY,THE WEEK ARRAY,THE WEEK ARRAY, IN THE JUNGLE, THE DIGITAL JUNGLE, THE VIRUS SLEEPS TONIIGHHHTTTTT
		$this->week_field_arr = array();
		$this->week_field_arr[0] = "work_sun";
		$this->week_field_arr[1] = "work_mon";
		$this->week_field_arr[2] = "work_tues";
		$this->week_field_arr[3] = "work_wed";
		$this->week_field_arr[4] = "work_thurs";
		$this->week_field_arr[5] = "work_fri";
		$this->week_field_arr[6] = "work_sat";
	}
	
	


	/**
	 * Marks a campaign as deleted
	 */
	function delete($id){

		return $_SESSION['dbapi']->adelete($id,$this->table);
	}


	/**
	 * Get a Name by ID
	 * param 	$id		The database ID of the record
	 * 	 * @return	assoc-array of the database record
	 */
	function getByID($id){
		$id = intval($id);

		return $_SESSION['dbapi']->querySQL("SELECT * FROM `".$this->table."` ".
						" WHERE id='".$id."' "

					);
	}




	/**
	 * getResults($asso_array)

	 * Array Fields:
	 * 	fields	: The select fields for the sql query, * is default
	 *	id		: Int/Array of Ints
	 *  enabled	: String only, "yes"/"no"
	 *

	 *  skip_id : Int/Array of ID's to skip (AND seperated, != operator)
	 *
	 *  order : ORDER BY field, Assoc-array,
	 * 		Example: "order" = array("id"=>"DESC")
	 *  limit : Assoc-Array of 2 keys/values.
	 * 		"count"=>(amount to limit by)
	 * 		"offset"=>(optional, the number of records to skip)
	 */
	function getResults($info){

		$fields = ($info['fields'])?$info['fields']:'*';


		$sql = "SELECT $fields FROM `".$this->table."` WHERE 1 ";
		//$sql = "SELECT $fields FROM `".$this->table."` WHERE account_id='".$_SESSION['account']['id']."' ";





		// TIME SEARCH
		// array(start time, end time)

//		if(is_array($info['time'])){
//
//			$sql .= " AND `time` BETWEEN '".intval($info['time'][0])."' AND '".intval($info['time'][1])."' ";
//
//		}




	## ID FIELD SEARCH
		## ARRAY OF id's SEARCH
		if(is_array($info['id'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['id'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`id`='".intval($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE ID SEARCH
		}else if($info['id']){

			$sql .= " AND `id`='".intval($info['id'])."' ";

		}


		## AGENT USERNAME SEARCH
		if(is_array($info['username'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['username'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "(`username` LIKE '".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%') ";
			}

			$sql .= ") ";

		## SINGLE SEARCH
		}else if($info['username']){

			$sql .= " AND (`username` LIKE '".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['username'])."%') ";

		}


## OFFICe SEARCH
		if(is_array($info['office_id'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['office_id'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "(`office`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."') ";
			}

			$sql .= ") ";

		## SINGLE SEARCH
		}else if($info['office_id']){

			$sql .= " AND (`office`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['office_id'])."') ";

		}


		if($info['date_mode'] == 'daterange'){

			## DATE SEARCH
			if($info['date1'] && $info['date2']){

				$stime = strtotime($info['date1']);
				$etime = strtotime($info['date2']) + 86399;

				$sql .= " AND `time_started` BETWEEN '$stime' AND '$etime'  ";
			}

		}else{
			## DATE SEARCH
			if($info['date']){

				$stime = strtotime($info['date']);
				$etime = $stime + 86399;

				$sql .= " AND `time_started` BETWEEN '$stime' AND '$etime'  ";
			}
		}

		if(is_array($info['call_group'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['call_group'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "(`call_group`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."') ";
			}

			$sql .= ") ";

		## SINGLE SEARCH
		}else if($info['call_group']){

			$sql .= " AND (`call_group`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['call_group'])."') ";

		}


		// MAIN USERS ONLY
		if($info['main_users']){

			$sql .= " AND `username` NOT REGEXP '[0-9]\$' ";

		}

		// ONLY SHOW PROBLEMS CHECKBOX
		if($info['show_problems']){


			$sql .= " AND ((`paid_time` + `paid_corrections`) > activity_time) ";

		}



	## SKIP/IGNORE ID's
		if(isset($info['skip_id'])){

			$sql .= " AND (";

			if(is_array($info['skip_id'])){
				$x=0;
				foreach($info['skip_id'] as $sid){

					if($x++ > 0)$sql .= " AND ";

					$sql .= "`id` != '".intval($sid)."'";
				}

			}else{
				$sql .= "`id` != '".intval($info['skip_id'])."' ";
			}

			$sql .= ")";
		}


	### ORDER BY
		if(is_array($info['order'])){

			$sql .= " ORDER BY ";
			$x=0;
			foreach($info['order'] as $k=>$v){
				if($x++ > 0)$sql .= ",";

				$sql .= "`$k` ".mysqli_real_escape_string($_SESSION['dbapi']->db,$v)." ";
			}

		}

		if(is_array($info['limit'])){

			$sql .= " LIMIT ".
						(($info['limit']['offset'])?$info['limit']['offset'].",":'').
						$info['limit']['count'];

		}


		//echo $sql;

		## RETURN RESULT SET
		return $_SESSION['dbapi']->ROquery($sql);
	}



	function getCount(){

		$row = mysqli_fetch_row($this->getResults(
						array(
							"fields" => "COUNT(id)"
						)
					));

		return $row[0];
	}


	function loadSchedules($company_id = 0, $office_id = 0){
		$out = array();
		
		$sql =
		"SELECT * FROM `schedules` WHERE 1 ".
		(($company_id > 0)?" AND `company_id`='".intval($company_id)."' ":'').
		(($office_id > 0)?" AND `office_id`='".intval($office_id)."' ":'').
		
		
		" ORDER BY company_id ASC, office_id ASC, user_group ASC, start_time ASC";
		
		//echo $sql;
		
		$rowarr = $_SESSION['dbapi']->fetchAllAssoc(
				$sql
				);
		
		//print_r($rowarr);exit;
		
		foreach($rowarr as $row){
			
			$cid = intval($row['company_id']);
			$ofc = intval($row['office_id']);
			
			if(!is_array($out[$cid])){
				$out[$cid] = array();
			}
			
			if(!is_array($out[$cid][$ofc])){
				$out[$cid][$ofc] = array();
			}
			
			$out[$cid][$ofc][] = $row;
		}
		
		return $out;
	}
	
	function loadRules($company_id = 0){
		
		
		$out = array();
		$rowarr = $_SESSION['dbapi']->fetchAllAssoc(
				
			"SELECT * FROM `companies_rules` WHERE 1 ".
			(($company_id > 0)?" AND `company_id`='".intval($company_id)."' ":'').
			" ORDER BY company_id ASC, trigger_value ASC"
		);
	
		foreach($rowarr as $row){
			$out[$row['company_id']][] = $row;
		}
		
		return $out;
	}
	
	
	
	
	
	
	
	
	function selectCorrectSchedule($schedules, $time = 0, $user_group = null){
		
		if($time == 0)$time = time();
		
		$diw = date("w", $time);
		
		$fieldname = $this->week_field_arr[$diw];

		$user_group = ($user_group != null)?trim($user_group):null;
		
		foreach($schedules as $schedule){
			
			
			// FILTER THE ONES WHO DONT HAVE THE DAY IN QUESTION ENABLED
			if($schedule[$fieldname] == 'no') continue;
			
			
			// USER GROUP FILTER
			if($user_group != null){
				
				if($schedule['user_group'] == null) continue;
			
				$garr = preg_split("/,/", $schedule['user_group'], -1, PREG_SPLIT_NO_EMPTY);

				if(!in_array($user_group , $garr, false )) continue;
				//if($user_group != $schedule['user_group']) continue;
			
				
				
			}
			
			return $schedule;
		}
		
		
		// IF NO SCHEDULE FOUND, BUT GROUP WAS SPECIFIED, TRY W/O GROUP
		if($user_group != null)return $this->selectCorrectSchedule($schedules, $time, null);
		
		
		return null;
	}
	
	
	
	
	
	
	function autoCalcEmployeeHours($start_time_override = 0){
		
		$out = '';
		
		if($start_time_override > 0){
			$stime = $start_time_override;
			$etime = $stime + 86399;
		}else{
			// CURRENT DAY DEFAULT
			$stime = mktime(0,0,0);
			$etime = $stime + 86399;
		}
		
		
		$out .= date("g:i:sa m/d/Y")." - Calculating hours for activity on '".date("m/d/Y", $stime)."'\n";
		
		
		// LOAD THE RULES DATABASE
		$all_rules = $this->loadRules();
		
		/**
		 * Schedule Array
		[ Array (Company ID) ][ Array (Office ID) ] [0-x array of records]
		
		$all_schedules[0][0] = default global schedules (Group=NULL are globals)
		**/
		$all_schedules = $this->loadSchedules();
		
		//print_r($all_schedules);
		
		// GET STACK OF COMPANYS (and there offices) THAT HAVE AUTO CALCULATE ENABLED
		$companies = $_SESSION['dbapi']->fetchAllAssoc("SELECT * FROM `companies` WHERE `status`='enabled' AND `opt_auto_calc_hours` IN ('yes','debug-only') ");

		$out .= date("g:i:sa m/d/Y")." - Loading Rules (".count($all_rules).") and Companies (".count($companies).")\n";
		
		
		
		$rules = array();
		foreach($companies as $comp){
			
			$compname = '#'.$comp['id'].' '.$comp['name'];
			
			
			// (COMPANY SPECIFIC, OFFICE GLOBAL) DEFAULT
			if(count($all_schedules[$comp['id']][0]) > 0){
				
				$cur_schedules = $all_schedules[$comp['id']][0];
				
			// (COMPANY AND OFFICE GLOBAL) DEFAULT
			}else{
				
				$cur_schedules = $all_schedules[0][0];
			}
			
			
			
			// FOR EACH COMPANY, GRAB ALL EMPLOYEE HOURS RECORDS FOR THEIR OFFICES.
			$offices = $_SESSION['dbapi']->fetchAllAssoc("SELECT * FROM `offices` WHERE `enabled`='yes' AND `company_id`='".intval($comp['id'])."' ");
		
			$y=0;
			$ofcstr = '';
			foreach($offices as $ofc){
				
				if($y++ > 0)$ofcstr .= ',';
				
				$ofcstr .= $ofc['id'];
			}
			
			$out .= date("g:i:sa m/d/Y")." - Processing $compname - Offices ($ofcstr)\n";
			
			
			// IF THE COMPANY HAS ITS OWN SPECIFIC RULES, USE THOSE
			if(count($all_rules[$comp['id']]) > 0){
				
				$rules = $all_rules[$comp['id']];
				
			// ELSE USE DEFAULT RULES
			}else{
				$rules = $all_rules[0];
			}
			
			
			$res = $_SESSION['dbapi']->query("SELECT * FROM `activity_log` WHERE `office` IN ($ofcstr) AND `time_started` BETWEEN '$stime' AND '$etime' ORDER BY `time_started` ASC");
			
			$user_activity_arr = array();
			
			$out .= date("g:i:sa m/d/Y")." - Located ".mysqli_num_rows($res)." matching activity records.\n";
			
			$users_break_time  = 0;
			while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
				
			//	print_r($row);
				// (COMPANY SPECIFIC, OFFICE SPECIFIC) DEFAULT
				if(isset($all_schedules[$comp['id']][$row['office']]) && count($all_schedules[$comp['id']][$row['office']]) > 0){
					
					$schedules = $all_schedules[$comp['id']][$row['office']];
					
				// (DECIDED ABOVE) DEFAULT
				}else{
					
					$schedules = $cur_schedules;
					
				}
			
				$schedule = $this->selectCorrectSchedule($schedules, $stime, $row['call_group']);
				
				

				
				
				//echo "SELECTED SCHEDULE:";
				//print_r($schedule);exit;
				
				$base_username = trim(strtoupper($row['username']));
				
				$second_hand_mode = false;
				if($base_username[strlen($base_username)-1] == '2'){
					
					$base_username = substr($base_username,0, strlen($base_username)-1);
					$second_hand_mode = true;
				}
				
				
				
				/**
				 * CHECK THE ACTIVITY START TIME, VS THE SCHEDULED START TIME, TO DETERMINE IF LATE
				 */
				// GENERATE A TIMESTAMP FOR THE START OF THE DAY IN QUESTION
				$tsttime = mktime(0,0,0, date("m", $stime), date("d", $stime), date("Y", $stime));
				
				// ADD TIME OFFSET FROM SCHEDULE
				$tsttime+= $schedule['start_time'];
				
				$hand_is_late = false;
				
				if($row['time_started'] > ($tsttime + $this->shift_flex_time) ){
					
					$out .=  "USER IS LATE: $base_username started(".date("H:i:s", $row['time_started']).") vs scheduled(".date("H:i:s", ($tsttime + $this->shift_flex_time)).")\n";
					$hand_is_late = true;
				}else{
					//$out .=  "USER ON TIME: $base_username started(".date("H:i:s", $row['time_started']).") vs scheduled(".date("H:i:s", ($tsttime + $this->shift_flex_time)).")\n";
				}
				
				
				
				
				//echo "BASE USERNAME: $base_username \n"; /// + $row['seconds_DISPO'] 
				
				// CONVERT TO HOURS (IN CALL + READY + QUEUE TIME, NO PAUSE or DISPO time)
				//$activity_time = $paid_hrs = round( ($row['seconds_INCALL'] + $row['seconds_READY'] + $row['seconds_QUEUE']) / 3600, 3);
				
				// USE THE "DETECTED (OLD)" ACTIVITY TRACKER INSTEAD
				$activity_time = $paid_hrs = round( ($row['activity_time'] / 60), 3); 
				
				
				// SEE IF THE USER EXISTS ALREADY
				// IF SO, PICK THE LARGER OF THE ACTIVITY TIMES TO USE, AND PUSH ACTIVITY TO EXISTING STACK
				if(array_key_exists($base_username, $user_activity_arr)){
					
					if($activity_time > $user_activity_arr[$base_username]['activity_time']){
						$user_activity_arr[$base_username]['activity_time'] = $activity_time;
					}
					
					$user_activity_arr[$base_username]['activity'][] = $row;
				
					$user_activity_arr[$base_username]['num_hands'] = count($user_activity_arr[$base_username]['activity']);
					
					$user_activity_arr[$base_username]['num_hands_late'] += (($hand_is_late)?1:0);
					
					if(!$second_hand_mode){
						$user_activity_arr[$base_username]['main_user_activity_id'] = $row['id'];
					}
					
					
				// IF NOT FOUND, CREATE THE USERS BASE ACTIVITY STACK 
				}else{
					$user_activity_arr[$base_username] = array(
					
						'user'				=> $base_username,
						'main_user_activity_id' => $row['id'],
						'activity_time'		=> $activity_time,
						'activity'			=> array($row),
						'schedule'			=> $schedule,
						'num_hands'			=> 1,
						'num_hands_late'	=> ($hand_is_late)?1:0
					);
				}

			}
			
			
			foreach($user_activity_arr as $base_username=>$row){
				
				// CALCULATE THE EMPLOYEES PAID TIME, USING THE RULES
				$activity_time = $paid_hrs = $row['activity_time'];
				
				$users_break_time = 0;
				
				$rule_breaker = false;
				
				$schedule = $row['schedule'];
				
				/**
				 * SCHEDULE - DETECT IF THE USER STARTED LATE, COMPARED TO THE SCHEDULE
				 */
				$is_user_late = ($row['num_hands_late'] == $row['num_hands'])?'yes':'no';
				
				// IF THE USER IS ABOUT TO BE MARKED AS LATE, PRE-SET TEH CURRENT DATE TO LATE TOO, SO IT CALCULATES RIGHT
				if($row['activity'][0]['has_set_late'] == 'no'){
	
					$row['activity'][0]['is_late'] = $is_user_late;
					
				}
						
						

				

				
				
				$applicable_rules = array();
				
				// PRE-SCAN AND FILTER RULES
				foreach($rules as $rule){
	
					if( ($rule['late_rule'] == 'yes' && $row['activity'][0]['is_late'] != 'yes') ||
						($rule['late_rule'] == 'no' && $row['activity'][0]['is_late'] == 'yes')
					){ continue;}
					
					/**
					 * SCHEDULE - DETECT WHICH SET OF RULES TO USE, BASED ON SCHEDULE
					 */
					if($schedule['id'] && $rule['schedule_id'] != $schedule['id']){
						continue;
					}else if(!$schedule['id'] && $rule['schedule_id'] != 0){
						continue;
					}

					$applicable_rules[] = $rule;
				}
				
				// SECOND PASS, FOR GLOBAL SCHEDULE RULES
				if(count($applicable_rules) <= 0){
					
					$applicable_rules = array();
					
					// SECOND PRE-SCAN AND FILTER OF RULES
					foreach($rules as $rule){
						
						if( ($rule['late_rule'] == 'yes' && $row['activity'][0]['is_late'] != 'yes') ||
							($rule['late_rule'] == 'no' && $row['activity'][0]['is_late'] == 'yes')
						){ continue;}
						
						if($rule['schedule_id'] != 0){
							continue;
						}
						
						$applicable_rules[] = $rule;
					}
				}
				
				
				echo "Agent ".$row['user']." - Applicable rules: ".	print_r($applicable_rules, 1)."\n";
				

				
			//	echo "Schedule: ".print_r($row,1)."\n";
				
				foreach($applicable_rules as $rule){
					
					if($rule_breaker)break;
					
// 					if( ($rule['late_rule'] == 'yes' && $row['activity'][0]['is_late'] != 'yes') ||
// 						($rule['late_rule'] == 'no' && $row['activity'][0]['is_late'] == 'yes')
// 					){ continue;}

					
					
					
					switch($rule['rule_type']){
					default:
						break;
					
					case 'hours':
						
						if($rule['trigger_name'] == 'greater_than' || $rule['trigger_name'] == 'greater_equal'){
							
							$triggered = false;
							
							if($rule['trigger_name'] == 'greater_equal'){
								if($activity_time >= $rule['trigger_value']){
									$triggered = true;
								}
							}else{
								if($activity_time > $rule['trigger_value']){
									$triggered = true;
								}
							}
							// TRIGGERED RULE
							if($triggered){ //$activity_time > $rule['trigger_value']){
								
								switch($rule['action']){
								default:
									break;
								case 'paid_break':
								case 'paid_lunch':
									
									$users_break_time  += $rule['action_value'];
								
									
									break;

									
									
								case 'set_hours':
									
									$users_break_time = 0;
									$paid_hrs = $rule['action_value'];
									
									// KICK OUT OF THE RULES AT THIS POINT (skip any rules that follow)
									$rule_breaker = true;
									
									break;
								}
								
								
							}
							
							
						}
						
						
						break;
					}
				
				} // END FOREACH(RULES)
				
				
				$paid_hrs += $users_break_time;
				
				
				
				
				// UPDATE THE EMPLOYEES ACTIVITY LOG RECORD
				if($activity_time != $paid_hrs){

					// UPPER CAP HIT                
					if($rule_breaker){
						
						$out .= date("g:i:sa m/d/Y")." ".$compname." - User $base_username (".$row['activity'][0]['call_group'].")".(($row['num_hands'] > 1)?'(Hands: '.$row['num_hands'].')':'').": Updating Paid Time to $paid_hrs\t\t($activity_time Capped)\n";
						
					}else{
						$out .= date("g:i:sa m/d/Y")." ".$compname." - User $base_username (".$row['activity'][0]['call_group'].")".(($row['num_hands'] > 1)?'(Hands: '.$row['num_hands'].')':'').": Updating Paid Time to $paid_hrs\t\t($activity_time + Breaks: ".$users_break_time.")\n";
					}
				}else{
					
					$out .= date("g:i:sa m/d/Y")." ".$compname." - User $base_username (".$row['activity'][0]['call_group'].")".(($row['num_hands'] > 1)?'(Hands: '.$row['num_hands'].')':'').": Updating Paid Time to $paid_hrs\n";
				}

				
				
				
				
				$extrasql = '';
				if($row['activity'][0]['has_set_late'] == 'no'){
				
					$extrasql .= ",is_late='".$is_user_late."',has_set_late='yes-$is_user_late' ";

				}
				
				$sql = "UPDATE `activity_log` SET `paid_time`='".(($paid_hrs * 60))."', `paid_break_time`='".($users_break_time * 60)."' $extrasql WHERE `id`='".$row['main_user_activity_id']."' ";
				
				
				
				if($comp['opt_auto_calc_hours'] == 'yes'){
				
					//echo $sql."\n";
					// UPDATE ACTIVITY LOG RECORD FOR MAIN USER 
					$_SESSION['dbapi']->execSQL($sql);
					
				}else{
					echo "Company (#".$comp['id'].' - '.$comp['name'].") in Debug only mode, no changes made.\n";
					
				}

				
				
				
				//echo "TEETH DISABLED - No changes made\n";
				
				
				
			}
			
			
			
			
			
		}
		
		
		
		
		return $out;
	} // END Function autoCalcEmployeeHours()
	
	
	function autoCalculateEmployeeByActivity($activity_id){
		
		// LOAD USER ACTIVITY RECORD
		$activity = $_SESSION['dbapi']->querySQL("SELECT * FROM `activity_log` WHERE id='".intval($activity_id)."'");
		
		
		return $this->autoCalculateEmployee($activity['time_started'], $activity['username']);
		
	}
	
	
	function autoCalculateEmployee($time, $username, $rules = null, $schedule = null){
		
		$stime = $time;
		$etime = $stime + 86399;
		
		
		$out = '';
		
		$out .= date("g:i:sa m/d/Y")." - Calculating hours for USER(".$username.") activity on '".date("m/d/Y", $time)."'\n";
		
		
		// LOAD USER ACTIVITY RECORD
		$activity = $_SESSION['dbapi']->querySQL("SELECT * FROM `activity_log` WHERE `username`='".$username."' AND `time_started` BETWEEN '$stime' AND '$etime' ORDER BY `time_started` ASC");
		
		// IF NOT FOUND, CHECK FOR SECOND HAND
		if(!$activity){
			
			$activity = $_SESSION['dbapi']->querySQL("SELECT * FROM `activity_log` WHERE `username`='".$username."2' AND `time_started` BETWEEN '$stime' AND '$etime' ORDER BY `time_started` ASC");
			
		}
		
		
		if(!$activity){
			$out .= "Activity not found for user $username/".$username."2 on ".date("m/d/Y", $stime)."\n";
			return $out;
		}

		// DETERMINE OFFICE AND PARENT COMPANY
		$office_id = $activity['office'];
		$ofc = $_SESSION['dbapi']->offices->getByID($office_id);
		$company_id = $ofc['company_id'];
		
		$comp = $_SESSION['dbapi']->querySQL("SELECT * FROM companies WHERE `id`='".intval($company_id)."'");
		
		$compname = $comp['name'];

		if($comp['status'] != 'enabled'){
			$out .= "Company #".$company_id." - $compname is DISABLED, calculation skipped.\n";
			return $out;
		}
		
		if($comp['opt_auto_calc_hours'] == 'no'){
			
			$out .= "Company #".$company_id." - $compname AUTO CALC IS DISABLED, calculation skipped.\n";
			return $out;
		}
		
		
		
		
		if(!$schedule){
			/**
			 * Schedule Array
			 [ Array (Company ID) ][ Array (Office ID) ] [0-x array of records]
			 
			 $all_schedules[0][0] = default global schedules (Group=NULL are globals)
			 **/
			$all_schedules = $this->loadSchedules();
		
			
			
			
			if(count($all_schedules[$company_id][0]) > 0){
				
				$cur_schedules = $all_schedules[$company_id][0];
				
				// (COMPANY AND OFFICE GLOBAL) DEFAULT
			}else{
				
				$cur_schedules = $all_schedules[0][0];
			}
			
			
			if(isset($all_schedules[$company_id][$ofc['id']]) && count($all_schedules[$company_id][$ofc['id']]) > 0){
				
				$schedules = $all_schedules[$company_id][$ofc['id']];
				
				// (DECIDED ABOVE) DEFAULT
			}else{
				
				$schedules = $cur_schedules;
				
			}
			
			$schedule = $this->selectCorrectSchedule($schedules, $stime, $activity['call_group']);
			
		}
		
		
		
		
		
		
		
		$base_username = trim(strtoupper($activity['username']));
		
		$second_hand_mode = false;
		if($base_username[strlen($base_username)-1] == '2'){
			
			$base_username = substr($base_username,0, strlen($base_username)-1);
			$second_hand_mode = true;
		}
		
		
		
		/**
		 * CHECK THE ACTIVITY START TIME, VS THE SCHEDULED START TIME, TO DETERMINE IF LATE
		 */
		// GENERATE A TIMESTAMP FOR THE START OF THE DAY IN QUESTION
		$tsttime = mktime(0,0,0, date("m", $stime), date("d", $stime), date("Y", $stime));
		
		// ADD TIME OFFSET FROM SCHEDULE
		$tsttime+= $schedule['start_time'];
		
		$hand_is_late = false;
		
		if($activity['time_started'] > ($tsttime + $this->shift_flex_time) ){
			
			$out .=  "USER IS LATE: $base_username started(".date("H:i:s", $activity['time_started']).") vs scheduled(".date("H:i:s", ($tsttime + $this->shift_flex_time)).")\n";
			$hand_is_late = true;
		}else{
			//$out .=  "USER ON TIME: $base_username started(".date("H:i:s", $row['time_started']).") vs scheduled(".date("H:i:s", ($tsttime + $this->shift_flex_time)).")\n";
		}
		
		
		
		
		//echo "BASE USERNAME: $base_username \n"; /// + $row['seconds_DISPO']
		
		// CONVERT TO HOURS (IN CALL + READY + QUEUE TIME, NO PAUSE or DISPO time)
		//$activity_time = $paid_hrs = round( ($row['seconds_INCALL'] + $row['seconds_READY'] + $row['seconds_QUEUE']) / 3600, 3);
		
		// USE THE "DETECTED (OLD)" ACTIVITY TRACKER INSTEAD
		$activity_time = $paid_hrs = round( ($activity['activity_time'] / 60), 3);
		
		

		$row = array(
					
					'user'				=> $base_username,
					'main_user_activity_id' => $activity['id'],
					'activity_time'		=> $activity_time,
					'activity'			=> array($activity),
					'schedule'			=> $schedule,
					'num_hands'			=> 1,
					'num_hands_late'	=> ($hand_is_late)?1:0
			);
		
		
		$is_user_late = ($row['num_hands_late'] == $row['num_hands'])?'yes':'no';
		
		// IF THE USER IS ABOUT TO BE MARKED AS LATE, PRE-SET TEH CURRENT DATE TO LATE TOO, SO IT CALCULATES RIGHT
		if($activity['has_set_late'] == 'no'){
			
			$activity['is_late'] = $is_user_late;
			
		}

		// IF RULES NOT PASSED, LOAD THEM, AND DETERMINE WHICH ONES TO USE
		if(!$rules){
			// LOAD THE RULES DATABASE
			$all_rules = $this->loadRules();
			
			// IF THE COMPANY HAS ITS OWN SPECIFIC RULES, USE THOSE
			if(count($all_rules[$company_id]) > 0){
				
				$rules = $all_rules[$company_id];
				
				// ELSE USE DEFAULT RULES
			}else{
				$rules = $all_rules[0];
			}
			
			
			
		}
		
		// APPLY RULES

		$applicable_rules = array();
		
		// PRE-SCAN AND FILTER RULES
		foreach($rules as $rule){
			
			if( ($rule['late_rule'] == 'yes' && $activity['is_late'] != 'yes') ||
				($rule['late_rule'] == 'no' && $activity['is_late'] == 'yes')
			){ continue;}
					
			/**
			 * SCHEDULE - DETECT WHICH SET OF RULES TO USE, BASED ON SCHEDULE
			 */
			if($schedule['id'] && $rule['schedule_id'] != $schedule['id']){
				continue;
			}else if(!$schedule['id'] && $rule['schedule_id'] != 0){
				continue;
			}
			
			$applicable_rules[] = $rule;
		}
		
		// SECOND PASS, FOR GLOBAL SCHEDULE RULES
		if(count($applicable_rules) <= 0){
			
			$applicable_rules = array();
			
			// SECOND PRE-SCAN AND FILTER OF RULES
			foreach($rules as $rule){
				
				if( ($rule['late_rule'] == 'yes' && $activity['is_late'] != 'yes') ||
					($rule['late_rule'] == 'no' && $activity['is_late'] == 'yes')
				){ continue;}
				
				if($rule['schedule_id'] != 0){
					continue;
				}
				
				$applicable_rules[] = $rule;
			}
		}
		
		
		//echo "Agent ".$row['user']." - Applicable rules: ".	print_r($applicable_rules, 1)."\n";
		
		
		$rule_breaker = false;
		//	echo "Schedule: ".print_r($row,1)."\n";
		
		foreach($applicable_rules as $rule){
			
			if($rule_breaker)break;
			
			// 					if( ($rule['late_rule'] == 'yes' && $row['activity'][0]['is_late'] != 'yes') ||
			// 						($rule['late_rule'] == 'no' && $row['activity'][0]['is_late'] == 'yes')
			// 					){ continue;}
			
			
			
			
			switch($rule['rule_type']){
				default:
					break;
					
				case 'hours':
					
					if($rule['trigger_name'] == 'greater_than' || $rule['trigger_name'] == 'greater_equal'){
						
						$triggered = false;
						
						if($rule['trigger_name'] == 'greater_equal'){
							if($activity_time >= $rule['trigger_value']){
								$triggered = true;
							}
						}else{
							if($activity_time > $rule['trigger_value']){
								$triggered = true;
							}
						}
						
						// TRIGGERED RULE
						if($triggered){ //$activity_time > $rule['trigger_value']){
							
							switch($rule['action']){
							default:
								break;
							case 'paid_break':
							case 'paid_lunch':
								
								$users_break_time  += $rule['action_value'];
								
								
								break;
								
								
								
							case 'set_hours':
								
								$users_break_time = 0;
								$paid_hrs = $rule['action_value'];
								
								// KICK OUT OF THE RULES AT THIS POINT (skip any rules that follow)
								$rule_breaker = true;
								
								break;
							}
							
							
						}
						
						
					}
					
					
					break;
			}
			
		} // END FOREACH(RULES)
		
		
		$paid_hrs += $users_break_time;
		
		
		
		
		// UPDATE THE EMPLOYEES ACTIVITY LOG RECORD
		if($activity_time != $paid_hrs){
			
			// UPPER CAP HIT
			if($rule_breaker){
				
				$out .= date("g:i:sa m/d/Y")." ".$compname." - User $base_username (".$row['activity'][0]['call_group'].")".(($row['num_hands'] > 1)?'(Hands: '.$row['num_hands'].')':'').": Updating Paid Time to $paid_hrs\t\t($activity_time Capped)\n";
				
			}else{
				$out .= date("g:i:sa m/d/Y")." ".$compname." - User $base_username (".$row['activity'][0]['call_group'].")".(($row['num_hands'] > 1)?'(Hands: '.$row['num_hands'].')':'').": Updating Paid Time to $paid_hrs\t\t($activity_time + Breaks: ".$users_break_time.")\n";
			}
		}else{
			
			$out .= date("g:i:sa m/d/Y")." ".$compname." - User $base_username (".$row['activity'][0]['call_group'].")".(($row['num_hands'] > 1)?'(Hands: '.$row['num_hands'].')':'').": Updating Paid Time to $paid_hrs\n";
		}
		
		
		
		
		
		$extrasql = '';
		if($activity['has_set_late'] == 'no'){
			
			$extrasql .= ",is_late='".$is_user_late."',has_set_late='yes-$is_user_late' ";
			
		}
		
		$sql = "UPDATE `activity_log` SET `paid_time`='".(($paid_hrs * 60))."', `paid_break_time`='".($users_break_time * 60)."' $extrasql WHERE `id`='".$activity['id']."' ";
		
		
		
		if($comp['opt_auto_calc_hours'] == 'yes'){
			
			//$out .=  $sql."\n";
			// UPDATE ACTIVITY LOG RECORD FOR MAIN USER
			$_SESSION['dbapi']->execSQL($sql);
			
		}else{
			$out .=  "Company (#".$comp['id'].' - '.$comp['name'].") in Debug only mode, no changes made.\n";
			
		}
		

		
		
		
		
		
		
		
		
		
		
		
		
		
		
		return $out;
		
	}
	
	
	
	
	
	function updateEmployeeHoursMissedDays($calculate_from_stime = 0){

		$out = '';
		
		// HARDCODED WORKWEEK START - TUEDAYS ARE START OF NEW WEEK
		$start_of_week_offset = 172800;
		
		
		// USE THE TIME THEY PASSED, OR GENERATE TODAYS TIME
		$curtime = ($calculate_from_stime > 0)?$calculate_from_stime : time();
		
		// Filter/ROUND IT OFF TO THE START OF THE DAY
		$curtime = mktime(0,0,0, date("m", $curtime), date("d", $curtime), date("Y", $curtime));
	
		
		

		
		
		// FIGURE OUT WHERE WE ARE IN THE WORK WEEK SO FAR
		
		$diw = date("w", $curtime);
		$curoffset = ($diw * 86400);
		
		
		// GET THE START OF THE WORK WEEK
		$week_start = $curtime - $curoffset;
		
		// USE THE CURRENT WEEKS "start of workweek" time by default (TUESDAY)
		$start_of_workweek = $week_start + $start_of_week_offset;

		// IF THE CURRENT DAY IS BEFORE THE NEW START OF WORKWEEK
		// LOOK BACK TO THE PREVIOUS WEEKS "start of week" time
		if($curoffset < $start_of_week_offset){
			
			// BACK 1 WEEK (LAST TUESDAY)
			$start_of_workweek -= 604800;

		}
		
		
		// BUILD TIMEFRAME FOR THE SEARCH
		$stime = $start_of_workweek;
		$etime = $stime + 604799;
		
	
 		$out .= "Start of week: ".date("m/d/Y", $stime)."\n";
 		$out .= "End of week: ".date("m/d/Y", $etime)."\n";
// 		exit;
		
//  		echo $out;
 		
		$curw = date("W", $stime);
		
		$is_even_week = ($curw % 2 == 0)?true:false;
//  		echo "Current week number: ".$curw;
//  		exit;
 		
		
		// LOAD SCHEDULES (AND RULES?)
		$all_schedules = $this->loadSchedules();
		
		//print_r($all_schedules);
		
		// GET STACK OF COMPANYS (and there offices) THAT HAVE AUTO CALCULATE ENABLED
		$companies = $_SESSION['dbapi']->fetchAllAssoc("SELECT * FROM `companies` WHERE `status`='enabled' AND `opt_auto_calc_hours` IN ('yes','debug-only') ");
		
		foreach($companies as $comp){
			
			$compname = '#'.$comp['id'].' '.$comp['name'];
			
			
			// (COMPANY SPECIFIC, OFFICE GLOBAL) DEFAULT
			if(count($all_schedules[$comp['id']][0]) > 0){
				
				$cur_schedules = $all_schedules[$comp['id']][0];
				
				// (COMPANY AND OFFICE GLOBAL) DEFAULT
			}else{
				
				$cur_schedules = $all_schedules[0][0];
			}
			
			
			
			// FOR EACH COMPANY, GRAB ALL EMPLOYEE HOURS RECORDS FOR THEIR OFFICES.
			$offices = $_SESSION['dbapi']->fetchAllAssoc("SELECT * FROM `offices` WHERE `enabled`='yes' AND `company_id`='".intval($comp['id'])."' ");
			
			$y=0;
			$ofcstr = '';
			foreach($offices as $ofc){
				
				if($y++ > 0)$ofcstr .= ',';
				
				$ofcstr .= $ofc['id'];
			}
			
			$out .= date("g:i:sa m/d/Y")." - Processing $compname - Offices ($ofcstr)\n";
			
			

			
			

			

			
			$absent_user_stack = array();
			
			$realtime = mktime(0,0,0); // DONT CHECK TODAY OR FURTHER
			
			// GO THOUGH EACH DAY OF THE TIMEFRAME
			for($x = $stime;$x < $etime;$x+= 86400){
				
				$has_processed_user = array();
				
				if($x >= $realtime){
					
					$out .= "Today reached, stopping processing.\n";
					
					break;
				}
				
				$out .= "Processing Date ".date("m/d/Y", $x)."\n";
				
				foreach($offices as $ofc){
				
					if(isset($all_schedules[$comp['id']][$ofc['id']]) && count($all_schedules[$comp['id']][$ofc['id']]) > 0){
						
						$schedules = $all_schedules[$comp['id']][$ofc['id']];
						
						// (DECIDED ABOVE) DEFAULT
					}else{
						
						$schedules = $cur_schedules;
						
					}
					
					
					
					
					$userstack = $_SESSION['dbapi']->fetchAllAssoc("SELECT DISTINCT(username),call_group FROM `activity_log` WHERE `office`='".$ofc['id']."' AND `time_started` BETWEEN '$stime' AND '$etime'");
					
					$out .= date("g:i:sa m/d/Y")." - Located ".count($userstack)." unique users for $compname.\n";
					//$groupstack = $_SESSION['dbapi']->fetchAllAssoc("SELECT DISTINCT(call_group) FROM `activity_log` WHERE `office`='$ofc' AND `time_started` BETWEEN '$stime' AND '$etime'");
					
					
					
					
					// GO THRU EACH USER ON THE STACK, AND LOOK FOR THERE ACTIVITY
					foreach($userstack as $udat){
						
						$username = $udat['username'];
						$call_group = $udat['call_group'];
						
						$base_username = trim(strtoupper($username));
						
						$second_hand_mode = false;
						if($base_username[strlen($base_username)-1] == '2'){
							
							$base_username = substr($base_username,0, strlen($base_username)-1);
							$second_hand_mode = true;
						}
						
						if(array_key_exists($base_username, $has_processed_user)){
							$out .= $base_username." - Username (other hand) already processed.\n";
							continue;
						}
						
						
						
						if(array_key_exists($base_username, $absent_user_stack) && $absent_user_stack[$base_username] > 0){
							
							// ALREADY MARKED ABSENT FOR THE WEEK, NO NEED TO KEEP CHECKING
							$out .= $base_username." - Username already marked absent, skipping ".date("l",$x)." processed.\n";
							continue;
							
						}
						
						
						$schedule = $this->selectCorrectSchedule($schedules, $x, $call_group);
						
						
						// CHECK IF SUPPOSED TO BE WORKING TODAY FIRST
						$curdiw = date("w", $x);
						
						if($schedule[$this->week_field_arr[$curdiw]] != 'yes' ||
							
								($schedule['schedule_mode'] == 'alternating-even' && !$is_even_week) ||
								
								($schedule['schedule_mode'] == 'alternating-odd' && $is_even_week)
								
							){
								$out .= "User $username ($call_group OFC ".$ofc['id'].") not scheduled to work on ".date("l",$x). (($schedule[$this->week_field_arr[$curdiw]] == 'yes')?"(Schedule : ".$schedule['schedule_mode'].')':''). "\n";
							continue;
						}
						
						
						$out .= $compname.' - '.$base_username." ($call_group) - Checking (".date("m/d/Y", $x).") if they worked ...";
						
						
						$activity = $_SESSION['dbapi']->querySQL(
								"SELECT * FROM `activity_log` ".
								" WHERE `username` IN ('".mysqli_real_escape_string($_SESSION['dbapi']->db, $base_username)."', '".mysqli_real_escape_string($_SESSION['dbapi']->db, $base_username.'2')."') ".
								" AND `time_started` BETWEEN '$x' AND '".($x + 86399)."' ".
								" ORDER BY `time_started` ASC");
						
						
						/// DETECT IF THEY MISSED THE DAY, MARK THEM ABSENT FOR THAT DAY
						if(!$activity || ($activity['paid_time']+$activity['paid_corrections']) <= 0){
							
							// CREATE A RECORD JUST TO MARK THEM ABSENT?? 
						
							$out .= "NO, Adding to absent list.\n";
							
							/// MAKE A LIST OF THE ABSENT PEOPLE
							// PUSH USER TO THE ABSENT STACK
							$absent_user_stack[$base_username] = $x;
						}else{
							
							$out .= "Yes.\n";
						}
						
// 						else if($activity['activity_time'] <= 0){
							
// 							// MARK RECORD 
							
// 							$_SESSION['dbapi']->execSQL("UPDATE `activity_log` SET `absent`='yes' WHERE id='".$activity['id']."' ");
// 						}
						
						
						
					} // END FOREACH USERSTACK
				
				} // END FOREACH OFFICES
				
			} // END WEEK TIMEFRAME LOOP
			
			
			$out .= "\n\n";
			
			// LOOP THROUGH THE ABSENT LIST, AND LOOP THROUGH WORKWEEK AGAIN, MARKING THEM ALL "LATE" FOR THE WORK WEEK SO FAR. 
			foreach($absent_user_stack as $username => $time_first_absent){
				
				$out .= "Marking User ($username) LATE for the workweek of ".date("m/d/Y", $stime)." thru ".date("m/d/Y", $etime)." (First absence: ".date("m/d/Y", $time_first_absent).")\n";
				
				//$_SESSION['dbapi']->execSQL(
				$sql =	"UPDATE `activity_log` SET `is_late`='yes', `has_set_late`='yes-yes' ".
						" WHERE `username` IN ('".mysqli_real_escape_string($_SESSION['dbapi']->db, $username)."', '".mysqli_real_escape_string($_SESSION['dbapi']->db, $username.'2')."') ".
						" AND time_started BETWEEN '$stime' AND '$etime' ".
						"";
				$_SESSION['dbapi']->execSQL($sql);
				//echo $sql."\n\n";
				//		);
			
				
				// THEN RECALCULATE 
				$realtime = mktime(0,0,0);
				
				// GO THOUGH EACH DAY OF THE TIMEFRAME
				for($x = $stime;$x < $etime;$x+= 86400){
					
					if($x >= $realtime){
						
						$out .= "Today reached, stopping processing.\n";
						
						break;
					}
					
					
					// RECALCULATE BREAKS/LUNCHES FOR THE TIME PERIOD, BASED ON NEW LATE SETTINGS
					$out .= $this->autoCalculateEmployee($x, $username);
					
					
				}
				
			}
			
			//exit;
			
				
				
				
				
				
				
				
				
		} // END COMPANY LOOP
	
		return $out;
	}
	
	
	
	
	
	
	
	
	
	
	
	
}
