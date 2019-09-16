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


}
