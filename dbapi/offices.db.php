<?
/**
 * Offices SQL Functions
 */



class OfficesAPI{

	var $table = "offices";

	function delete($id){
		$_SESSION['dbapi']->adelete($id,$this->table);
	}
	/**
	 * Get a Office by ID
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

		### ENABLED FIELD
		if($info['enabled']){

			$sql .= " AND `enabled`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['enabled'])."' ";

		}		



		### NAME SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['name'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['name'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`name` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE USERNAME SEARCH
		}else if($info['name']){

			$sql .= " AND `name` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['name'])."%' ";

		}

		
## STATUS SEARCH
		if(is_array($info['status'])){
			
			$sql .= " AND (";
			
			$x=0;
			foreach($info['status'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";
				
				$sql .= "`status`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."' ";
			}
			
			$sql .= ") ";
			
			## SINGLE STATUS SEARCH
		}else if($info['status']){
			
			$sql .= " AND `status`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['status'])."' ";
			
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