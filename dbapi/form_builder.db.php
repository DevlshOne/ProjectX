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

	function saveField($d) {
	    $insKeys = [];
	    $insVals = [];
	    if(empty($d['dbID'])) {
            foreach($d as $k => $v) {
                switch($k) {
                    case 'isRequired':
                        $insKeys[] = "is_required";
                        $insVals[] = (isset($v) ? '0' : '1');
                        break;
                    case 'name':
                        $insKeys[] = "name";
                        $insVals[] = $v;
                        break;
                    case 'lblWidth':
                        $insKeys[] = "label_width";
                        $insVals[] = $v;
                        break;
                    case 'lblHeight':
                        $insKeys[] = "label_height";
                        $insVals[] = $v;
                        break;
                    case 'toolTip':
                        $insKeys[] = "tool_tip";
                        $insVals[] = $v;
                        break;
                    case 'placeHolder':
                        $insKeys[] = "place_holder";
                        $insVals[] = $v;
                        break;
                    case 'cssName':
                        $insKeys[] = "css_class";
                        $insVals[] = $v;
                        break;
                    case 'campID':
                        $insKeys[] = "campaign_id";
                        $insVals[] = $v;
                        break;
                    case 'screenNum':
                        $insKeys[] = "screen_num";
                        $insVals[] = $v;
                        break;
                    case 'dbID':
                    case 'fldName':
                    case 'txtLabel':
                    case 'idx':
                        break;
                    case 'fldValue':
                        $insKeys[] = "value";
                        $insVals[] = $v;
                        break;
                    case 'fldType':
                        $insKeys[] = "field_type";
                        $insVals[] = $v;
                        break;
                    case 'fldMaxLength':
                        $insKeys[] = "max_length";
                        $insVals[] = $v;
                        break;
                    case 'fldWidth':
                        $insKeys[] = "field_width";
                        $insVals[] = $v;
                        break;
                    case 'fldHeight':
                        $insKeys[] = "field_height";
                        $insVals[] = $v;
                        break;
                    case 'fldSpecial':
                        $insKeys[] = "special_mode";
                        $insVals[] = $v;
                        break;
                    case 'fldOptions':
                        $insKeys[] = "options";
                        $insVals[] = $v;
                        break;
                    case 'dbTable':
                        $insKeys[] = "db_table";
                        $insVals[] = $v;
                        break;
                    case 'dbField':
                        $insKeys[] = "db_field";
                        $insVals[] = $v;
                        break;
                    case 'fldVariables':
                        $insKeys[] = "variables";
                        $insVals[] = $v;
                        break;
                    case 'callStep':
                        $insKeys[] = "field_step";
                        $insVals[] = $v;
                        break;
                    case 'lblPosX':
                        $insKeys[] = "label_x";
                        $insVals[] = $v;
                        break;
                    case 'lblPosY':
                        $insKeys[] = "label_y";
                        $insVals[] = $v;
                        break;
                    case 'fldPosX':
                        $insKeys[] = "field_x";
                        $insVals[] = $v;
                        break;
                    case 'fldPosY':
                        $insKeys[] = "field_y";
                        $insVals[] = $v;
                        break;
                    case 'isHidden':
                        $insKeys[] = "is_hidden";
                        $insVals[] = $v;
                        break;
                    case 'isLocked':
                        $insKeys[] = "is_locked";
                        $insVals[] = $v;
                        break;
                    default:
                        $setFieldsArr[] = "`" . $k . "` = '" . $v . "'";
                        break;
                }
            }
            $keys = join("`,`", $insKeys);
            $vals = join("','", $insVals);
            $sql = "INSERT INTO `" . $this->table . "` (`" . $keys . "`) VALUES ('" . $vals . "')";
        } else {
    	    $setFieldsArr = [];
	        $newID = $d['dbID'];
            $sql = "UPDATE `" . $this->table . "` SET ";
            foreach($d as $k => $v) {
                switch($k) {
                    case 'isRequired':
                        $setFieldsArr[] = "`is_required` = '" . (isset($v) ? '0' : '1') . "'";
                        break;
                    case 'name':
                        $setFieldsArr[] = "`name` = '" . $v . "'";
                        break;
                    case 'lblWidth':
                        $setFieldsArr[] = "`label_width` = '" . $v . "'";
                        break;
                    case 'lblHeight':
                        $setFieldsArr[] = "`label_height` = '" . $v . "'";
                        break;
                    case 'toolTip':
                        $setFieldsArr[] = "`tool_tip` = '" . $v . "'";
                        break;
                    case 'placeHolder':
                        $setFieldsArr[] = "`place_holder` = '" . $v . "'";
                        break;
                    case 'cssName':
                        $setFieldsArr[] = "`css_class` = '" . $v . "'";
                        break;
                    case 'fldName':
                    case 'txtLabel':
                    case 'idx':
                    case 'screenNum':
                    case 'campID':
                    case 'dbID':
                        break;
                    case 'fldValue':
                        $setFieldsArr[] = "`value` = '" . $v . "'";
                        break;
                    case 'fldType':
                        $setFieldsArr[] = "`field_type` = '" . $v . "'";
                        break;
                    case 'fldMaxLength':
                        $setFieldsArr[] = "`max_length` = '" . $v . "'";
                        break;
                    case 'fldWidth':
                        $setFieldsArr[] = "`field_width` = '" . $v . "'";
                        break;
                    case 'fldHeight':
                        $setFieldsArr[] = "`field_height` = '" . $v . "'";
                        break;
                    case 'fldSpecial':
                        $setFieldsArr[] = "`special_mode` = '" . $v . "'";
                        break;
                    case 'fldOptions':
                        $setFieldsArr[] = "`options` = '" . $v . "'";
                        break;
                    case 'dbTable':
                        $setFieldsArr[] = "`db_table` = '" . $v . "'";
                        break;
                    case 'dbField':
                        $setFieldsArr[] = "`db_field` = '" . $v . "'";
                        break;
                    case 'fldVariables':
                        $setFieldsArr[] = "`variables` = '" . $v . "'";
                        break;
                    case 'callStep':
                        $setFieldsArr[] = "`field_step` = '" . $v . "'";
                        break;
                    case 'lblPosX':
                        $setFieldsArr[] = "`label_x` = '" . $v . "'";
                        break;
                    case 'lblPosY':
                        $setFieldsArr[] = "`label_y` = '" . $v . "'";
                        break;
                    case 'fldPosX':
                        $setFieldsArr[] = "`field_x` = '" . $v . "'";
                        break;
                    case 'fldPosY':
                        $setFieldsArr[] = "`field_y` = '" . $v . "'";
                        break;
                    case 'isHidden':
                        $setFieldsArr[] = "`is_hidden` = '" . $v . "'";
                        break;
                    case 'isLocked':
                        $setFieldsArr[] = "`is_locked` = '" . $v . "'";
                        break;
                    default:
                        $setFieldsArr[] = "`" . $k . "` = '" . $v . "'";
                        break;
                }
            }
            $setStmts = join(', ', $setFieldsArr);
            $sql .= $setStmts . " WHERE `id` = '" . $newID . "'";
	    }
//        echo $sql;
        return $_SESSION['dbapi']->query($sql);
    }

    function copyFields($src, $tgt) {
	    $sql = "SELECT * FROM `" . $this->table . "` WHERE `campaign_id` = '" . $src . "'";
	    $data = $_SESSION['dbapi']->fetchAllAssoc($sql);
	    foreach($data as $row) {
            $row['campaign_id'] = $tgt;
            unset($row['id']);
            $keys = join("`,`", array_keys($row));
            $vals = join("','", array_values($row));
            $sql2 = "INSERT INTO " . $this->table . " (`" . $keys . "`) VALUES ('" . $vals . "')";
            $_SESSION['dbapi']->query($sql2);
        }
    }

    function getFieldsByScreen($id, $scr) {
	    $campaign_id = intval($id);
	    $screen_id = intval($scr);
	    $sql = "SELECT * FROM `" . $this->table . "` WHERE `campaign_id` = " . $campaign_id . " AND `screen_num` = " . $screen_id . " AND `deleted` = 'no'";
	    $data = $_SESSION['dbapi']->fetchAllAssoc($sql);
        return $data;
    }

    function markFieldDeleted($id) {
	    $custom_field_id = intval($id);
	    $sql = "UPDATE `" . $this->table . "` SET `deleted`='yes' WHERE `id`= " . $custom_field_id;
//	    echo $sql;
	    $_SESSION['dbapi']->query($sql);
	    return;
    }

	function getResults($info){
		$fields = ($info['fields'])?$info['fields']:'*';
		$sql = "SELECT $fields FROM `".$this->table."` WHERE `deleted` = 'no' GROUP BY `campaign_id` ";
		if(isset($info['id']) && is_array($info['id'])){
			$sql .= " AND (";
			$x=0;
			foreach($info['id'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";
				$sql .= "`id`='".intval($sid)."' ";
			}
			$sql .= ") ";
		} else if(isset($info['campaign_id'])) {
			$sql .= " AND `campaign_id`='" . intval($info['id']) . "' ";
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