<?
/**
 * Extensions SQL Functions
 */



class FeaturesAPI{

	var $table = "features";



	/**
	 * Marks a extension as enabled=no (deleted)
	 */
	function delete($id){
		unset($dat);
		$dat['status'] = 'disabled';
		return $_SESSION['dbapi']->aedit($id,$dat,$this->table);
	}


	/**
	 * Get a extension by ID
	 * @param 	$extension_id		The database ID of the record
	 * 	 * @return	assoc-array of the database record
	 */
	function getByID($feature_id){
		$feature_id = intval($feature_id);

		return $_SESSION['dbapi']->querySQL("SELECT * FROM `".$this->table."` ".
						" WHERE id='".$feature_id."' "

					);
	}

	/**
	 * Gets the count of users that are assigned this feature set
	 *
	 * @param	$feature_id	The database ID of the feature set to get the count for
	 * @return	integer containing count of users using the feature id
	 */
	function getUsersAssigned($feature_id){

		list($cnt) = $_SESSION['dbapi']->queryROW("SELECT COUNT(id) FROM `users` WHERE feature_id='$feature_id'");

		return $cnt;
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



		if($info['name']){

			$sql .= " AND `name` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['name'])."%' ";

		}




	### STATUS FIELD
		if($info['status']){

			$sql .= " AND `status`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['status'])."' ";

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
							"fields" => "COUNT(id)",
							"status"=> "active"
						)
					));

		return $row[0];
	}


}
