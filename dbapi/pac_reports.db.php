<?
/**
 * PAC SQL Functions
 */



class PACReportsAPI{

	var $table = "pac_reports";



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
		list($name) = $_SESSION['dbapi']->queryROW("SELECT name FROM `".$this->table."` ".
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


	## AMOUNT FIELD SEARCH
		## ARRAY OF amounts's SEARCH
		if(is_array($info['amount'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['amount'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`amount`='".intval($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE ID SEARCH
		}else if($info['amount']){

			$sql .= " AND `amount`='".intval($info['amount'])."' ";

		}



	### PROJECT SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['project'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['project'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`project` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE NAME SEARCH
		}else if($info['project']){

			$sql .= " AND `project` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['project'])."%' ";

		}



	### PHONE NUMBER SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['phone'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['phone'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`phone` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE NAME SEARCH
		}else if($info['phone']){

			$sql .= " AND `phone` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['phone'])."%' ";

		}






		### PAYMENT GATEWAY SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['payment_gateway'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['payment_gateway'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`payment_gateway` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE NAME SEARCH
		}else if($info['payment_gateway']){

			$sql .= " AND `payment_gateway` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['payment_gateway'])."%' ";

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
