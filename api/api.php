<?php
    /**
     * API Gateway - XML interface between front end and database
     * Written by: Jonathan Will
     *
     *
     * globals:
     *        $api    :    API Core Functions class
     */
    session_start();
## INIT
    $basedir = "../";
    ## CORE FUNCTIONS
    include_once($basedir . 'api/functions.api.php');
    include_once($basedir . 'utils/rendertime.php');
    include_once($basedir . 'utils/functions.php');
    include_once($basedir . 'utils/feature_functions.php');
    // NEEDED FOR TEMPLATE APPLY FUNCTION
    include_once($basedir . "classes/vici_templates.inc.php");
    ## INIT SESSION CLASS $api
    $_SESSION['api'] = new API_Functions();
## FILE HEADER
    $_SESSION['api']->outputFileHeader();
## AUTHENTICATION
    ## SESSION AUTH
    if (!$_SESSION['user']['id']) {
        $_SESSION['api']->errorOut('Not logged in.', true, -101);
    }
## START MAIN FLOW
    ## BASE INCLUDES - SITE CONFIG + DATABASE CONNECTION
    include_once($basedir . "site_config.php");
    include_once($basedir . "dbapi/dbapi.inc.php");
    include_once($basedir . "db.inc.php");
    include_once($basedir . 'utils/db_utils.php');
    // UPDATE THE USERS LAST ACTION TIME
    $_SESSION['dbapi']->users->updateLastActionTime();
## SELECT THE DATA TYPES TO RETRIEVE
    switch ($_REQUEST['get']) {
        default:
            $_SESSION['api']->errorOut("Data type not specified.");
            break;
        ## SECONDARY AJAX - BULK INFORMATION GRABBING FOR POST-LIST-RENDERING DATA LOADING/POPULATION
        case 'secondary_ajax':
            switch ($_REQUEST['area']) {
                default:
                    $_SESSION['api']->errorOut("Area not specified.");
                    break;
                case 'name':
                    include_once($basedir . "api/names.api.php");
                    $names = new API_Names();
                    $names->handleSecondaryAjax();
                    break;
                case 'login_tracker':
                case 'login':
                    include_once($basedir . "api/login_tracker.api.php");
                    $login_tracker = new API_LoginTracker();
                    $login_tracker->handleSecondaryAjax();
                    break;
                case 'voice':
                    include_once($basedir . "api/voices.api.php");
                    $voices = new API_Voices();
                    $voices->handleSecondaryAjax();
                    break;
                case 'extension':
                    include_once($basedir . "api/extensions.api.php");
                    $ext = new API_Extensions();
                    $ext->handleSecondaryAjax();
                    break;
                case 'script':
                    include_once($basedir . "api/scripts.api.php");
                    $scr = new API_Scripts();
                    $scr->handleSecondaryAjax();
                    break;
                case 'message':
                    include_once($basedir . "api/messages.api.php");
                    $messages = new API_Messages();
                    $messages->handleSecondaryAjax();
                    break;
                case 'problem':
                    include_once($basedir . "api/problems.api.php");
                    $problems = new API_Problems();
                    $problems->handleSecondaryAjax();
                    break;
                case 'scriptstat':
                    include_once($basedir . "api/script_statistics.api.php");
                    $scriptstats = new API_Script_Statistics();
                    $scriptstats->handleSecondaryAjax();
                    break;
                case 'lead':
                    include_once($basedir . "api/lead_management.api.php");
                    $leads = new API_Lead_Management();
                    $leads->handleSecondaryAjax();
                    break;
                case 'ringing_calls':
                    include_once($basedir . "api/ringing_calls.api.php");
                    $rings = new API_Ringing_Calls();
                    $rings->handleSecondaryAjax();
                    break;
                case 'dispo_log':
                    include_once($basedir . "api/dispo_log.api.php");
                    $dispo = new API_Dispo_Log();
                    $dispo->handleSecondaryAjax();
                    break;
                case 'feature':
                    include_once($basedir . "api/feature_control.api.php");
                    $feat = new API_Features();
                    $feat->handleSecondaryAjax();
                    break;
                case 'usergroup':
                    include_once($basedir . "api/user_groups.api.php");
                    $obj = new API_UserGroups();
                    $obj->handleSecondaryAjax();
                    break;
                case 'user_groups_master':
                    include_once($basedir . "api/user_groups_master.api.php");
                    $obj = new API_UserGroupsMaster();
                    $obj->handleSecondaryAjax();
                    break;
                case 'userteam':
                    include_once($basedir . "api/user_teams.api.php");
                    $obj = new API_UserTeams();
                    $obj->handleSecondaryAjax();
                    break;
                case 'form_builder':
                    include_once($basedir . "api/form_builder.api.php");
                    $obj = new API_FormBuilder();
                    $obj->handleSecondaryAjax();
                    break;
                case 'action_log':
                    include_once($basedir . "api/action_log.api.php");
                    $al = new API_ActionLog();
                    $al->handleSecondaryAjax();
                    break;
                case 'import':
                    include_once($basedir . "api/list_tool_imports.api.php");
                    $im = new API_ListToolImport();
                    $im->handleSecondaryAjax();
                    break;
                case 'task':
                    include_once($basedir . "api/list_tool_tasks.api.php");
                    include_once($basedir . "classes/JXMLP.inc.php");
                    $lt = new API_Tasks();
                    $lt->handleSecondaryAjax();
                    break;
                case 'report_email':
                case 'report':
                    include_once($basedir . "api/report_emails.api.php");
                    $al = new API_ReportEmails();
                    $al->handleSecondaryAjax();
                    break;
                case 'quiz_results':
                case 'quiz':
                    include_once($basedir . "api/quiz_results.api.php");
                    $obj = new API_QuizResults();
                    $obj->handleSecondaryAjax();
                    break;
                case 'question':
                case 'quiz_question':
                    include_once($basedir . "api/quiz_questions.api.php");
                    $obj = new API_Questions();
                    $obj->handleSecondaryAjax();
                    break;
                case 'sales_management':
                case 'sale':
                    include_once($basedir . "classes/home.inc.php");
                    include_once($basedir . "api/sales_management.api.php");
                    $obj = new API_Sales_Management();
                    $obj->handleSecondaryAjax();
                    break;
            }
            break;
        case 'activity_log':
            include_once($basedir . "api/activity_log.api.php");
            $activitys = new API_Activitys();
            $activitys->handleAPI();
            break;
        case 'action_log':
            include_once($basedir . "api/action_log.api.php");
            $al = new API_ActionLog();
            $al->handleAPI();
            break;
        case 'campaigns':
            include_once($basedir . "api/campaigns.api.php");
            $campaigns = new API_Campaigns();
            $campaigns->handleAPI();
            break;
        case 'campaign_parents':
            include_once($basedir . "api/cmpgn_parents.api.php");
            $campaign_parents = new API_CampaignParents();
            $campaign_parents->handleAPI();
            break;
        case 'dialer_status':
            include_once($basedir . "api/dialer_status.api.php");
            $dialer_status = new API_DialerStatus();
            $dialer_status->handleAPI();
            break;
        case 'home':
            include_once($basedir . "api/home.api.php");
            $home_screen = new API_Home();
            $home_screen->handleAPI();
            break;
        case 'extensions':
            include_once($basedir . "api/extensions.api.php");
            $extensions = new API_Extensions();
            $extensions->handleAPI();
            break;
        case 'messages':
            include_once($basedir . "api/messages.api.php");
            $messages = new API_Messages();
            $messages->handleAPI();
            break;
        case 'names':
            include_once($basedir . "api/names.api.php");
            $names = new API_Names();
            $names->handleAPI();
            break;
        case 'login_tracker':
            include_once($basedir . "api/login_tracker.api.php");
            $login_tracker = new API_LoginTracker();
            $login_tracker->handleAPI();
            break;
        case 'problems':
            include_once($basedir . "api/problems.api.php");
            $problems = new API_Problems();
            $problems->handleAPI();
            break;
        case 'scripts':
            include_once($basedir . "api/scripts.api.php");
            $scripts = new API_Scripts();
            $scripts->handleAPI();
            break;
        case 'voices':
            include_once($basedir . "api/voices.api.php");
            $voices = new API_Voices();
            $voices->handleAPI();
            break;
        case 'users':
            include_once($basedir . "api/users.api.php");
            $users = new API_Users();
            $users->handleAPI();
            break;
        case 'scriptstats':
            include_once($basedir . "api/script_statistics.api.php");
            $scripts = new API_Script_Statistics();
            $scripts->handleAPI();
            break;
        case 'lead_management':
            include_once($basedir . "api/lead_management.api.php");
            $leads = new API_Lead_Management();
            $leads->handleAPI();
            break;
        case 'employee_hours':
            include_once($basedir . "api/employee_hours.api.php");
            $employee_hours = new API_Employee_Hours();
            $employee_hours->handleAPI();
            break;
        case 'ringing_calls':
            include_once($basedir . "api/ringing_calls.api.php");
            $rings = new API_Ringing_Calls();
            $rings->handleAPI();
            break;
        case 'dispo_log':
            include_once($basedir . "api/dispo_log.api.php");
            $dispos = new API_Dispo_Log();
            $dispos->handleAPI();
            break;
        // FEATURE CONTROL
        case 'features':
            include_once($basedir . "api/feature_control.api.php");
            $feat = new API_Features();
            $feat->handleAPI();
            break;
        case 'usergroups':
        case 'user_groups':
            include_once($basedir . "api/user_groups.api.php");
            $obj = new API_UserGroups();
            $obj->handleAPI();
            break;
        case 'userteams':
        case 'user_teams':
            include_once($basedir . "api/user_teams.api.php");
            $obj = new API_UserTeams();
            $obj->handleAPI();
            break;
        case 'user_groups_master':
            include_once($basedir . "api/user_groups_master.api.php");
            $obj = new API_UserGroupsMaster();
            $obj->handleAPI();
            break;
        case 'form_builder':
            include_once($basedir . "api/form_builder.api.php");
            $obj = new API_FormBuilder();
            $obj->handleAPI();
            break;
        case 'change_password':
            include_once($basedir . "api/change_password.api.php");
            $changepw = new API_ChangePassword();
            $changepw->handleAPI();
            break;
        case 'report_emails':
        case 'reports':
            include_once($basedir . "api/report_emails.api.php");
            $re = new API_ReportEmails();
            $re->handleAPI();
            break;
        case 'verifier_testing_tool':
            include_once($basedir . "api/verifier_testing_tool.api.php");
            $obj = new API_VerifierTestingTool();
            $obj->handleAPI();
            break;
        case 'list_tool_tasks':
            include_once($basedir . "api/list_tool_tasks.api.php");
            $obj = new API_Tasks();
            $obj->handleAPI();
            break;
        case 'list_tool_imports':
        case 'imports':
            include_once($basedir . "api/list_tool_imports.api.php");
            $obj = new API_ListToolImport();
            $obj->handleAPI();
            break;
        case 'pac_reports':
        case 'pacs':
            include_once($basedir . "api/pac_reports.api.php");
            $obj = new API_PACReports();
            $obj->handleAPI();
            break;
        case 'quiz_results':
            include_once($basedir . "api/quiz_results.api.php");
            $obj = new API_QuizResults();
            $obj->handleAPI();
            break;
        case 'quiz_question':
        case 'quiz_questions':
        case 'questions':
            include_once($basedir . "api/quiz_questions.api.php");
            $obj = new API_Questions();
            $obj->handleAPI();
            break;
        case 'my_notes':
        case 'notes':
        case 'note':
            include_once($basedir . "api/home_tile_notes.api.php");
            $obj = new API_MyNotes();
            $obj->handleAPI();
            break;
        case 'user_count':
            include_once($basedir . "classes/home.inc.php");
            include_once($basedir . "api/home_tile_user_count.api.php");
            $obj = new API_HomeTileUserCount();
            $obj->handleAPI();
            break;
        case 'sales_management':
            include_once($basedir . "classes/home.inc.php");
            include_once($basedir . "api/sales_management.api.php");
            $obj = new API_Sales_Management();
            $obj->handleAPI();
            break;
    }
