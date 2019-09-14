<?



class API_MyNotes{

	var $xml_parent_tagname = "Notes";
	var $xml_record_tagname = "Note";


	function handleAPI(){



// 		if(!checkAccess('names')){


// 			$_SESSION['api']->errorOut('Access denied to Names');

// 			return;
// 		}

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


			$_SESSION['dbapi']->my_notes->delete($id);

			logAction('delete', 'my_notes', $id, "");


			$_SESSION['api']->outputDeleteSuccess();


			break;

		case 'view':


			$id = intval($_REQUEST['id']);

			$row = $_SESSION['dbapi']->my_notes->getByID($id);




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

			$id = intval($_POST['note_id']);


			unset($dat);


			$dat['notes'] = trim($_POST['note_text']);
			$dat['time'] = time();

			if($id){
		
				$row = $_SESSION['dbapi']->my_notes->getByID($id);
				
				if($row['user_id'] != $_SESSION['user']['id']){
					
					$_SESSION['api']->errorOut("ERROR: You do not own this note.",$die=true, -1);
					exit;
				}
				
				
				$_SESSION['dbapi']->aedit($id,$dat,$_SESSION['dbapi']->my_notes->table);

				$newrow = $_SESSION['dbapi']->my_notes->getByID($id);
				
				logAction('edit', 'my_notes', $id, "", $row, $newrow);
				

			}else{

				$dat['user_id'] = $_SESSION['user']['id'];


				$_SESSION['dbapi']->aadd($dat,$_SESSION['dbapi']->my_notes->table);
				$id = mysqli_insert_id($_SESSION['dbapi']->db);

				$newrow = $_SESSION['dbapi']->my_notes->getByID($id);

				logAction('add', 'my_notes', $id, "", array(), $newrow);
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
			if($_REQUEST['s_notes']){

				$dat['notes'] = trim($_REQUEST['s_notes']);

			}


			## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
			if(isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])){

				$pagemode = true;

				$cntdat = $dat;
				$cntdat['fields'] = 'COUNT(id)';
				list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->my_notes->getResults($cntdat));

				$dat['limit'] = array(
									"offset"=>intval($_REQUEST['index']),
									"count"=>intval($_REQUEST['pagesize'])
								);

			}


			## ORDER BY SYSTEM
			if($_REQUEST['orderby'] && $_REQUEST['orderdir']){
				$dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
			}






			$res = $_SESSION['dbapi']->my_notes->getResults($dat);



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
// 			case 'voice_name':

// 				// COULD BE REPLACED LATER WITH A CUSOMIZABLE SCREEN DB TABLE
// 				if($tmparr[2] <= 0){
// 					$out_stack[$idx] = '-';
// 				}else{

// 					//echo "ID#".$tmparr[2];

// 					$out_stack[$idx] = $_SESSION['dbapi']->voices->getName($tmparr[2]);
// 				}

// 				break;

			}## END SWITCH




		}



		$out = $_SESSION['api']->renderSecondaryAjaxXML('Data',$out_stack);

		//print_r($out_stack);
		echo $out;

	} ## END HANDLE SECONDARY AJAX


}

