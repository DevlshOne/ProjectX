<?
/**
 * Login Tracker SQL Functions
 */



class LoginTrackerAPI{

	var $table = "logins";


	/**
	 * Get a Login by ID
	 * 	@param 	$id		The database ID of the record
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



		### USERNAME SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['username'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['username'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`username` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE USERNAME SEARCH
		}else if($info['username']){

			$sql .= " AND `username` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['username'])."%' ";

		}

		### RESULT SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['result'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['result'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`result` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE RESULT SEARCH
		}else if($info['result']){

			$sql .= " AND `result` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['result'])."%' ";

		}	
		
		### SECTION SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['section'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['section'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`section` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE SECTION SEARCH
		}else if($info['section']){

			$sql .= " AND `section` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['section'])."%' ";

		}
		
		### IP SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['ip'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['ip'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`ip` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE SECTION SEARCH
		}else if($info['ip']){

			$sql .= " AND `ip` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['ip'])."%' ";

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
