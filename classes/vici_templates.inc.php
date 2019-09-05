<?	/***************************************************************
	 *	VICI TEMPLATES - TEMPLATES TO APPLY SETTINGS TO USERS IN VICI
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['vici_templates'] = new ViciTemplates;


class ViciTemplates{

	var $table	= 'vici_user_templates';			## Classes main table to operate on
	var $orderby	= 'id';		## Default Order field
	var $orderdir	= 'DESC';	## Default order direction


	function ViciTemplates(){


		## REQURES DB CONNECTION!

		$this->handlePOST();
	}


	function applyTemplate($template_id, $cluster_id, $px_user_id, $vici_user_id){

		$template_id = intval($template_id);
		$cluster_id = intval($cluster_id);
		$px_user_id = intval($px_user_id);
		$vici_user_id = intval($vici_user_id);

		## CONNECT TO PX (or make sure we are)
		connectPXDB();


		## LOAD TEMPLATE FROM PX
		$template = $_SESSION['dbapi']->querySQL("SELECT * FROM `".$this->table."` WHERE id='".$template_id."' ");

		## LOAD USER FROM PX
		$row = $_SESSION['dbapi']->users->getByID($px_user_id);





		## APPLY TEMPLATE
		### PARSE TEMPLATE CONTENTS,
		### SPLIT IT UP BY TABLE

		$template_data = trim($template['db_settings']);

		$lines = preg_split("/\r\n|\r|\n/", $template_data, -1, PREG_SPLIT_NO_EMPTY);

		$table_arr = array();

		foreach($lines as $line){

			// FIND THE PERIOD, TO SPLIT THE TABLE AND FIELD
			$tmparr = preg_split("/\./", $line, 2, PREG_SPLIT_NO_EMPTY);

			if(count($tmparr) < 2){
				echo "Skipping line, missing period to split table and field name\nLINE: $line\n";
				continue;
			}


			if(!$table_arr[$tmparr[0]]){

				$table_arr[$tmparr[0]] = array();

			}


			$table_arr[$tmparr[0]][] = $tmparr[1];
		}

		### GO THROUGH THE RESULTING ARRAY

		## CONNECT TO VICI CLUSTER
		$dbidx = getClusterIndex($cluster_id);
		connectViciDB($dbidx);

		foreach($table_arr as $tbl=>$fields){

			### BUILD THE SQL UPDATE STATEMENTS
			$sql = "UPDATE `".$tbl."` SET ";

			$cnt=0;
			foreach($fields as $field){
				$sql .= ($cnt++ > 0)?', ':'';

				$tmparr = preg_split("/=/",$field);

				if(is_numeric($tmparr[1]) && intval($tmparr[1]) == trim($tmparr[1])){

					$sql .= "`".$tmparr[0]."`='".$tmparr[1]."'";

				}else{

					$sql .= "`".$tmparr[0]."`=".$tmparr[1];
				}
			}


			$sql.= " WHERE `user_id`='$vici_user_id' ";

			### EXECUTE THEM
			execSQL($sql);
			//echo $sql;
		}




		//print_r($table_arr);


		## ???

		## PROFIT

	}






	function handlePOST(){

	}

	function handleFLOW(){


	}




}
