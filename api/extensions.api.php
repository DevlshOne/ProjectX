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
		case 'bulk_operations':
			
			
			$dat = array();
			
			if($_POST['bulk_iax']){
				
				$dat['iax_password'] = trim($_POST['new_iax_password']);
			
			}
			
			
			if($_POST['bulk_iax_host']){
				
				$dat['iax_host'] = trim($_POST['new_iax_host']);
				
			}
			
			if($_POST['bulk_sip']){
				
				$dat['sip_password'] = trim($_POST['new_sip_password']);
				
			}
			
			// ONLY EDIT IF THERE IS SOEMTHING TO EDIT
			if(count($dat) > 0){
				
				foreach($_POST['editing_extensions'] as $ext_id){
					
					aedit($ext_id, $dat, "extensions");

				}
				
			}

			
			$_SESSION['api']->outputEditSuccess(1);
			exit;
			
			
			break;
		case 'bulk_add':
			
			
			$phonepass = trim($_POST['phone_password']);
			
			
			$dat = array();
			
			//adding_bulk_extension
			$dat['status'] = 'enabled';
			
			$dat['server_id']	= intval($_POST['server_id']);
			
			$svrrow = getPXServer($dat['server_id']);
			
			
			
			$dat['iax_host'] 	= trim($_POST['iax_host']);
			$dat['iax_password'] = trim($_POST['iax_password']);
			$dat['sip_password'] = trim($_POST['sip_password']);
			
			// 			if(strlen(trim($_POST['password'])) > 0){
			// 				$dat['password'] = $_POST['password'];
			// 			}
			
			$dat['description'] = trim($_POST['description']);
			
			if(trim($_POST['register_as'])){
				$dat['register_as'] = trim($_POST['register_as']);
			}
			
			if(trim($_POST['register_pass'])){
				$dat['register_pass'] = trim($_POST['register_pass']);
			}
			
			$start = intval($_POST['start_number']);
			$end = intval($_POST['end_number']);
			
			if($start > $end){
				$tmp = $end;
				$end = $start;
				$start = $tmp;
			}
			
			$cnt = 0;
			$skipcnt = 0;
			for($x = $start;$x <= $end;$x++){
				
				// BINDING THESE TOGETHER
				$dat['number'] = $x;
				$dat['station_id']	= $dat['number'];
				
				$existing = $_SESSION['dbapi']->extensions->getByServerAndExtension($dat['server_id'], $dat['number']);
				
				if(!$existing){

					$cnt += $_SESSION['dbapi']->aadd($dat,$_SESSION['dbapi']->extensions->table);
					
				}else{
					
					$skipcnt++;
				}
				//$id = mysqli_insert_id($_SESSION['dbapi']->db);
			}
			
			$cluster_id = intval($svrrow['cluster_id']);
			if($cluster_id > 0){
				
				$dbidx = getClusterIndex($cluster_id);
				
				if($dbidx > -1){
					
					connectViciDB($dbidx);
					
					$protocol = "IAX2";
					$phone_type = "";
					$local_gmt = "-8.00";		// MIGHT NEED TO ADJUST FOR THEIR TIMEZONE
					
					$ins_cmd_start = "INSERT INTO asterisk.phones(`extension`, `dialplan_number`, `voicemail_id`, `phone_ip`, `computer_ip`, `server_ip`,".
							" `login`, `pass`, `status`, `active`, `phone_type`, `fullname`, `company`, `picture`, `messages`, `old_messages`, `protocol`,".
							" `local_gmt`, `ASTmgrUSERNAME`, `ASTmgrSECRET`, `login_user`, `login_pass`, `login_campaign`, `park_on_extension`,".
							" `conf_on_extension`, `VICIDIAL_park_on_extension`, `VICIDIAL_park_on_filename`, `monitor_prefix`, `recording_exten`,".
							" `voicemail_exten`, `voicemail_dump_exten`, `ext_context`, `dtmf_send_extension`, `call_out_number_group`, `client_browser`,".
							" `install_directory`, `local_web_callerID_URL`, `VICIDIAL_web_URL`, `AGI_call_logging_enabled`, `user_switching_enabled`, ".
							" `conferencing_enabled`, `admin_hangup_enabled`, `admin_hijack_enabled`, `admin_monitor_enabled`, `call_parking_enabled`, ".
							" `updater_check_enabled`, `AFLogging_enabled`, `QUEUE_ACTION_enabled`, `CallerID_popup_enabled`, `voicemail_button_enabled`,".
							" `enable_fast_refresh`, `fast_refresh_rate`, `enable_persistant_mysql`, `auto_dial_next_number`, `VDstop_rec_after_each_call`,".
							" `DBX_server`, `DBX_database`, `DBX_user`, `DBX_pass`, `DBX_port`, `DBY_server`, `DBY_database`,`DBY_user`, `DBY_pass`, `DBY_port`,".
							" `outbound_cid`, `enable_sipsak_messages`, `email`, `template_id`, `conf_override`, `phone_context`, `phone_ring_timeout`,".
							" `conf_secret`, `delete_vm_after_email`, `is_webphone`, `use_external_server_ip`, `codecs_list`, `codecs_with_template`,".
							" `webphone_dialpad`, `on_hook_agent`, `webphone_auto_answer`, `voicemail_timezone`, `voicemail_options`, `user_group`,".
							" `voicemail_greeting`, `voicemail_dump_exten_no_inst`, `voicemail_instructions`) VALUES ";
					
					//echo "Generating and inserting vicidial phone...\n";
					
					for($x = $start;$x <= $end;$x++){
						
						$curext = $x;
						
						$insert_cmd = $ins_cmd_start. " (";
							
						$insert_cmd.= "'$curext','$curext','$curext','','',"; // phone_ip and computer_ip blank
						
						// SERVER IP, USER, PHONE PASSWORD
						$insert_cmd.= "'".$dat['iax_host']."','$curext','".addslashes($phonepass)."',";
						
						// ACTIVE, ACTIVE, PHONE TYPE, FULLNAME
						$insert_cmd.= "'ACTIVE','Y', '".addslashes($phone_type)."', '".addslashes($dat['description'])."', ";
						
						
						// COMPANY, PICTURE, MESSAGES, OLD MESSAGES, PROTOCOL
						$insert_cmd.= "'','','0','0','$protocol',";
						
						// LOCAL GMT, THEN A BUNCH OF DEFAULT BULLSHIT
						$insert_cmd.= "'$local_gmt','cron','1234',NULL,NULL,NULL,'8301','8302','8301','park','8612', '8309', '8501','85026666666666', ";
						
						// MORE DEFAULT BULLSHIT
						$insert_cmd.= "'default','local/8500998@default','Zap/g2/','/usr/bin/mozilla','/usr/local/perl_TK',";
						
						$insert_cmd.= "'http://astguiclient.sf.net/test_callerid_output.php','http://astguiclient.sf.net/test_VICIDIAL_output.php',";
						
						// PERMISSIONS (AGI_call_logging_enabled, user_switching_enabled, conferencing_enabled, admin_hangup_enabled, admin_hijack_enabled, admin_monitor_enabled, call_parking_enabled, updater_check_enabled, AFLogging_enabled, QUEUE_ACTION_enabled, CallerID_popup_enabled, voicemail_button_enabled, enable_fast_refresh)
						$insert_cmd.= "'1','1','1','0','0','1','1','1','1','1','1','1','0',";
						
						// REFRESH FREQ, FEW MORE DEFAULT FIELDS FOR CUSTOM DB CRAP OR SOMETHING
						$insert_cmd.= "'1000','0','1','1',NULL,'asterisk','cron','1234','3306',NULL,'asterisk','cron','1234','3306',";
						
						// OUTBOUND CALLER ID, bla bla bla ring timeout
						$insert_cmd.= "'$curext','0',NULL,'',NULL,'default',60,";
						
						// IAX PASSWORD!
						$insert_cmd.= "'".addslashes($dat['iax_password'])."', 'N','N','N','','0','Y','N','Y',";
						
						// VOICEMAIL TIMEZONE, VM OPTIONS, USER GROUP, greeting, dump ext, vm instructions
						$insert_cmd.="'pacific','','---ALL---','','85026666666667','Y'";
						
						$insert_cmd.= "); ";
						
						
						// INSERT TO DB (IGNORE ERRORS QUIETLY)
						execSQL($insert_cmd, 2);
						//	echo $insert_cmd;
							
							
					} // END FOR(EXTENSIONS) LOOP
					
					
					
					execSQL("UPDATE asterisk.servers SET rebuild_conf_files='Y'");
				}
				
			}
			
			
			
			
			logAction('bulk_add', 'extensions', 0, "Added Bulk Extensions $start to $end");
			
			
			$_SESSION['api']->outputEditSuccess($cnt);
			
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

			$dat['sip_password'] = trim($_POST['sip_password']);
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

