<?php
    /**
     * MAIN PAGE - The main entry point for the admin module.
     * Written By: Jonathan Will
     *
     */
    // ENSURE SESSION IS RUNNING, CAUSE WE NEED THAT SHIT
    session_start();
    $uri = NULL;
    // IF /dev2 HIT, KICK TO STAGING
    if (preg_match('/\/dev2\//', $_SERVER['REQUEST_URI'])) {
        $uri = preg_replace("/\/dev2\//", "/staging/", $_SERVER['REQUEST_URI']);
        // IF /dev HIT, KICK TO "reports" AKA PRODUCTION
    } else if (preg_match('/\/dev\//', $_SERVER['REQUEST_URI'])) {
        $uri = preg_replace("/\/dev\//", "/reports/", $_SERVER['REQUEST_URI']);
    }
    if ($uri != NULL) {
        header("Location: " . $uri);
        exit;
    }
    //print_r($_SERVER);
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
    include_once("classes/genericDD.inc.php");
    include_once("classes/interface.inc.php");
    include_once("classes/languages.inc.php");
    // DESTROY THE SESSION/LOGOUT ?o
    if (isset($_REQUEST['o'])) {
        if (isset($_SESSION['user']) && $_SESSION['user']['id'] > 0) {
            $_SESSION['dbapi']->users->updateLogoutTime();
        }
        session_unset();
        jsRedirect("index.php");
        exit;
    }
    /*?><!DOCTYPE HTML>
     <html>
     <head>
     <title>Project X - Management Tools and Reports</title>

     <link href='https://fonts.googleapis.com/css?family=Open+Sans:300,400,700' rel='stylesheet' type='text/css'>
     <link rel="stylesheet" type="text/css" href="css/style.css"/>
     <link rel="stylesheet" href="css/navstyle.css"> <!-- Resource style -->
     <link rel="stylesheet" type="text/css" href="css/cupertino/jquery-ui-1.10.3.custom.min.css"/><?**/
    // NO_SCRIPT - shuts off extra interface stuff, because page being loaded via AJAX
    if (!isset($_REQUEST['no_script']) || (isset($_REQUEST['force_scripts']) && $_REQUEST['force_scripts'])) {
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Project X - Management Tools and Reports</title>
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico"/>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script type="text/javascript" src="js/functions.js"></script>
    <!--    <link rel="stylesheet" type="text/css" href="css/reset.css">-->
    <META HTTP-EQUIV="Access-Control-Allow-Origin" CONTENT="http://skynet.advancedtci.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400italic,600,700%7COpen+Sans:300,400,400italic,600,700">
    <link rel="stylesheet" type="text/css" href="css/style.css"/>
    <!--    <link rel="stylesheet" type="text/css" href="css/navstyle.css"> -->
    <!--    <link rel="stylesheet" type="text/css" href="src/assets/js/plugins/jquery-ui/jquery-ui.min.css"/>-->
    <!--    <link rel="stylesheet" type="text/css" href="themes/default/css/uniform.default.css" media="screen"/>-->
    <!--    <link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.min.css"/>-->
    <link rel="stylesheet" type="text/css" href="css/jquery.dataTables.css"/>
    <link rel="stylesheet" id="css-main" href="src/assets/css/oneui.min.css">
    <script type="text/javascript" src="js/jquery-1.10.2.min.js"></script>
    <script type="text/javascript" src="src/assets/js/plugins/jquery-ui/jquery-ui.min.js"></script>
    <!--    <script type="text/javascript" src="js/popper.min.js"></script>-->
    <!--    <script type="text/javascript" src="bootstrap/js/bootstrap.min.js"></script>-->
    <!--    <script type="text/javascript" src="js/jquery.uniform.min.js"></script>-->
    <script type="text/javascript" src="js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="js/ajax_functions.js"></script>
    <script type="text/javascript" src="js/functions.js"></script>
    <script type="text/javascript" src="js/page_system.js"></script>
    <!--    <script type="text/javascript" src="js/modernizr.js"></script> -->
    <script>
        $('nav').ready(function () {
            $('span.nav-main-link-name').not('#change_password').each(function () {
                $(this).on('click', function (e) {
                    loadSection($(this).closest('a.nav-main-link').attr('href'));
                    e.preventDefault();
                });
            });
            $('#change_password').on('click', function () {
                loadChangePassword();
            });
        });
        var dispTimer = false;

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
                    var win = window.open("about:blank");
                    $(win.document.body).html(data);
                    win.focus();
                    //alert("open window here");
                } else {
                    $('#main-container').html(data);
                }
                if (area) {
                    $('#' + area + '_submit_report_button').show();
                    $('#' + area + '_loading_plx_wait_span').hide();
                }
            });
            return false;
        }

        function download(type, filename, text) {
            let element = document.createElement('a');
            element.setAttribute('href', 'data:text/' + type + ';charset=utf-8,' + encodeURIComponent(text));
            element.setAttribute('download', filename);
            element.style.display = 'none';
            document.body.appendChild(element);
            element.click();
            document.body.removeChild(element);
        }

        function genCSV(tableElement) {
            $(tableElement).each(function () {
                let $table = $(this);
                let dFile = $('#reportTitle').val() + '.csv';
                let csv = $table.table2CSV({
                    delivery: 'value',
                    filename: dFile
                });
                download('csv', dFile, csv);
                // let hdrs = 'data:text/csv;charset=UTF-8,' + encodeURIComponent(csv);
                // window.location.download = dFile;
                // window.location.href = hdrs;
            });
        }

        function loadSection(url) {
            $('#main-container').empty();
            $('#main-container').html('<table class="tightTable"><tr><td class="align-center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>').load(url);
            if (dispTimer) {
                clearInterval(dispTimer);
                dispTimer = false;
            }
        }

        function viewChangeHistory(area, areaid) {
            var objname = 'dialog-modal-view_change_history';
            $('#' + objname).dialog("open");
            $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
            $('#' + objname).load("index.php?area=action_log&view_change_history=1&view_area=" + encodeURI(area) + "&view_area_id=" + areaid + "&printable=1&no_script=1");
            $('#' + objname).dialog('option', 'position', 'center');
        }

        function applyUniformity() {
            return;
            $("input:submit, button, input:button").button();
            $("input:text, input:password, input:reset, input:checkbox, input:radio, input:file").uniform();
            $('.priorityRender').each(function (index) {
                $(this).html(
                    priorityProcessing($(this).html())
                );
                // console.log( index + ": " + $( this ).text() );
            });
        }
    </script>
</head>
<body>
<div id="page-container" class="sidebar-o sidebar-dark enable-page-overlay side-scroll page-header-fixed enable-cookies">
    <aside id="side-overlay"></aside>
    <?
        }
        // USER IS ALREADY LOGGED IN, PRESENT THE ADMIN INTERFACE
        if (isset($_SESSION['user']) && $_SESSION['user']['id'] > 0) {
        // RELOAD THE USER/ACCOUNT/FEATURE SET, MAKE SURE USER STILL ENABLED, ACCOUNT STILL ACTIVE, ETC
        $_SESSION['dbapi']->users->refreshFeaturesAndPrivs();
        $_SESSION['dbapi']->users->updateLastActionTime();
        // NO_SCRIPT - shuts off extra interface stuff, because page being loaded via AJAX
        if (!isset($_REQUEST['no_script']) && !isset($_REQUEST['no_nav'])) {
        ?>
        <nav id="sidebar" aria-label="Main Navigation">
            <ul class="nav-main">
                <div class="content-header bg-white-5">
                    <!-- Logo -->
                    <a class="navbar-brand" href="index.php"><img src="images/cci-logo-200-2.png" height="30" border="0"></a>
                </div>
                <?
                    if (checkAccess('campaigns') || checkAccess('voices') || checkAccess('names') || checkAccess('scripts')) {
                        ?>
                        <li class="nav-main-item">
                            <a href="#" class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false">
                                <i class="nav-main-link-icon si si-energy"></i>
                                <span class="nav-main-heading">Campaigns</span>
                            </a>
                            <ul class="nav-main-submenu">
                                <?
                                    if (checkAccess('campaigns')) {
                                        ?>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="?area=campaigns&no_script=1">
                                                <span class="nav-main-link-name">Campaign Setup</span>
                                            </a>
                                        </li>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="?area=campaign_parents&no_script=1">
                                                <span class="nav-main-link-name">Campaign Parents</span>
                                            </a>
                                        </li>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="?area=form_builder&no_script=1">
                                                <span class="nav-main-link-name">Form Builder</span>
                                            </a>
                                        </li>
                                        <?
                                    }
                                    if (checkAccess('voices')) {
                                        ?>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="?area=voices&no_script=1">
                                                <span class="nav-main-link-name">Voices</span>
                                            </a>
                                        </li>
                                        <?
                                    }
                                    if (checkAccess('names')) {
                                        ?>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="?area=names&no_script=1">
                                                <span class="nav-main-link-name">Names</span>
                                            </a>
                                        </li>
                                        <?
                                    }
                                    if (checkAccess('scripts')) {
                                        ?>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="?area=scripts&no_script=1">
                                                <span class="nav-main-link-name">Scripts</span>
                                            </a>
                                        </li>
                                        <?
                                    }
                                    if (checkAccess('quiz_questions')) {
                                        ?>
                                        <li class="nav-main-item">
                                            <a class="nav-main-link" href="?area=quiz_questions&no_script=1">
                                                <span class="nav-main-link-name">Quiz Questions</span>
                                            </a>
                                        </li>
                                        <?
                                    }
                                ?>
                            </ul>
                        </li>
                        <?
                    }
                    if (checkAccess('sales_management') || checkAccess('lead_management') || checkAccess('employee_hours') || checkAccess('ringing_calls') || checkAccess('messages') || checkAccess('server_status') || checkAccess('extensions')) {
                        ?>
                        <li class="nav-main-item">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Management Tools
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                <?
                                    if (checkAccess('lead_management')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=lead_management&no_script=1">Lead Management</a>
                                        <?
                                    }
                                    if (checkAccess('sales_management')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=sales_management&no_script=1">Sales Management</a>
                                        <?
                                    }
                                    if (checkAccess('employee_hours')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=employee_hours&no_script=1">Employee Hours</a>
                                        <?
                                    }
                                    if (checkAccess('phone_lookup')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=phone_lookup&no_script=1">DRIPP Lookup</a>
                                        <?
                                    }
                                    if (checkAccess('quiz_results')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=quiz_results&no_script=1">Quiz Results</a>
                                        <?
                                    }
                                    if (checkAccess('ringing_calls')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=ringing_calls&no_script=1">Ring Report</a>
                                        <?
                                    }
                                    if (checkAccess('messages')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=messages&no_script=1">Agent Messages</a>
                                        <?
                                    }
                                    if (checkAccess('dialer_status')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=dialer_status&no_script=1">Dialer Status</a>
                                        <?
                                    }
                                    if (checkAccess('server_status')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=server_status&no_script=1">Server Status</a>
                                        <?
                                    }
                                    if (checkAccess('extensions')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=extensions&no_script=1">Extensons</a>
                                        <?
                                    }
                                ?>
                            </div>
                        </li>
                        <?
                    }
                    if (checkAccess('list_tools')) {
                        ?>
                        <li class="nav-main-item">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                List Tools
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                <a class="dropdown-item" href="?area=list_tools&tool=build_list&no_script=1">List Builder</a>
                                <a class="dropdown-item" href="?area=list_tools&tool=dnc_tools&no_script=1">DNC Management</a>
                                <a class="dropdown-item" href="?area=list_tools&tool=manage_lists&no_script=1">Vici List Management</a>
                                <a class="dropdown-item" href="?area=list_tools&tool=tasks&no_script=1">Task / Status Management</a>
                                <a class="dropdown-item" href="?area=list_tools&tool=load_list&no_script=1">Import Leads</a>
                                <a class="dropdown-item" href="?area=list_tools&tool=view_importss&no_script=1">List Imports / Counts</a>
                                <a class="dropdown-item" href="?area=list_tools&tool=performance_reports&no_script=1">List Performance Report</a>
                                <a class="dropdown-item" href="?area=list_tools&tool=vici_report&no_script=1">Vicidial List Count</a>
                            </div>
                        </li>
                        <?
                    }
                    if (checkAccess('fronter_closer') || checkAccess('sales_analysis') || checkAccess('agent_call_stats') || checkAccess('user_charts') || checkAccess('recent_hangups') || checkAccess('script_statistics') || checkAccess('dispo_log') || checkAccess('capacity_report') || checkAccess('report_emails') || checkAccess('user_status_report')) {
                        ?>
                        <li class="nav-main-item">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Reports
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                <?
                                    if (checkAccess('fronter_closer')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=fronter_closer&no_script=1">Fronter / Closer</a>
                                        <?
                                    }
                                    if (checkAccess('sales_analysis')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=sales_analysis&no_script=1">Sales Analysis</a>
                                        <?
                                    }
                                    if (checkAccess('agent_call_stats')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=agent_call_stats&no_script=1">Verifier Call Stats</a>
                                        <?
                                    }
                                    if (checkAccess('rouster_report')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=rouster_report&no_script=1">Rouster Call Stats</a>
                                        <?
                                    }
                                    if (checkAccess('summary_report')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=summary_report&no_script=1">Summary Report</a>
                                        <?
                                    }
                                    if (checkAccess('dialer_sales')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=dialer_sales&no_script=1">Area Code Sales By Dialer</a>
                                        <?
                                    }
                                    if (checkAccess('user_charts')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=user_charts&no_script=1">User Charts</a>
                                        <?
                                    }
                                    if (checkAccess('recent_hangups')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=recent_hangups&no_script=1">Recent Hangups</a>
                                        <?
                                    }
                                    if (checkAccess('dispo_log')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=dispo_log&no_script=1">Disposition Logs</a>
                                        <?
                                    }
                                    if (checkAccess('capacity_report')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=capacity_report&no_script=1">Capacity Reports</a>
                                        <?
                                    }
                                    if (checkAccess('report_emails')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=report_emails&no_script=1">Report Email Setup</a>
                                        <?
                                    }
                                    if (checkAccess('user_status_report')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=user_status_report&no_script=1">User Status Report</a>
                                        <?
                                    }
                                ?>
                            </div>
                        </li>
                        <?
                    }
                    if (checkAccess('pac_web_donations')) {
                        ?>
                        <li class="nav-main-item">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                PACs Maintenance
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                <?
                                    if (checkAccess('pac_reports')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=pac_reports&no_script=1">Web Donations</a>
                                        <?
                                    }
                                ?>
                            </div>
                        </li>
                        <?
                    }
                    if (checkAccess('users')) {
                        ?>
                        <li class="nav-main-item">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Users
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                <a class="dropdown-item" href="?area=users&no_script=1">Search / List Users</a>
                                <a class="dropdown-item" href="?area=user_groups&no_script=1">Group Manager</a>
                                <a class="dropdown-item" href="?area=user_teams&no_script=1">Team Manager</a>
                                <a class="dropdown-item" href="?area=user_groups_master&no_script=1">Master Groups Manager</a>
                                <?
                                    if (checkAccess('feature_control')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=feature_control&no_script=1">Feature Control</a>
                                        <?
                                    }
                                    if (checkAccess('login_tracker')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=login_tracker&no_script=1">Login tracker</a>
                                        <?
                                    }
                                    if (checkAccess('action_log')) {
                                        ?>
                                        <a class="dropdown-item" href="?area=action_log&no_script=1">Action Logs!</a>
                                        <?
                                    }
                                ?>
                            </div>
                        </li>
                        <?
                    }
                ?>
            </ul>

        </nav>
        <header id="page-header">
            <!-- Header Content -->
            <div class="content-header">
                <!-- Left Section -->
                <div class="d-flex align-items-center">
                    <!-- Toggle Sidebar -->
                    <!-- Layout API, functionality initialized in Template._uiApiLayout()-->
                    <button type="button" class="btn btn-sm btn-dual mr-2 d-lg-none" data-toggle="layout" data-action="sidebar_toggle">
                        <i class="fa fa-fw fa-bars"></i>
                    </button>
                    <!-- END Toggle Sidebar -->

                    <!-- Toggle Mini Sidebar -->
                    <!-- Layout API, functionality initialized in Template._uiApiLayout()-->
                    <button type="button" class="btn btn-sm btn-dual mr-2 d-none d-lg-inline-block" data-toggle="layout" data-action="sidebar_mini_toggle">
                        <i class="fa fa-fw fa-ellipsis-v"></i>
                    </button>
                    <!-- END Toggle Mini Sidebar -->

                    <!-- Apps Modal -->
                    <!-- Opens the Apps modal found at the bottom of the page, after footer’s markup -->
                    <button type="button" class="btn btn-sm btn-dual mr-2" data-toggle="modal" data-target="#one-modal-apps">
                        <i class="si si-grid"></i>
                    </button>
                    <!-- END Apps Modal -->
                </div>
                <!-- END Left Section -->

                <!-- Right Section -->
                <div class="d-flex align-items-center">
                    <!-- User Dropdown -->
                    <div class="dropdown d-inline-block ml-2">
                        <button type="button" class="btn btn-sm btn-dual" id="page-header-user-dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="d-none d-sm-inline-block ml-1"><?= $_SESSION["user"]["username"]; ?></span>
                            <i class="fa fa-fw fa-angle-down d-none d-sm-inline-block"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right p-0 border-0 font-size-sm" aria-labelledby="page-header-user-dropdown">
                            <div class="p-2">
                                <h5 class="dropdown-header text-uppercase">Actions</h5>
                                <a class="dropdown-item d-flex align-items-center justify-content-between" id="change_password" href="#">
                                    <span>Change Password</span>
                                    <i class="si si-lock ml-1"></i>
                                </a>
                                <a class="dropdown-item d-flex align-items-center justify-content-between" href="?o">
                                    <span>Log Out</span>
                                    <i class="si si-logout ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- END User Dropdown -->

                    <!-- Notifications Dropdown -->
                    <div class="dropdown d-inline-block ml-2">
                        <button type="button" class="btn btn-sm btn-dual" id="page-header-notifications-dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="si si-bell"></i>
                            <span class="badge badge-primary badge-pill">0</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right p-0 border-0 font-size-sm" aria-labelledby="page-header-notifications-dropdown">
                            <div class="p-2 bg-primary text-center">
                                <h5 class="dropdown-header text-uppercase text-white">Notifications</h5>
                            </div>
                            <div class="p-2 border-top">
                                <a class="btn btn-sm btn-light btn-block text-center" href="javascript:void(0)">
                                    <i class="fa fa-fw fa-bell-slash mr-1"></i> No Notifications
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- END Notifications Dropdown -->
                </div>
                <!-- END Right Section -->
            </div>
            <!-- END Header Content -->

            <!-- Header Loader -->
            <!-- Please check out the Loaders page under Components category to see examples of showing/hiding it -->
            <div id="page-header-loader" class="overlay-header bg-white">
                <div class="content-header">
                    <div class="w-100 text-center">
                        <i class="fa fa-fw fa-circle-notch fa-spin"></i>
                    </div>
                </div>
            </div>
            <!-- END Header Loader -->
        </header>
    <?
        if (isset($_REQUEST['area']) && $_REQUEST['area']) {
    ?>
        <script>
            loadSection('<?=stripurl('no_script')?>&no_script=1');
        </script>
        <?
    }
        $_SESSION['interface']->makeNewheader();
    } else {
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
                if (checkAccess('campaigns')
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
                break;
            case 'voices':
                if (($_SESSION['user']['priv'] >= 5) ||    // ADMINS ALLOWED, OR
                    ($_SESSION['user']['priv'] == 4 && $_SESSION['features']['voices'] == 'yes') // MANAGERS WITH VOICES ACCESS
                ) {
                    include_once("classes/voices.inc.php");
                    $_SESSION['voices']->handleFLOW();
                } else {
                    accessDenied("Voices");
                }
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
                break;
            case 'login_tracker':
                if (($_SESSION['user']['priv'] >= 5) ||    // ADMINS ALLOWED, OR
                    ($_SESSION['user']['priv'] == 4 && $_SESSION['features']['login_tracker'] == 'yes') // MANAGERS WITH LOGIN TRACKER ACCESS
                ) {
                    include_once("classes/login_tracker.inc.php");
                    $_SESSION['login_tracker']->handleFLOW();
                } else {
                    accessDenied("LoginTracker");
                }
                break;
            case 'user_status_report':
                if (($_SESSION['user']['priv'] >= 5) ||    // ADMINS ALLOWED, OR
                    ($_SESSION['user']['priv'] == 4 && $_SESSION['features']['user_status_report'] == 'yes') // MANAGERS WITH USER STATUS REPORT ACCESS
                ) {
                    include_once("classes/user_status_report.inc.php");
                    $_SESSION['user_status_report']->handleFLOW();
                } else {
                    accessDenied("UserStatusReport");
                }
                break;
            case 'user_teams':
                if (checkAccess('user_teams')) {
                    include_once("classes/user_teams.inc.php");
                    $_SESSION['user_teams']->handleFLOW();
                } else {
                    accessDenied("UserTeams");
                }
                break;
            case 'process_tracker_schedules':
                if (($_SESSION['user']['priv'] >= 5) ||    // ADMINS ALLOWED, OR
                    ($_SESSION['user']['priv'] == 4 && $_SESSION['features']['process_tracker_schedules'] == 'yes') // MANAGERS WITH USER STATUS REPORT ACCESS
                ) {
                    include_once("classes/process_tracker_schedules.inc.php");
                    $_SESSION['process_tracker_schedules']->handleFLOW();
                } else {
                    accessDenied("ProcessTrackerSchedules");
                }
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
            case 'sales_management':
                if (checkAccess('sales_management') // MANAGERS WITH LEAD MANAGEMENT ACCESS
                ) {
                    include_once("classes/sales_management.inc.php");
                    $_SESSION['sales_management']->handleFLOW();
                } else {
                    accessDenied("Sales Management");
                }
                break;
            case 'lead_management':
                if (checkAccess('lead_management') // MANAGERS WITH LEAD MANAGEMENT ACCESS
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
            case 'dialer_sales':
                include_once("classes/dialer_sales.inc.php");
                $_SESSION['dialer_sales']->handleFlow();
                break;
            case 'dialer_status':
                include_once("classes/dialer_status.inc.php");
                $_SESSION['dialer_status']->handleFlow();
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
            case 'capacity_report':
                include_once("classes/capacity_report.inc.php");
                $_SESSION['capacity_report']->handleFLOW();
                break;
            case 'callerid_stats_report':
                include_once("classes/callerid_stats_report.inc.php");
                $_SESSION['callerid_stats_report']->handleFLOW();
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
            case 'change_expired_password':
                include_once("classes/change_password.inc.php");
                $_SESSION['change_password']->handleFLOW(true);
                break;
        }
        if (isset($_REQUEST['no_nav'])) {
    ?></div><?
    }
    }
    // USER NOT LOGGED IN, SHOW LOGIN SCREEN
    } else {
        # GENERATE NEW LOGIN SALT AND ADD TO SESSION IF THERE IS NONE
        if (!isset($_SESSION['login_salt'])) {
            $_SESSION['login_salt'] = $_SESSION['dbapi']->users->generateSalt();
        }
        include_once("classes/login.inc.php");
        $_SESSION['login'] = new LoginClass();
        $_SESSION['login']->makeLoginForm();
    }
?>
<footer id="page-footer"></footer>
</div>
<!-- OneUI JS Core -->
<script src="src/assets/js/oneui.core.min.js"></script>
<!-- OneUI JS Custom -->
<script src="src/assets/js/oneui.app.min.js"></script>
</body>
</html>
