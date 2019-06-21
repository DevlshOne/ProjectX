<?
/**
 * List Tool - Tasks - SQL Functions
 */



class TasksAPI{

	var $table = "tasks";



	/**
	 * Marks a campaign as deleted
	 */
	function delete($id){

//		return $_SESSION['dbapi']->adelete($id,$this->table);
	}


	/**
	 * Get a Name by ID
	 * @param 	$id		The database ID of the record
	 * 	 * @return	assoc-array of the database record
	 */
	function getByID($id){
		$id = intval($id);

		connectListDB();

		return querySQL("SELECT * FROM `".$this->table."` ".
						" WHERE id='".$id."' "

					);
	}

	function cancelTask($id){

		$id = intval($id);
		if($id < 1)return -1;

		connectListDB();


		$task = $this->getByID($id);


		// LOOK FOR TASK GROUPS
		if($task['group_id'] > 0){

			// AND ATTEMPT TO CANCEL THE WHOLE GROUP
			return execSQL("UPDATE `tasks` SET `status`='canceling' WHERE (`id`='$id' OR `group_id`='".$task['group_id']."') AND `status` IN('new','running')");


		}else{


			return execSQL("UPDATE `tasks` SET `status`='canceling' WHERE `id`='$id' AND `status` IN('new','running')");
		}
	}

	function getSource($id){
		$id = intval($id);

		connectListDB();

		return  querySQL("SELECT * FROM `imports` ".
						" WHERE id='".$id."' "

					);


//		return date("m/d/Y",$row['time']).' - '.ucfirst($row['phone_type']).' - '.$row['name'];
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








		if(is_array($info['import_id'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['import_id'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`source_import_id`='".intval($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE ID SEARCH
		}else if($info['import_id']){

			$sql .= " AND `source_import_id`='".intval($info['import_id'])."' ";

		}

		## COMMAND
		if(is_array($info['command'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['command'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`command`='".trim($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE ID SEARCH
		}else if($info['command']){

			$sql .= " AND `command`='".trim($info['command'])."' ";

		}


		if(is_array($info['status'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['status'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`status`='".trim($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE ID SEARCH
		}else if($info['status']){

			$sql .= " AND `status`='".trim($info['status'])."' ";

		}



		if(is_array($info['time'])){

			$sql .= " AND `time_created` BETWEEN '".intval($info['time'][0])."' AND '".intval($info['time'][1])."' ";

		}


	### NAME SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
//		if(is_array($info['name'])){
//
//			$sql .= " AND (";
//
//			$x=0;
//			foreach($info['name'] as $idx=>$n){
//				if($x++ > 0)$sql .= " OR ";
//
//				$sql .= "`name` LIKE '%".mysqli_real_escape_string($_SESSION['db'],$n)."%' ";
//			}
//
//			$sql .= ") ";
//
//		## SINGLE NAME SEARCH
//		}else if($info['name']){
//
//			$sql .= " AND `name` LIKE '%".mysqli_real_escape_string($_SESSION['db'],$info['name'])."%' ";
//
//		}



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


	//	$sql .= " GROUP BY `group_id` ";


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

		connectListDB();

		## RETURN RESULT SET
		$res = query($sql);


		// MIGHT NOT BE NEEDED, DUE TO DBAPI
		//connectPXDB();


		return $res;



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
