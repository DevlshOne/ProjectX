<?
/**
 * Quiz Results SQL Functions
 */



class QuizResultsAPI{

	var $table = "quiz_results";



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


	function getName($id){
		$id=intval($id);
		list($name) = $_SESSION['dbapi']->queryROW("SELECT name FROM `quiz` WHERE id='".$id."' ");
		return $name;
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


		if(is_array($info['time'])){

			$sql .= " AND `time_started` BETWEEN '".intval($info['time'][0])."' AND '".intval($info['time'][1])."' ";

		}



		## QUIZ ID
		if(is_array($info['quiz_id'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['quiz_id'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`quiz_id`='".intval($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE ID SEARCH
		}else if($info['quiz_id']){

			$sql .= " AND `quiz_id`='".intval($info['quiz_id'])."' ";

		}

		## RESPONSE TIME
		if(is_array($info['response_time'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['response_time'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`response_time`='".intval($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE ID SEARCH
		}else if($info['response_time']){

			$sql .= " AND `response_time`='".intval($info['response_time'])."' ";

		}


		## HIDE QUESTION
		if(is_array($info['hide_question'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['hide_question'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`hide_question`='".mysqli_real_escape_string($_SESSION['db'],trim($sid))."' ";
			}

			$sql .= ") ";

		## SINGLE ID SEARCH
		}else if($info['hide_question']){

			$sql .= " AND `hide_question`='".mysqli_real_escape_string($_SESSION['db'],trim($info['hide_question']))."' ";

		}

	### NAME SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['username'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['username'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`username` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE NAME SEARCH
		}else if($info['username']){

			$sql .= " AND `username` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['username'])."%' ";

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
