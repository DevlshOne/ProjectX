<?



class API_LoginTracker{

	var $xml_parent_tagname = "Logins";
	var $xml_record_tagname = "Login";

	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";


	function handleAPI(){



		if(!checkAccess('login_tracker')){


			$_SESSION['api']->errorOut('Access denied to Login Tracker');

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


			$_SESSION['dbapi']->login_tracker->delete($id);

			logAction('delete', 'logins', $id, "");


			$_SESSION['api']->outputDeleteSuccess();


			break;

		case 'view':


			$id = intval($_REQUEST['id']);

			$row = $_SESSION['dbapi']->login_tracker->getByID($id);




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

			$id = intval($_POST['adding_login']);


			unset($dat);


			$dat['username'] = trim($_POST['username']);
			$dat['result'] = trim($_POST['result']);
			$dat['section'] = intval($_POST['section']);

			if($id){

				$_SESSION['dbapi']->aedit($id,$dat,$_SESSION['dbapi']->login_tracker->table);

				logAction('edit', 'logins', $id, "Username=".$dat['username']);

			}else{



				$_SESSION['dbapi']->aadd($dat,$_SESSION['dbapi']->login_tracker->table);
				$id = mysqli_insert_id($_SESSION['dbapi']->db);


				logAction('add', 'logins', $id, "Username=".$dat['username']);
			}




			$_SESSION['api']->outputEditSuccess($id);



			break;

		default:
		case 'list':



			$dat = array();
			$totalcount = 0;
			$pagemode = false;





			## ID SEARCH
			if($_REQUEST['s_id']){

				$dat['id'] = intval($_REQUEST['s_id']);

			}

			## USERNAME SEARCH
			if($_REQUEST['s_username']){

				$dat['username'] = trim($_REQUEST['s_username']);

			}

			## RESULT SEARCH
			if($_REQUEST['s_result']){

				$dat['result'] = trim($_REQUEST['s_result']);

			}

			## SECTION SEARCH
			if($_REQUEST['s_section']){

				$dat['section'] = trim($_REQUEST['s_section']);

			}
			
			## IP SEARCH
			if($_REQUEST['s_ip']){

				$dat['ip'] = trim($_REQUEST['s_ip']);

			}			



			## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
			if(isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])){

				$pagemode = true;

				$cntdat = $dat;
				$cntdat['fields'] = 'COUNT(id)';
				list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->login_tracker->getResults($cntdat));

				$dat['limit'] = array(
									"offset"=>intval($_REQUEST['index']),
									"count"=>intval($_REQUEST['pagesize'])
								);

			}


			## ORDER BY SYSTEM
			if($_REQUEST['orderby'] && $_REQUEST['orderdir']){
				$dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
			}






			$res = $_SESSION['dbapi']->login_tracker->getResults($dat);



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
			case 'voice_name':

				// COULD BE REPLACED LATER WITH A CUSOMIZABLE SCREEN DB TABLE
				if($tmparr[2] <= 0){
					$out_stack[$idx] = '-';
				}else{

					//echo "ID#".$tmparr[2];

					$out_stack[$idx] = $_SESSION['dbapi']->voices->getName($tmparr[2]);
				}

				break;

			}## END SWITCH




		}



		$out = $_SESSION['api']->renderSecondaryAjaxXML('Data',$out_stack);

		//print_r($out_stack);
		echo $out;

	} ## END HANDLE SECONDARY AJAX


}

