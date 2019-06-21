<?
/**
 * Ringing Calls Report SQL Functions
 */



class RingingCallsAPI{

	var $table = "ringing_calls";



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

	## LEAD ID FIELD SEARCH
		## ARRAY OF id's SEARCH
		if(is_array($info['lead_id'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['lead_id'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`lead_id`='".intval($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE LEAD ID SEARCH
		}else if($info['lead_id']){

			$sql .= " AND `lead_id`='".intval($info['lead_id'])."' ";

		}

	### STATUS SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
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

	### PHONE SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['phone_number'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['phone_number'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`phone_number` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE PHONE SEARCH
		}else if($info['phone_number']){

			$sql .= " AND `phone_number` LIKE '".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['phone_number'])."' ";

		}

	## CARRIER PREFIX FIELD SEARCH
		## ARRAY OF id's SEARCH
		if(is_array($info['carrier_prefix'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['carrier_prefix'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`carrier_prefix`='".intval($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE CARRIER SEARCH
		}else if($info['carrier_prefix']){

			$sql .= " AND `carrier_prefix`='".intval($info['carrier_prefix'])."' ";

		}



	### Cluster SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['cluster_id'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['cluster_id'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`cluster_id`='".intval($n)."' ";
			}

			$sql .= ") ";

		## SINGLE CLUSTER SEARCH
		}else if($info['cluster_id']){

			$sql .= " AND `cluster_id`='".intval($info['cluster_id'])."' ";

		}

	### UNIQUE ID SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['uniqueid'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['uniqueid'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`uniqueid`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."' ";
			}

			$sql .= ") ";

		## SINGLE UNIQUE ID SEARCH
		}else if($info['uniqueid']){

			$sql .= " AND `uniqueid`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['uniqueid'])."' ";

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


		##echo $sql;

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
