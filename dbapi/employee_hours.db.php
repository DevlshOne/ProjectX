<?
/**
 * Employee hours mgt tool db functions
 */



class EmployeeHoursAPI{

	var $table = "activity_log";



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


			$sql .= " AND (`paid_time` > activity_time) ";

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


	function loadRules($company_id = 0){
		
		
		$out = array();
		$rowarr = $_SESSION['dbapi']->fetchAllAssoc(
				
			"SELECT * FROM `companies_rules` WHERE 1 ".
			(($company_id > 0)?" AND `company_id`='' ":'').
			" ORDER BY company_id ASC, trigger_value ASC"
		);
	
		foreach($rowarr as $row){
			$out[$row['company_id']][] = $row;
		}
		
		return $out;
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
		
		

		
		// GET STACK OF COMPANYS (and there offices) THAT HAVE AUTO CALCULATE ENABLED
		$companies = $_SESSION['dbapi']->fetchAllAssoc("SELECT * FROM `companies` WHERE `status`='enabled' AND `opt_auto_calc_hours`='yes' ");

		$out .= date("g:i:sa m/d/Y")." - Loading Rules (".count($all_rules).") and Companies (".count($companies).")\n";
		
		
		$ofcstr = '';
		$rules = array();
		foreach($companies as $comp){
			
			$compname = '#'.$comp['id'].' '.$comp['name'];
			
			// FOR EACH COMPANY, GRAB ALL EMPLOYEE HOURS RECORDS FOR THEIR OFFICES.
			$offices = $_SESSION['dbapi']->fetchAllAssoc("SELECT * FROM `offices` WHERE `enabled`='yes' AND `company_id`='".intval($comp['id'])."' ");
		
			$y=0;
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
			
			
			$res = $_SESSION['dbapi']->query("SELECT * FROM `activity_log` WHERE `office` IN ($ofcstr) AND `time_started` BETWEEN '$stime' AND '$etime' ");
			
			
			$out .= date("g:i:sa m/d/Y")." - Located ".mysqli_num_rows($res)." matching activity records.\n";
			$users_break_time  = 0;
			while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
				
				// CALCULATE THE EMPLOYEES PAID TIME, USING THE RULES
				
				// CONVERT TO HOURS (IN CALL + READY + QUEUE TIME, NO PAUSE)
				$activity_time = $paid_hrs = round( ($row['seconds_INCALL'] + $row['seconds_READY'] + $row['seconds_QUEUE']) / 3600, 3);
				
				$users_break_time = 0;
				
				foreach($rules as $rule){
					
					switch($rule['rule_type']){
					default:
						break;
					
					case 'hours':
						
						if($rule['trigger_name'] == 'greater_than'){
							
							// TRIGGERED RULE
							if($activity_time > $rule['trigger_value']){
								
								switch($rule['action']){
								default:
									break;
								case 'paid_break':
								case 'paid_lunch':
									
									$users_break_time  += $rule['action_value'];
								
									
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
					$out .= date("g:i:sa m/d/Y")." ".$compname." - User ".$row['username']." : Updating Paid Time to $paid_hrs ($activity_time + Breaks: ".$users_break_time.")\n";
				}else{
					
					$out .= date("g:i:sa m/d/Y")." ".$compname." - User ".$row['username']." : Updating Paid Time to $paid_hrs\n";
				}
				
				
				
			}
			
			
			
			
			
		}
		
		
		
		
		return $out;
	} // END Function autoCalcEmployeeHours()
}
