<?



class API_QuizResults{

	var $xml_parent_tagname = "Quizs";
	var $xml_record_tagname = "Quiz";

	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";


	function handleAPI(){



		if(!checkAccess('quiz_results')){


			$_SESSION['api']->errorOut('Access denied to Quiz Results');

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


			$_SESSION['dbapi']->quiz_results->delete($id);

			logAction('delete', 'quiz_results', $id, "");


			$_SESSION['api']->outputDeleteSuccess();


			break;

		case 'view':


			$id = intval($_REQUEST['id']);

			$row = $_SESSION['dbapi']->quiz_results->getByID($id);




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
			if($_REQUEST['s_quiz_id']){

				$dat['quiz_id'] = intval($_REQUEST['s_quiz_id']);

			}

			## USERNAME SEARCH
			if($_REQUEST['s_username']){

				$dat['username'] = trim($_REQUEST['s_username']);

			}


			if($_REQUEST['s_response_time']){

				$dat['response_time'] = intval($_REQUEST['s_response_time']);

			}

			if(trim($_REQUEST['s_hide_question'])){

				$dat['hide_question'] = (strtolower(trim($_REQUEST['s_hide_question'])) == "true")?"true":"false";

			}


			## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
			if(isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])){

				$pagemode = true;

				$cntdat = $dat;
				$cntdat['fields'] = 'COUNT(id)';
				list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->quiz_results->getResults($cntdat));

				$dat['limit'] = array(
									"offset"=>intval($_REQUEST['index']),
									"count"=>intval($_REQUEST['pagesize'])
								);

			}


			## ORDER BY SYSTEM
			if($_REQUEST['orderby'] && $_REQUEST['orderdir']){
				$dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
			}






			$res = $_SESSION['dbapi']->quiz_results->getResults($dat);



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
			case 'quiz_name':

				// COULD BE REPLACED LATER WITH A CUSOMIZABLE SCREEN DB TABLE
				if($tmparr[2] <= 0){
					$out_stack[$idx] = '-';
				}else{

					//echo "ID#".$tmparr[2];

					$out_stack[$idx] = $_SESSION['dbapi']->quiz_results->getName($tmparr[2]);
				}

				break;

			}## END SWITCH




		}



		$out = $_SESSION['api']->renderSecondaryAjaxXML('Data',$out_stack);

		//print_r($out_stack);
		echo $out;

	} ## END HANDLE SECONDARY AJAX


}

