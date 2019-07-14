<?php
/**
 * MAIN PAGE - The main entry point for the admin module.
 * Written By: Jonathan Will
 *
 */
// ENSURE SESSION IS RUNNING, CAUSE WE NEED THAT SHIT
session_start();

/**
 * Database connection made here
 */
include_once("site_config.php");

// GENERIC DB FUNCTIONS
include_once("db.inc.php");
include_once("utils/microtime.php");
include_once("dbapi/dbapi.inc.php");

/**
 * Additional includes/requires go here
 */
include_once("utils/jsfunc.php");
include_once("utils/stripurl.php");
include_once("utils/format_phone.php");
include_once("utils/rendertime.php");
include_once("utils/DropDowns.php");
include_once("utils/functions.php");
include_once("utils/feature_functions.php");
include_once("utils/db_utils.php");

/**
 * Loading up module classes
 */
include_once("classes/genericDD.inc.php");
include_once("classes/interface.inc.php");
include_once("classes/languages.inc.php");

// DESTROY THE SESSION/LOGOUT ?o
if (isset($_REQUEST['o'])) {
    session_unset();
    jsRedirect("index.php");
    exit;
}

// NO_SCRIPT - shuts off extra interface stuff, because page being loaded via AJAX
if(!isset($_REQUEST['no_script']) || (isset($_REQUEST['force_scripts']) && $_REQUEST['force_scripts'])){

?><!DOCTYPE HTML>
<html>
<head>
    <title>Project X - Management Tools and Reports</title>


    <script src="js/functions.js"></script>

    <link rel="stylesheet" href="css/reset.css"> <!-- CSS reset -->

    <META HTTP-EQUIV="Access-Control-Allow-Origin" CONTENT="http://skynet.advancedtci.com">


    <link href='https://fonts.googleapis.com/css?family=Open+Sans:300,400,700' rel='stylesheet' type='text/css'>

    <link rel="stylesheet" type="text/css" href="css/style.css"/>
    <link rel="stylesheet" href="css/navstyle.css"> <!-- Resource style -->
    <link rel="stylesheet" type="text/css" href="css/cupertino/jquery-ui-1.10.3.custom.min.css"/>

    <link rel="stylesheet" href="themes/default/css/uniform.default.css" media="screen"/>

    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico"/>
    <link rel="icon" type="image/x-icon" href="favicon.ico">

    <link rel="stylesheet" type="text/css" href="css/jquery.dataTables.css"/>

    <?/*			<script src="js/jquery-1.9.1.js"></script>**/
    ?>

    <script src="js/jquery-1.10.2.min.js"></script>

    <?/*<script src="//code.jquery.com/jquery-2.2.4.min.js"></script>*/
    ?>

    <script src="js/jquery-ui-1.10.3.custom.min.js"></script>
    <script src="js/jquery.uniform.min.js"></script>


    <?/*<script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>*/
    ?>

    <script src="js/jquery.dataTables.min.js"></script>


    <script src="js/ajax_functions.js"></script>
    <script src="js/functions.js"></script>
    <script src="js/page_system.js"></script>


    <?
        /** NEW NAVIGATION STUFF
         *
         ***/
    ?>


    <script src="js/modernizr.js"></script> <!-- Modernizr -->
    <script src="js/jquery.menu-aim.js"></script>
    <script src="js/main.js"></script> <!-- Resource jQuery -->


    <script>

        function genReport(frm, area, printable) {


            if (area) {

                $('#' + area + '_submit_report_button').hide();
                $('#' + area + '_loading_plx_wait_span').show();
            }

            var url = frm.action;

            if (printable) {
                url += "&no_nav=1";
            }

            $.post(url, $('#' + frm.id).serialize()).done(function (data) {

                if (printable) {

                    //$('#main_content').html(data);

                    var win = window.open("about:blank");
                    $(win.document.body).html(data);

                    win.focus();
                    //alert("open window here");

                } else {

                    $('#main_content').html(data);

                }


                if (area) {

                    $('#' + area + '_submit_report_button').show();
                    $('#' + area + '_loading_plx_wait_span').hide();
                }

            });
            return false;
        }


        function loadSection(url) {

            $('#main_content').load(url);


            $('.cd-side-nav').find('.hover').removeClass('hover');
            $('.cd-side-nav').find('.selected').removeClass('selected');
            $('.cd-side-nav').removeClass('nav-is-visible');
            $('.cd-main-header').find('.nav-is-visible').removeClass('nav-is-visible');
            //$("#menu").mouseleaveMenu();

        }


        function viewChangeHistory(area, areaid) {
            var objname = 'dialog-modal-view_change_history';


            $('#' + objname).dialog("open");

            $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

            $('#' + objname).load("index.php?area=action_log&view_change_history=1&view_area=" + encodeURI(area) + "&view_area_id=" + areaid + "&printable=1&no_script=1");

            $('#' + objname).dialog('option', 'position', 'center');

        }

        function applyUniformity() {
            $("input:submit, button, input:button").button();
            $("input:text, input:password, input:reset, input:checkbox, input:radio, input:file").uniform();
        }
    </script>
</head>
<body>
<?
    }

    // USER IS ALREADY LOGGED IN, PRESENT THE ADMIN INTERFACE
    if (isset($_SESSION['user']) && $_SESSION['user']['id'] > 0) {

        // NO_SCRIPT - shuts off extra interface stuff, because page being loaded via AJAX
        if (!isset($_REQUEST['no_script']) && !isset($_REQUEST['no_nav'])) {

            //$_SESSION['interface']->makeHeader();
            $_SESSION['interface']->makeNewHeader();

            if ($_REQUEST['area']) {

                ?>
                <script>
                    loadSection('<?=stripurl('no_script')?>&no_script=1');
                </script><?
            }

        } else {

            if (isset($_REQUEST['no_nav'])) {
                ?><div class="content-wrapper" id="main_content"><?
            }

            switch ($_REQUEST['area']) {
                case 'home':
                default:

                    include_once("classes/home.inc.php");
                    $_SESSION['home']->handleFLOW();

                    break;
                case 'activity_log':

                    if ($_SESSION['user']['priv'] < 5) {

                        accessDenied("ADMIN ONLY");

                    } else {

                        include_once("classes/activity_log.inc.php");
                        $_SESSION['activity_log']->handleFLOW();

                    }

                    break;

                case 'action_log':

                    if (!checkAccess('action_log')) {

                        accessDenied("Action Log");

                    } else {

                        include_once("classes/action_log.inc.php");
                        $_SESSION['action_log']->handleFLOW();

                    }

                    break;

                case 'campaigns':

                    if (
                    checkAccess('campaigns')
                        //	($_SESSION['user']['priv'] >= 5) || 	// ADMINS ALLOWED, OR
                        //	($_SESSION['user']['priv'] == 4 && $_SESSION['features']['campaigns'] == 'yes') // MANAGERS WITH CAMPAIGN ACCESS
                    ) {

                        include_once("classes/campaigns.inc.php");
                        $_SESSION['campaigns']->handleFLOW();
                    } else {

                        accessDenied("Campaigns");

                    }

                    break;

                case 'campaign_parents':
                    if (checkAccess('campaigns')) {
                        include_once("classes/cmpgn_parents.inc.php");
                        $_SESSION['cmpgn_parents']->handleFLOW();
                    } else {
                        accessDenied("Campaigns");
                    }
                    break;

                case 'form_builder':
                    if (checkAccess('campaigns')) {
                        include_once("classes/form_builder.inc.php");
                        $_SESSION['form_builder']->handleFLOW();
                    } else {
                        accessDenied("Campaigns");
                    }
                    break;

                case 'scripts':

                    if (($_SESSION['user']['priv'] >= 5) ||    // ADMINS ALLOWED, OR
                        ($_SESSION['user']['priv'] == 4 && $_SESSION['features']['scripts'] == 'yes') // MANAGERS WITH SCRIPT ACCESS
                    ) {

                        include_once("classes/scripts.inc.php");
                        $_SESSION['scripts']->handleFLOW();

                    } else {

                        accessDenied("Scripts");

                    }

//				if($_SESSION['user']['priv'] == 4 && ($_SESSION['user']['feat_config'] != 'yes' && $_SESSION['feat_advanced'] != 'yes')){
//
//					echo "You lack the ability to access this section. Access to config is denied.";
//
//				}else{
//
//					include_once("classes/scripts.inc.php");
//					$_SESSION['scripts']->handleFLOW();
//				}

                    break;

                case 'server_status':

                    if (($_SESSION['user']['priv'] >= 5) ||    // ADMINS ALLOWED, OR
                        ($_SESSION['user']['priv'] == 4 && $_SESSION['features']['server_status'] == 'yes') // MANAGERS WITH SERVER STATUS ACCESS
                    ) {

                        include_once("classes/server_status.inc.php");
                        $_SESSION['server_status']->handleFLOW();

                    } else {

                        accessDenied("Server Status");

                    }

//				if($_SESSION['user']['priv'] < 5){
//
//					echo "You lack the ability to access this section. Access to server status is denied.";
//
//				}else{
//
//					include_once("classes/server_status.inc.php");
//					$_SESSION['server_status']->handleFLOW();
//
//				}

                    break;

                case 'users':

                    if (($_SESSION['user']['priv'] >= 5) ||    // ADMINS ALLOWED, OR
                        ($_SESSION['user']['priv'] == 4 && $_SESSION['features']['users'] == 'yes') // MANAGERS WITH USERS ACCESS
                    ) {

                        include_once("classes/users.inc.php");
                        $_SESSION['users']->handleFLOW();

                    } else {

                        accessDenied("Users");

                    }

//				if($_SESSION['user']['priv'] == 4 && $_SESSION['user']['feat_advanced'] != 'yes'){
//
//					echo "You lack the ability to access this section. Access to advanced config is denied.";
//
//				}else{
//
//					include_once("classes/users.inc.php");
//					$_SESSION['users']->handleFLOW();
//
//				}

                    break;
                case 'extensions':

                    if (($_SESSION['user']['priv'] >= 5) ||    // ADMINS ALLOWED, OR
                        ($_SESSION['user']['priv'] == 4 && $_SESSION['features']['extensions'] == 'yes') // MANAGERS WITH Extensions ACCESS
                    ) {

                        include_once("classes/extensions.inc.php");
                        $_SESSION['extensions']->handleFLOW();

                    } else {

                        accessDenied("Extensions");

                    }

//				if($_SESSION['user']['priv'] == 4 && $_SESSION['user']['feat_advanced'] != 'yes'){
//
//					echo "You lack the ability to access this section. Access to advanced config is denied.";
//
//				}else{
//					include_once("classes/extensions.inc.php");
//					$_SESSION['extensions']->handleFLOW();
//
//				}

                    break;
//			case 'reports':
//
//				if($_SESSION['user']['priv'] == 4 && $_SESSION['user']['feat_reports'] != 'yes'){
//
//					echo "You lack the ability to access this section. Access to reports is denied.";
//
//				}else{
//
//					echo "Reports coming as soon as someone tells me what kind of reports they want";
//
//				}
//
//				break;
                case 'voices':

                    if (($_SESSION['user']['priv'] >= 5) ||    // ADMINS ALLOWED, OR
                        ($_SESSION['user']['priv'] == 4 && $_SESSION['features']['voices'] == 'yes') // MANAGERS WITH VOICES ACCESS
                    ) {

                        include_once("classes/voices.inc.php");
                        $_SESSION['voices']->handleFLOW();

                    } else {

                        accessDenied("Voices");

                    }

//				if($_SESSION['user']['priv'] == 4 && $_SESSION['user']['feat_advanced'] != 'yes'){
//
//					echo "You lack the ability to access this section. Access to advanced config is denied.";
//
//				}else{
//
//					include_once("classes/voices.inc.php");
//					$_SESSION['voices']->handleFLOW();
//				}

                    break;
                case 'messages':

                    if (($_SESSION['user']['priv'] >= 5) ||    // ADMINS ALLOWED, OR
                        ($_SESSION['user']['priv'] == 4 && $_SESSION['features']['messages'] == 'yes') // MANAGERS WITH MESSAGES ACCESS
                    ) {

                        include_once("classes/messages.inc.php");
                        $_SESSION['messages']->handleFLOW();

                    } else {

                        accessDenied("Messages");

                    }

//				if($_SESSION['user']['priv'] == 4 && $_SESSION['user']['feat_messages'] != 'yes'){
//
//					echo "You lack the ability to access this section. Access to messages is denied.";
//
//				}else{
//					include_once("classes/messages.inc.php");
//					$_SESSION['messages']->handleFLOW();
//				}

                    break;
                case 'names':

                    if (($_SESSION['user']['priv'] >= 5) ||    // ADMINS ALLOWED, OR
                        ($_SESSION['user']['priv'] == 4 && $_SESSION['features']['names'] == 'yes') // MANAGERS WITH NAMES ACCESS
                    ) {

                        include_once("classes/names.inc.php");
                        $_SESSION['names']->handleFLOW();

                    } else {

                        accessDenied("Names");

                    }

//				if($_SESSION['user']['priv'] == 4 && $_SESSION['feat_advanced'] != 'yes'){
//
//					echo "You lack the ability to access this section. Access to advanced config is denied.";
//
//				}else{
//
//					include_once("classes/names.inc.php");
//					$_SESSION['names']->handleFLOW();
//
//				}
                    break;

                case 'problems':

                    if (($_SESSION['user']['priv'] >= 5) ||    // ADMINS ALLOWED, OR
                        ($_SESSION['user']['priv'] == 4 && $_SESSION['features']['problems'] == 'yes') // MANAGERS WITH PROBLEMS ACCESS
                    ) {

                        include_once("classes/problems.inc.php");
                        $_SESSION['problems']->handleFLOW();

                    } else {

                        accessDenied("Problems");

                    }

//				if($_SESSION['user']['priv'] == 4 && $_SESSION['user']['feat_problems'] != 'yes'){
//
//					echo "You lack the ability to access this section. Access to problems is denied.";
//
//				}else{
//
//					include_once("classes/problems.inc.php");
//					$_SESSION['problems']->handleFLOW();
//				}

                    break;

                case 'ringing_calls':

                    if (($_SESSION['user']['priv'] >= 5) ||    // ADMINS ALLOWED, OR
                        ($_SESSION['user']['priv'] == 4 && $_SESSION['features']['ringing_calls'] == 'yes') // MANAGERS WITH ringing_calls ACCESS
                    ) {

                        include_once("classes/ringing_calls.inc.php");
                        $_SESSION['ringing_calls']->handleFLOW();

                    } else {

                        accessDenied("Ring Report");

                    }

                    break;

                case 'fronter_closer':

                    if (($_SESSION['user']['priv'] >= 5) ||    // ADMINS ALLOWED, OR
                        ($_SESSION['user']['priv'] == 4 && $_SESSION['features']['fronter_closer'] == 'yes') // MANAGERS WITH FRONTER/CLOSER ACCESS
                    ) {

                        include_once("classes/fronter_closer.inc.php");
                        $_SESSION['fronter_closer']->handleFLOW();

                    } else {

                        accessDenied("Fronter/Closer");

                    }

                    break;
                case 'lead_management':

                    if (($_SESSION['user']['priv'] >= 5) ||    // ADMINS ALLOWED, OR
                        ($_SESSION['user']['priv'] == 4 && $_SESSION['features']['lead_management'] == 'yes') // MANAGERS WITH LEAD MANAGEMENT ACCESS
                    ) {

                        include_once("classes/lead_management.inc.php");

                        $_SESSION['lead_management']->handleFLOW();

                    } else {

                        accessDenied("Lead Management");

                    }

                    break;

                case 'sales_analysis':

                    if (($_SESSION['user']['priv'] >= 5) ||    // ADMINS ALLOWED, OR
                        ($_SESSION['user']['priv'] == 4 && $_SESSION['features']['sales_analysis'] == 'yes') // MANAGERS WITH SALES ANAL. ACCESS
                    ) {

                        include_once("classes/sales_analysis.inc.php");
                        $_SESSION['sales_analysis']->handleFLOW();

                    } else {

                        accessDenied("Sales Analysis");

                    }

                    break;

                case 'summary_report':

                    include_once("classes/summary_report.inc.php");
                    $_SESSION['summary_report']->handleFLOW();

                    break;

                case 'employee_hours':

                    if (($_SESSION['user']['priv'] >= 5) ||    // ADMINS ALLOWED, OR
                        ($_SESSION['user']['priv'] == 4 && $_SESSION['features']['employee_hours'] == 'yes') // MANAGERS WITH EMPLOYEE HOURS ACCESS
                    ) {

                        include_once("classes/employee_hours.inc.php");

                        $_SESSION['employee_hours']->handleFLOW();

                    } else {

                        accessDenied("Employee Hours");

                    }

                    break;

                case 'recent_hangups':

                    if (($_SESSION['user']['priv'] >= 5) ||    // ADMINS ALLOWED, OR
                        ($_SESSION['user']['priv'] == 4 && $_SESSION['features']['recent_hangups'] == 'yes') // MANAGERS WITH RECENT HANGUPS ACCESS
                    ) {

                        include_once("classes/recent_hangups.inc.php");
                        $_SESSION['recent_hangups']->handleFLOW();

                    } else {

                        accessDenied("Recent Hangups");

                    }

                    break;

                case 'script_statistics':

                    if (($_SESSION['user']['priv'] >= 5) ||    // ADMINS ALLOWED, OR
                        ($_SESSION['user']['priv'] == 4 && $_SESSION['features']['script_statistics'] == 'yes') // MANAGERS WITH SCRIPT STATS ACCESS
                    ) {

                        include_once("classes/script_statistics.inc.php");
                        $_SESSION['script_statistics']->handleFLOW();

                    } else {

                        accessDenied("Script Statistics");

                    }

                    break;

                case 'rouster_report':

                    if (checkAccess('rouster_report')) {

//				if(	($_SESSION['user']['priv'] >= 5) || 	// ADMINS ALLOWED, OR
//					($_SESSION['user']['priv'] == 4 && $_SESSION['features']['agent_call_stats'] == 'yes') // MANAGERS WITH AGENT CALL STATS ACCESS
//				){

                        include_once("classes/rouster_report.inc.php");

                        $_SESSION['rouster_report']->handleFLOW();

                    } else {

                        accessDenied("Rouster Report");

                    }

                    break;

                case 'agent_call_stats':

//error_reporting(E_ALL);
//ini_set("display_errors", 1);

                    if (checkAccess('agent_call_stats')) {

//				if(	($_SESSION['user']['priv'] >= 5) || 	// ADMINS ALLOWED, OR
//					($_SESSION['user']['priv'] == 4 && $_SESSION['features']['agent_call_stats'] == 'yes') // MANAGERS WITH AGENT CALL STATS ACCESS
//				){

                        include_once("classes/agent_call_stats.inc.php");

                        $_SESSION['agent_call_stats']->handleFLOW();

                    } else {

                        accessDenied("Agent Call Stats");

                    }

                    break;

                case 'dispo_log':

//				if(	($_SESSION['user']['priv'] >= 5) || 	// ADMINS ALLOWED, OR
//					($_SESSION['user']['priv'] == 4 && $_SESSION['features']['dispo_log'] == 'yes') // MANAGERS WITH DISPO LOG ACCESS
//				){

                    if (checkAccess('dispo_log')) {

                        include_once("classes/dispo_log.inc.php");
                        $_SESSION['dispo_log']->handleFLOW();

                    } else {

                        accessDenied("Dispo Log");

                    }

                    break;

                case 'user_charts':

//				if(	($_SESSION['user']['priv'] >= 5) || 	// ADMINS ALLOWED, OR
//					($_SESSION['user']['priv'] == 4 && $_SESSION['features']['user_charts'] == 'yes') // MANAGERS WITH USER CHARTS ACCESS
//				){

                    if (checkAccess('user_charts')) {

                        include_once("classes/user_charts.inc.php");
                        $_SESSION['user_charts']->handleFLOW();

                    } else {

                        accessDenied("User Charts");

                    }

                    break;

                case 'feature_control':

                    if (checkAccess('feature_control')) {

                        include_once("classes/feature_control.inc.php");
                        $_SESSION['feature_control']->handleFLOW();

                    } else {
                        accessDenied("Feature Control");
                    }

                    break;

                case 'user_groups':
                    if (checkAccess('users')) {
                        include_once("classes/user_groups.inc.php");
                        $_SESSION['user_groups']->handleFLOW();
                    } else {
                        accessDenied("Users");
                    }
                    break;

                case 'user_groups_master':
                    if (checkAccess('users')) {
                        include_once("classes/user_groups_master.inc.php");
                        $_SESSION['user_groups_master']->handleFLOW();
                    } else {
                        accessDenied("Users");
                    }
                    break;

                case 'report_emails':
                    if (checkAccess('report_emails')) {
                        include_once("classes/report_emails.inc.php");
                        $_SESSION['report_emails']->handleFLOW();
                    } else {
                        accessDenied("Report Emails");
                    }
                    break;

                case 'list_tools':

                    include_once("classes/campaigns.inc.php");
                    include_once("classes/list_tools.inc.php");
                    $_SESSION['list_tools']->handleFLOW();

                    break;

                case 'pac_reports':

                    include_once("classes/pac_reports.inc.php");
                    $_SESSION['pac_reports']->handleFLOW();

                    break;

                case 'quiz_results':

                    include_once("classes/quiz_results.inc.php");
                    $_SESSION['quiz_results']->handleFLOW();

                    break;

                case 'quiz_questions':

                    include_once("classes/quiz_questions.inc.php");
                    $_SESSION['quiz_questions']->handleFLOW();

                    break;

                case 'phone_lookup':

                    include_once("classes/phone_lookup.inc.php");
                    $_SESSION['phone_lookup']->handleFLOW();

                    break;

//			case 'fec_filer':
//
//				include_once("classes/fec_filer.inc.php");
//				$_SESSION['fec_filer']->handleFLOW();
//
//				break;

                case 'change_password':

                    include_once("classes/change_password.inc.php");
                    $_SESSION['change_password']->handleFLOW();

                    break;
            }

            if (isset($_REQUEST['no_nav'])) {
                ?></div><?
            }

        }

        // USER NOT LOGGED IN, SHOW LOGIN SCREEN
    } else {

        include_once("classes/login.inc.php");

        $_SESSION['login'] = new LoginClass();

        $_SESSION['login']->makeLoginForm();

    }

?>
<script>

    applyUniformity();

</script><?

    // NO_SCRIPT - shuts off extra interface stuff, because page being loaded via AJAX
    if (!isset($_REQUEST['no_script']) || (isset($_REQUEST['force_scripts']) && $_REQUEST['force_scripts'])){
?></body>
</html><?
    }
