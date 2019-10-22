<?php

/**
 * Scripts SQL Functions
 */



class ScriptsAPI{

	var $table = "scripts";
	var $voices_files_table = "voices_files";



	/**
	 * DELETES THE RECORD
	 */
	function delete($id){

		return $_SESSION['dbapi']->adelete($id,$this->table);
	}


	/**
	 * Get a script by ID
	 * @param 	$script_id		The database ID of the record
	 * 	 * @return	assoc-array of the database record
	 */
	function getByID($script_id){
		$script_id = intval($script_id);

		return $_SESSION['dbapi']->querySQL("SELECT * FROM `".$this->table."` ".
						" WHERE id='".$script_id."' "

					);
	}

	/**
	 * Get a voice file by ID
	 * @param 	$voice_file_id		The database ID of the record
	 * @return	assoc-array of the database record
	 */
	function getVoiceFileByID($voice_file_id){
		$voice_file_id = intval($voice_file_id);

		return $_SESSION['dbapi']->querySQL("SELECT * FROM `".$this->voices_files_table."` ".
						" WHERE id='".$voice_file_id."' "

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

		if(!$info)$info = array();



		$fields = ($info['fields'])?$info['fields']:"`".$this->table."`.*";


		$file_search = trim($info['filename']);


		if($file_search){

			$sql = "SELECT $fields FROM `".$this->table."` ".
					" LEFT JOIN `voices_files` ON `voices_files`.script_id = `".$this->table."`.id ".
					"WHERE 1 AND `voices_files`.`file` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db, $file_search)."%' ";

		}else{

			$sql = "SELECT $fields FROM `".$this->table."` WHERE 1 ";

		}


	## ID FIELD SEARCH
		## ARRAY OF id's SEARCH
		if(is_array($info['id'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['id'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`".$this->table."`.`id`='".intval($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE ID SEARCH
		}else if($info['id']){

			$sql .= " AND `".$this->table."`.`id`='".intval($info['id'])."' ";

		}


		if(isset($info['screen_num'])){

			$sql .= " AND `".$this->table."`.`screen_num`='".intval($info['screen_num'])."' ";

		}

		if($info['voice_id']){

			$sql .= " AND `".$this->table."`.`voice_id`='".intval($info['voice_id'])."' ";

		}

		if($info['campaign_id']){

			$sql .= " AND `".$this->table."`.`campaign_id`='".intval($info['campaign_id'])."' ";

		}


	### NAME SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['name'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['name'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`".$this->table."`.`name` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE NAME SEARCH
		}else if($info['name']){

			$sql .= " AND `".$this->table."`.`name` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['name'])."%' ";

		}

		if($info['description']){

			$sql .= " AND `".$this->table."`.`description` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['description'])."%' ";

		}


		if($info['variables']){

			$sql .= " AND `".$this->table."`.`variables` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['variables'])."%' ";

		}


		if($info['key']){

			$sql .= " AND `".$this->table."`.`keys` = '".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['key'])."' ";

		}

		if($info['keys']){

			$sql .= " AND `".$this->table."`.`keys` = '".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['keys'])."' ";

		}






	## SKIP/IGNORE ID's
		if(isset($info['skip_id'])){

			$sql .= " AND (";

			if(is_array($info['skip_id'])){
				$x=0;
				foreach($info['skip_id'] as $sid){

					if($x++ > 0)$sql .= " AND ";

					$sql .= "`".$this->table."`.`id` != '".intval($sid)."'";
				}

			}else{
				$sql .= "`".$this->table."`.`id` != '".intval($info['skip_id'])."' ";
			}

			$sql .= ")";
		}


	### ORDER BY
		if(is_array($info['order'])){

			$sql .= " ORDER BY ";
			$x=0;
			foreach($info['order'] as $k=>$v){
				if($x++ > 0)$sql .= ",";

				$sql .= "`".$this->table."`.`$k` ".mysqli_real_escape_string($_SESSION['dbapi']->db,$v)." ";
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
