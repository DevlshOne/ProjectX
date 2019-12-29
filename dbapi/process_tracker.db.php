<?
/**
 * PROCESS TRACKER - SQL Functions
 * 
 * 
 * 
 * $procid = $_SESSION['dbapi']->process_tracker->logStartProcess($process_code, $result='started', $process_command=null, $process_settings=null, $process_logs=null, $time_ended = null)
 * 
 * $_SESSION['dbapi']->process_tracker->logFinishProcess($proc_id, $result, $process_logs)
 * 
 */



class ProcessTrackerAPI{

	var $table = "process_tracker";
	var $schedule_table = "process_tracker_schedules";



	/**
	 * Deletes a Process Tracker Schedule
	 */
	function deleteSchedule($id){
		$_SESSION['dbapi']->adelete($id,$this->schedule_table);
	}


	/**
	 * Get a Name by ID
	 * @param 	$id		The database ID of the record
	 * 	 * @return	assoc-array of the database record
	 */
	function getByID($id){
		$id = intval($id);

		return $_SESSION['dbapi']->querySQL("SELECT * FROM `".$this->table."` ".
						" WHERE id='".$id."' "

					);
	}

	/**
	 * Get a Name by ID
	 * @param 	$id		The database ID of the record
	 * 	 * @return	assoc-array of the database record
	 */
	function getScheduleByID($id){
		$id = intval($id);

		return $_SESSION['dbapi']->querySQL("SELECT * FROM `".$this->schedule_table."` ".
						" WHERE id='".$id."' "

					);
	}


	function getName($id){
		$id=intval($id);
		list($name) = $_SESSION['dbapi']->queryROW("SELECT process_code FROM `".$this->table."` ".
						" WHERE id='".$id."' ");
		return $name;
	}

	function getByCode($name){
		$id=intval($id);
		list($recid) = $_SESSION['dbapi']->queryROW("SELECT id FROM `".$this->table."` ".
						" WHERE process_code LIKE '".mysqli_real_escape_string($_SESSION['dbapi']->db,$name)."' ");
		return $recid;
	}


	/**
	 * getScheduleResults($asso_array)

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
	function getScheduleResults($info){

		$fields = ($info['fields'])?$info['fields']:'*';


		$sql = "SELECT $fields FROM `".$this->schedule_table."` WHERE 1 ";


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



		### ENABLED SEARCH
		if($info['enabled']){

			$sql .= " AND `enabled`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['enabled'])."' ";

		}

		### SCHEDULE NAME SEARCH
		if($info['schedule_name']){

			$sql .= " AND `schedule_name` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['schedule_name'])."%' ";

		}
		
		### SCRIPT PROCESS CODE SEARCH
		if($info['script_process_code']){

			$sql .= " AND `script_process_code`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['script_process_code'])."' ";

		}

		### SCRIPT FREQUENCY SEARCH
		if($info['script_frequency']){

			$sql .= " AND `script_frequency`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['script_frequency'])."' ";

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

		## RETURN RESULT SET
		return $_SESSION['dbapi']->query($sql);

	}

	function getResults($info){

		$fields = ($info['fields'])?$info['fields']:'*';


		$sql = "SELECT $fields FROM `".$this->table."` ";

		
		// TIME SEARCH
		// array(start time, end time)
		if(is_array($info['time'])){

			$sql .=	" WHERE `time_started` BETWEEN '".intval($info['time'][0])."' AND '".intval($info['time'][1])."' ";
			
		}else{
			$sql .= " WHERE 1 ";
		}
		

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



	### PROCESS CODE SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['process_code'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['process_code'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`process_code` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE NAME SEARCH
		}else if($info['process_code']){

			$sql .= " AND `process_code` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['process_code'])."%' ";

		}

		
		
		if(is_array($info['result'])){
			
			$sql .= " AND (";
			
			$x=0;
			foreach($info['result'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";
				
				$sql .= "`result`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."' ";
			}
			
			$sql .= ") ";
			
			## SINGLE NAME SEARCH
		}else if($info['result']){
			
			$sql .= " AND `result`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['result'])."' ";
			
		}
		
		
		
		
		
		
		
		// SEARCH SETTINGS
		if(is_array($info['process_settings'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['process_settings'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`process_settings` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE SETTINGS SEARCH
		}else if($info['process_settings']){

			$sql .= " AND `process_settings` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['process_settings'])."%' ";

		}

		
		
		// SEARCH SETTINGS
		if(is_array($info['process_logs'])){
			
			$sql .= " AND (";
			
			$x=0;
			foreach($info['process_logs'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";
				
				$sql .= "`process_logs` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}
			
			$sql .= ") ";
			
			## SINGLE SETTINGS SEARCH
		}else if($info['process_logs']){
			
			$sql .= " AND `process_logs` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['process_logs'])."%' ";
			
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


		#echo $sql;

		## RETURN RESULT SET
		return $_SESSION['dbapi']->query($sql);
	}	



	function getCount(){

		$row = mysqli_fetch_row($this->getResults(
						array(
							"fields" => "COUNT(id)"
						)
					));

		return $row[0];
	}
	
	
	
    function updateScheduleStatus($id,$mode,$time){

        # UPDATE THE LAST SUCCESS OR FIELD FIELD FOR A SCHEDULE
        # MODE = success or fail
        $dat = [];

        # SET FIELD VALUE
        switch($mode){
            case 'success':
                $dat = array(
                    'last_success' 	=> $time,
                );
                break;

            case 'fail':
                $dat = array(
                    'last_failed' 	=> $time,
                );
                break;

        }
        
        # UPDATE RECORD
        $_SESSION['dbapi']->aedit($id,$dat,$this->schedule_table);

    }	
	

	function processCheck($process_code,$process_time_start,$process_time_end) {

        # CHECK FOR COMPLETED PROCESSES BASED ON PROCESS CODE AND TIME START/END
        # RETURN TRUE/FALSE IF COMPLETED PROCESS FOUND

        # BUILD SQL FOR COMPLETED PROCESS CHECK
        $process_check_sql  =   "SELECT id FROM `".$this->table."` WHERE 1";
        $process_check_sql .=   " AND `process_code` = '".$process_code."' AND `time_started` >= '".$process_time_start."' AND `time_ended` <= '".$process_time_end."' AND `result` = 'completed' ";

        # RUN QUERY AGAINST PROCESS TRACKER TABLE AND MATCH WITH SCHEDULE INFO
        $process_check_res = $_SESSION['dbapi']->query($process_check_sql);

        # IF COMPLETED PROCESS FOUND RETURN FALSE
        if(($processcnt=mysqli_num_rows($process_check_res)) > 0){

            return true;
            exit;

        } 

        return false;

	}
	
	
	function sendAlert($failed_check) {

		// INCLUDE PEAR FUNCTIONS
		include_once 'Mail.php';
		include_once 'Mail/mime.php';

		if(is_array($failed_check)) {


			$alert_data 	 = "Schedule Name: ".$failed_check['schedule_name']."\n";
			$alert_data 	.= "Script Process Code: ".$failed_check['script_process_code']."\n";
			$alert_data 	.= "Script Frequency: ".$failed_check['script_frequency']."\n";
			$alert_data 	.= "Time Start: ".$failed_check['time_start']."\n";
			$alert_data 	.= "Last Alert: ".date("Y-m-d h:i:sa",$failed_check['last_failed'])."\n";
 

			$alert_subject 	= "Process Check Failed Alert - ".$failed_check['schedule_name']." - ".date("Y-m-d h:i:sa",$failed_check['last_failed']);
			
			$alert_headers 	= array(
				"From"		=> "dbrummer <dbrummer@localhost.localdomain>",
				"Subject"	=> $alert_subject
			);

			$mime = new Mail_mime(array('eol' => "\n"));

			$mime->setTXTBody($alert_data, false);

			$mail_body = $mime->get();
			$mail_header=$mime->headers($alert_headers);
		
			$mail =& Mail::factory('mail');

			if ($mail->send($failed_check['notification_email'], $mail_header, $mail_body) != true) {
				echo date("Y-m-d h:i:sa")." - ERROR: Mail::send() call failed sending to ".$failed_check['notification_email'];
		
			}else{
				
				echo date("Y-m-d h:i:sa")." - Successfully emailed ".$failed_check['notification_email']." - ".$alert_subject."\n";
		
			}


		}

	}
	
	
	
	function logStartProcess($process_code, $result='started', $process_command=null, $process_settings=null, $process_logs=null, $time_ended = null){
		
		$process_code = trim($process_code);
		$process_code = filterName($process_code, 32);
		
		
		
		
		$dat = array(
				
				'time_started' => time(),
				'process_code' 		=> $process_code,
				'result'			=> $result,
			
		);
		
		if($time_ended != null){
			
			$dat['time_ended'] = $time_ended;
			$dat['result'] = ($result == 'started')?'completed':$result;
			
		}
		
		if($process_command != null){
			$dat['process_command']	= $process_command;
		}
	
		if($process_settings != null){
			$dat['process_settings']	= $process_settings;
		}
		
		if($process_logs != null){
			$dat['process_logs']	= $process_logs;
		}
		
		
		if($_SESSION['dbapi']->aadd($dat, $this->table)){
			
			
			return mysqli_insert_id($_SESSION['dbapi']->db);
		}
		
		
	}
	
	
	
	
	function logFinishProcess($proc_id, $result, $process_logs = ''){
		
		$dat = array(
				'time_ended' 	=> time(),
				'result'		=> $result,
				'process_logs'	=> $process_logs
				);
		
		return $_SESSION['dbapi']->aedit($proc_id, $dat, $this->table);
		
		
				
	}
	
	
	
	
	
	
	
	


}
