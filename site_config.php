<?
/**
 * RINGING CALLS - CONFIG FILE
 */



	$_SESSION['site_config']['basedir'] = '/var/www/html/ProjectX-ReportsAndAdmin/';
	#$_SESSION['site_config']['basedir'] = '/var/www/html/dev/';
	
	$_SESSION['site_config']['upload_dir'] = "/playback/";
	$_SESSION['site_config']['trash_dir'] = "/playback/trash/";

	$_SESSION['site_config']['upload_max_size'] = 2097152;

/****************************/
//  PX CONFIG
/****************************/

	/**
	 * PX DB CONNECTION
	 */
	$_SESSION['site_config']['pxdb']['sqlhost']	= "10.101.15.65";
	$_SESSION['site_config']['pxdb']['sqllogin']   	= "projectxdb";
	$_SESSION['site_config']['pxdb']['sqlpass']   	= "hYGjWDAX4LFmdR4C";//"L8RXy1svpilqcogM";
	$_SESSION['site_config']['pxdb']['sqldb'] 		= "projectx";


/**
 * List Tool DB CONNECTION
 */
        $_SESSION['site_config']['listdb']['sqlhost']           = "10.10.0.43";
        $_SESSION['site_config']['listdb']['sqllogin']          = "pxlisttool";
        $_SESSION['site_config']['listdb']['sqlpass']           = "mK48RhIYC1";
        $_SESSION['site_config']['listdb']['sqldb']             = "px_list_tool";



/****************************/
//  CCI DATA CONFIG
/****************************/

	/**
	 * CCI DB CONNECTION
	 */
	$_SESSION['site_config']['ccidb']['sqlhost']	= "10.101.15.205";
	$_SESSION['site_config']['ccidb']['sqllogin']   = "pxsales";//"cci"; // or courtesy? -andrew
	$_SESSION['site_config']['ccidb']['sqlpass']   	= "84t97nkSf9su53jsT";//"Muw94uPe";
	$_SESSION['site_config']['ccidb']['sqldb'] 		= "ccidata";



/****************************/
//  OPENSIPS DATA CONFIG
/****************************/

	/**
	 * OPENSIPS DB CONNECTION

	$_SESSION['site_config']['opensipsdb']['sqlhost']		= "10.100.0.200";
	$_SESSION['site_config']['opensipsdb']['sqllogin']		= "pxreporting";
	$_SESSION['site_config']['opensipsdb']['sqlpass']   	= "nrAesou0rethash";
	$_SESSION['site_config']['opensipsdb']['sqldb'] 		= "opensips";
	*/



/****************************/
//  CLUSTER CONFIG
/****************************/

	$x=0;

	/**
	 * COLD DEV CLUSTER
	 */
	$_SESSION['site_config']['db'][$x]['name']		= "Vici Cold Dev";
	$_SESSION['site_config']['db'][$x]['cluster_id']	= 23;
	$_SESSION['site_config']['db'][$x]['web_host']		= "10.101.15.80";
	$_SESSION['site_config']['db'][$x]['sqlhost']		= "10.101.15.80";
	$_SESSION['site_config']['db'][$x]['sqllogin']   	= "devpx";
	$_SESSION['site_config']['db'][$x]['sqlpass']   	= "FreedomFries666";
	$_SESSION['site_config']['db'][$x]['sqldb'] 		= "asterisk";
	$x++;

	/**
         * VERIFIER DEV CLUSTER
         */
        $_SESSION['site_config']['db'][$x]['name']              = "Vici Verifier Dev";
        $_SESSION['site_config']['db'][$x]['cluster_id']        = 25;
        $_SESSION['site_config']['db'][$x]['web_host']          = "10.101.15.81";
        $_SESSION['site_config']['db'][$x]['sqlhost']           = "10.101.15.81";
        $_SESSION['site_config']['db'][$x]['sqllogin']          = "devpx";
        $_SESSION['site_config']['db'][$x]['sqlpass']           = "FreedomFries666";
        $_SESSION['site_config']['db'][$x]['sqldb']             = "asterisk";
        $x++;



