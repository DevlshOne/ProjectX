<?
/**
 * List Tool - Imports SQL Functions
 */



class ImportsAPI{

	var $table = "imports";



	/**
	 * Marks a campaign as deleted
	 */
//	function delete($id){
//
//		return $_SESSION['dbapi']->adelete($id,$this->table);
//	}


	/**
	 * Get a Name by ID
	 * @param 	$id		The database ID of the record
	 * 	 * @return	assoc-array of the database record
	 */
	function getByID($id){

		connectListDB();


		$id = intval($id);

		return querySQL("SELECT * FROM `".$this->table."` ".
						" WHERE id='".$id."' "

					);
	}


	function getName($id){

		connectListDB();


		$id=intval($id);
		list($name) = queryROW("SELECT name FROM `".$this->table."` ".
						" WHERE id='".$id."' ");
		return $name;
	}


	function getImportDate($id){

		connectListDB();


		$id=intval($id);
		list($time) = queryROW("SELECT `time` FROM `".$this->table."` ".
						" WHERE id='".$id."' ");

		return date("m-d-Y", $time);
	}

	function recountLeads($import_id){

		connectListDB();

		execSQL("UPDATE `imports` SET current_lead_count=( SELECT COUNT(`phone`) FROM `leads` WHERE `import_id`='$import_id') WHERE id='$import_id'");

		// DOING IT THIS WAY INSTEAD, TO ALSO RETURN THE COUNT TO RENDER
//		list($cnt) = queryROW("SELECT COUNT(`phone`) FROM `leads` WHERE `import_id`='$import_id'");
//
//		execSQL("UPDATE `imports` SET current_lead_count='$cnt' WHERE id='$import_id'");
//
//		return $cnt;
		//current_lead_count
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


		connectListDB();



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



		if(is_array($info['status'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['name'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`status`='".mysqli_real_escape_string($_SESSION['db'],$n)."' ";
			}

			$sql .= ") ";

		## SINGLE NAME SEARCH
		}else if($info['status']){

			$sql .= " AND `status`='".mysqli_real_escape_string($_SESSION['db'],$info['status'])."' ";

		}










	### NAME SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['name'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['name'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`name` LIKE '%".mysqli_real_escape_string($_SESSION['db'],$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE NAME SEARCH
		}else if($info['name']){

			$sql .= " AND `name` LIKE '%".mysqli_real_escape_string($_SESSION['db'],$info['name'])."%' ";

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

				$sql .= "`$k` ".mysqli_real_escape_string($_SESSION['db'],$v)." ";
			}

		}

		if(is_array($info['limit'])){

			$sql .= " LIMIT ".
						(($info['limit']['offset'])?$info['limit']['offset'].",":'').
						$info['limit']['count'];

		}


		#echo $sql;

		//connectListDB();

		## RETURN RESULT SET
		return query($sql,1);
	}



	function getLeadCount($import_id){

		connectListDB();


		list($cnt) = queryROW("SELECT COUNT(`phone`) FROM `leads` WHERE `import_id`='$import_id'");
		return $cnt;
	}

	function getCount(){

		connectListDB();

		$row = mysqli_fetch_row($this->getResults(
						array(
							"fields" => "COUNT(id)"
						)
					));

		return $row[0];
	}


}
