<?



class API_ActionLog{

	var $xml_parent_tagname = "Actions";
	var $xml_record_tagname = "Action";

	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";


	function handleAPI(){

		if(!checkAccess('action_log')){


			$_SESSION['api']->errorOut('Access denied to Action Log');

			return;
		}

		switch($_REQUEST['action']){
//		case 'delete':
//
//			$id = intval($_REQUEST['id']);
//
//			//$row = $_SESSION['dbapi']->campaigns->getByID($id);
//
//			$_SESSION['api']->outputDeleteSuccess();


//			break;

		case 'view':


			$id = intval($_REQUEST['id']);

			$row = $_SESSION['dbapi']->action_log->getByID($id);




			## BUILD XML OUTPUT
			$out = "<".$this->xml_record_tagname." ";

			foreach($row as $key=>$val){


				$out .= $key.'="'.htmlentities($val).'" ';

			}

			$out .= " />\n";






			///$out .= "</".$this->xml_record_tagname.">";

			echo $out;



			break;
		case 'edit':
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
//
//			}else{
//
//
//
//				$_SESSION['dbapi']->aadd($dat,$_SESSION['dbapi']->names->table);
//				$id = mysqli_insert_id($_SESSION['dbapi']->db);
//
//			}
//
//
//
//
//			$_SESSION['api']->outputEditSuccess($id);



			break;

		default:
		case 'list':



			$dat = array();
			$totalcount = 0;
			$pagemode = false;


			// RESERVED FOR TIME SEARCH STUFF
			if($_REQUEST['s_date']){


				$tmp0 = strtotime($_REQUEST['s_date']);
				$tmp1 = $tmp0 + 86399;

				//echo date("g:i:s m/d/Y", $tmp0).' ';
				//echo date("g:i:s m/d/Y", $tmp1).' ';

				$dat['time'] = array($tmp0, $tmp1);

			}else{



				//$dat['time'] = array(mktime(0,0,0), mktime(23,59,59));


			}


			## ID SEARCH
			if($_REQUEST['s_id']){

				$dat['id'] = intval($_REQUEST['s_id']);

			}

			if($_REQUEST['s_user_id']){

				$dat['user_id'] = intval($_REQUEST['s_user_id']);

			}


			if($_REQUEST['s_record_id']){

				$dat['record_id'] = intval($_REQUEST['s_record_id']);

			}



			## USERNAME SEARCH
			if($_REQUEST['s_username']){

				$dat['user'] = trim($_REQUEST['s_username']);

			}


			if($_REQUEST['s_action']){

				$dat['action'] = trim($_REQUEST['s_action']);

			}

			if($_REQUEST['s_area']){

				$dat['area'] = trim($_REQUEST['s_area']);

			}


			if($_REQUEST['s_desc']){

				$dat['description'] = trim($_REQUEST['s_desc']);

			}


			## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
			if(isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])){

				$pagemode = true;

				$cntdat = $dat;
				$cntdat['fields'] = 'COUNT(id)';
				list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->action_log->getResults($cntdat));

				$dat['limit'] = array(
									"offset"=>intval($_REQUEST['index']),
									"count"=>intval($_REQUEST['pagesize'])
								);

			}


			## ORDER BY SYSTEM
			if($_REQUEST['orderby'] && $_REQUEST['orderdir']){
				$dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
			}






			$res = $_SESSION['dbapi']->action_log->getResults($dat);



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

			//print_r($tmparr);


			switch($tmparr[1]){
			default:

				## ERROR
				$out_stack[$idx] = -1;

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

