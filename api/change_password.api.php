<?



class API_ChangePassword{

	var $table	= 'users';			## Classes main table to operate on

	var $password_field = 'password';

	function handleAPI(){

		// SANITY CHECK FOR USER ID, THEN CHECK FOR FIELDS IN THE PASSWORD FORM
		if($_SESSION['user']['id'] > 0 && isset($_REQUEST['changing_password']) && $_REQUEST['pass_hash'] && $_REQUEST['old_pass_hash']){


			$new_pass = trim($_REQUEST['pass_hash']);
			$old_pass = trim($_REQUEST['old_pass_hash']);


			$result = $this->updatePassword($new_pass, $old_pass, $_SESSION['user']['id']);

			if($result){
					$_SESSION['api']->outputEditSuccess($_SESSION['user']['id']);

					logAction('change_password', 'users', $_SESSION['user']['id'], "");

			}else{
						$_SESSION['api']->errorOut("No changes made. Check pw.");
			}
			//echo $result;


			exit;
		}


		//


	}



	function updatePassword($new_hash, $old_hash, $user_id){

		return execSQL("UPDATE `".$this->table."` SET `".$this->password_field."`='".mysqli_real_escape_string($_SESSION['db'],$new_hash)."' WHERE id='".intval($user_id)."' AND `".$this->password_field."`='".mysqli_real_escape_string($_SESSION['db'],$old_hash)."' ");

	}



}

