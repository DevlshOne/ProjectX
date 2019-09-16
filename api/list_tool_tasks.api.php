<?



class API_Tasks{

	var $xml_parent_tagname = "Tasks";
	var $xml_record_tagname = "Task";

	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";


	function handleAPI(){



		if(!checkAccess('list_tools')){
			$_SESSION['api']->errorOut('Access denied to List tool - Tasks');

			return;
		}



		switch($_REQUEST['action']){
		case 'delete':

			$id = intval($_REQUEST['id']);

			//$row = $_SESSION['dbapi']->campaigns->getByID($id);

//
//			$_SESSION['dbapi']->names->delete($id);
//
//			logAction('delete', 'names', $id, "");
//
//
//			$_SESSION['api']->outputDeleteSuccess();


			break;

		case 'cancel':

			$id = intval($_REQUEST['task_id']);

			$result = $_SESSION['dbapi']->list_tool_tasks->cancelTask($id);

			echo $result;


			break;

		case 'view':


			$id = intval($_REQUEST['id']);

			$row = $_SESSION['dbapi']->list_tool_tasks->getByID($id);




			## BUILD XML OUTPUT
			$out = "<".$this->xml_record_tagname." ";

			foreach($row as $key=>$val){


				$out .= $key.'="'.htmlentities($val).'" ';

			}

			$out .= " />\n";






			///$out .= "</".$this->xml_record_tagname.">";

			echo $out;



			break;
//		case 'edit':
//
//			$id = intval($_POST['adding_name']);
//
//
//			unset($dat);
//
//
//			$dat['name'] = trim($_POST['name']);
//			$dat['filename'] = trim($_POST['filename']);
//			$dat['voice_id'] = intval($_POST['voice_id']);
//
//			if($id){
//
//				$_SESSION['dbapi']->aedit($id,$dat,$_SESSION['dbapi']->names->table);
//
//				logAction('edit', 'names', $id, "Name=".$dat['name']);
//
//			}else{
//
//
//
//				$_SESSION['dbapi']->aadd($dat,$_SESSION['dbapi']->names->table);
//				$id = mysqli_insert_id($_SESSION['dbapi']->db);
//
//
//				logAction('add', 'names', $id, "Name=".$dat['name']);
//			}
//
//
//
//
//			$_SESSION['api']->outputEditSuccess($id);
//
//
//
//			break;

		default:
		case 'list':



			$dat = array();
			$totalcount = 0;
			$pagemode = false;







			if($_REQUEST['s_date_mode']){

				if($_REQUEST['s_date_mode'] != 'any'){

					if($_REQUEST['s_date_mode'] == 'daterange'){

						$tmp0 = strtotime($_REQUEST['s_date_month'].'/'.$_REQUEST['s_date_day'].'/'.$_REQUEST['s_date_year']);
						$tmp1 = strtotime($_REQUEST['s_date2_month'].'/'.$_REQUEST['s_date2_day'].'/'.$_REQUEST['s_date2_year']);


						$tmp0 = mktime(0,0,0, date("m", $tmp0), date("d", $tmp0), date("Y", $tmp0));
						$tmp1 = mktime(23,59,59, date("m", $tmp1), date("d", $tmp1), date("Y", $tmp1));

					}else{

						$tmp0 = strtotime($_REQUEST['s_date_month'].'/'.$_REQUEST['s_date_day'].'/'.$_REQUEST['s_date_year']);
						$tmp0 = mktime(0,0,0, date("m", $tmp0), date("d", $tmp0), date("Y", $tmp0));

						$tmp1 = $tmp0 + 86399;

	//					$tmp0 = strtotime($_REQUEST['s_date']);
	//					$tmp1 = $tmp0 + 86399;

					}
					//echo date("g:i:s m/d/Y", $tmp0).' ';
					//echo date("g:i:s m/d/Y", $tmp1).' ';

					$dat['time'] = array($tmp0, $tmp1);

				}
			}else{



				//$dat['time'] = array(mktime(0,0,0), mktime(23,59,59));


			}







			## ID SEARCH
			if($_REQUEST['s_id']){

				$dat['id'] = intval($_REQUEST['s_id']);

			}



			if($_REQUEST['s_import_id']){

				$dat['import_id'] = trim($_REQUEST['s_import_id']);

			}


			if($_REQUEST['s_command']){

				$dat['command'] = trim($_REQUEST['s_command']);

			}


			if($_REQUEST['s_status']){

				$dat['status'] = trim($_REQUEST['s_status']);

			}



			## USERNAME SEARCH
//			if($_REQUEST['s_name']){
//
//				$dat['name'] = trim($_REQUEST['s_name']);
//
//			}
//
//			if($_REQUEST['s_filename']){
//
//				$dat['filename'] = trim($_REQUEST['s_filename']);
//
//			}



			## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
			if(isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])){

				$pagemode = true;

				$cntdat = $dat;
				$cntdat['fields'] = 'COUNT(id)';
				list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->list_tool_tasks->getResults($cntdat));

				$dat['limit'] = array(
									"offset"=>intval($_REQUEST['index']),
									"count"=>intval($_REQUEST['pagesize'])
								);

			}


			## ORDER BY SYSTEM
			if($_REQUEST['orderby'] && $_REQUEST['orderdir']){
				$dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
			}






			$res = $_SESSION['dbapi']->list_tool_tasks->getResults($dat);



	## OUTPUT FORMAT TOGGLE
			switch($_SESSION['api']->mode){
			default:
			case 'xml':


		## GENERATE XML

				if($pagemode){

					$out = '<'.$this->xml_parent_tagname." totalcount=\"".intval($totalcount)."\">\n";
				}else{
					$out = '<'.$this->xml_parent_tagname.">\n";
				}

				$out .= $_SESSION['api']->renderResultSetXML($this->xml_record_tagname,$res);

				$out .= '</'.$this->xml_parent_tagname.">";
				break;

		## GENERATE JSON
			case 'json':

				$out = '['."\n";

				$out .= $_SESSION['api']->renderResultSetJSON($this->json_record_tagname,$res);

				$out .= ']'."\n";
				break;
			}


	## OUTPUT DATA!
			echo $out;

		}
	}



	function handleSecondaryAjax(){



		$out_stack = array();

		//print_r($_REQUEST);

		foreach($_REQUEST['special_stack'] as $idx => $data){

			$tmparr = preg_split("/:/",$data);

		//	print_r($tmparr);


			switch($tmparr[1]){
			default:

				## ERROR
				$out_stack[$idx] = -1;

				break;

//['[get:cluster_name:config_xml]','align_center'],
//['[get:list_ids:config_xml]','align_center'],

			case 'cluster_name':


				$taskid = $tmparr[2];

				// GET XML FROM TASK
				$task = $_SESSION['dbapi']->list_tool_tasks->getByID($taskid);

				// CLEAN UP THE XML
				$xml = preg_replace("/\t|\r\n|\n/",' ',$task['config_xml']);

//echo $xml."\n";

				// PARSE CONFIG XML, EXTRACT CLUSTER ID
				$arr = $_SESSION['JXMLP']->parseOne($xml,'Config',1);

//print_r($arr);
				// LOOKUP AND RETURN CLUSTER NAME

				// FROM: BUILD_LIST COMMAND
				if(trim($arr['target_vici_cluster_id'])){

					$out_stack[$idx] = getClusterName($arr['target_vici_cluster_id']);

				// FROM: MOVE VICI LIST COMMAND
				}else if(trim($arr['target_cluster_id'])){

					$out_stack[$idx] = getClusterName($arr['target_cluster_id']);

				}else{

					$out_stack[$idx] = '-';

				}


				break;

			case 'list_ids':

				$taskid = $tmparr[2];

				// GET XML FROM TASK
				$task = $_SESSION['dbapi']->list_tool_tasks->getByID($taskid);


				// CLEAN UP THE XML
				$xml = preg_replace("/\t|\r\n|\n/",' ',$task['config_xml']);


//echo $task['config_xml']."\n\n";

				// PARSE CONFIG XML, EXTRACT CLUSTER ID
				$arr = $_SESSION['JXMLP']->parseOne($xml,'Config',1);

				// LOOKUP AND RETURN LIST ID's

				// FROM: BUILD_LIST COMMAND
				if(trim($arr['target_list_ids'])){

					$out_stack[$idx] = $arr['target_list_ids'];//getClusterName($arr['target_vici_cluster_id']);

				// FROM: MOVE VICI LIST COMMAND
				}else if(trim($arr['source_lists']) && trim($arr['target_list_id'])){

					$out_stack[$idx] = $arr['source_lists'].' &gt; '.$arr['target_list_id'];

				}else if(trim($arr['target_list_id'])){

					$out_stack[$idx] = $arr['target_list_id'];

				}else{

					$out_stack[$idx] = '-';

				}

				break;

			case 'import_name':

				if($tmparr[2] <= 0){
					$out_stack[$idx] = '-';
				}else{

					// MULTIPLE SPECIFIED
					if(stripos($tmparr[2], ",") >= 0){

						$tmpidarr = preg_split("/,/", $tmparr[2], -1, PREG_SPLIT_NO_EMPTY);

						$out_stack[$idx] = "";
						$x=0;
						foreach($tmpidarr as $tmpid){

							if($x++ > 0)$out_stack[$idx] .= ",";

							$out_stack[$idx] .= $_SESSION['dbapi']->imports->getName($tmparr[2]);

						}

					// SINGULAR SPECIFIED
					}else{

						$sourceid=intval($tmparr[2]);
						if($sourceid > 0){

							$out_stack[$idx] = $_SESSION['dbapi']->imports->getName($tmparr[2]);

						}else{

							switch($sourceid){
							default:

								$out_stack[$idx] = $tmparr[2];

								break;

							case -3:

								$out_stack[$idx] = "Vicidial";

								break;
							case -2:

								$out_stack[$idx] = "DNC List";

								break;
							}
						}

					}
				}

				break;
			case 'import_date':



					// MULTIPLE SPECIFIED
					if(stripos($tmparr[2], ",") > 0){

						$tmpidarr = preg_split("/,/", $tmparr[2], -1, PREG_SPLIT_NO_EMPTY);

						$out_stack[$idx] = "";
						$x=0;
						foreach($tmpidarr as $tmpid){

							if($x++ > 0)$out_stack[$idx] .= ",";

							$out_stack[$idx] .= $_SESSION['dbapi']->imports->getImportDate($tmparr[2]);

						}

					// SINGULAR SPECIFIED
					}else{



						$sourceid=intval($tmparr[2]);

						if($sourceid > 0){

							$out_stack[$idx] = $_SESSION['dbapi']->imports->getImportDate($tmparr[2]);

						}else{

							switch($sourceid){
							default:

								$out_stack[$idx] = $tmparr[2];

								break;

							case -10:

								$out_stack[$idx] = "Skynet Import";

								break;

							case -3:

								$out_stack[$idx] = "Vicidial";

								break;
							case -2:

								$out_stack[$idx] = "DNC List";

								break;
							}

						}

					}


				break;
//			case 'voice_name':
//
//				// COULD BE REPLACED LATER WITH A CUSOMIZABLE SCREEN DB TABLE
//				if($tmparr[2] <= 0){
//					$out_stack[$idx] = '-';
//				}else{
//
//					//echo "ID#".$tmparr[2];
//
//					$out_stack[$idx] = $_SESSION['dbapi']->voices->getName($tmparr[2]);
//				}
//
//				break;

			}## END SWITCH




		}



		$out = $_SESSION['api']->renderSecondaryAjaxXML('Data',$out_stack);

		//print_r($out_stack);
		echo $out;

	} ## END HANDLE SECONDARY AJAX


}

