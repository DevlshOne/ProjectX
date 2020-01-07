<?php


class LoginClass{


	function __construct() {

		$this->handleLoginPost();

	}










	/** handleLoginPost()
	 * Login functions - such as the gui to login, and prolly the post handling codefu too
	 * Requires DBAPI
	 */
	function handleLoginPost(){



		if(isset($_POST['loginbutton']) && isset($_POST['username']) && isset($_POST['md5pass'])){


			//echo "Login POST";
			//print_r($_REQUEST);exit;


			$user = trim($_POST['username']);
			$pass = trim($_POST['md5pass']);

			$kicktourl = ((isset($_POST['kick_to']))?trim($_POST['kick_to']):'');

//			$row = $_SESSION['dbapi']->querySQL("SELECT users.*,accounts.enabled AS account_enabled,accounts.parent_account_id FROM users ".
//					" INNER JOIN accounts ON users.account_id = accounts.id ".
//					" WHERE users.enabled='yes' ". // AND accounts.enabled='yes' // MOVING THIS TO PHP, SO WE CAN ALERT ACCORDINGLY
//					" AND users.username='".mysqli_real_escape_string($_SESSION['dbapi']->db,$user)."' ".
//					" AND users.password='".mysqli_real_escape_string($_SESSION['dbapi']->db,$pass)."' ".
//					" LIMIT 1 "
//					);
//
//			## CHECK THAT ACCOUNT AND PARENT ACCOUNTS ARE ENABLED
//			$account_enabled = (($row['account_enabled'] != 'yes')?
//									false:
//									(($row['parent_account_id'] > 0)?
//										isAccountEnabled($row['parent_account_id']):
//										true
//									)
//								);

			$row = $_SESSION['dbapi']->users->checkLogin($user,$pass,$_SESSION['login_salt']);



			## USER/PASS INVALID, OR ACCOUNT DISABLED
			if(!$row || $row <= 0){


				$_SESSION['dbapi']->users->tracklogin(0,$user,$pass,'Failure');



				jsAlert("ERROR: YOUR USER/PASS ARE INCORRECT",0);

				# GENERATE NEW LOGIN SALT
				$_SESSION['login_salt'] = $_SESSION['dbapi']->users->generateSalt();


				jsRedirect(stripurl(array('area','no_script')));
				exit;


			// MUST BE AN ADMIN OR MANAGER TO ACCESS THIS CODE
			}else if($row['priv'] < 4){


				$_SESSION['dbapi']->users->tracklogin(0,$user,$pass,'Failure');



				jsAlert("ERROR: You must be an administrator/manager to access this section.",0);


				jsRedirect(stripurl(''));
				exit;



			}else{

				

				// LOAD AND CHECK ACCOUNT STATUS
				$_SESSION['account'] = $_SESSION['dbapi']->accounts->getByID($row['account_id']);

				if(!$_SESSION['account']['id']){

					$_SESSION['dbapi']->users->tracklogin(0,$user,$pass,'Failure','Account '.intval($row['account_id']).' not found.');

					jsAlert("ERROR: Account ID#".intval($row['account_id'])." was not found.",0);
					jsRedirect(stripurl(''));
					exit;

				}


				if($_SESSION['account']['status'] != 'active'){

					$_SESSION['dbapi']->users->tracklogin(0,$user,$pass,'Failure','Account '.intval($row['account_id']).' status is '.$_SESSION['account']['status']);

					jsAlert("ERROR: Account ID#".intval($row['account_id'])." is listed as '".$_SESSION['account']['status']."'",0);
					jsRedirect(stripurl(''));
					exit;

				}

				
				// CHECK FOR OTHER USERS FROM DIFFERENT IP ADDRESS LOGGED IN
				$last_login = $_SESSION['dbapi']->querySQL("SELECT * FROM `logins` ".
						" WHERE `username`='".mysqli_real_escape_string($_SESSION['dbapi']->db, $row['username'])."' ".
						
						// THEY SUCCESSFULLY LOGGED INTO THE ADMIN
						" AND `result`='success' AND `section`='admin' ".
						
						// AND THEY HAVEN'T LOGGED OUT PROPERLY
						" AND `time_out`=0 ".

						" ORDER BY id DESC LIMIT 1");
				
// 				print_r($last_login);
// 				exit;
				
				// IF THEY HAVEN'T LOGGED OUT PROPERLY, AND ARE COMING FROM ANOTHER IP ADDRESS
				// AND THERE LAST ACTION WAS SOONER THAN 15 MINUTES AGO
				if($last_login['time_out'] == 0 && 
						($_SERVER['REMOTE_ADDR'] != $last_login['ip']) && 
						($last_login['time_last_action'] > (time() - 900) )
					){

					unset($_SESSION['account']);
					
					// REJECT LOGIN!
					$_SESSION['dbapi']->users->tracklogin(0,$user,$pass,'Failure','User is logged in another station ('.$last_login['ip'].').');
					
					jsAlert('ERROR: User is logged in another station ('.$last_login['ip'].')\nLast Action: '.date("H:i:s", $last_login['time_last_action']),1);
					jsRedirect(stripurl(''));
					exit;
					
				}


				$login_id = $_SESSION['dbapi']->users->tracklogin($row['id'],$user,$pass,'Success');

				## STORE USER RECORD IN SESSION!
				$_SESSION['user'] = $row;

				# GENERATE NEW LOGIN SALT
				$_SESSION['login_salt'] = $_SESSION['dbapi']->users->generateSalt();
				
				$_SESSION['logins'] = $_SESSION['dbapi']->querySQL("SELECT * FROM `logins` WHERE id='".$login_id."' ");

				## LOAD FEATURES FOR THE USER, IF THEY ARE SET

				if($row['feature_id'] > 0){

					$_SESSION['features'] = $_SESSION['dbapi']->querySQL("SELECT * FROM features WHERE id='".intval($row['feature_id'])."' ");

				}


				// LOAD ASSIGNED OFFICES
				if($row['priv'] < 5){

					// INIT THE ARRAY
					$_SESSION['assigned_offices'] = array();

					// POPULATE THE ALLOWED/ASSIGNED OFFICES ARRAY
					$re2 = $_SESSION['dbapi']->query("SELECT * FROM `users_offices` WHERE user_id='".mysqli_real_escape_string($_SESSION['dbapi']->db,$row['id'])."'");


					$_SESSION['assigned_office_groups'] = array();

					$_SESSION['assigned_groups'] = array();

					while($r2 = mysqli_fetch_array($re2, MYSQLI_ASSOC)){

						$_SESSION['assigned_offices'][] = $r2['office_id'];

						// POPULATE THE GROUP ARRAY FOR THE SELECTED OFFICE(S)
						if(!is_array($_SESSION['assigned_office_groups'][$r2['office_id']])){
							$_SESSION['assigned_office_groups'][$r2['office_id']] = array();
						}

						$re3 = $_SESSION['dbapi']->query("SELECT * FROM `user_groups` WHERE `office`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$r2['office_id'])."'");

						while($r3 = mysqli_fetch_array($re3, MYSQLI_ASSOC)){

							$_SESSION['assigned_groups'][] = $r3['user_group'];

							$_SESSION['assigned_office_groups'][$r2['office_id']][] = $r3['user_group'];

						}
					}

				}


				## UPDATE THE TIME OF LAST LOGIN
				$_SESSION['dbapi']->users->updateLastLoginTime();

				
				if($kicktourl){

					$_SESSION['one_time_kick_to'] = $kicktourl;

				}

				jsRedirect('index.php');///.(($kicktourl)?"?kick_to=".urlencode($kicktourl):'')  );
				exit;

			}

		}

	}


	function handleDirectLogin(){


		if(isset($_REQUEST['login_link']) && isset($_REQUEST['user']) && isset($_REQUEST['login_code'])){

			$user 			= trim($_REQUEST['user']);
			$login_code 	= trim($_REQUEST['login_code']);

			$row = $_SESSION['dbapi']->querySQL("SELECT users.*  FROM users ".
					" WHERE users.enabled='yes' ". // AND accounts.enabled='yes' // MOVING THIS TO PHP, SO WE CAN ALERT ACCORDINGLY
					" AND users.username='".mysqli_real_escape_string($_SESSION['dbapi']->db,$user)."' ".
					" AND users.login_code='".mysqli_real_escape_string($_SESSION['dbapi']->db,$login_code)."' ".
					" LIMIT 1 "
					);


			## USER/PASS INVALID, OR ACCOUNT DISABLED
			if(!$row){

				$_SESSION['dbapi']->users->tracklogin(0,0,$user,$login_code,'failure-code');




				jsAlert("ERROR: YOUR USER AND LOGIN CODE ARE INCORRECT",0);



				jsRedirect(stripurl(array('login_link','user','login_code')));
				exit;

			}else{


				$_SESSION['dbapi']->users->tracklogin($row['account_id'],$row['id'],$user,$login_code,'success-code');



				## STORE USER RECORD IN SESSION!
				$_SESSION['user'] = $row;

				// LOAD ASSIGNED OFFICES
				// INIT THE ARRAY
				$_SESSION['assigned_offices'] = array();

				// POPULATE THE ALLOWED/ASSIGNED OFFICES ARRAY
				$re2 = $_SESSION['dbapi']->query("SELECT * FROM `users_offices` WHERE user_id='".mysqli_real_escape_string($_SESSION['dbapi']->db,$row['id'])."'");


				$_SESSION['assigned_office_groups'] = array();

				while($r2 = mysqli_fetch_array($re2, MYSQLI_ASSOC)){

					$_SESSION['assigned_offices'][] = $r2['office_id'];

					// POPULATE THE GROUP ARRAY FOR THE SELECTED OFFICE(S)
					if(!is_array($_SESSION['assigned_office_groups'][$r2['office_id']])){
						$_SESSION['assigned_office_groups'][$r2['office_id']] = array();
					}

					$re3 = $_SESSION['dbapi']->query("SELECT * FROM `user_groups` WHERE `office`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$r2['office_id'])."'");

					while($r3 = mysqli_fetch_array($re3, MYSQLI_ASSOC)){

						$_SESSION['assigned_office_groups'][$r2['office_id']][] = $r3['user_group'];

					}
				}


				## UPDATE THE TIME OF LAST LOGIN
				$_SESSION['dbapi']->users->updateLastLoginTime();


				jsRedirect('index.php');
				exit;

			}

		}

	}




	function makeLoginForm(){


		?><script src="js/md5.js"></script>
		<style>
		.red{
			color:red;
		}
		</style>
		<script>
			function checkLoginForm(frm){

				if(!frm.username.value){
					alert("Error: Please enter a username");

					frm.username.select();
					return false;
				}

				if(!frm.password.value){
					alert("Error: Please enter your password");

					frm.password.select();
					return false;
				}

				var obj=getEl('md5pass');

				obj.value = hex_md5(hex_md5(frm.password.value)+'<?=$_SESSION['login_salt']?>');

				frm.password.value='';


				return true;
			}


		</script>

		<form method="POST" action="<?=stripurl('')?>" target="_top" onsubmit="return checkLoginForm(this)">

				<input type="hidden" name="kick_to" value="<?=htmlentities($_REQUEST['kick_to'])?>">
				<input type="hidden" id="md5pass" name="md5pass" value="">

		<table border="0" width="100%" height="99%" cellpadding="0" cellspacing="0">
		<tr>
			<td align="center">

				<table border="0"  style="font-family:Corbel;">
					<tr>
						<th height="40" colspan="2" align="center">

							<img src="images/cci-logo-300.png" width="300" height="204" border="0" />
							<br /><br />


							<h1>Project X - Administration</h1>

						</th>
					</tr>
					<tr>
						<td colspan="2" ><br/></td>
					</tr>
					<tr>
						<th class="padlight" align="left" id="email">Username:</th>
						<td class="padlight" align="right"><input type="text" name="username" style="font-size:18px;width:200px" value="<?=(isset($_REQUEST['uname'])) ? $_REQUEST['uname'] : "" ?>" size="30"></td>
					</tr>
					<tr>
						<th class="padlight" align="left" id="password">Password:</th>
						<td class="padlight" align="right"><input type="password" name="password" style="font-size:18px;width:200px" value="" size="30"></td>
					</tr>
					<tr>
						<td class="padlight" colspan="2" height="70" align="center" style="margin-left:20px">
							<input type="submit" value="Login" class="big" name="loginbutton">
						</td>
					</tr>
				</table>
			</td>
		</tr>

			</form>
		</table><?



	}




}