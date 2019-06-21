<?
/**
 * Dispo Log - DB interface
 *
 */



class DispoLogAPI{

	var $table = "dispo_log";



	/**
	 * Marks a campaign as deleted
	 */
	function delete($id){

		return $_SESSION['dbapi']->adelete($id,$this->table);
	}


	/**
	 * Get by ID
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


//print_r($info);


		// DATE SEARCH
		if($info['date_mode'] == 'daterange'){

			## DATE SEARCH
			if($info['date1'] && $info['date2']){

				$stime = intval(strtotime($info['date1'])) * 1000;
				$etime = (intval(strtotime($info['date2'])) + 86399) * 1000;

				$sql .= " AND `micro_time` BETWEEN '$stime' AND '$etime'  ";
			}

		}else{
			## DATE SEARCH
			if($info['date']){

				$stime = intval(strtotime($info['date'])) * 1000;
				$etime = (intval(strtotime($info['date'])) + 86399) * 1000;

				$sql .= " AND `micro_time` BETWEEN '$stime' AND '$etime'  ";
			}
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

	## LEAD ID FIELD SEARCH
		## ARRAY OF id's SEARCH
		if(is_array($info['lead_id'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['lead_id'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`vici_lead_id`='".intval($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE LEAD ID SEARCH
		}else if($info['lead_id']){

			$sql .= " AND `vici_lead_id`='".intval($info['lead_id'])."' ";

		}
	## LEAD TRACKING ID FIELD SEARCH
		## ARRAY OF id's SEARCH
		if(is_array($info['lead_tracking_id'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['lead_tracking_id'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`lead_tracking_id`='".intval($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE LEAD ID SEARCH
		}else if($info['lead_tracking_id']){

			$sql .= " AND `lead_tracking_id`='".intval($info['lead_tracking_id'])."' ";

		}

	### STATUS SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['dispo'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['dispo'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`dispo`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."' ";
			}

			$sql .= ") ";

		## SINGLE STATUS SEARCH
		}else if($info['dispo']){

			$sql .= " AND `dispo`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['dispo'])."' ";

		}


		### AGENT SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['agent_username'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['agent_username'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`agent_username`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."' ";
			}

			$sql .= ") ";

		## SINGLE STATUS SEARCH
		}else if($info['agent_username']){

			$sql .= " AND `agent_username`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['agent_username'])."' ";

		}



		## RESULT SEARCH
		if(is_array($info['result'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['result'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`result`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."' ";
			}

			$sql .= ") ";

			## SINGLE STATUS SEARCH
		}else if($info['result']){

			$sql .= " AND `result`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['result'])."' ";

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


}
