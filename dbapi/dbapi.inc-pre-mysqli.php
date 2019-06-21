<?
/**
 * DBAPI - A collection of all the database and SQL functions
 */




$_SESSION['dbapi'] = new DBAPI();





class DBAPI {

	// DATABASE CONNECTION
	var $db;


	// API OBJECTS
	var	$activitys,
		$action_log,
		$campaigns,
		$extensions,
		$messages,
		$names,
		$problems,
		$scripts,
		$users,
		$user_groups,
		$voices,

		$report_emails,

		// FEATURE CONTROL
		$features,

		$imports,


		// MERGED FROM REPORT SYSTEM
		$ringing_calls,
		$lead_management,
		$employee_hours,
		$scriptstats,
		$dispo_log,

		$list_tool_tasks;


	/**
	 * DBAPI Constructor
	 * Initialize the class, connect, etc
	 */
	function __construct(){

		## INCLUDE ALL THE REQUIRED FILES
		$this->initIncludes();

		## CONNECT TO THE DATABASE
		$this->connect();


		## INIT THE SESSION SITE CONFIG DATA FROM PREFS TABLE
		$this->initSiteConfig();
	}


	/**
	 * Destructor - Cleanup Time
	 */
	function __destruct(){

		## DISCONNECT ON DESTRUCTION
		$this->disconnect();

	}


	/**
	 * Include all the required files
	 */
	function initIncludes(){

		## INCLUDE DATABASE CONNECTION INFO AND OTHER SITE DATA
		//include_once($_SERVER["DOCUMENT_ROOT"]."/site_config.php");
		//include_once("./site_config.php");


		## ACTIVITY LOG
		include_once($_SESSION['site_config']['basedir']."dbapi/activity_log.db.php");
		$this->activitys = new ActivitysAPI();


		## ACTION LOG
		include_once($_SESSION['site_config']['basedir']."dbapi/action_log.db.php");
		$this->action_log = new ActionLogAPI();


		## CAMPAIGNS
		include_once($_SESSION['site_config']['basedir']."dbapi/campaigns.db.php");
		$this->campaigns = new CampaignsAPI();

		## EXTENSIONS
		include_once($_SESSION['site_config']['basedir']."dbapi/extensions.db.php");
		$this->extensions = new ExtensionsAPI();

		## Messages
		include_once($_SESSION['site_config']['basedir']."dbapi/messages.db.php");
		$this->messages = new MessagesAPI();

		## NAMES
		include_once($_SESSION['site_config']['basedir']."dbapi/names.db.php");
		$this->names = new NamesAPI();


		include_once($_SESSION['site_config']['basedir']."dbapi/problems.db.php");
		$this->problems = new ProblemsAPI();

		## Scripts
		include_once($_SESSION['site_config']['basedir']."dbapi/scripts.db.php");
		$this->scripts = new ScriptsAPI();


		## USERS
		include_once($_SESSION['site_config']['basedir']."dbapi/users.db.php");
		$this->users = new UsersAPI();


		## VOICES
		include_once($_SESSION['site_config']['basedir']."dbapi/voices.db.php");
		$this->voices = new VoicesAPI();






	// MERGED FROM REPORT SYSTEM
		## RINGING CALLS
		include_once($_SESSION['site_config']['basedir']."dbapi/ringing_calls.db.php");
		$this->ringing_calls = new RingingCallsAPI();


		include_once($_SESSION['site_config']['basedir']."dbapi/lead_management.db.php");
		$this->lead_management = new LeadManagementAPI();

		include_once($_SESSION['site_config']['basedir']."dbapi/employee_hours.db.php");
		$this->employee_hours = new EmployeeHoursAPI();

		## SCRIPT STATSTICS
		include_once($_SESSION['site_config']['basedir']."dbapi/script_statistics.db.php");
		$this->scriptstats = new ScriptStatisticsAPI();


		## DISPO LOG
		include_once($_SESSION['site_config']['basedir']."dbapi/dispo_log.db.php");
		$this->dispo_log = new DispoLogAPI();


		## FEATURE CONTROL
		include_once($_SESSION['site_config']['basedir']."dbapi/feature_control.db.php");
		$this->features = new FeaturesAPI();



		## User Groups
		include_once($_SESSION['site_config']['basedir']."dbapi/user_groups.db.php");
		$this->user_groups = new UserGroupsAPI();

		## Report Emails
		include_once($_SESSION['site_config']['basedir']."dbapi/report_emails.db.php");
		$this->report_emails = new ReportEmailsAPI();


		include_once($_SESSION['site_config']['basedir']."dbapi/list_tool_tasks.db.php");
		$this->list_tool_tasks = new TasksAPI();

		include_once($_SESSION['site_config']['basedir']."dbapi/list_tool_imports.db.php");
		$this->imports = new ImportsAPI();
	}


	/**
	 * Loads site config data from the "prefs" table
	 */
	 function initSiteConfig(){

        $res = $this->query("SELECT * FROM prefs ORDER BY `key` ASC");
        while($row = mysql_fetch_array($res, MYSQL_ASSOC)){
			if (!isset($_SESSION['site_config'][$row['key']])){
				$_SESSION['site_config'][$row['key']] = $row['value'];
			}

        }

    }


	/**
	 * Use the info in site_config and connect to database
	 */
	function connect(){

		# CREATE MYSQL DATABASE CONNECTION
    	$this->db = mysql_connect(
						$_SESSION['site_config']['pxdb']['sqlhost'],
						$_SESSION['site_config']['pxdb']['sqllogin'],
						$_SESSION['site_config']['pxdb']['sqlpass']
					) or die(mysql_error()."Connection to MySQL Failed.");

		mysql_select_db($_SESSION['site_config']['pxdb']['sqldb'],$this->db)
			or die("Could not select database ".$_SESSION['site_config']['pxdb']['sqldb']);

	}

	/**
	 * Disconnect from database and cleanup variables
	 */
	function disconnect(){

		mysql_close($this->db);

		unset($this->db);
	}





/******** UTILITY/CORE DATABASE FUNCTIONS ********/


	/**
	 * Adds a record to the database, using the keys=>values from the provided associative array
	 * (It escapes the keys and values automatically)
	 */
	function aadd($assoarray,$table){
		$startsql	= "INSERT INTO `$table`(";
		$midsql		= ") VALUES (";
		$endsql		= ")";
		$out = $startsql;
		$x=0;


		#print_r($assoarray);

		foreach($assoarray as $key=>$val){
			$out.= "`$key`";
			$out.=($x+1<count($assoarray))?',':'';


			//if(is_string($val) && $val == 'NULL')		$midsql.= "NULL";
			//else

			if(!is_numeric($val) && $val === null){
				$midsql.= "NULL";
			}else{
				$midsql.= "'".mysql_real_escape_string($val)."'";
			}



			$midsql.=($x+1<count($assoarray))?',':'';
			$x++;
		}
		$out .= $midsql.$endsql;
		#print $out;
		return $this->execSQL($out);
	}


	/**
	 * Edits an existing record in the database (by $id), only editing the keys=>values provided in asso array
	 * (Filters/escapes fields/values as well)
	 * @param $extra_where 	Can be used to provide extra sql such as (" AND account_id='$accountid' ") to add security/restrictions/etc
	 */
	function aedit($id,$assoarray,$table,$extra_where=""){
		$startsql	= "UPDATE `$table` SET ";
		$endsql		= " WHERE id='$id'";

		$out = $startsql;
		$x=0;
		foreach($assoarray as $key=>$val){

			if(!is_numeric($val) && $val === null){
				$out.= "`$key`=NULL";
			}else{
				$out.= "`$key`='".mysql_real_escape_string($val)."'";
			}

			//if($val != 'NULL')
			//else				$out.= "`$key`=NULL";

			$out.=($x+1<count($assoarray))?',':'';
			$x++;
		}
		$out .= $endsql.$extra_where;

		#jsAlert('OUT to Database from db.inc ' . $out);
		return $this->execSQL($out);
	}

	/**
	 * Deletes a record, that has the $id provided
	 */
	function adelete($id,$table){
		$id=intval($id);
		return $this->execSQL("DELETE FROM `$table` WHERE id='$id'");
	}



	/**
	 * The primary execute SQL function
	 * (Used for Inserts/Updates)
	 */
    function execSQL($cmd, $ignore_error=false){

        if(!$ignore_error){
                mysql_query($cmd,$this->db) or die("Error in execSQL(".$cmd."):".mysql_error());
        }else{
                $res = mysql_query($cmd, $this->db);

                if($res === FALSE){

                        echo "(Bypassing) Error in execSQL(".$cmd."):".mysql_error();
                        return FALSE;
                }
        }

		if(($cnt=mysql_affected_rows()) > 0)
			return $cnt;
		else
			return 0;
	}


	/**
	 * Returns the COUNT(id) of the specified table, using the specified where clause
	 */
	function getCount($table,$whereclause){
		$cmd = "SELECT COUNT('".$table.".id') FROM $table $whereclause";
		$row = mysql_fetch_row(mysql_query($cmd,$this->db));
		return $row[0];
	}


	function getResult($cmd){	return $this->query($cmd,1);}	# Returns all the records returned.
	function queryROW($cmd)	{	return $this->query($cmd,2);}	# Returns an array of 1 result
	function queryOBJ($cmd)	{	return $this->query($cmd,3);}	# Returns an object of first result
	function querySQL($cmd)	{	return $this->query($cmd,4);}	# Returns as associative-array(hash) of 1 result
	function queryROWS($cmd){ 	return $this->query($cmd,5);}	# Returns the number of rows in a result set
	function fetchROW($cmd)	{ 	return $this->query($cmd,6);}	# Returns an associative array that corresponds to the fetched row, or FALSE if there are no more rows.


	/**
	 * The primary query function, used by most other functions that use a resultset (selects)
	 */
	function query($cmd, $mode=0){			# with mode = 0 or 1, it will return the result set, all the records returned.
		##print $cmd."<br>";
		$res = mysql_query($cmd,$this->db);
		if(!$mode || $mode == 1){
			return $res;
		}else if($mode == 2){
			return mysql_fetch_row($res);
		}else if($mode == 3){
			return mysql_fetch_object($res);
		}else if($mode == 4){
			return mysql_fetch_array($res, MYSQL_ASSOC);
		}else if($mode == 5){
			return mysql_num_rows($res);
		}else if($mode == 6){
			return mysql_fetch_assoc($res);
		}
	}
}




