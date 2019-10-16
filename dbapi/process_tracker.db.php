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



	/**
	 * Marks a campaign as deleted
	 */
	function delete($id){

		return false;
		//$_SESSION['dbapi']->adelete($id,$this->table);
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
