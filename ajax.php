<?php
/**
 * AJAX PORTAL - USED TO EXTRACT DATA AND RETURN IN VARIOUS FRIENDLY FORMATS
 */


	session_start();


// INCLUDES
	include_once("site_config.php");

	include_once($_SESSION['site_config']['basedir']."dbapi/dbapi.inc.php");

	include_once($_SESSION['site_config']['basedir']."utils/getbasedir.php");
	include_once($_SESSION['site_config']['basedir']."utils/feature_functions.php");

// LOGIN CHECKING
	if(!isset($_SESSION['user']['id'])){



		die("Not Logged in");


	}
	
	
	// RELOAD THE USER/ACCOUNT/FEATURE SET, MAKE SURE USER STILL ENABLED, ACCOUNT STILL ACTIVE, ETC
	$_SESSION['dbapi']->users->refreshFeaturesAndPrivs(2);
	
	
	// UPDATE THE USERS LAST ACTION TIME
	$_SESSION['dbapi']->users->updateLastActionTime();
	
	


	function generateFilename($input){

		$pathparts = pathinfo($input);//$_FILES['splash_img']['name']);

		## Replace the periods(.) with dashes
		$name =  str_replace(".",'-',$pathparts['filename']);

		## REPLACE SPACES WITH UNDERSCORES
		$name =  str_replace(" ",'_',$name);

		## Append uniq string, then re-append file extension
		$name = $name.'-'.uniqid('').'.'.$pathparts['extension'];

		return $name;
	}



//header("Access-Control-Allow-Origin: http://skynet.advancedtci.com");

/** START CODE **/


switch($_REQUEST['mode']){
default:

	die("No mode specified.");
	break;

case 'force_logout':
	
	
	if(!checkAccess('login_tracker_kick_user')){
		
		die("ERROR: Access to kick users out DENIED");
	}
	
	$login_id = intval($_REQUEST['force_logout_user']);
	
	if($login_id <= 0){
		die("ERROR: Invalid login ID specified");
	}
	
	include_once($_SESSION['site_config']['basedir']."db.inc.php");
	include_once($_SESSION['site_config']['basedir']."utils/db_utils.php");
	
	connectPXDB();
	
	if(execSQL("UPDATE `logins` SET `time_out`=UNIX_TIMESTAMP() WHERE id='$login_id'") > 0){
		echo "Success";
	}else{
		
		echo "ERROR: Record not updated.";
	}
	

	exit;
	
	break;
case 'capacity_report':
	
	
	include_once($_SESSION['site_config']['basedir']."db.inc.php");
	include_once($_SESSION['site_config']['basedir']."utils/db_utils.php");
	include_once($_SESSION['site_config']['basedir']."utils/functions.php");
	
	include_once($_SESSION['site_config']['basedir']."classes/capacity_report.inc.php");
	
	
	$stime = mktime(0,0,0);
	$etime = $stime + 86399;
	
	echo $_SESSION['capacity_report']->generateChartData('day', $stime, $etime);
	
	
	break;

case 'download_fec_form':


 	include_once($_SESSION['site_config']['basedir']."db.inc.php");
	include_once($_SESSION['site_config']['basedir']."utils/db_utils.php");
	include_once($_SESSION['site_config']['basedir']."utils/functions.php");

	include_once($_SESSION['site_config']['basedir']."classes/fec_filer.inc.php");


	if($_REQUEST['format']){

		$_SESSION['fec_filer']->setFormat(trim($_REQUEST['format']));

	}


	$data = $_SESSION['fec_filer']->exportReport();

	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename='.
			'fec_file_'.$_SESSION['fecdata']['current_file']['id'].'-'.$_SESSION['fecdata']['current_file']['report_code'].'-'.time().'.'.$_SESSION['fec_filer']->file_extension
		);
	header('Content-Transfer-Encoding: binary');
	header('Connection: Keep-Alive');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: ' . strlen($data) );

	echo $data;
	exit;




	break;

case 'web_donation_import_upload':

//	print_r($_REQUEST);
//	print_r($_FILES);
/**
 *
Array
(
    [mode] => web_donation_import_upload
    [uploading_web_csv] =>
    [project] => BCRSF
)
Array
(
    [csv_file] => Array
        (
            [name] => give-export-payments-06-26-2018 Americans for the Cure of Breast Cancer.csv
            [type] => application/octet-stream
            [tmp_name] => /tmp/phptS51f5
            [error] => 0
            [size] => 559
        )

)
 */

 	include_once($_SESSION['site_config']['basedir']."db.inc.php");
	include_once($_SESSION['site_config']['basedir']."utils/db_utils.php");

//	include_once($_SESSION['site_config']['basedir']."dbapi/dbapi.inc.php");
	include_once($_SESSION['site_config']['basedir']."classes/pac_reports.inc.php");

	$_SESSION['pac_reports']->project = strtoupper(trim($_REQUEST['project']));

 	$output = $_SESSION['pac_reports']->parsePacsFile($_FILES['csv_file']['tmp_name']);

	$cnt = $_SESSION['pac_reports']->pushPacsToDB($output);

	?><script>

		window.parent.WebCSVUploadSuccess(<?=$cnt?>);

	</script><?

	//echo "Rows affected: ".$cnt."\n";


	break;

case 'pac_reports_export':

	include_once($_SESSION['site_config']['basedir']."db.inc.php");
	include_once($_SESSION['site_config']['basedir']."utils/db_utils.php");

	include_once($_SESSION['site_config']['basedir']."dbapi/dbapi.inc.php");
	include_once($_SESSION['site_config']['basedir']."classes/pac_reports.inc.php");

	$stime = 0;
	$etime = 0;

	if($_REQUEST['date_mode'] != 'any'){

		if($_REQUEST['date_mode'] == 'daterange'){

			$tmp0 = strtotime($_REQUEST['date_month'].'/'.$_REQUEST['date_day'].'/'.$_REQUEST['date_year']);
			$tmp1 = strtotime($_REQUEST['date2_month'].'/'.$_REQUEST['date2_day'].'/'.$_REQUEST['date2_year']);


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

		$stime = $tmp0;
		$etime = $tmp1;

	}


	list($output,$totals) = $_SESSION['pac_reports']->exportNams($stime, $etime,false);

	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename=pac_reports-nams-export.csv');
	header('Content-Transfer-Encoding: binary');
	header('Connection: Keep-Alive');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: ' . strlen($output) );

	echo $output;
	exit;



	break;


case 'load_vici_list_info':


	include_once($_SESSION['site_config']['basedir']."db.inc.php");
	include_once($_SESSION['site_config']['basedir']."utils/db_utils.php");
//	include_once($_SESSION['site_config']['basedir']."utils/functions.php");
//	include_once($_SESSION['site_config']['basedir']."classes/list_tools.inc.php");

	$cluster_id = intval($_REQUEST['cluster_id']);


	if($cluster_id <= 0){

		echo "0\n";
		exit;
	}


	connectViciDB(getClusterIndex($cluster_id));


	$res = query("SELECT `list_id`,`list_name`,`campaign_id`,`active` FROM `vicidial_lists` ORDER BY `list_id` ASC",1);


	if(mysqli_num_rows($res) <= 0){


		echo "0\n";
		exit;
	}



	echo "1\n";

	// CACHED FOR 5 MINUTES
	if($_SESSION['vici_cluster_states_statuses_data'][$cluster_id] && ((time() < ($_SESSION['vici_cluster_states_statuses_time'][$cluster_id] + 15)) )   ){

//echo "cached\n";
		echo $_SESSION['vici_cluster_states_statuses_data'][$cluster_id];
		exit;
	}
//	echo "Fresh load\n";


	$output = "";

	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){


		$statestr = '';
		$zz = 0;
		$stateres = query("SELECT DISTINCT(state) AS state FROM asterisk.vicidial_list WHERE list_id='".$row['list_id']."' ORDER BY state ASC",1);
		while($srow = mysqli_fetch_array($stateres, MYSQLI_ASSOC)){

			if($zz++ > 0)$statestr.=',';
			$statestr.= $srow['state'];
		}
		$statusstr = '';
		$zz=0;
		$statusres = query("SELECT DISTINCT(status) AS status FROM asterisk.vicidial_list WHERE list_id='".$row['list_id']."' ORDER BY status ASC",1);
		while($srow = mysqli_fetch_array($statusres, MYSQLI_ASSOC)){

			if($zz++ > 0)$statusstr.=',';
			$statusstr.= $srow['status'];
		}


		$output .= $row['list_id']."\t$statestr\t$statusstr\n";


	}

	$_SESSION['vici_cluster_states_statuses_data'][$cluster_id] = $output;
	$_SESSION['vici_cluster_states_statuses_time'][$cluster_id] = time();

	echo $output;







	break;











/**
 * LIST TOOLS - DNC FUNCTIONS
 */
case 'dnc':

	include_once($_SESSION['site_config']['basedir']."db.inc.php");
	include_once($_SESSION['site_config']['basedir']."utils/db_utils.php");
	include_once($_SESSION['site_config']['basedir']."utils/functions.php");
	include_once($_SESSION['site_config']['basedir']."classes/list_tools.inc.php");

	connectListDB();

	switch($_REQUEST['action']){
	default:

		die("Action not defined: ".$_REQUEST['action']);

		break;


	case 'add_number':


		$cnt = $_SESSION['list_tools']->addNumber($_REQUEST['value']);

		if($cnt > 0){
			echo "1:Successfully Added";
		}else{
			echo "0:Number already exists";
		}
		exit;

		break;

	case 'add_campaign_number':

		$num = trim(preg_replace("/[^0-9]/",'',$_REQUEST['value']));
		$camp = trim($_REQUEST['campaign']);

		$type="DNC";

		//echo "1:TEST SUCCESS";
		//echo "0:TEST FAILURE";

		$cnt = $_SESSION['list_tools']->addCampaignNumber($num,$camp, $type);

		if($cnt > 0){
			echo "1:Successfully Added";
		}else{
			echo "0:Number already exists";
		}

//		$cnt = $_SESSION['list_tools']->addNumber();
//
//		if($cnt > 0){
//			echo "1:Successfully Added";
//		}else{
//			echo "0:Number already exists";
//		}

		exit;

		break;

	case 'remove_campaign_number':

		$num = trim(preg_replace("/[^0-9]/",'',$_REQUEST['value']));
		$camp = trim($_REQUEST['campaign']);
		$type=trim($_REQUEST['dnc_type']);

		// FAILSAFE FOR TYPE, DEFAULT TO DNC TYPE
		$type = ($type)?$type:"DNC";


		//removeCampaignNumber($num, $campaign, $type="DNC")
		$cnt = $_SESSION['list_tools']->removeCampaignNumber($num,$camp,$type);
		if($cnt > 0){
			echo "1:Successfully Removed";
		}else if($cnt < 0){
			echo "0:Not Allowed to remove";
		}else{
			echo "0:Number not found";
		}
		exit;


		break;
	//($num, $campaign, $type="DNC")

	case 'remove_number':

		$cnt = $_SESSION['list_tools']->removeNumber($_REQUEST['value']);
		if($cnt > 0){
			echo "1:Successfully Removed";
		}else{
			echo "0:Number not found";
		}
		exit;


		break;

	case 'remove_name':

		$cnt = $_SESSION['list_tools']->removeName($_REQUEST['first_name'], $_REQUEST['last_name']);
		if($cnt > 0){
			echo "1:Successfully Removed";
		}else{
			echo "0:Name not found";
		}
		exit;


		break;

	case 'add_name':

		if(trim($_REQUEST['first_name']) && trim($_REQUEST['last_name'])){

			$cnt = $_SESSION['list_tools']->addName($_REQUEST['first_name'], $_REQUEST['last_name']);

			if($cnt > 0){
				echo "1:Successfully Added";
			}else{
				echo "0:Name already exists";
			}
		}else{
			echo "0:First or Last Name not specified";
		}
		exit;



		break;

	case 'lookup_name':

		if(trim($_REQUEST['first_name']) || trim($_REQUEST['last_name'])){

			$res = $_SESSION['list_tools']->lookupName($_REQUEST['first_name'], $_REQUEST['last_name']);


			if(mysqli_num_rows($res) > 0){


				echo mysqli_num_rows($res)."\n";

				$x=0;
				while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
					//print_r($row);
					if($x++ > 0)echo "\n";
					echo $row['last_name'].','.$row['first_name'].":".(($row['time_added'] > 0)?date("m/d/Y",$row['time_added']):"Permanent");
				}

			}else{

				echo "0:Name Not found";
			}

		}else{

			echo "0:Name not specified";
		}
		exit;

		break;

//ajax.php?mode=dnc&action=lookup_campaign_number&value=blabla&campaign=
	case 'lookup_campaign_number':

		$campaign = trim($_REQUEST['campaign']);

		$rowarr = $_SESSION['list_tools']->lookupCampaignNumber($_REQUEST['value'], $campaign);


		if(count($rowarr) > 0){

			foreach($rowarr as $row){

				echo "1:";

				//(($row['time_added'] > 0 && ($row['campaign_code'] != '[ALL]' || $row['dnc_type'] != 'DNC'))?date("m/d/Y",$row['time_added']):"Permanent");

				// DETECT LITIGATOR/PERMANENT DNC
//				if($row['campaign_code'] == '[ALL]' && $row['dnc_type'] == 'DNC'){
//
//					echo "Permanent:";
//
//				}else if($row['campaign_code'] == '[ALL]' && $row['dnc_type'] == 'NIX'){
//
//					echo "[NIX]:";
//
//				}else{

					echo $row['campaign_code'].":";

					echo $row['dnc_type'].":";

//				}

				echo date("m/d/Y",$row['time_added']);


				echo "\n";
			}

		}else{

			echo "0:Number Not found";
		}
		exit;


		break;

	case 'lookup_number':

		$row = $_SESSION['list_tools']->lookupNumber($_REQUEST['value']);


		if($row){

			echo "1:".(($row['time_added'] > 0)?date("m/d/Y",$row['time_added']):"Permanent").":".$row['state'];

		}else{

			echo "0:Number Not found";
		}
		exit;

		break;

	}



	break;

case 'generate_auth_key':

	include_once($_SESSION['site_config']['basedir']."db.inc.php");
	include_once($_SESSION['site_config']['basedir']."utils/db_utils.php");
	include_once($_SESSION['site_config']['basedir']."utils/functions.php");
	include_once($_SESSION['site_config']['basedir']."classes/list_tools.inc.php");


	$code = $_SESSION['list_tools']->generateAuthKey();


	echo $code;
	exit;



	break;

// USED FOR PROBLEM SYSTEM
// TO MARK RECORDS ACKNOWLEDGED,SOLVED ETC
case 'mark_record':

	$recid = intval($_POST['record_id']);
	$call_id = intval($_REQUEST['call_id']);

	//print_r($_POST);




	if($call_id > 0){

		$status = $_POST['status'];




		// LOOK UP CALL RECORD

		$dat = array();
		$dat['status'] = $status;
		$dat['time_modified'] = time();
		$cnt = $_SESSION['dbapi']->aedit($call_id, $dat, 'ringing_calls');


		echo $cnt;
		exit;



	}else{

		if($recid > 0){
			if($_POST['problem_acknowledged']){

				$dat = array();
				$dat['problem_acknowledged'] = $_POST['problem_acknowledged'];
				$_SESSION['dbapi']->aedit($recid, $dat, 'lead_tracking');

				echo ("0:Success");

			}else if($_POST['problem_solved']){

				$dat = array();
				$dat['problem_solved'] = $_POST['problem_solved'];
				$_SESSION['dbapi']->aedit($recid, $dat, 'lead_tracking');

				echo ("0:Success");

			}else{

				echo ("-1:Fail:No valid field specified");

			}
		}else{

			echo ("-1:Fail:No valid id specified");

		}

		exit;

	}

	break;


case 'get_group_dropdown':


	$cluster_id = intval($_REQUEST['cluster_id']);


	$res = $_SESSION['dbapi']->query("SELECT user_group,name FROM user_groups WHERE vici_cluster_id='$cluster_id'",1);
	$out = '<UserGroups cluster="'.$cluster_id.'">';
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		$out .= '<UserGroup group="'.htmlentities($row['user_group']).'" name="'.htmlentities($row['name']).'" />';

	}

	$out .= '</UserGroups>';


	echo $out;

	break;

/**
 * Suggest a bind port for an extension
 */
case 'suggest_port':


	// FIND A PORT NOT IN USE

	// MAKE SURE ITS NOT TOO HIGH (port+1 < 65535) && (port + 10001) > 65535


	break;
/**
 * REORDER THE VOICES_FILES
 */
case 'change_order':

	$script_id = intval($_REQUEST['script_id']);
	$file_id = intval($_REQUEST['file_id']);
	$direction = ($_REQUEST['direction']=='up')?"up":"down";



	$file = $_SESSION['dbapi']->voices->getFileByID($file_id);

	// MOVE FILE DOWN
	if($direction == "down"){

		// FIND THE ID OF THE NEXT HIGHEST ORDERNUM, IF IT EXISTS
		$row = $_SESSION['dbapi']->querySQL(
							"SELECT * FROM `".$_SESSION['dbapi']->voices->files_table."` ".
							" WHERE script_id='".$script_id."' ".
							" AND `ordernum` > '".$file['ordernum']."' ".
							" AND `repeat`='no' ".
							" ORDER BY `ordernum` ASC"
							//" ORDER BY `ordernum` ASC, id ASC"
						);
		// SOMETHING HIGHER FOUND, SWAP THEM
		if($row){

			$dat = array();
			$dat['ordernum'] = $row['ordernum'];

			$dat2 = array();
			$dat2['ordernum'] = $file['ordernum'];

			$_SESSION['dbapi']->aedit($file['id'], $dat, $_SESSION['dbapi']->voices->files_table);
			$_SESSION['dbapi']->aedit($row['id'], $dat2, $_SESSION['dbapi']->voices->files_table);

		}


	// MOVE FILE UPPPPP
	}else{

		// FIND THE ID OF THE NEXT LOWEST ORDERNUM, IF IT EXISTS
		$row = $_SESSION['dbapi']->querySQL(
							"SELECT * FROM `".$_SESSION['dbapi']->voices->files_table."` ".
							" WHERE script_id='".$script_id."' ".
							" AND `ordernum` < '".$file['ordernum']."'".
							" AND `repeat`='no' ".
							" ORDER BY `ordernum` DESC"
							//" ORDER BY `ordernum` ASC, id ASC"
						);
		// SOMETHING LOWER FOUND, SWAP THEM
		if($row){

			$dat = array();
			$dat['ordernum'] = $row['ordernum'];

			$dat2 = array();
			$dat2['ordernum'] = $file['ordernum'];

			$_SESSION['dbapi']->aedit($file['id'], $dat, $_SESSION['dbapi']->voices->files_table);
			$_SESSION['dbapi']->aedit($row['id'], $dat2, $_SESSION['dbapi']->voices->files_table);

		}
	}




	break;

/**
 * Added By Jonathan Will - 2/25/2016
 *
 */
case 'check_user_exists':

	if(!checkAccess('users')){

		echo "-1:NO ACCESS\n";
		exit;
	}


	$username = trim($_REQUEST['username']);


	list($id) = $_SESSION['dbapi']->queryROW("SELECT id FROM users WHERE username='".mysqli_real_escape_string($_SESSION['dbapi']->db,$username)."' LIMIT 1");

	if($id > 0){

		echo "1:User Already Exists!";

	}else{

		echo "0:User not found";

	}


	break;


/**
 * Check to see if the selected keystroke is already in use by that campaign+voice+screen_num
 * $script_id is used to make sure its not the same one we are already editing
 */
case 'check_keys':

	$script_id = intval($_REQUEST['script_id']);


	$campaign_id = intval($_REQUEST['campaign_id']);
	$keys = $_REQUEST['keys'];
	$voice_id = intval($_REQUEST['voice_id']);
	$screen_num = intval($_REQUEST['screen_num']);

	$reserved_keys = array(
// 						"31",
// 						"32",
// 						"33",
// 					//	"q",
// 						",",
// 						"+",
// 						"9",
//						"0"
					);



	$allowed = true;

	$key_arr = str_split($keys);

	foreach($key_arr as $key){
		if(in_array($key, $reserved_keys)){
			$allowed = false;
			break;
		}
	}

	// RESERVED KEYS
	if(!$allowed){

		echo "-1";

	}else{



		$sql = "SELECT id FROM `scripts` ".
									" WHERE screen_num='".$screen_num."' ".
									" AND voice_id='".$voice_id."' ".
									" AND campaign_id='".$campaign_id."' ".
									" AND `keys`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$keys)."' ".
									(($script_id > 0)?" AND id != '".$script_id."' ":'');

	//echo $sql;
		// GET ALL SCRIPTS THAT ARE USING THIS COMBINATION
//		$row = $_SESSION['dbapi']->querySQL($sql);


//		if($row['id'] > 0){
//			echo "0";
//		}else{
			echo "1";
//		}
	}


	break;

/**
 * PX LIST TOOL - LOADING BAY
 */
case 'list_upload':


	$warning_msg_stack = array();


	print_r($_POST);
	print_r($_FILES);


	include_once("classes/list_tools.inc.php");




	// UPLOAD THE FILES


	//$_SESSION['list_tools']->list_upload_folder

	// MOVE THEM INTO POSITION, WHERE SERVER SIDE CAN ACCESS
	// OR UPLOAD TO THE PX-LIST-SERVER DIRECTLY

	// ADD TASK RECORD TO TRIGGER TEH PROCESS TO START


	if(count($warning_msg_stack) > 0){


		?><script>

			window.parent.listUploadSuccess('<?=$dat['sound_file']?>',"The following warnings were issued:\n<?

				foreach($warning_msg_stack as $msg){

					echo $msg.'\n';

				}
			?>");

		</script><?


	}else{


		?><script>

			window.parent.listUploadSuccess('<?=$dat['sound_file']?>');

		</script><?



	}


	break;

case 'sound_upload':


	$warning_msg_stack = array();

	unset($dat);

	$voice_id = intval($_REQUEST['voice_id']);
	$script_id = intval($_REQUEST['script_id']);
	$file_description = $_REQUEST['file_description'];


	$voice = $_SESSION['dbapi']->voices->getByID($voice_id);



	## UPLOADING SOUND FILE
	if($_FILES['sound_file']['name']){


		switch($_FILES['sound_file']['type']){
		case "audio/wav":
	 	case "audio/x-wav":
	 	case "audio/wave":
	 	case "audio/x-pn-wav":

			if($_FILES['sound_file']['size'] < $_SESSION['site_config']['upload_max_size']){

				if ($_FILES['sound_file']['error'] > 0){

					$warning_msg_stack[] = "Upload Return Code: ".$_FILES['sound_file']['error'];

				} else {

					## GENERATE A UNIQUE FILENAME
					$name = generateFilename($_FILES['sound_file']['name']);

					$folder = $_SESSION['site_config']['upload_dir'].'voice-'.$voice['id'].'/';


					if(!is_dir($folder)){

						mkdir ( $folder , 0775 , true);

					}


					## BUILD FULL OUTPUT PATH
					$output_filename = $folder.$name;

					## SANITY CHECK - MAKE SURE IT DOESN'T EXIST
					if (file_exists($output_filename)){

						$warning_msg_stack[] = " File: ".$name." already exists.";

					## MOVE FILE TO FINAL DESTINATION, ADD TO DB RECORD
					} else {


						if(move_uploaded_file($_FILES['sound_file']['tmp_name'],$output_filename)){

							## RELATIVE PATH - WEB/URLS
							$dat['voice_id'] = $voice['id'];
							$dat['script_id'] = $script_id;
							$dat['description'] = $file_description;
							$dat['file'] = $output_filename;

							list($dat['ordernum']) = $_SESSION['dbapi']->queryROW(
															"SELECT MAX(ordernum) FROM ".$_SESSION['dbapi']->voices->files_table.
															" WHERE script_id='".$script_id."' AND `repeat`='no'");
							## INCREMENT MAX ORDERNUM
							$dat['ordernum']++;

							switch($_REQUEST['upload_mode']){
							case 'script':
							default:

								$dat['repeat'] = 'no';
								break;
							case 'repeat-short':
								$dat['repeat'] = 'short';
								break;
							case 'repeat-long':
								$dat['repeat'] = 'long';
								break;
							case 'repeat-question':
								$dat['repeat'] = 'question';
								break;
							}


							if($dat['repeat'] != 'no'){

								$dat['ordernum'] = 2147483646;

							}


						}else{
							$warning_msg_stack[] = " Upload: Error moving sound file to output directory ($output_filename)";

						}

					}
				}
			}else{

				$warning_msg_stack[] = " Upload: File too large.";

			}

			break;
		default:

			$warning_msg_stack[] = " Invalid file type. (".$_FILES['sound_file']['type'].")";

			break;
		}

	}

	if($dat){

		$_SESSION['dbapi']->aadd($dat,$_SESSION['dbapi']->voices->files_table);

		$fileid = mysqli_insert_id($_SESSION['dbapi']->db);




		if(count($warning_msg_stack) > 0){


			?><script>

				window.parent.accountUploadSuccess('<?=$dat['sound_file']?>',"The following warnings were issued:\n<?

					foreach($warning_msg_stack as $msg){

						echo $msg.'\n';

					}
				?>");

			</script><?


		}else{


			?><script>

				window.parent.accountUploadSuccess('<?=$dat['sound_file']?>');

			</script><?



		}

	}else{

		?><script>

			window.parent.accountUploadFailed(-1060, "The following errors were reported:\n<?

				foreach($warning_msg_stack as $msg){

					echo $msg.'\n';

				}
			?>");

		</script><?

	}


	break;
}


