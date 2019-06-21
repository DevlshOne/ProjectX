<?



class API_Messages{

	var $xml_parent_tagname = "Messages";
	var $xml_record_tagname = "Message";

	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";


	function handleAPI(){

		if(!checkAccess('messages')){

			$_SESSION['api']->errorOut('Access denied to Messages');

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


			$_SESSION['dbapi']->messages->delete($id);

			logAction('delete', 'messages', $id, "");

			$_SESSION['api']->outputDeleteSuccess();


			break;

		case 'view':


			$id = intval($_REQUEST['id']);

			$row = $_SESSION['dbapi']->messages->getByID($id);




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

			$id = intval($_POST['adding_message']);


			unset($dat);


			$dat['type'] = trim($_POST['type']);
			$dat['message'] = trim($_POST['message']);

			switch($dat['type']){
			case 'user':

				$dat['who'] = trim($_POST['username']);
				break;
			case 'campaign':
				$dat['who'] = intval($_POST['campaign_id']);
				break;
			case 'all':
				$dat['who'] = '';
				break;
			}


			if($id){

				$_SESSION['dbapi']->aedit($id,$dat,$_SESSION['dbapi']->messages->table);

				logAction('edit', 'messages', $id, "");

			}else{



				$_SESSION['dbapi']->aadd($dat,$_SESSION['dbapi']->messages->table);
				$id = mysqli_insert_id($_SESSION['dbapi']->db);

				logAction('add', 'messages', $id, "");

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
			if($_REQUEST['s_type']){

				$dat['type'] = trim($_REQUEST['s_type']);

			}

			if($_REQUEST['s_who']){

				$dat['who'] = trim($_REQUEST['s_who']);

			}

			if($_REQUEST['s_message']){

				$dat['message'] = trim($_REQUEST['s_message']);

			}

			## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
			if(isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])){

				$pagemode = true;

				$cntdat = $dat;
				$cntdat['fields'] = 'COUNT(id)';
				list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->messages->getResults($cntdat));

				$dat['limit'] = array(
									"offset"=>intval($_REQUEST['index']),
									"count"=>intval($_REQUEST['pagesize'])
								);

			}


			## ORDER BY SYSTEM
			if($_REQUEST['orderby'] && $_REQUEST['orderdir']){
				$dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
			}






			$res = $_SESSION['dbapi']->messages->getResults($dat);



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
			}
		}



		$out = $_SESSION['api']->renderSecondaryAjaxXML('Data',$out_stack);

		//print_r($out_stack);
		echo $out;
	}
}

