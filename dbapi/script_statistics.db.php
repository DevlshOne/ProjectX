<?
/**
 * Ringing Calls Report SQL Functions
 */



class ScriptStatisticsAPI{

	var $table = "scripts";



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





	## CAMPAIGN ID
		if(is_array($info['campaign_id'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['campaign_id'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`campaign_id`='".intval($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE CAMPAIGN ID SEARCH
		}else if($info['campaign_id']){

			$sql .= " AND `campaign_id`='".intval($info['campaign_id'])."' ";

		}


	## VOICE ID
		if(is_array($info['voice_id'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['voice_id'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`voice_id`='".intval($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE CAMPAIGN ID SEARCH
		}else if($info['voice_id']){

			$sql .= " AND `voice_id`='".intval($info['voice_id'])."' ";

		}


		### FIRSTNAME
		if(is_array($info['name'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['name'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`name` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE PHONE SEARCH
		}else if($info['name']){

			$sql .= " AND `name` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['name'])."%' ";

		}



		if(is_array($info['key'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['key'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`keys` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE PHONE SEARCH
		}else if($info['key']){

			$sql .= " AND `keys` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['key'])."%' ";

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


	//	echo $sql;

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
