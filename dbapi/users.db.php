<?php

/**
 * Users SQL Functions
 */



class UsersAPI{

	var $table = "users";



	/**
	 * Check login
	 *
	 * @param	$user	The username to login to.
	 * @param	$pass	Password to login with
	 *
	 * @return	The users table row on success, or null on failure to login
	 */
	function checkLogin($user,$pass){


		$row = $_SESSION['dbapi']->querySQL(
				"SELECT `".$this->table."`.* FROM `".$this->table."` ".

				" WHERE `".$this->table."`.enabled='yes' ". // AND accounts.enabled='yes' // MOVING THIS TO PHP, SO WE CAN ALERT ACCORDINGLY
				" AND `".$this->table."`.username='".mysqli_real_escape_string($_SESSION['dbapi']->db,$user)."' ".
				" AND `".$this->table."`.password='".mysqli_real_escape_string($_SESSION['dbapi']->db,$pass)."' ".
				" LIMIT 1 "
				);


		if(!$row){

			return -1;

		}else{

			return $row;
		}
	}




	/**
	 * Marks a User as enabled=no (deleted)
	 */
	function delete($id){


		unset($dat);
		$dat['enabled'] = 'no';
		return $_SESSION['dbapi']->aedit($id,$dat,$this->table);
	}


	/**
	 * Get a user by ID
	 * @param 	$user_id		The database ID of the record
	 * @param	$account_id		Optional account ID restriction
	 * 	 * @return	assoc-array of the database record
	 */
	function getByID($user_id,$account_id=0){
		$user_id = intval($user_id);

		return $_SESSION['dbapi']->querySQL("SELECT * FROM `".$this->table."` ".
						" WHERE id='".$user_id."' "

					);
	}

	function getName($id){
		$id=intval($id);
		list($name) = $_SESSION['dbapi']->queryROW("SELECT username FROM `".$this->table."` ".
						" WHERE id='".$id."' ");
		return $name;
	}


	/**
	 * getResults($asso_array)

	 * Array Fields:
	 * 	fields	: The select fields for the sql query, * is default
	 *	id		: Int/Array of Ints
	 *  enabled	: String only, "yes"/"no"
	 *

	 *  skip_id : Int/Array of ID's to skip (AND seperated, != operator)
	 *
	 *  order : ORDER BY field, Assoc-array,
	 * 		Example: "order" = array("id"=>"DESC")
	 *  limit : Assoc-Array of 2 keys/values.
	 * 		"count"=>(amount to limit by)
	 * 		"offset"=>(optional, the number of records to skip)
	 */
	function getResults($info){

		$fields = ($info['fields'])?$info['fields']:'*';


		$sql = "SELECT $fields FROM `".$this->table."` WHERE 1 ";


	## ID FIELD SEARCH
		## ARRAY OF id's SEARCH
		if(is_array($info['id'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['id'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`id`='".intval($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE ID SEARCH
		}else if($info['id']){

			$sql .= " AND `id`='".intval($info['id'])."' ";

		}



	### ENABLED FIELD
		if($info['enabled']){

			$sql .= " AND `enabled`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['enabled'])."' ";

		}

	### USERNAME SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['username'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['username'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`username` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE NAME SEARCH
		}else if($info['username']){

			$sql .= " AND `username` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['username'])."%' ";

		}


		## AGENT NAME SEARCH
		if(is_array($info['name'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['name'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= " CONCAT(first_name,' ',last_name) LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE NAME SEARCH
		}else if($info['name']){

			$sql .= " AND  CONCAT(first_name,' ',last_name) LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['name'])."%' ";

		}

		## AGENT NAME SEARCH
		if(is_array($info['group_name'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['group_name'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= " user_group LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE GROUP SEARCH
		}else if($info['group_name']){

			$sql .= " AND  user_group LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['group_name'])."%' ";

		}



	### FEATURE SEARCH




	if(is_array($info['feature_id'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['feature_id'] as $idx=>$fid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`feature_id`='".intval($fid)."' ";
			}

			$sql .= ") ";

		## SINGLE ID SEARCH
		}else if($info['feature_id']){

			$sql .= " AND `feature_id`='".intval($info['feature_id'])."' ";

		}

	### PRIVILEDGE SEARCH
		if(is_array($info['priv'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['priv'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`priv`='".intval($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE ID SEARCH
		}else if($info['priv']){

			$sql .= " AND `priv`='".intval($info['priv'])."' ";

		}

	## PRIV SEARCH - LESS THAN OR EQUAL TO (LTE)
		if(is_array($info['priv_lte'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['priv'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`priv` <= '".intval($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE ID SEARCH
		}else if($info['priv']){

			$sql .= " AND `priv` <= '".intval($info['priv'])."' ";

		}

	## SKIP/IGNORE ID's
		if(isset($info['skip_id'])){

			$sql .= " AND (";

			if(is_array($info['skip_id'])){
				$x=0;
				foreach($info['skip_id'] as $sid){

					if($x++ > 0)$sql .= " AND ";

					$sql .= "`id` != '".intval($sid)."'";
				}

			}else{
				$sql .= "`id` != '".intval($info['skip_id'])."' ";
			}

			$sql .= ")";
		}


	### ORDER BY
		if(is_array($info['order'])){

			$sql .= " ORDER BY ";
			$x=0;
			foreach($info['order'] as $k=>$v){
				if($x++ > 0)$sql .= ",";

				$sql .= "`$k` ".mysqli_real_escape_string($_SESSION['dbapi']->db,$v)." ";
			}

		}

		if(is_array($info['limit'])){

			$sql .= " LIMIT ".
						(($info['limit']['offset'])?$info['limit']['offset'].",":'').
						$info['limit']['count'];

		}


		#echo $sql;

		## RETURN RESULT SET
		return $_SESSION['dbapi']->query($sql);
	}








	function getCount(){

		$row = mysqli_fetch_row($this->getResults(
						array(
							"fields" => "COUNT(id)",
							"enabled"=> "yes"
						)
					));

		return $row[0];
	}


	/**
	 * Track a users login attempt
	 * @param $uid			The user ID, or 0 if login failed
	 * @param $username		The Username attempted
	 * @param $password		The password attempt
	 * @param $res			String of login result: "Success" / "Failure"
	 *
	 * @return	The login ID of the record created
	 */
	function tracklogin($uid,$username,$password,$res, $details=''){

		$dat = array();
		$dat['user_id']		= $uid;
		$dat['username']	= $username;
		$dat['pass_attempt'] = $password;
		$dat['result']		= $res;
		
		$dat['time']		= time();
		$dat['time_last_action']= $dat['time'];
		
		$dat['ip']			= $_SERVER["REMOTE_ADDR"];
		$dat['browser']		= $_SERVER['HTTP_USER_AGENT'];
		
		$dat['section'] = 'admin';

		$dat['details'] = $details;
		$_SESSION['dbapi']->aadd($dat,'logins');

		return mysqli_insert_id($_SESSION['dbapi']->db);
	}


	function refreshFeaturesAndPrivs($return_mode=0){
		
		// RELOAD THE USER RECORD
		$_SESSION['user'] = $_SESSION['dbapi']->querySQL("SELECT * FROM `users` WHERE id='".$_SESSION['user']['id']."' ");
		
		$logout = false;
		$reason = "";
		if($_SESSION['user']['enabled'] != 'yes'){
			$logout = true;
			$reason .= '  User has been disabled.\n';
			
		}
		
		// LOAD AND CHECK ACCOUNT STATUS
		$_SESSION['account'] = $_SESSION['dbapi']->accounts->getByID($_SESSION['user']['account_id']);
		
		if(!$_SESSION['account']['id'] || $_SESSION['account']['status'] != 'active'){
			
			$logout = true;
			
			$reason .= '  Account not found or inactive.\n';
			
		}
		
		
		if($logout){
			
			if(isset($_SESSION['user']) && $_SESSION['user']['id'] > 0){
				
				$_SESSION['dbapi']->users->updateLogoutTime();
				
			}
			
			
			

			$reason = 'You have been logged out:\n'.$reason;
			
			
			switch($return_mode){
			default:
			case 0:
			
				session_unset();
				
				jsAlert($reason, 1);
			
				jsRedirect("index.php");
				exit;
			
			case 1:
				
				$_SESSION['api']->errorOut("$reason");
				
				session_unset();
				
				exit;
				
				
			case 2:
				
				die("ERROR: ".$reason);
				
			}
		}
		

		// RELOAD THE FEATURE RECORD
		if($_SESSION['user']['feature_id'] > 0){
			
			$_SESSION['features'] = $_SESSION['dbapi']->querySQL("SELECT * FROM features WHERE id='".intval($_SESSION['user']['feature_id'])."' ");
			
		}

		
		
	}
	
	
	
	/**
	 * Updates the 'last_login' time field, to current time
	 * Requires $_SESSION['user'] to be initialized already
	 */
	function updateLastLoginTime(){
		unset($dat);
		$dat['last_login'] = time();
		$_SESSION['dbapi']->aedit($_SESSION['user']['id'],$dat,$this->table);
	}

	function updateLastActionTime(){
		
		// CHECK FOR THEM TO BE LOGGED OUT FIRST
		$logins = $_SESSION['dbapi']->querySQL("SELECT * FROM `logins` WHERE id='".$_SESSION['logins']['id']."' ");
		
		// THEY'VE BEEN FORCE LOGGED OUT
		if($logins['id'] > 0 && $logins['time_out'] > 0){
			
			session_unset();
			
			
			jsRedirect("index.php");
			exit;
			
		}
		
		$_SESSION['logins'] = $logins;
		
		
		$dat = array( 'time_last_action' => time() );
		
		$_SESSION['dbapi']->aedit($_SESSION['logins']['id'],$dat,"logins");
	}
	
	
	function updateLogoutTime(){
		
		$dat = array();
				
		$dat['time_out'] = time();
		$dat['duration'] = $dat['time_out'] - $_SESSION['logins']['time'];
		
		$_SESSION['dbapi']->aedit($_SESSION['logins']['id'],$dat,"logins");
	
	}

	function userExists($username){

		list($id) = $_SESSION['dbapi']->queryROW(
						"SELECT id FROM `".$this->table."` ".
						" WHERE username='".mysqli_real_escape_string($_SESSION['dbapi']->db,$username)."' ".
						" AND enabled='yes' ");

		return ($id)?true:false;
	}

}
