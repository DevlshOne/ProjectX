<?



class API_Extensions{

	var $xml_parent_tagname = "Extensions";
	var $xml_record_tagname = "Extension";

	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";


	function handleAPI(){


		if(!checkAccess('extensions')){


			$_SESSION['api']->errorOut('Access denied to Extensions');

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


			$_SESSION['dbapi']->extensions->delete($id);

			logAction('delete', 'extensions', $id, "");

			$_SESSION['api']->outputDeleteSuccess();


			break;

		case 'view':


			$id = intval($_REQUEST['id']);

			$row = $_SESSION['dbapi']->extensions->getByID($id);




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

			$id = intval($_POST['adding_extension']);


			unset($dat);
			$dat['status'] = $_POST['status'];


			$dat['server_id'] = intval($_POST['server_id']);


			// BINDING THESE TOGETHER
			$dat['number'] = intval($_POST['number']);
			$dat['station_id']	= $dat['number'];//intval($_POST['station_id']);


			$dat['iax_host'] = trim($_POST['iax_host']);
			$dat['iax_password'] = trim($_POST['iax_password']);

// 			if(strlen(trim($_POST['password'])) > 0){
// 				$dat['password'] = $_POST['password'];
// 			}

			$dat['register_as'] = trim($_POST['register_as']);
			$dat['register_pass'] = trim($_POST['register_pass']);


			$warning_msg = null;




		//	$portnum = intval($_POST['port_num']);


			if($id){

				/**if($portnum%2 != 0){

					$dat['port_num'] = 0;

					$warning_msg = "Port number invalid, cannot be an ODD number.";

				}else{


					// CHECK IF PORTNUM EXISTS IN ONE BESIDES ITSELF
					list($test) = $_SESSION['dbapi']->queryROW( "SELECT id FROM `".$_SESSION['dbapi']->extensions->table."` ".
																" WHERE port_num='".$portnum."' AND server_id='".$dat['server_id']."' ".
																" AND status = 'enabled' ".
																" AND id != '".$id."' "
																);
					if($test){
						$dat['port_num'] = 0;

						$warning_msg = "The specified PORT is already in use, please choose another.";
					}else{

						$dat['port_num'] = $portnum;

					}
				}*/




				$_SESSION['dbapi']->aedit($id,$dat,$_SESSION['dbapi']->extensions->table);

				logAction('edit', 'extensions', $id, "");


			}else{

				// MAKE SURE ITS AN EVEN NUMBER
			/**	if($portnum%2 != 0){

					$dat['port_num'] = 0;

					$warning_msg = "Port number invalid, cannot be an ODD number.";

				}else{

					// CHECK IF PORTNUM IS ALREADY USED
					list($test) = $_SESSION['dbapi']->queryROW("SELECT id FROM `".$_SESSION['dbapi']->extensions->table."` ".
																" WHERE port_num='".$portnum."' AND server_id='".$dat['server_id']."' ".
																" AND status = 'enabled' "

																);
					if($test){
						$dat['port_num'] = 0;

						$warning_msg = "The specified PORT is already in use on that server, please choose another.";
					}else{

						$dat['port_num'] = $portnum;

					}
				}**/

				$_SESSION['dbapi']->aadd($dat,$_SESSION['dbapi']->extensions->table);
				$id = mysqli_insert_id($_SESSION['dbapi']->db);

				logAction('add', 'extensions', $id, "");
			}




			if($warning_msg != null){

				$_SESSION['api']->outputEditSuccess($id, $warning_msg);

			}else{

				$_SESSION['api']->outputEditSuccess($id);
			}



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



			if($_REQUEST['s_number']){

				$dat['number'] = intval($_REQUEST['s_number']);

			}

			if($_REQUEST['s_station_id']){

				$dat['station_id'] = intval($_REQUEST['s_station_id']);

			}


			if($_REQUEST['s_server_id']){

				$dat['server_id'] = intval($_REQUEST['s_server_id']);

			}




			if($_REQUEST['s_status']){

				$dat['status'] = $_REQUEST['s_status'];

			}

			if($_REQUEST['s_in_use']){

				$dat['in_use'] = trim($_REQUEST['s_in_use']);

			}

			if($_REQUEST['s_in_use_by_userid']){

				$dat['in_use_by_userid'] = intval($_REQUEST['s_in_use_by_userid']);

			}




			## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
			if(isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])){

				$pagemode = true;

				$cntdat = $dat;
				$cntdat['fields'] = 'COUNT(id)';
				list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->extensions->getResults($cntdat));

				$dat['limit'] = array(
									"offset"=>intval($_REQUEST['index']),
									"count"=>intval($_REQUEST['pagesize'])
								);

			}


			## ORDER BY SYSTEM
			if($_REQUEST['orderby'] && $_REQUEST['orderdir']){
				$dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
			}






			$res = $_SESSION['dbapi']->extensions->getResults($dat);



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
			case 'username':

				if($tmparr[2] <= 0){
					$out_stack[$idx] = '-';
				}else{
					$out_stack[$idx] = $_SESSION['dbapi']->users->getName($tmparr[2]);
				}

				break;
			case 'server_name':

				if($tmparr[2] <= 0){
					$out_stack[$idx] = '-';
				}else{
					list($out_stack[$idx]) = $_SESSION['dbapi']->queryROW("SELECT name FROM servers WHERE id='".intval($tmparr[2])."' ");
				}

				break;
			}## END SWITCH




		}



		$out = $_SESSION['api']->renderSecondaryAjaxXML('Data',$out_stack);

		//print_r($out_stack);
		echo $out;

	} ## END HANDLE SECONDARY AJAX



}

