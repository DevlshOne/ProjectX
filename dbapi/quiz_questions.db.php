<?
/**
 * QUIZ Questions SQL Functions
 */



class QuizQuestionsAPI{

	var $table = "quiz_questions";



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


	function getFilename($id){
		$id=intval($id);
		list($name) = $_SESSION['dbapi']->queryROW("SELECT filename FROM `".$this->table."` ".
						" WHERE id='".$id."' ");
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

	### NAME SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['question'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['question'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`question` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE NAME SEARCH
		}else if($info['question']){

			$sql .= " AND `question` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['question'])."%' ";

		}



		if(is_array($info['answer'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['answer'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`answer` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE NAME SEARCH
		}else if($info['answer']){

			$sql .= " AND `answer` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['answer'])."%' ";

		}

		if(is_array($info['filename'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['filename'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`filename` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE NAME SEARCH
		}else if($info['filename']){

			$sql .= " AND `file` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['filename'])."%' ";

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
