<?php
/**
 * DBAPI - A collection of all the database and SQL functions
 */


$_SESSION['dbapi'] = new DBAPI();


class DBAPI {

	/**
	 * CONTROL VARIABLES (EDITABLE)
	 */
    public $query_debugging = true;			// A TOGGLE TO BASICALLY SHUT OFF ALL QUERY STATS/DEBUGGING

    public $slow_query_debugging_only = true;	// A TOGGLE TO ONLY LOG THE SLOW QUERIES (query_debugging needs to be true, for this to work)
    public $slow_page_debugging_only = true;	// A TOGGLE TO SHUT OFF TEH FLOOD OF EVERY PAGE LOAD HITTING THE LOGS, AND ONLY SHOW ONES OVER THE LIMIT

    public $explain_queries = true;			// A TOGGLE TO RUN THE SQL "EXPLAIN $yourQuery", AND PUT RESULTS INTO THE LOGS TOO (also requires query_debugging to be enabled. If slow_query_debugging_only is TRUE, then the explain results will go into teh SLOW log file instead of query log file.)

    public $slow_query_time_limit = 10;	// IN SECONDS, HOW LONG BEFORE A QUERY IS CONSIDERED SLOW. INTS WORK, FLOAT SHOULD BE SUPPORTED TOO
    public $slow_page_time_limit = 10;		// IN SECONDS, HOW LONG A PAGE LOAD HAS TO TAKE, BEFORE IT GETS LOGGED (when slow_page_debugging_only is set to TRUE)

    public $query_log_file 		= "/var/www/logs/LMT_queries.log";			// THE FILE TO DUMP ALL RAW QUERIES
    public $slow_query_log_file 	= "/var/www/logs/LMT_slow_queries.log";		// THE FILE TO DUMP THE SLOW QUERIES





//// SHOULDNT NEED TO EDIT BELOW HERE ///


	// DATABASE CONNECTION
    public $db;



	// INIT THE TIMERS
    public $page_start_time = 0;
    public $page_query_count = 0;


<<<<<<< dbapi/dbapi.inc.php
    // API OBJECTS
=======
	// API OBJECTS
>>>>>>> dbapi/dbapi.inc.php
    public $accounts;
    public $activitys;
    public $action_log;
    public $campaigns;
    public $campaign_parents;
	public $login_tracker;
    public $extensions;
    public $messages;
    public $names;
    public $problems;
    public $scripts;
    public $users;
    public $user_groups;
    public $user_groups_master;
    public $voices;
    public $report_emails;
    // FEATURE CONTROL
    public $features;
    public $imports;
    // MERGED FROM REPORT SYSTEM
    public $ringing_calls;
    public $lead_management;
    public $employee_hours;
    public $scriptstats;
    public $dispo_log;
    public $pac_reports;
    public $quiz_results;
    public $quiz_questions;
    public $list_tool_tasks;


	/**
	 * DBAPI Constructor
	 * Initialize the class, connect, etc
	 */
	function __construct(){

		## INCLUDE ALL THE REQUIRED FILES
		$this->initIncludes();

		$this->page_start_time = microtime_float();

		## CONNECT TO THE DATABASE
		$this->connect();


		## INIT THE SESSION SITE CONFIG DATA FROM PREFS TABLE
		$this->initSiteConfig();
	}


	/**
	 * Destructor - Cleanup Time
	 */
	function __destruct(){

		if($this->query_debugging == true){

			$page_end_time = microtime_float();

			$time_taken = $page_end_time - $this->page_start_time;


			if(!$this->slow_page_debugging_only || ($this->slow_page_debugging_only == true && $time_taken > $this->slow_page_time_limit) ){

				$this->logPageLoad($time_taken);



			}

		}

		## DISCONNECT ON DESTRUCTION
		$this->disconnect();



	}

	function logPageLoad($time_taken){

		// WRITE TO THE LOG FILE
		$output = date("H:i:s m/d/Y").' End Page - '.$_SESSION['user']['username'].'(#'.$_SESSION['user']['id'].") - Total Queries: ".$this->page_query_count." - Page Run Time: ".round($time_taken,3)." sec - ".$_SERVER['REQUEST_URI']." from ".$_SERVER['REMOTE_ADDR']."\n";
		file_put_contents($this->query_log_file, $output, FILE_APPEND);


		// WRITE TO THE DATABASE
		try{

			$dat = array();
			$dat['time'] = time();
			$dat['user'] = $_SESSION['user']['username'];
			$dat['user_id'] = $_SESSION['user']['id'];
			$dat['total_queries'] = $this->page_query_count;
			$dat['total_load_time'] = round($time_taken,3);
			$dat['ip_address'] = $_SERVER['REMOTE_ADDR'];
			$dat['url'] = $_SERVER['REQUEST_URI'];

			$dat['post_values'] = print_r($_POST, 1);


			$_SESSION['dbapi']->aadd($dat,'analysis_page_loads');

		} catch (Exception $e) {

//echo "Caught Exception ".$e->getMessage()."\n";

		}

	}


	/**
	 * Include all the required files
	 */
	function initIncludes(){

		## INCLUDE DATABASE CONNECTION INFO AND OTHER SITE DATA
		//include_once($_SERVER["DOCUMENT_ROOT"]."/site_config.php");
		//include_once("./site_config.php");


		include_once($_SESSION['site_config']['basedir']."utils/microtime.php");

		## ACTIVITY LOG
		include_once($_SESSION['site_config']['basedir']."dbapi/activity_log.db.php");
		$this->activitys = new ActivitysAPI();


		## ACTION LOG
		include_once($_SESSION['site_config']['basedir']."dbapi/action_log.db.php");
		$this->action_log = new ActionLogAPI();


		## CAMPAIGNS
		include_once($_SESSION['site_config']['basedir']."dbapi/campaigns.db.php");
		$this->campaigns = new CampaignsAPI();

        ## CAMPAIGN PARENTS
        include_once($_SESSION['site_config']['basedir']."dbapi/cmpgn_parents.db.php");
        $this->campaign_parents = new CampaignParentsAPI();

		## EXTENSIONS
		include_once($_SESSION['site_config']['basedir']."dbapi/extensions.db.php");
		$this->extensions = new ExtensionsAPI();

		## Messages
		include_once($_SESSION['site_config']['basedir']."dbapi/messages.db.php");
		$this->messages = new MessagesAPI();

		## NAMES
		include_once($_SESSION['site_config']['basedir']."dbapi/names.db.php");
		$this->names = new NamesAPI();

		## LOGIN TRACKER
		include_once($_SESSION['site_config']['basedir']."dbapi/login_tracker.db.php");
		$this->login_tracker = new LoginTrackerAPI();

		## Problems
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

		include_once($_SESSION['site_config']['basedir']."dbapi/user_groups_master.db.php");
		$this->user_groups_master = new UserGroupsMasterAPI();

		## Report Emails
		include_once($_SESSION['site_config']['basedir']."dbapi/report_emails.db.php");
		$this->report_emails = new ReportEmailsAPI();


		include_once($_SESSION['site_config']['basedir']."dbapi/list_tool_tasks.db.php");
		$this->list_tool_tasks = new TasksAPI();

		include_once($_SESSION['site_config']['basedir']."dbapi/list_tool_imports.db.php");
		$this->imports = new ImportsAPI();


		include_once($_SESSION['site_config']['basedir']."dbapi/pac_reports.db.php");
		$this->pac_reports = new PACReportsAPI();



		include_once($_SESSION['site_config']['basedir']."dbapi/quiz_results.db.php");
		$this->quiz_results = new QuizResultsAPI();


		include_once($_SESSION['site_config']['basedir']."dbapi/quiz_questions.db.php");
		$this->quiz_questions = new QuizQuestionsAPI();


		include_once($_SESSION['site_config']['basedir']."dbapi/accounts.db.php");
		$this->accounts = new AccountsAPI();

	}


	/**
	 * Loads site config data from the "prefs" table
	 */
	 function initSiteConfig(){

        $res = $this->query("SELECT * FROM prefs ORDER BY `key` ASC");
        while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
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
    	$this->db = mysqli_connect(
						$_SESSION['site_config']['pxdb']['sqlhost'],
						$_SESSION['site_config']['pxdb']['sqllogin'],
						$_SESSION['site_config']['pxdb']['sqlpass'],
						$_SESSION['site_config']['pxdb']['sqldb']
					) or die(mysqli_error($this->db)."Connection to MySQL Failed.");

//		mysql_select_db($_SESSION['site_config']['pxdb']['sqldb'],$this->db)
//			or die("Could not select database ".$_SESSION['site_config']['pxdb']['sqldb']);

	}

	/**
	 * Disconnect from database and cleanup variables
	 */
	function disconnect(){

		mysqli_close($this->db);

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
				$midsql.= "'".addslashes($val)."'";
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
				$out.= "`$key`='".addslashes($val)."'";
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



	function explainSQL($cmd){

		$sql = "EXPLAIN ".$cmd;

		// IGNORE DEBUG HERE, sO WE DONT LOOP FOREVER
		$row = $this->query($sql, 4, true);

		$output = "Explain: ";
		foreach($row as $key=>$val){

			$output .= '('.$key.'='.$val.")   ";

		}

		return $output;
	}

	/**
	 * The primary execute SQL function
	 * (Used for Inserts/Updates)
	 */
    function execSQL($cmd, $ignore_error=false, $no_debug = false){
		//echo $cmd;

		if($this->query_debugging == true && !$no_debug){

			if(!$this->slow_query_debugging_only){

				if($this->explain_queries){

					$expout = $this->explainSQL($cmd);


					file_put_contents($this->query_log_file, $_SESSION['user']['username'].'(#'.$_SESSION['user']['id'].") @ ".date("H:i:s m/d/Y")."\n".$cmd."\n".$expout."\n",FILE_APPEND);
				}else{
					file_put_contents($this->query_log_file, $_SESSION['user']['username'].'(#'.$_SESSION['user']['id'].") @ ".date("H:i:s m/d/Y")."\n".$cmd."\n",FILE_APPEND);
				}

			}

			$time_started = microtime_float();

		}


		$this->page_query_count++;

        if(!$ignore_error){
                mysqli_query($this->db,$cmd) or die("Error in execSQL(".$cmd."):".mysqli_error($this->db));
        }else{
                $res = mysqli_query($this->db,$cmd);

            if ($res === false) {
                        echo "(Bypassing) Error in execSQL(".$cmd."):".mysqli_error($this->db);
                return false;
                }
        }


        if($this->query_debugging == true && !$no_debug){

			$time_ended = microtime_float();


			$time_taken = $time_ended - $time_started;

			if(!$this->slow_query_debugging_only){

				file_put_contents($this->query_log_file, "Exec Time: ".$time_taken." # of rows:".mysqli_num_rows($res)."\n\n" ,FILE_APPEND);

			}

			//echo "<br />Load time: ".$time_taken;

			if($time_taken > $this->slow_query_time_limit){

				file_put_contents($this->slow_query_log_file, $_SESSION['user']['username'].'(#'.$_SESSION['user']['id'].") @ ".date("H:i:s m/d/Y")."\n".
														$cmd."\n".

														(($this->slow_query_debugging_only && $this->explain_queries)?$this->explainSQL($cmd)."\n":'').

														"Exec Time: ".$time_taken." # of rows:".mysqli_num_rows($res)."\n\n"
														,FILE_APPEND);



			}
		}



		if(($cnt=mysqli_affected_rows($this->db)) > 0)
			return $cnt;
		else
			return 0;
	}


	/**
	 * Returns the COUNT(id) of the specified table, using the specified where clause
	 */
	function getCount($table,$whereclause){
		$cmd = "SELECT COUNT('".$table.".id') FROM $table $whereclause";
		$row = mysqli_fetch_row(mysqli_query($this->db,$cmd));
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
	function query($cmd, $mode=0, $no_debug = false){			# with mode = 0 or 1, it will return the result set, all the records returned.
		//print $cmd."<br>";

		if($this->query_debugging == true && !$no_debug){

			if(!$this->slow_query_debugging_only){

				if($this->explain_queries){

					$expout = $this->explainSQL($cmd);

					file_put_contents($this->query_log_file, $_SESSION['user']['username'].'(#'.$_SESSION['user']['id'].") @ ".date("H:i:s m/d/Y")."\n".$cmd."\n".$expout."\n",FILE_APPEND);

				}else{
					file_put_contents($this->query_log_file, $_SESSION['user']['username'].'(#'.$_SESSION['user']['id'].") @ ".date("H:i:s m/d/Y")."\n".$cmd."\n",FILE_APPEND);
				}

			}

			$time_started = microtime_float();

		}


		$this->page_query_count++;

		$res = mysqli_query($this->db,$cmd);

		if($this->query_debugging == true && !$no_debug){

			$time_ended = microtime_float();


			$time_taken = $time_ended - $time_started;

			if(!$this->slow_query_debugging_only){

				file_put_contents($this->query_log_file, "Query Time: ".$time_taken." # of rows:".mysqli_num_rows($res)."\n\n" ,FILE_APPEND);

			}

			//echo "<br />Load time: ".$time_taken;

			if($time_taken > $this->slow_query_time_limit){

				file_put_contents($this->slow_query_log_file, $_SESSION['user']['username'].'(#'.$_SESSION['user']['id'].") @ ".date("H:i:s m/d/Y")."\n".
														$cmd."\n".
														(($this->slow_query_debugging_only && $this->explain_queries)?$this->explainSQL($cmd)."\n":'').
														"Query Time: ".$time_taken."    # of rows:".mysqli_num_rows($res)."\n\n"
														,FILE_APPEND);



			}
		}


		if(!$mode || $mode == 1){
			return $res;
		}else if($mode == 2){
			return mysqli_fetch_row($res);
		}else if($mode == 3){
			return mysqli_fetch_object($res);
		}else if($mode == 4){
			return mysqli_fetch_array($res, MYSQLI_ASSOC);
		}else if($mode == 5){
			return mysqli_num_rows($res);
		}else if($mode == 6){
			return mysqli_fetch_assoc($res);
		}
	}
}




