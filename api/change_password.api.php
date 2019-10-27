<?



class API_ChangePassword{

	var $table	= 'users';			## Classes main table to operate on

	var $password_field = 'password';

	var $previous_password_field = 'previous_passwords';

	var $num_previous_passwords = '4'; # NUMBER OF PREVIOUS PASSWORDS TO STORE

	function handleAPI(){

		# VARIABLE DECLARATIONS
		$changedpw_time = time();

		// SANITY CHECK FOR USER ID, THEN CHECK FOR FIELDS IN THE PASSWORD FORM
		if($_SESSION['user']['id'] > 0 && isset($_REQUEST['changing_password']) && $_REQUEST['pass_hash'] && $_REQUEST['old_pass_hash']){


			$new_pass = trim($_REQUEST['pass_hash']);
			$old_pass = trim($_REQUEST['old_pass_hash']);


			# PREVIOUS PASSWORD CHECK FOR PRIV 4 AND HIGHER
			if($_SESSION['user']['priv'] >= 4){

				# CHECK PREVIOUS PASSWORD MATCH
				if($this->checkPreviousPasswords($_SESSION['user']['previous_passwords'],$new_pass)){

					# ERROR OUT
					$_SESSION['api']->errorOut("New password matches a previous password used, please try again.");
					exit;

				}

			} 

			# UPDATE NEWPASS
			$result = $this->updatePassword($new_pass, $old_pass, $_SESSION['user']['id'],$changedpw_time);

			# CHECK RESULT OF NEWPASS UPDATE
			if($result){

				# OUTPUT SUCCESSFUL UPDATE
				$_SESSION['api']->outputEditSuccess($_SESSION['user']['id']);

				# UPDATE CHANGEDPW TIME IN SESSION TOO
				$_SESSION['user']['changedpw_time'] = $changedpw_time;

				# LOG PASSWORD CHANGE ACTION
				logAction('change_password', 'users', $_SESSION['user']['id'], "");


				# UPDATE PREVIOUS PASSWORD DB FIELD AND SESSION
				$this->storePreviousPassword($_SESSION['user']['id'],$_SESSION['user']['previous_passwords'],$old_pass);



				exit;

			}else{

				# ERROR OUT	
				$_SESSION['api']->errorOut("No changes made. Check pw.");
				exit;

			}
			
		}


	}



	function updatePassword($new_hash, $old_hash,$user_id,$changedpw_time){

		return execSQL("UPDATE `".$this->table."` SET `".$this->password_field."`='".mysqli_real_escape_string($_SESSION['db'],$new_hash)."',`changedpw_time`='".$changedpw_time."' WHERE id='".intval($user_id)."' AND `".$this->password_field."`='".mysqli_real_escape_string($_SESSION['db'],$old_hash)."' ");

	}


	function checkPreviousPasswords($password_hashes,$new_pass){

		# CREATE ARRAY FROM PREVIOUS PASSWORDS
		$previous_password_hashes = explode("\n",$password_hashes);

		# CHECK PREVIOUS PASSWORDS TO RUN AGAINST NEWPASS
		if(count($previous_password_hashes)>0){

			# LOOP THROUGH PREVIOUS PASSWORDS AND COMPARE WITH NEWPASS
			foreach($previous_password_hashes as $previous_password){

				# IF PREVIOUS PASSWORD HASH MATCHES NEWPASS HASH OF HASH RETURN TRUE
				if(md5($new_pass) == $previous_password){

					return true;

				}

			}

		}

		# RETURN FALSE ON NO MATCHES OR BLANK PREVIOUS PASSWORDS
		return false;

	}


	function storePreviousPassword($user_id,$password_hashes,$old_pass){

		# CREATE ARRAY FROM PREVIOUS PASSWORDS
		$previous_password_hashes = explode("\n",$password_hashes);

		# ADD NEW PASS TO END OF PREVIOUS PASSWORD ARRAY
		array_push($previous_password_hashes,md5($old_pass));

		# COUNT PREVIOUS PASSWORD ARRAY AND POP OLDEST PREVIOUS PASSWORD
		if(count($previous_password_hashes) > intval($this->num_previous_passwords)){

			array_shift($previous_password_hashes);

		}

		# CREATE ARRAY FOR SESSION AND DB STORAGE
		$new_previous_passwords = implode("\n",$previous_password_hashes);

		# UPDATE SESSION AND DB WITH NEW PREVIOUS PASSWORD LIST
		$_SESSION['user']['previous_passwords'] = $new_previous_passwords;

		$update_db = execSQL("UPDATE `".$this->table."` SET `".$this->previous_password_field."`='".mysqli_real_escape_string($_SESSION['db'],$new_previous_passwords)."' WHERE id='".intval($user_id)."'");

	}

}

