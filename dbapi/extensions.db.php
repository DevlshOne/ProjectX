<?
/**
 * Extensions SQL Functions
 */



class ExtensionsAPI{

	var $table = "extensions";



	/**
	 * Marks a extension as enabled=no (deleted)
	 */
	function delete($id){
		unset($dat);
		$dat['status'] = 'deleted';
		return $_SESSION['dbapi']->aedit($id,$dat,$this->table);
	}


	/**
	 * Get a extension by ID
	 * @param 	$extension_id		The database ID of the record
	 * 	 * @return	assoc-array of the database record
	 */
	function getByID($extension_id){
		$extension_id = intval($extension_id);

		return $_SESSION['dbapi']->ROquerySQL("SELECT * FROM `".$this->table."` ".
						" WHERE id='".$extension_id."' "

					);
	}
	function getExtensionByID($extension_id){
		$extension_id = intval($extension_id);
		
		list($ext) = $_SESSION['dbapi']->ROqueryROW("SELECT number FROM `".$this->table."` ".
				" WHERE id='".$extension_id."' "
				
				);
		return $ext;
	}
	
	function getByServerAndExtension($server_id, $extension){
		
		$server_id = intval($server_id);
		$extension = intval($extension);
		
		return $_SESSION['dbapi']->ROquerySQL("SELECT * FROM `".$this->table."` ".
				" WHERE number='".$extension."' AND server_id='".$server_id."' AND `status`='enabled' "
				
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



		if($info['number']){

			$sql .= " AND `number` LIKE '%".intval($info['number'])."%' ";

		}


		if($info['in_use_by_userid']){

			$sql .= " AND `in_use_by_userid`='".intval($info['in_use_by_userid'])."' ";

		}

		if($info['station_id']){

			$sql .= " AND `station_id`='".intval($info['station_id'])."' ";

		}

		if($info['server_id']){

			$sql .= " AND `server_id`='".intval($info['server_id'])."' ";

		}



	### STATUS FIELD
		if($info['status']){

			$sql .= " AND `status`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['status'])."' ";

		}



		if($info['in_use']){

			$sql .= " AND `in_use`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['in_use'])."' ";

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
							"status"=> "enabled"
						)
					));

		return $row[0];
	}


}
