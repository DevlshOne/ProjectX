<?
/**
 * Names SQL Functions
 */



class ProblemsAPI{

	var $table = "lead_tracking";



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



	## PROBLEM SEARCH
		if($info['problem']){

			$sql .= " AND `problem`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['problem'])."' ";

		}else{

			$sql .= " AND `problem`='yes' ";

		}

	## LEAD ID
		if($info['lead_id']){

			$sql .= " AND `lead_id`='".intval($info['lead_id'])."' ";

		}


	## SERVER ID
		if($info['px_server_id']){

			$sql .= " AND `px_server_id`='".intval($info['px_server_id'])."' ";

		}


	## PROBLEM DESCRIPTION
		if($info['problem_description']){

			$sql .= " AND `problem_description` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['problem_description'])."%' ";

		}

	## PROBLEM ACKNOWLEDGED
		if($info['problem_acknowledged']){
				$sql .= " AND `problem_acknowledged`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['problem_acknowledged'])."' ";

		}

	## PROBLEM SOLVED FLAG
		if($info['problem_solved']){
				$sql .= " AND `problem_solved`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['problem_solved'])."' ";

		}else{

			$sql .= " AND `problem_solved`='no' ";

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
