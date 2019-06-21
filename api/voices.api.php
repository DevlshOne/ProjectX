<?



class API_Voices{

	var $xml_parent_tagname = "Voices";
	var $xml_record_tagname = "Voice";

	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";


	function handleAPI(){


		if(!checkAccess('voices')){


			$_SESSION['api']->errorOut('Access denied to Voices');

			return;
		}



//		if($_SESSION['user']['priv'] < 5){
//
//
//			$_SESSION['api']->errorOut('Access denied to non admins.');
//
//			return;
//		}





		switch($_REQUEST['action']){
		case 'delete':

			$id = intval($_REQUEST['id']);

			//$row = $_SESSION['dbapi']->campaigns->getByID($id);


			$_SESSION['dbapi']->voices->delete($id);


			logAction('delete', 'voices', $id, "");

			$_SESSION['api']->outputDeleteSuccess();


			break;

		case 'view':


			$id = intval($_REQUEST['id']);

			$row = $_SESSION['dbapi']->voices->getByID($id);




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

			$id = intval($_POST['adding_voice']);


			unset($dat);
			$dat['status']		= $_POST['status'];
			$dat['campaign_id']	= intval($_POST['campaign_id']);
			$dat['language_id']	= intval($_POST['language_id']);
			$dat['name']		=  trim($_POST['name']);
			$dat['actor_name']	=  trim($_POST['actor_name']);



			if($id){


				$_SESSION['dbapi']->aedit($id,$dat,$_SESSION['dbapi']->voices->table);

				logAction('edit', 'voices', $id, "");

			}else{



				$_SESSION['dbapi']->aadd($dat,$_SESSION['dbapi']->voices->table);
				$id = mysqli_insert_id($_SESSION['dbapi']->db);


				logAction('add', 'voices', $id, "");

			}




			$_SESSION['api']->outputEditSuccess($id);



			break;

		default:
		case 'list':



			$dat = array();
			$totalcount = 0;
			$pagemode = false;



			$dat['status'] = 'enabled';





			## ID SEARCH
			if($_REQUEST['s_id']){

				$dat['id'] = intval($_REQUEST['s_id']);

			}

			## STATUS
			if($_REQUEST['s_status']){

				$dat['status'] = $_REQUEST['s_status'];

			}

			## CAMPAIGN
			if($_REQUEST['s_campaign_id']){

				$dat['campaign_id'] = intval($_REQUEST['s_campaign_id']);

			}

			## LANGUAGE
			if($_REQUEST['s_language_id']){

				$dat['language_id'] = intval($_REQUEST['s_language_id']);

			}





			## NAME SEARCH
			if($_REQUEST['s_name']){

				$dat['name'] = trim($_REQUEST['s_name']);

			}

			## ACTOR NAME
			if($_REQUEST['s_actor_name']){

				$dat['actor_name'] = trim($_REQUEST['s_actor_name']);

			}






			## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
			if(isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])){

				$pagemode = true;

				$cntdat = $dat;
				$cntdat['fields'] = 'COUNT(id)';
				list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->voices->getResults($cntdat));

				$dat['limit'] = array(
									"offset"=>intval($_REQUEST['index']),
									"count"=>intval($_REQUEST['pagesize'])
								);

			}


			## ORDER BY SYSTEM
			if($_REQUEST['orderby'] && $_REQUEST['orderdir']){
				$dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
			}






			$res = $_SESSION['dbapi']->voices->getResults($dat);



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
			case 'campaign_name':

				if($tmparr[2] <= 0){
					$out_stack[$idx] = '-';
				}else{
					$cpgn = $_SESSION['dbapi']->campaigns->getByID($tmparr[2]);
					$out_stack[$idx] = $cpgn['name'];
				}

				break;
			case 'language_name':

				if($tmparr[2] <= 0){
					$out_stack[$idx] = '-';
				}else{
					list($langname) = $_SESSION['dbapi']->queryROW("SELECT name FROM `languages` WHERE id='".intval($tmparr[2])."' ");
					$out_stack[$idx] = $langname;
				}


				break;

			}## END SWITCH




		}



		$out = $_SESSION['api']->renderSecondaryAjaxXML('Data',$out_stack);

		//print_r($out_stack);
		echo $out;

	} ## END HANDLE SECONDARY AJAX



}

