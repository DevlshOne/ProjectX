<?



class API_UserGroupsMaster{

	var $xml_parent_tagname = "Usergroups";
	var $xml_record_tagname = "Usergroup";

	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";



	function handleAPI(){


		if(!checkAccess('users')){


			$_SESSION['api']->errorOut('Access denied to User Groups');

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

			// DELETE FROM VICI, THEN FROM PX, USING THE ACTION PACKED, EDGE OF YOUR SEAT, ALL-IN-WONDER FUNCTION, delete()
			$_SESSION['dbapi']->user_groups_master->delete($id);


			logAction('delete', 'user_groups_master', $id, "");

			$_SESSION['api']->outputDeleteSuccess();


			break;

		case 'view':


			$id = intval($_REQUEST['id']);

			$row = $_SESSION['dbapi']->user_groups_master->getByID($id);




			## BUILD XML OUTPUT
			$out = "<".$this->xml_record_tagname." ";

			foreach($row as $key=>$val){

				if($key == 'password')continue;

				$out .= $key.'="'.htmlentities($val).'" ';

			}

			$out .= " >\n";






			$out .= "</".$this->xml_record_tagname.">";

			echo $out;



			break;


		case 'edit':

			$id = intval($_POST['adding_user_group']);


			unset($dat);

			$dat['name'] = trim($_POST['name']);
			$dat['office'] = trim($_POST['office']);



			if($id){



				$_SESSION['dbapi']->aedit($id,$dat,$_SESSION['dbapi']->user_groups_master->table);


				logAction('edit', 'user_groups_master', $id, "");


			}else{

				$dat['user_group'] = trim($_POST['user_group']);

				$_SESSION['dbapi']->aadd($dat,$_SESSION['dbapi']->user_groups_master->table);
				$id = mysqli_insert_id($_SESSION['dbapi']->db);

				logAction('add', 'user_groups_master', $id, "");

			}


		//	$this->syncGroupToVici($id);



			$_SESSION['api']->outputEditSuccess($id);



			break;

		default:
		case 'list':



			$dat = array();
			$totalcount = 0;
			$pagemode = false;



			## AGENT NAME SEARCH
			if($_REQUEST['s_name']){

				$dat['name'] = trim($_REQUEST['s_name']);

			}


			## GROUP NAME
			if($_REQUEST['s_group_name']){

				$dat['group_name'] = trim($_REQUEST['s_group_name']);

			}




			##





			## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
			if(isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])){

				$pagemode = true;

				$cntdat = $dat;
				$cntdat['fields'] = 'COUNT(id)';
				list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->user_groups->getResults($cntdat));

				$dat['limit'] = array(
									"offset"=>intval($_REQUEST['index']),
									"count"=>intval($_REQUEST['pagesize'])
								);

			}


			## ORDER BY SYSTEM
			if($_REQUEST['orderby'] && $_REQUEST['orderdir']){
				$dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
			}






			$res = $_SESSION['dbapi']->user_groups_master->getResults($dat);



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

//			case 'cluster_name':
//
//				// vici_cluster_id
//
//				if($tmparr[2] <= 0){
//					$out_stack[$idx] = '-';
//				}else{
//
//					//echo "ID#".$tmparr[2];
//
//					$out_stack[$idx] = getClusterName($tmparr[2]);
//				}
////



				break;
			}## END SWITCH




		}



		$out = $_SESSION['api']->renderSecondaryAjaxXML('Data',$out_stack);

		//print_r($out_stack);
		echo $out;

	} ## END HANDLE SECONDARY AJAX




}


