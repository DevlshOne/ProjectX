<?
/**
 * Form Builder SQL Functions
 * Author: Dave Mednick
 * Date: 20190715
 * Task: https://trello.com/c/Hka5kEQf/111-px-form-generation-system-be-able-to-drag-and-drop-fields-into-a-form-area-and-set-the-name-and-field-values-and-have-it-genera
 **/

class FormBuilderAPI{
	var $table = "custom_fields";

	/**
	 * Marks a form as deleted
     * @param integer $id The id of the item to be deleted
     * @return query results status
	 */
	function delete($id){

		return $_SESSION['dbapi']->adelete($id,$this->table);
	}

	/**
	 * Get a Name by ID
	 * @param 	$id		The database ID of the record
	 * @return	assoc-array of the database record
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

	function getByName($name){
		$id = intval($id);
		list($filename) = $_SESSION['dbapi']->queryROW("SELECT filename FROM `".$this->table."` ".
						" WHERE name LIKE '".mysqli_real_escape_string($_SESSION['dbapi']->db,$name)."' ");
		return $filename;
	}

	function getResults($info){
		$fields = ($info['fields'])?$info['fields']:'*';
		$sql = "SELECT $fields FROM `".$this->table."` WHERE `deleted` = 'no' GROUP BY `campaign_id` ";
		if(is_array($info['id'])){
			$sql .= " AND (";
			$x=0;
			foreach($info['id'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";
				$sql .= "`id`='".intval($sid)."' ";
			}
			$sql .= ") ";
		}else if($info['id']){
			$sql .= " AND `id`='".intval($info['id'])."' ";
		}
		if(is_array($info['name'])){
			$sql .= " AND (";
			$x=0;
			foreach($info['name'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`name` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}
			$sql .= ") ";
		## SINGLE NAME SEARCH
		}else if($info['name']){
			$sql .= " AND `name` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['name'])."%' ";
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
        ## GROUP BY
        #$sql .= " GROUP BY `campaign_id` ";
        ## LIMITS
		if(is_array($info['limit'])){
			$sql .= " LIMIT ".
						(($info['limit']['offset'])?$info['limit']['offset'].",":'').
						$info['limit']['count'];
		}
		## echo $sql;
		## RETURN RESULT SET
		return $_SESSION['dbapi']->query($sql);
	}

	function getCount(){
		$row = mysqli_fetch_row($this->getResults(
						array(
							"fields" => "COUNT(DISTINCT `campaign_id`)"
						)
					));
		return $row[0];
	}
}