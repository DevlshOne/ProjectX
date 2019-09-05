<?
/**
 * Activity Logs SQL Functions
 */



class ActionLogAPI{

	var $table = "action_log";




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





		// TIME SEARCH
		// array(start time, end time)

		if(is_array($info['time'])){

			$sql .= " AND `time` BETWEEN '".intval($info['time'][0])."' AND '".intval($info['time'][1])."' ";

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


		## USER ID SEARCH
		if(is_array($info['user_id'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['user_id'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`user_id`='".intval($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE ID SEARCH
		}else if($info['user_id']){

			$sql .= " AND `user_id`='".intval($info['user_id'])."' ";

		}




		if(is_array($info['record_id'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['record_id'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`record_id`='".intval($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE ID SEARCH
		}else if($info['record_id']){

			$sql .= " AND `record_id`='".intval($info['record_id'])."' ";

		}








	### NAME SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['user'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['user'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`user` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE NAME SEARCH
		}else if($info['user']){

			$sql .= " AND `user` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['user'])."%' ";

		}


	## ACTION
		if(is_array($info['action'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['action'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`action` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE NAME SEARCH
		}else if($info['action']){

			$sql .= " AND `action` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['action'])."%' ";

		}

	## AREA
		if(is_array($info['area'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['area'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`area` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE NAME SEARCH
		}else if($info['area']){

			$sql .= " AND `area` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['area'])."%' ";

		}


		if(is_array($info['description'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['description'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`description` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE NAME SEARCH
		}else if($info['description']){

			$sql .= " AND `description` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['description'])."%' ";

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


}
