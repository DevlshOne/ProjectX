<?



class API_Users{

	var $xml_parent_tagname = "Users";
	var $xml_record_tagname = "User";

	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";





	function checkViciUserIDExists($user_id){

		list($id) = queryROW(

				"SELECT user_id FROM vicidial_users WHERE user_id='".mysqli_real_escape_string($_SESSION['db'],$user_id)."' "

					);
		return ($id > 0)?$id:-1;
	}

	function findViciUserID($username){

		list($id) = queryROW(

				"SELECT user_id FROM vicidial_users WHERE user='".mysqli_real_escape_string($_SESSION['db'],$username)."' "

					);
		return $id;
	}


	function addUsersToVici($users, $cluster_id, $main_group, $office, $vici_template_id){



		// ADD USERS TO THE VICI SERVERS FIRST

		/// CONNECT TO VICI CLUSTER BY ID
		//// GET CLUSTERS INDEX NUMBER BY ID
		$dbidx = getClusterIndex($cluster_id);
		//// TELL IT TO CONNECT TO VICI
		connectViciDB($dbidx);


		//echo $cluster_id.' '.$dbidx;

		$user_arr = array();
		foreach($users as $idx=>$userid){

			$user = $_SESSION['dbapi']->users->getByID($userid);

			$user_arr[$idx] = $user;

			/// CHECK TO SEE IF USER ALREADY EXISTS
			$vici_user_id = $this->findViciUserID($user['username']);

			/// IF THE USER ALREADY EXISTS, EDIT INSTEAD OF ADD
			if($vici_user_id > 0){


				/// SAFE TO ASSUME ITS THE SAME USER?? 2-1-2016
// [17:23] <@Hurricane> yes should not hurt as like I said they dont normally reuse them forawhile due to collections are tracked by initals
// [17:23] <@Hurricane> so they will never have (or shouldnt) 2 people on different servers withsame initals

				$dat = array();
				$dat['user'] = $user['username'];
				$dat['pass'] = $user['vici_password'];
				$dat['full_name'] = trim($user['first_name'].' '.$user['last_name']);
				$dat['user_group'] = $main_group;

				switch($user['priv']){
				case 5: // ADMIN
					$dat['user_level'] = 9;
					break;
				case 4: // MANAGER
					$dat['user_level'] = 8;
					break;
				default:
					$dat['user_level'] = 1;
					break;
				}

				aeditByField('user',$user['username'],$dat,'vicidial_users');
				//aedit($vici_user_id, $dat,'vicidial_users');


				$user_arr[$idx]['vici_user_id'] = $vici_user_id;

			}else{
				/// INSERT THE VICI USER RECORD, GRAB ITS ID

				$dat = array();
				$dat['user'] = $user['username'];
				$dat['pass'] = $user['vici_password'];
				$dat['full_name'] = trim($user['first_name'].' '.$user['last_name']);
				$dat['user_group'] = $main_group;

				switch($user['priv']){
				case 5: // ADMIN
					$dat['user_level'] = 9;
					break;
				case 4: // MANAGER
					$dat['user_level'] = 8;
					break;
				default:
					$dat['user_level'] = 1;
					break;
				}


				aadd($dat,'vicidial_users');

				$vici_user_id = mysqli_insert_id($_SESSION['db']);

				$user_arr[$idx]['vici_user_id'] = $vici_user_id;
			}

			//echo $vici_user_id;

			/// SET THE USER GROUPS AND OFFICE? (vici_user_groups)
			/// ^^ DONT THINK I NEED TO. VICI USER GROUPS SHOULD ALREADY BE SETUP, WITH CORRECT OFFICE

			/// APPLY VICI TEMPLATE
			$_SESSION['vici_templates']->applyTemplate($vici_template_id, $cluster_id, $userid, $vici_user_id);

			logAction('bulk_add', 'users', $userid, "Added users: ".$userid+" to cluster ".$cluster_id." (".getClusterName($cluster_id).")");

		}


		// THEN ADD TO THE PX's 'user_group_translations' TABLE
		/// CONNECT BACK TO THE PX DB
		connectPXDB();

		/// LOOP THROUGH USERS AGAIN
		foreach($user_arr as $user){

			/// ADD THEM TO TABLE
			$dat = array();
			$dat['user_id'] = intval($user['id']);
			$dat['vici_user_id'] = intval($user['vici_user_id']);
			$dat['cluster_id'] = $cluster_id;
			$dat['group_name'] = $main_group;
			$dat['office'] = $office;

			aadd($dat, 'user_group_translations');
		}




		return 1;
	}



	function emergencyLogoutFromVici($userid, $cluster_id){


		if(!checkAccess('users')){

			return -1;
		}

		// LOAD THE USER FROM THE DB
		$user = $_SESSION['dbapi']->users->getByID($userid);

		$cluster = getClusterRow($cluster_id);

		// PULL THE WEB URL FROM SITE CONFIG STACK
		$vici_ip = $cluster['web_ip'];//getClusterWebHost($cluster_id);


		// USER ID OR NAME NOT FOUND
		if(!$user['id'] || !trim($user['username'])){
			return -2;
		}

		// CURL POST TO VICIDIAL
		$url = "http://".$vici_ip."/vicidial/user_status.php";


		$post = array(
			"DB"	=>"",
			"user"	=>trim($user['username']),
			"stage"	=>"log_agent_out"
		);

//echo $url."\n";



		$ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);

	    curl_setopt($ch, CURLOPT_USERPWD, $cluster['web_api_user'] . ":" . $cluster['web_api_pass']);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post); // $fields_string);

	    $data = curl_exec($ch);
	    $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $response_error = curl_error($ch);

	    ## CLOSE CURL SESSION
	    curl_close($ch);


//echo $data."\n\n";

		// DETECT THAT THEY ARE NOT LOGGED IN
		if(stripos($data, "is not logged in") > -1){

			return "0:Error - User is not logged in.";

		}


		// DETECT SUCCESS
		if(stripos($data, "has been emergency logged out") > -1 ){ //|| stripos($data, "|GOOD") > -1

			return "1:Success\n";

		}


		return "0:Error - Unknown problem occurred - ".trim($data);





//		echo "Result: $response_code : $response_error\n";

//		print_r($data);

//		if($response_code != 200){
//
//			echo "0:Error - ".$response_code." ".$response_error."\n";
//
//		}else{
//
//			echo "1:Success\n";
//
//		}

	}

	function deleteUserFromVici($userid, $cluster_id){


		if(!checkAccess('users')){

			return -1;
		}



	// LOAD USER INFORMATION

		$user = $_SESSION['dbapi']->users->getByID($userid);

		$grouptrans = $_SESSION['dbapi']->querySQL("SELECT * FROM user_group_translations WHERE user_id='".intval($userid)."' AND cluster_id='".intval($cluster_id)."'");

	// REMOVE USER FROM VICI_USERS

		/// DETERMINE WHAT THE CLUSTER INDEX IS
		$idx = getClusterIndex($cluster_id);

		/// CONNECT TO VICI CLUSTER
		connectViciDB($idx);

		// DELETE FROM VICI_USERS
		execSQL("DELETE FROM vicidial_users WHERE user_id='".$grouptrans['vici_user_id']."' ");


	// REMOVE CLUSTER RECORD FROM PX user_group_translations

		// CONNECT BACK TO PX
		connectPXDB();

		// INSTEAD OF JUST DELETING GROUP BY RECORD ID FROM ABOVE, DO A SWEEP TO MAKE SURE THERE ARE NOT DUPE RECORDS FOR THAT USER AND CLUSTER (CURRENTLY A BUG)
		execSQL("DELETE FROM user_group_translations WHERE user_id='".intval($user['id'])."' AND cluster_id='".intval($cluster_id)."' ");


		return 1;

	}





	function handleAPI(){


		if(!checkAccess('users')){


			$_SESSION['api']->errorOut('Access denied to Users');

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

		case 'edit_offices':

			$id = intval($_REQUEST['adding_user_offices']);


			$dat = array('allow_all_offices'=>'no');

			$_SESSION['dbapi']->aedit($id, $dat, 'users');


			// NUKE ALL USERS CURRENT OFFICES
			$_SESSION['dbapi']->execSQL("DELETE FROM `users_offices` WHERE user_id='".$id."'");

			// SAVE A NEW SET

			foreach($_REQUEST['sel_offices'] as $ofc_id){

				$ofc_id = intval($ofc_id);

				$dat = array(
					'user_id' => $id,
					'office_id' => $ofc_id
				);

				$_SESSION['dbapi']->aadd($dat, 'users_offices');

				list($ofcname) = $_SESSION['dbapi']->queryROW("SELECT `name` FROM `offices` WHERE id='".mysqli_real_escape_string($_SESSION['dbapi']->db,$ofc_id)."'");
				echo $ofc_id.' - '.$ofcname.'<br />';

			}

			exit;


			break;

		case 'delete':

			$id = intval($_REQUEST['id']);


			## CANNOT DELETE YOURSELF
			if($id == $_SESSION['user']['id']){

				$_SESSION['api']->errorOut('You cannot delete yourself.', true, -21);
				return;
			}

			$row = $_SESSION['dbapi']->users->getByID($id);


			## CHECK TO MAKE SURE THEIR IS ANOTHER ADMIN LEFT (cannot delete last admin)
			if($row['priv'] == 5){
				list($test) = $_SESSION['dbapi']->queryROW("SELECT COUNT(id) FROM users WHERE priv=5 AND enabled='yes'");


				if($test <= 1){
					## CANNOT DELETE LAST ADMIN

					$_SESSION['api']->errorOut('You cannot delete the last remaining administrator.', true, -22);
					return;
				}

			}


			// GET ALL THE VICI CLUSTERS THE USER IS ON
			$res = $_SESSION['dbapi']->query("SELECT * FROM `user_group_translations` WHERE user_id='$id'",1);

			$cnt=0;
			while($r2 = mysqli_fetch_array($res, MYSQLI_ASSOC)){
				// REMOVE USER FROM THOSE VICI CLUSTERS
				$this->deleteUserFromVici($id, $r2['cluster_id']);
				$cnt++;
			}


			// FINALLY, MARK TEH USER RECORD AS DELETED (DOESNT ACTUALLY DELETE)
			$_SESSION['dbapi']->users->delete($id);


			logAction('delete', 'users', $id, "Deleted from PX and $cnt Vici servers");


			$_SESSION['api']->outputDeleteSuccess();


			break;

		case 'view':


			$id = intval($_REQUEST['id']);

			$row = $_SESSION['dbapi']->users->getByID($id);




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


		case 'nuke_all_lockouts':


			$cnt=0;
			foreach($_SESSION['site_config']['db'] as $idx=>$db){

				//$db['cluster_id'] $db['name'];

				connectViciDB($idx);

				$sql = " UPDATE vicidial_users SET failed_login_count=0 WHERE 1 ";
				$cnt += execSQL($sql);
			}


			logAction('nuke_lockouts', 'users', 0, "Reset vicidial lockouts for $cnt users");

			$_SESSION['api']->outputEditSuccess(1);


			break;


		case 'bulk_add_users':

/**
 * Array
(
    [md5sum] => 912ec803b2ce49e4a541068d495ab570
    [bulk_add_usernames] => test|
    [priv] => 2
    [feature_id] =>
    [newpass] =>
    [confpass] =>
    [vici_password] => asdf
    [primary_user_group] => ADVF-SOUTH-AM
    [bulk_addtovici] => 1
    [av_cluster_id] => 1
    [av_main_group_dd] => ADVF-SOUTH-AM
    [av_office_id] => 90
    [av_template_id] => 1
)

 */

			//print_r($_POST);exit;


			$user_arr = preg_split("/\|/", $_POST['bulk_add_usernames'], -1, PREG_SPLIT_NO_EMPTY);
			$name_arr = preg_split("/\|/", $_POST['bulk_add_firstnames'], -1, PREG_SPLIT_NO_EMPTY);
			$pass = trim($_POST['md5sum']);
			$priv = intval($_POST['priv']);
			$user_id_stack = array();

			$cluster_id = intval($_POST['av_cluster_id']);

			$main_group = trim($_POST['av_main_group_dd']);
			$office = trim($_POST['av_office_id']);

			$vici_template_id = intval($_POST['av_template_id']);



			foreach($user_arr as $uidx=>$username){

				$username = trim($username);


				$dat = array();
				$dat['username'] = $username;

				list($dat['first_name'],$dat['last_name']) = preg_split("/\s/", $name_arr[$uidx], 2, PREG_SPLIT_NO_EMPTY);

				$dat['priv'] = $priv;

				$dat['user_group'] = trim($_POST['primary_user_group']);


				## ONLY CHANGE PASSWORD WHEN ITS PROVIDED
				if($pass == '-1'){

					$dat['password'] = null;

				}else if($pass){

					$dat['password'] = $pass;

				}

				$dat['vici_password'] = trim($_POST['vici_password']);


				if($dat['priv'] == 4){

					$dat['feature_id'] = intval($_POST['feature_id']);

				}

				$dat['createdby_time'] = time();
				$dat['createdby_userid'] = $_SESSION['user']['id'];

				if($_SESSION['dbapi']->users->userExists($username)){

					///jsAlert("ERROR: Cannot add. This username appears to already exist.");
					$_SESSION['api']->errorOut("Cannot add '".$username."'. This username appears to already exist.", true, -114);

				}else{

					$_SESSION['dbapi']->aadd($dat,$_SESSION['dbapi']->users->table);
					$id = mysqli_insert_id($_SESSION['dbapi']->db);

					$user_id_stack[] = $id;

					// ## GENERATE UNIQUE LOGIN CODE
					// unset($edit);
					// $unique_string = $id.$dat['username'].$dat['password'].uniqid('CCI',true);
					// $edit['login_code'] = md5($unique_string);
					// $_SESSION['dbapi']->aedit($id,$edit,$_SESSION['dbapi']->users->table);

				}




			}



			if($_REQUEST['bulk_addtovici']){

				// ALL CLUSTERS
				if(!$cluster_id){

					foreach($_SESSION['site_config']['db'] as $idx=>$db){



						$result += $this->addUsersToVici($user_id_stack, $db['cluster_id'], $main_group, $office, $vici_template_id);

					}


				}else{

					$result = $this->addUsersToVici($user_id_stack, $cluster_id, $main_group, $office, $vici_template_id);

				}

			}



			logAction('bulk_add', 'users', 0, "Added users: ".implode(",", $user_arr));




			$_SESSION['api']->outputEditSuccess(1);



			break;

		case 'bulk_operations':


			$user_arr = $_POST['add_to_users'];


			$warning_output = '';








			if($_POST['bulk_group']){

				$cluster_id = intval($_POST['cluster_id']);
				$new_group = trim($_POST['new_group_dd']);



				// ALL CLUSTERS
				if(!$cluster_id){

					foreach($_SESSION['site_config']['db'] as $idx=>$db){

						 //$db['cluster_id'], $main_group, $office, $vici_template_id);

						$cluster_id = $db['cluster_id'];

						foreach($user_arr as $user_id){

							$user = $_SESSION['dbapi']->users->getByID($user_id);

							// FIND USER GROUP TRANSLATION RECORD, TO SEE IF THE USER EVEN ON THAT CLUSTER
							$trans = $_SESSION['dbapi']->querySQL("SELECT * FROM `user_group_translations` ".
															" WHERE user_id='".$user['id']."' AND cluster_id='$cluster_id' ");

							if($trans){

								// DELETE USERS RECORDS FROM "USER GROUP TRANSLATIONS"
								/// ON SECOND THOUGHT, NUKE THE WHOLE FUCKING TABLE FOR THAT USER AND CLUSTER, TO ENSURE NO MIX UPS
								$_SESSION['dbapi']->execSQL("DELETE FROM `user_group_translations` WHERE user_id='".$user['id']."' AND cluster_id='$cluster_id'");


								// GET INDEX OF VICI CLUSTER BY ID
								$idx = getClusterIndex($cluster_id);

								// CONNECT TO VICI CLUSTER
								connectViciDB($idx);

								// EDIT THE USERS GROUP ON THE VICIDIAL SERVER
								execSQL("UPDATE `vicidial_users` SET user_group='$new_group' WHERE user_id='".$trans['vici_user_id']."' ");


								// ADD NEW TRANSLATE RECORD
								$dat=array();
								$dat['user_id'] = $user['id'];
								$dat['cluster_id'] = $cluster_id;
								$dat['vici_user_id'] = $trans['vici_user_id'];
								$dat['group_name'] = $new_group;
								$dat['office'] = $trans['office']; // COPY OFFICE FROM OLD RECORD, SINCE ITS A GROUP SPECIFIC FIELD, NOT A USER

								$_SESSION['dbapi']->aadd($dat, 'user_group_translations');

								logAction('bulk_group', 'users',$user['id'], "Change group to $new_group on cluster $cluster_id for user: ".$user['id']);


							// ALTERNATIVE PUNISHMENT
							}else{

								// ERROR OUT SAYING THE USER DOESNT EXIST ON THAT CLUSTER?
								$warning_output .= "User ".$user['username']." #".$user['id']." doesn't exist on vici cluster $cluster_id (".getClusterName($cluster_id).")\n";

								// OR ADD THE USER TO THE CLUSTER?


							}

						}





					}


				// SIGNLE CLUSTER MODE
				}else{


					foreach($user_arr as $user_id){

						$user = $_SESSION['dbapi']->users->getByID($user_id);

						// FIND USER GROUP TRANSLATION RECORD, TO SEE IF THE USER EVEN ON THAT CLUSTER
						$trans = $_SESSION['dbapi']->querySQL("SELECT * FROM `user_group_translations` ".
														" WHERE user_id='".$user['id']."' AND cluster_id='$cluster_id' ");

						if($trans){

							// DELETE USERS RECORDS FROM "USER GROUP TRANSLATIONS"
							/// ON SECOND THOUGHT, NUKE THE WHOLE FUCKING TABLE FOR THAT USER AND CLUSTER, TO ENSURE NO MIX UPS
							$_SESSION['dbapi']->execSQL("DELETE FROM `user_group_translations` WHERE user_id='".$user['id']."' AND cluster_id='$cluster_id'");


							// GET INDEX OF VICI CLUSTER BY ID
							$idx = getClusterIndex($cluster_id);

							// CONNECT TO VICI CLUSTER
							connectViciDB($idx);

							$sql = "UPDATE `vicidial_users` SET user_group='$new_group' WHERE user_id='".$trans['vici_user_id']."' ";

							//echo $sql;
							// EDIT THE USERS GROUP ON THE VICIDIAL SERVER
							execSQL($sql);


							// ADD NEW TRANSLATE RECORD
							$dat=array();
							$dat['user_id'] = $user['id'];
							$dat['cluster_id'] = $cluster_id;
							$dat['vici_user_id'] = $trans['vici_user_id'];
							$dat['group_name'] = $new_group;
							$dat['office'] = $trans['office']; // COPY OFFICE FROM OLD RECORD, SINCE ITS A GROUP SPECIFIC FIELD, NOT A USER

							$_SESSION['dbapi']->aadd($dat, 'user_group_translations');


							logAction('bulk_group', 'users',$user['id'], "Change group to $new_group on cluster $cluster_id for user: ".$user['id']);//.implode(",", $user_arr));

						// ALTERNATIVE PUNISHMENT
						}else{

							// ERROR OUT SAYING THE USER DOESNT EXIST ON THAT CLUSTER?
							$warning_output .= "User ".$user['username']." #".$user['id']." doesn't exist on vici cluster $cluster_id (".getClusterName($cluster_id).")\n";

							// OR ADD THE USER TO THE CLUSTER?


						}

					}
				}

				connectPXDB();

//				logAction('bulk_group', 'users', 0, "Change group to $new_group on cluster $cluster_id for users: ".implode(",", $user_arr));

			}


			if($_POST['bulk_addtovici']){

				$cluster_id = intval($_POST['av_cluster_id']);

				$main_group = trim($_POST['av_main_group_dd']);
				$office = trim($_POST['av_office_id']);

				$vici_template_id = intval($_POST['av_template_id']);

				$users_array = $_POST['add_to_users'];

				// ALL CLUSTERS
				if(!$cluster_id){

					foreach($_SESSION['site_config']['db'] as $idx=>$db){



						$result += $this->addUsersToVici($users_array, $db['cluster_id'], $main_group, $office, $vici_template_id);

					}


				}else{

					$result = $this->addUsersToVici($users_array, $cluster_id, $main_group, $office, $vici_template_id);

				}

				logAction('bulk_addtovici', 'users', 0, "Added users to vici: ".implode(",", $users_array));
			}




			if($_POST['bulk_featureset']){

				$feature_id = intval($_REQUEST['feature_id']);


				foreach($user_arr as $user_id){

					$changed = $_SESSION['dbapi']->execSQL("UPDATE `users` SET feature_id='$feature_id' WHERE id='$user_id'");

					if($changed > 0){
						logAction('bulk_featureset', 'users', $user_id, "Change feature set to $feature_id for: ".$user_id);
					}

				}


//				logAction('bulk_featureset', 'users', -1, "Change feature set to $feature_id for: ".implode(",", $user_arr));

			}


			if($_POST['bulk_vicitemplate']){


				$cluster_id = intval($_REQUEST['template_cluster_id']);
				$template_id = intval($_REQUEST['vici_template_id']);

				//$warning_output .= "Applying Template $template_id to $cluster_id\n";

				foreach($user_arr as $user_id){

					$user = $_SESSION['dbapi']->users->getByID($user_id);

					// FIND USER GROUP TRANSLATION RECORD, TO SEE IF THE USER EVEN ON THAT CLUSTER
					$trans = $_SESSION['dbapi']->querySQL("SELECT * FROM user_group_translations ".
													" WHERE user_id='".$user['id']."' AND cluster_id='$cluster_id' ");

					if($trans){

						// UPDATE THE USERS TRANSLATION RECORD TO HOLD TEH NEW TEMPLATE ID
						$dat = array('vici_template_id'=>$template_id);
						$_SESSION['dbapi']->aedit($trans['id'], $dat, 'user_group_translations');

						$_SESSION['vici_templates']->applyTemplate($template_id, $cluster_id, $user['id'], $trans['vici_user_id']);

						logAction('bulk_vicitemplate', 'users',$user_id, "Change vici template to $template_id for: ".$user_id);

					}else{
						$warning_output .= "User ".$user['username']." #".$user['id']." doesn't exist on vici cluster $cluster_id (".getClusterName($cluster_id).")\n";
					}

				}

//				logAction('bulk_vicitemplate', 'users', -1, "Change vici template to $template_id for: ".implode(",", $user_arr));

			}


			/// MAKE SURE THEY ARE CHANGING TO LESS THAN MANAGER PRIV, AKA CALLER OR TRAINING
			if($_POST['bulk_priv'] && intval($_POST['priv']) < 4){


				$newpriv = intval($_POST['priv']);

				$dat=array();
				$dat['priv'] = $newpriv;

				foreach($user_arr as $user_id){



					$_SESSION['dbapi']->aedit($user_id, $dat, 'users');

					logAction('bulk_priv', 'users', $user_id, "Change priv to $newpriv for: ".$user_id);

				}

//				logAction('bulk_priv', 'users', -1, "Change priv to $newpriv for: ".implode(",", $user_arr));

			}



			// RESET THE SELECTED USERS VICI LOGINS, TO FIX LOCKOUTS.
			if($_POST['bulk_login_reset']){

				$userdata_arr = array();
				$usersql = "AND `user` IN (";
				$x=0;
				foreach($user_arr as $user_id){

					$userdata_arr[$user_id] = $user = $_SESSION['dbapi']->users->getByID($user_id);

					$usersql .= ($x++ > 0)?',':'';
					$usersql .= "'".mysqli_real_escape_string($_SESSION['db'],$user['username'])."'";


					logAction('bulk_login_reset', 'users', $user_id, "Reset vici lockout for: ".$user_id);
				}

				$usersql .= ")";

				foreach($_SESSION['site_config']['db'] as $idx=>$db){

					//$db['cluster_id'] $db['name'];

					connectViciDB($idx);

					$sql = " UPDATE vicidial_users SET failed_login_count=0 WHERE 1 ".$usersql;
					execSQL($sql);
				}

//				logAction('bulk_login_reset', 'users', -1, "Reset vici lockout for: ".implode(",", $user_arr));

			}


			if($warning_output){

				$_SESSION['api']->outputEditSuccess(1, $warning_output);

			}else{


				$_SESSION['api']->outputEditSuccess(1);


			}


			exit;




			break;

		case 'add_to_vici':



			//print_r($_POST);
			$cluster_id = intval($_POST['vici_cluster_id']);

			$main_group = trim($_POST['main_group_dd']);
			$office = trim($_POST['office_id']);

			$vici_template_id = intval($_POST['vici_template_id']);

			$users_array = $_POST['add_to_users'];

			// ALL CLUSTERS
			if(!$cluster_id){

				foreach($_SESSION['site_config']['db'] as $idx=>$db){



					$result += $this->addUsersToVici($users_array, $db['cluster_id'], $main_group, $office, $vici_template_id);

				}


			}else{

				$result = $this->addUsersToVici($users_array, $cluster_id, $main_group, $office, $vici_template_id);

			}


			if($result > 0){

				$_SESSION['api']->outputEditSuccess($result);
				exit;

			}else{


				$_SESSION['api']->errorOut("Add to vici appears to have failed.",true, $result);

			}

			logAction('add_to_vici', 'users', 0, "Adding to vici cluster $cluster_id: ".implode(",", $users_array));



			break;


		case 'emergency_logout_from_vici':

			$userid = intval($_REQUEST['user_id']);
			$cluster_id = intval($_REQUEST['cluster_id']);


			$result = $this->emergencyLogoutFromVici($userid, $cluster_id);



			echo $result;
			exit;





			break;

		case 'delete_from_vici':


			// CHECK PERMISSIONS?
			// USER PERMISSION ALREADY CHECKED ABOVE, BUT CAN ADD ADDITIONAL VICI FEATURE CONTROL HERE LATER IF DESIRED


			$userid = intval($_REQUEST['user_id']);
			$cluster_id = intval($_REQUEST['cluster_id']);


			// CALL FUNCTION TO HANDLE THE DELETION SUB-PROCESSES
			if($this->deleteUserFromVici($userid, $cluster_id) > 0){

				$_SESSION['api']->outputDeleteSuccess();

			}else{

				$_SESSION['api']->errorOut("Delete from vici appears to have failed.",true, $result);

			}


			logAction('delete_from_vici', 'users', $userid, "Deleteing vici cluster $cluster_id");




			break;

		case 'edit':

			$id = intval($_POST['adding_user']);

			$username = trim($_POST['username']);

			if($id){
				$row = $_SESSION['dbapi']->users->getByID($id);
			}else{
				$row = null;
			}

			if($row['enabled'] == 'no' && intval($_POST['actually_delete_user']) > 0){
				
				$_SESSION['dbapi']->adelete($row['id'],'users');
				
				$_SESSION['api']->outputEditSuccess(-404);
				
				exit;
				
			}
			
			
			unset($dat);
			$dat['username'] = $username;
			$dat['priv'] = intval($_POST['priv']);

			$dat['first_name'] = trim($_POST['first_name']);
			$dat['last_name'] = trim($_POST['last_name']);
//			$dat['work_phone'] = $_POST['work_phone0']."-".$_POST['work_phone1']."-".$_POST['work_phone2'];
//			$dat['cell_phone'] = $_POST['cell_phone0']."-".$_POST['cell_phone1']."-".$_POST['cell_phone2'];

			$dat['default_timezone'] = $_POST['default_timezone'];


			## ONLY CHANGE PASSWORD WHEN ITS PROVIDED
			if($_POST['md5sum'] == '-1'){

				$dat['password'] = null;

			}else if($_POST['md5sum']){

				$dat['password'] = trim($_POST['md5sum']);

				## UPDATE CHANGED PW TIME IF PW WAS PROVIDED
				$dat['changedpw_time'] = time();

			}

			$dat['vici_password'] = trim($_POST['vici_password']);


			if($dat['priv'] == 4){

				$dat['feature_id'] = intval($_POST['feature_id']);

				$dat['allow_all_offices'] = ($_REQUEST['allow_all_offices'] == 'yes')?'yes':'no';

			}



			if($id){

				// MANAGER - FEATURES
				if($dat['priv'] == 4){

//					$dat['feat_config'] = ($_POST['feat_config'])?'yes':'no';
//					$dat['feat_advanced'] = ($_POST['feat_advanced'])?'yes':'no';
//					$dat['feat_reports'] = ($_POST['feat_reports'])?'yes':'no';
//					$dat['feat_problems'] = ($_POST['feat_problems'])?'yes':'no';
//					$dat['feat_messages'] = ($_POST['feat_messages'])?'yes':'no';
//					$dat['feat_agent_tracker'] = ($_POST['feat_agent_tracker'])?'yes':'no';


				}

				// FORCE A PASSWORD RESET
				if($_REQUEST['force_change_password']){
					$dat['changedpw_time'] = 0;
				}

				$dat['modifiedby_time'] = time();
				$dat['modifiedby_userid'] = $_SESSION['user']['id'];

				$dat['login_code'] = ($_POST['login_api_key'])?trim($_POST['login_api_key']):null;


				$_SESSION['dbapi']->aedit($id,$dat,$_SESSION['dbapi']->users->table);


				$newrow = $_SESSION['dbapi']->users->getByID($id);

				logAction('edit_user', 'users', $id, "Edited user $username Priv ".$dat['priv'], $row, $newrow);


			}else{


				$dat['createdby_time'] = time();
				$dat['createdby_userid'] = $_SESSION['user']['id'];
				
				$dat['modifiedby_time'] = time();
				$dat['modifiedby_userid'] = $_SESSION['user']['id'];
				
				// IF WE'RE NOT FORCING A PASSWORD RESET
				if(!$_REQUEST['force_change_password']){
					## SET CHANGED PW TIME ON USER CREATION
					$dat['changedpw_time'] = time();
				}else{
					$dat['changedpw_time'] = 0;
				}

				if($_SESSION['dbapi']->users->userExists($username)){

					///jsAlert("ERROR: Cannot add. This username appears to already exist.");
					$_SESSION['api']->errorOut("Cannot add. This username appears to already exist.", true, -14);

				}else{

					$_SESSION['dbapi']->aadd($dat,$_SESSION['dbapi']->users->table);
					$id = mysqli_insert_id($_SESSION['dbapi']->db);

					// ## GENERATE UNIQUE LOGIN CODE
					// unset($edit);
					// $unique_string = $id.$dat['username'].$dat['password'].uniqid('CCI',true);
					// $edit['login_code'] = md5($unique_string);
					// $_SESSION['dbapi']->aedit($id,$edit,$_SESSION['dbapi']->users->table);

					$newrow = $_SESSION['dbapi']->users->getByID($id);

				}

				logAction('add_user', 'users', $id, "Added new user $username Priv ".$dat['priv'], $row,$newrow);
			}




			$_SESSION['api']->outputEditSuccess($id);



			break;

		case 'create_api_key':

			# GENERATE A UNIQUE API KEY USING THE SALT FUNCTION WITH LENGTH OF 16 TO RECEIVE 32CHARS
			
			## CHANGED TO A-Za-z0-9 RANDOM STRING, INSTEAD OF HEX
			echo $_SESSION['dbapi']->users->generateSalt(32);
			exit;
			break;

		default:
		case 'list':



			$dat = array();
			$totalcount = 0;
			$pagemode = false;



			$dat['enabled'] = 'yes';


			## THIS COULD HAVE MORE SECURITY/RESTRICTIONS
			if($_REQUEST['account_id']){


				$dat['account_id'] = intval($_REQUEST['account_id']);

			}else{
				$dat['account_id'] = $_SESSION['account']['id'];
			}




			## ID SEARCH
			if($_REQUEST['s_id']){

				$dat['id'] = intval($_REQUEST['s_id']);

			}

			## USERNAME SEARCH
			if($_REQUEST['s_username']){

				$dat['username'] = trim($_REQUEST['s_username']);

			}


			## AGENT NAME SEARCH
			if($_REQUEST['s_name']){

				$dat['name'] = trim($_REQUEST['s_name']);

			}


			## GROUP NAME
			if($_REQUEST['s_group_name']){

				$dat['group_name'] = trim($_REQUEST['s_group_name']);

			}


			## CLUSTER ID
			if($_REQUEST['s_cluster_id']){

				$dat['cluster_id'] = trim($_REQUEST['s_cluster_id']);

			}


			## FEATURE ID
			if($_REQUEST['s_feature_id']){

				$dat['feature_id'] = intval($_REQUEST['s_feature_id']);

			}


			##


			

			if(intval($_REQUEST['s_priv'])){

				$dat['priv'] = intval($_REQUEST['s_priv']);
				
				if($dat['priv'] == -404){
					
					unset($dat['priv']);
					
					$dat['enabled'] = 'no';
					
				}

			}




			## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
			if(isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])){

				$pagemode = true;

				$cntdat = $dat;
				$cntdat['fields'] = 'COUNT(id)';
				list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->users->getResults($cntdat));

				$dat['limit'] = array(
									"offset"=>intval($_REQUEST['index']),
									"count"=>intval($_REQUEST['pagesize'])
								);

			}


			## ORDER BY SYSTEM
			if($_REQUEST['orderby'] && $_REQUEST['orderdir']){
				$dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
			}






			$res = $_SESSION['dbapi']->users->getResults($dat);



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






}


