<?



class API_Scripts{

	var $xml_parent_tagname = "Scripts";
	var $xml_record_tagname = "Script";

	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";


	function handleAPI(){


		if(!checkAccess('scripts')){


			$_SESSION['api']->errorOut('Access denied to Scripts');

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


// NEED TO DELETE TEH VOICES_FILES
// DELETE ACTUAL SOUND FILES TOO
// THEN FINALLY DELETE THE SCRIPT!


			$_SESSION['dbapi']->scripts->delete($id);





			logAction('delete', 'scripts', $id, "");

			$_SESSION['api']->outputDeleteSuccess();


			break;

		case 'view':


			$id = intval($_REQUEST['id']);

			$row = $_SESSION['dbapi']->scripts->getByID($id);




			## BUILD XML OUTPUT
			$out = "<".$this->xml_record_tagname." ";

			foreach($row as $key=>$val){


				$out .= $key.'="'.htmlentities($val).'" ';

			}

			$out .= ">\n";


			$res = $_SESSION['dbapi']->query("SELECT * FROM voices_files WHERE script_id='".$row['id']."' ");

			while($r2 = mysqli_fetch_array($res, MYSQLI_ASSOC)){

				$out .= "<VoiceFile ";
				foreach($r2 as $key=>$val){


					$out .= $key.'="'.htmlentities($val).'" ';

				}

				$out .= " />\n";

			}



			$out .= "</".$this->xml_record_tagname.">";

			echo $out;



			break;
		case 'edit':

			$id = intval($_POST['adding_script']);


			if($id > 0){
				$row = $_SESSION['dbapi']->scripts->getByID($id);
			}

			unset($dat);


			$dat['name'] = preg_replace("/[^a-zA-Z0-9\-\?'.,() ]/","", trim($_POST['name']));
			$dat['description']		= preg_replace("/[^a-zA-Z0-9\-\?'.,() ]/","", trim($_POST['description'] ));
			$dat['variables']		= trim($_POST['variables']);


			$dat['screen_num']		= intval($_POST['screen_num']);
			$dat['keys']			= trim($_POST['keys']);

			$dat['voice_id']		= intval($_POST['voice_id']);
			$dat['campaign_id']		= intval($_POST['campaign_id']);

			$dat['advance_script'] = (intval($_POST['advance_script']) > 0)?'yes':'no';



			$dat['time_modified'] = time();


			if($id){

				//

				$_SESSION['dbapi']->aedit($id,$dat,$_SESSION['dbapi']->scripts->table);

				$newrow = $_SESSION['dbapi']->scripts->getByID($id);

				logAction('edit', 'scripts', $id, "", $row, $newrow);

			}else{

				//$dat['time_created'] = time();


				$_SESSION['dbapi']->aadd($dat,$_SESSION['dbapi']->scripts->table);
				$id = mysqli_insert_id($_SESSION['dbapi']->db);

				$newrow = $_SESSION['dbapi']->scripts->getByID($id);

				logAction('add', 'scripts', $id, "", $row, $newrow);
			}


			if(($delid=intval($_POST['del_voice_id'])) > 0){


				$_SESSION['dbapi']->voices->deleteFile($delid);

				logAction('delete_voice_file', 'voices', $delid, "Deleted voice file for Script $id");
			}




			$_SESSION['api']->outputEditSuccess($id);



			break;

		case 'edit_voice_file':

			$id = intval($_POST['editing_vfile']);

			if($id > 0){
				$row = $_SESSION['dbapi']->scripts->getVoiceFileByID($id);
			}

			unset($dat);

			$dat['description']		= preg_replace("/[^a-zA-Z0-9\-\?'.,() ]/","", trim($_POST['description'] ));

			if($id){

				$_SESSION['dbapi']->aedit($id,$dat,$_SESSION['dbapi']->scripts->voices_files_table);

				$newrow = $_SESSION['dbapi']->scripts->getVoiceFileByID($id);

				//logAction('edit', 'scripts', $id, "", $row, $newrow);

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

			if(isset($_REQUEST['s_screen_num'])){

				$dat['screen_num'] = intval($_REQUEST['s_screen_num']);

			}


			if($_REQUEST['s_voice_id']){

				$dat['voice_id'] = intval($_REQUEST['s_voice_id']);

			}

			if($_REQUEST['s_campaign_id']){

				$dat['campaign_id'] = intval($_REQUEST['s_campaign_id']);

			}

			## USERNAME SEARCH
			if($_REQUEST['s_name']){

				$dat['name'] = trim($_REQUEST['s_name']);

			}

			if($_REQUEST['s_filename']){

				$dat['filename'] = trim($_REQUEST['s_filename']);

			}



			if($_REQUEST['s_keys']){

				$dat['keys'] = $_REQUEST['s_keys'];

			}else if($_REQUEST['s_key']){

				$dat['keys'] = $_REQUEST['s_key'];

			}

			if($_REQUEST['s_desc']){

				$dat['description'] = $_REQUEST['s_desc'];

			}


			if($_REQUEST['s_variables']){

				$dat['variables'] = trim($_REQUEST['s_variables']);
			}



			## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
			if(isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])){

				$pagemode = true;

				$cntdat = $dat;
				$cntdat['fields'] = 'COUNT(id)';
				list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->scripts->getResults($cntdat));

				$dat['limit'] = array(
									"offset"=>intval($_REQUEST['index']),
									"count"=>intval($_REQUEST['pagesize'])
								);

			}


			## ORDER BY SYSTEM
			if($_REQUEST['orderby'] && $_REQUEST['orderdir']){
				$dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
			}






			$res = $_SESSION['dbapi']->scripts->getResults($dat);



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
			case 'screen_name':

				// COULD BE REPLACED LATER WITH A CUSOMIZABLE SCREEN DB TABLE
				switch($tmparr[2]){
				default:

					$out_stack[$idx] = 'Screen '.$tmparr[2];
					break;
				case 0:
					$out_stack[$idx] = 'Quick Keys';
					break;
				case 1:
					$out_stack[$idx] = 'Intro Screen';
					break;
				}

				break;

			case 'last_modified_time':


				break;
			case 'last_modified':


				break;
			case 'last_modified_user':


				break;

			case 'campaign_name':

				if($tmparr[2] <= 0){
					$out_stack[$idx] = '-';
				}else{
					$out_stack[$idx] = $_SESSION['dbapi']->campaigns->getName($tmparr[2]);
				}

				break;
			case 'voice_name':

				if($tmparr[2] <= 0){
					$out_stack[$idx] = '-';
				}else{
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

