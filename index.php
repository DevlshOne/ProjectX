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
// NO_SCRIPT - shuts off extra interface stuff, because page being loaded via AJAX
if (!isset($_REQUEST['no_script']) || (isset($_REQUEST['force_scripts']) && $_REQUEST['force_scripts'])) {
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Project X - Management Tools and Reports</title>
    <META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
    <META HTTP-EQUIV="EXPIRES" CONTENT="Mon, 22 Jul 2002 11:11:11 GMT">

    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico"/>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script type="text/javascript" src="js/functions.js"></script>
    <!--    <link rel="stylesheet" type="text/css" href="css/reset.css">-->
    <META HTTP-EQUIV="Access-Control-Allow-Origin" CONTENT="http://skynet.advancedtci.com">
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400italic,600,700%7COpen+Sans:300,400,400italic,600,700">
   <?/* <link rel="stylesheet" type="text/css" href="css/jquery.dataTables.css" />*/?>
    <link rel="stylesheet" id="css-main" href="src/assets/css/oneui.min.css" />

    <link rel="stylesheet" type="text/css" href="src/assets/js/plugins/datatables/dataTables.bootstrap4.css" />
    <link rel="stylesheet" type="text/css" href="src/assets/js/plugins/datatables/buttons-bs4/buttons.bootstrap4.min.css">

    <link rel="stylesheet" href="css/jquery-ui.min.css" />
    <link rel="stylesheet" type="text/css" href="css/style.css"/>

    <script type="text/javascript" src="src/assets/js/core/jquery.min.js"></script>

    <script type="text/javascript" src="js/ajax_functions.js"></script>
    <script type="text/javascript" src="js/functions.js"></script>
    <script type="text/javascript" src="js/page_system.js"></script>
    <script type="text/javascript" src="src/assets/js/plugins/jquery-ui/jquery-ui.min.js"></script>




   <?/* <script type="text/javascript" src="js/jquery.dataTables.min.js"></script>*/?>
    <script>
        var feDebug = false;
        $('nav').ready(function () {
            // $('span.nav-main-link-name').not('#change_password').each(function () {
            //     $(this).on('click', function (e) {
            //         e.preventDefault();
            //         $(this).addClass('active');
            //         loadSection($(this).parent('a.nav-main-link').attr('href'));
            //     });
            // });
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
            One.block('state_loading', '#main-container');
            $('#main-container').empty().load(url);
            // $('#main-container').empty().html('<table class="tightTable"><tr><td class="align-center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>').load(url);
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
            $('input').addClass('form-control');
            $('label').addClass('col-form-label');
            $('input[type="checkbox"], :checkbox').removeClass('form-control').addClass('form-check-input-sm');
            $('button, :button, :submit, input[type="button"], input[type="submit"]').removeClass('form-control').addClass('btn btn-sm btn-primary');
            $('select').addClass('custom-select-sm');
            $('.ui-dialog-content').css('height','');
            $('.ui-widget-header').removeClass('ui-widget-header').addClass('bg-primary-light');
            return;
        }
    </script>
        <?
    if (!isset($_REQUEST['no_script']) && !isset($_REQUEST['no_nav'])) {
        ?>
        <script type="text/javascript" src="src/assets/js/oneui.core.min.js"></script>
        <script type="text/javascript" src="src/assets/js/oneui.app.min.js"></script>
        <script type="text/javascript" src="src/assets/js/plugins/sweetalert2/sweetalert2.min.js"></script>
        <script type="text/javascript" src="js/jquery-ui.min.js"></script>


	<?/**	<script src="js/datatables-with-copy.min.js"></script>
		<link rel="stylesheet" href="css/datatables-with-copy.min.css" />**/
		
        ?><script src="src/assets/js/plugins/datatables/jquery.dataTables.min.js"></script>
		<script src="src/assets/js/plugins/datatables/dataTables.bootstrap4.min.js"></script>
		<script src="src/assets/js/plugins/datatables/buttons/dataTables.buttons.min.js"></script>
		<script src="src/assets/js/plugins/datatables/buttons/buttons.print.min.js"></script>
		<script src="src/assets/js/plugins/datatables/buttons/buttons.html5.min.js"></script>
		<script src="src/assets/js/plugins/datatables/buttons/buttons.flash.min.js"></script>
		<script src="src/assets/js/plugins/datatables/buttons/buttons.colVis.min.js"></script>
		<script src="src/assets/js/pages/be_tables_datatables.min.js"></script>

        <?
    }
    ?>
</head>
<body>
<div id="page-container" class="sidebar-o sidebar-dark page-header-fixed">
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
                <a class="navbar-brand" href="index.php"><img src="images/cci-logo-200-2.png" height="30"
                                                              border="0" title="Home"></a>
            </div>
            <?
            if (checkAccess('campaigns') || checkAccess('voices') || checkAccess('names') || checkAccess('scripts')) {
                ?>
                <li class="nav-main-item">
                    <a href="#" class="nav-main-link nav-main-link-submenu" data-toggle="submenu"
                       aria-haspopup="true" aria-expanded="false">
                        <i class="nav-main-link-icon fa fa-donate"></i>
                        <span class="nav-main-heading">Campaigns</span>
                    </a>
                    <ul class="nav-main-submenu">
                        <?
                        if (checkAccess('campaigns')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=campaigns&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Campaign Setup</span>
                                </a>
                            </li>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=campaign_parents&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Campaign Parents</span>
                                </a>
                            </li>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=form_builder&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Form Builder</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('voices')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=voices&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Voices</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('names')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=names&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Names</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('scripts')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=scripts&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Scripts</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('quiz_questions')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=quiz_questions&no_script=1" onclick="loadSection(this.href);return false">
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
                    <a href="#" class="nav-main-link nav-main-link-submenu" data-toggle="submenu"
                       aria-haspopup="true" aria-expanded="false">
                        <i class="nav-main-link-icon fa fa-tools"></i>
                        <span class="nav-main-heading">Management Tools</span>
                    </a>
                    <ul class="nav-main-submenu">
                        <?
                        if (checkAccess('lead_management')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=lead_management&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Lead Management</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('sales_management')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=sales_management&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Sales Management</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('employee_hours')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=employee_hours&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Employee Hours</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('phone_lookup')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=phone_lookup&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">DRIPP Lookup</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('quiz_results')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=quiz_results&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Quiz Results</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('ringing_calls')) {
                            ?>
<!--                            <li class="nav-main-item">-->
<!--                                <a class="nav-main-link" href="?area=ringing_calls&no_script=1" onclick="loadSection(this.href);return false">-->
<!--                                    <span class="nav-main-link-name">Ring Report</span>-->
<!--                                </a>-->
<!--                            </li>-->
                            <?
                        }
                        if (checkAccess('messages')) {
                            ?>
<!--                            <li class="nav-main-item">-->
<!--                                <a class="nav-main-link" href="?area=messages&no_script=1" onclick="loadSection(this.href);return false">-->
<!--                                    <span class="nav-main-link-name">Agent Messages</span>-->
<!--                                </a>-->
<!--                            </li>-->
                            <?
                        }
                        if (checkAccess('dialer_status')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=dialer_status&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Dialer Status</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('server_status')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=server_status&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Server Status</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('extensions')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=extensions&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Extensions</span>
                                </a>
                            </li>
                            <?
                        }
                        ?>
                    </ul>
                </li>
                <?
            }
            if (checkAccess('list_tools')) {
                ?>
                <li class="nav-main-item">
                    <a href="#" class="nav-main-link nav-main-link-submenu" data-toggle="submenu"
                       aria-haspopup="true" aria-expanded="false">
                        <i class="nav-main-link-icon fa fa-list"></i>
                        <span class="nav-main-heading">List Tools</span>
                    </a>
                    <ul class="nav-main-submenu">
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="?area=list_tools&tool=build_list&no_script=1" onclick="loadSection(this.href);return false">
                                <span class="nav-main-link-name">List Builder</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="?area=list_tools&tool=dnc_tools&no_script=1" onclick="loadSection(this.href);return false">
                                <span class="nav-main-link-name">DNC Management</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="?area=list_tools&tool=manage_lists&no_script=1" onclick="loadSection(this.href);return false">
                                <span class="nav-main-link-name">VICI List Management</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="?area=list_tools&tool=tasks&no_script=1" onclick="loadSection(this.href);return false">
                                <span class="nav-main-link-name">Task / Status Management</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="?area=list_tools&tool=load_list&no_script=1" onclick="loadSection(this.href);return false">
                                <span class="nav-main-link-name">Import Leads</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="?area=list_tools&tool=view_imports&no_script=1" onclick="loadSection(this.href);return false">
                                <span class="nav-main-link-name">List Imports / Counts</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="?area=list_tools&tool=performance_reports&no_script=1" onclick="loadSection(this.href);return false">
                                <span class="nav-main-link-name">List Performance Report</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="?area=list_tools&tool=vici_report&no_script=1" onclick="loadSection(this.href);return false">
                                <span class="nav-main-link-name">VICIDIAL List Count</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <?
            }
            if (checkAccess('fronter_closer') || checkAccess('sales_analysis') || checkAccess('agent_call_stats') || checkAccess('user_charts') || checkAccess('recent_hangups') || checkAccess('script_statistics') || checkAccess('dispo_log') || checkAccess('capacity_report') || checkAccess('report_emails') || checkAccess('user_status_report')) {
                ?>
                <li class="nav-main-item">
                    <a href="#" class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false">
                        <i class="nav-main-link-icon fa fa-database"></i>
                        <span class="nav-main-heading">Reports</span>
                    </a>
                    <ul class="nav-main-submenu">
                        <?
                        if (checkAccess('fronter_closer')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=fronter_closer&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Fronter / Closer</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('sales_analysis')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=sales_analysis&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Sales Analysis</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('agent_call_stats')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=agent_call_stats&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Verifier Call Stats</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('rouster_report')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=rouster_report&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Rouster Call Stats</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('summary_report')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=summary_report&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Summary Report</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('dialer_sales')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=dialer_sales&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">AC Sales By Dialer</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('user_charts')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=user_charts&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">User Charts</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('recent_hangups')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=recent_hangups&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Recent Hangups</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('dispo_log')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=dispo_log&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Disposition Logs</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('capacity_report')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=capacity_report&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Capacity Reports</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('report_emails')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=report_emails&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Report Email Setup</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('user_status_report')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=user_status_report&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">User Status Report</span>
                                </a>
                            </li>
                            <?
                        }
                        
                        
                        if (checkAccess('script_statistics')) {
                        	?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=script_statistics&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Script Statistics</span>
                                </a>
                            </li>
                            <?
                        }
                        
                        
                        
                        ?>
                    </ul>
                </li>
                <?
            }
            if (checkAccess('pac_web_donations')) {
                ?>
                <li class="nav-main-item">
                    <a href="#" class="nav-main-link nav-main-link-submenu" data-toggle="submenu"
                       aria-haspopup="true" aria-expanded="false">
                        <i class="nav-main-link-icon fa fa-toolbox"></i>
                        <span class="nav-main-heading">PACs Maintenance</span>
                    </a>
                    <ul class="nav-main-submenu">
                        <?
                        if (checkAccess('pac_reports')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=pac_reports&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Web Donations</span>
                                </a>
                            </li>
                            <?
                        }
                        ?>
                    </ul>
                </li>
                <?
            }
            if (checkAccess('users')) {
                ?>
                <li class="nav-main-item">
                    <a href="#" class="nav-main-link nav-main-link-submenu" data-toggle="submenu"
                       aria-haspopup="true" aria-expanded="false">
                        <i class="nav-main-link-icon fa fa-user"></i>
                        <span class="nav-main-heading">Users</span>
                    </a>
                    <ul class="nav-main-submenu">
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="?area=users&no_script=1" onclick="loadSection(this.href);return false">
                                <span class="nav-main-link-name">Search / List Users</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="?area=user_groups&no_script=1" onclick="loadSection(this.href);return false">
                                <span class="nav-main-link-name">Group Manager</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="?area=user_teams&no_script=1" onclick="loadSection(this.href);return false">
                                <span class="nav-main-link-name">Team Manager</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="?area=user_groups_master&no_script=1" onclick="loadSection(this.href);return false">
                                <span class="nav-main-link-name">Master Groups Manager</span>
                            </a>
                        </li>
                        <?
                        if (checkAccess('feature_control')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=feature_control&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Feature Control</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('login_tracker')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=login_tracker&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Login Tracker</span>
                                </a>
                            </li>
                            <?
                        }
                        if (checkAccess('action_log')) {
                            ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="?area=action_log&no_script=1" onclick="loadSection(this.href);return false">
                                    <span class="nav-main-link-name">Action Logs</span>
                                </a>
                            </li>
                            <?
                        }
                        ?>
                    </ul>
                </li>
                <?
            }
            ?>
        </ul>
    </nav>
    <header id="page-header">
        <!-- Header Content -->
        <div class="content-header" style="background-color:rgba(64,64,64,.20)">
            <!-- Left Section -->
            <div class="d-flex align-items-center">
                <!-- Toggle Sidebar -->
                <!-- Layout API, functionality initialized in Template._uiApiLayout()-->
                <button type="button" class="btn btn-sm btn-dual mr-2 d-lg-none" data-toggle="layout"
                        data-action="sidebar_toggle">
                    <i class="fa fa-fw fa-bars"></i>
                </button>
                <!-- END Toggle Sidebar -->
                <!-- Toggle Mini Sidebar -->
                <!-- Layout API, functionality initialized in Template._uiApiLayout()-->
                <button type="button" class="btn btn-sm btn-dual mr-2 d-none d-lg-inline-block" data-toggle="layout"
                        data-action="sidebar_mini_toggle" title="Toggle Sidebar">
                    <i class="fa fa-fw fa-ellipsis-v"></i>
                </button>
                <!-- END Toggle Mini Sidebar -->

                <!-- Apps Modal -->
                <!-- Opens the Apps modal found at the bottom of the page, after footer’s markup -->
                <button type="button" class="btn btn-sm btn-dual mr-2" data-toggle="modal"
                        data-target="#one-modal-apps">
                    <i class="si si-grid"></i>
                </button>
                <!-- END Apps Modal -->
            </div>
            <!-- END Left Section -->

            <!-- Right Section -->
            <div class="d-flex align-items-center">
                <!-- User Dropdown -->
                <div class="dropdown d-inline-block ml-2">
                    <button type="button" class="btn btn-sm btn-dual" id="page-header-user-dropdown"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-none d-sm-inline-block ml-1"><?= $_SESSION["user"]["username"]; ?></span>
                        <i class="fa fa-fw fa-angle-down d-none d-sm-inline-block"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right p-0 border-0 font-size-sm"
                         aria-labelledby="page-header-user-dropdown">
                        <div class="p-2">
                            <h5 class="dropdown-header text-uppercase">Actions</h5>
                            <a class="dropdown-item d-flex align-items-center justify-content-between"
                               id="change_password" href="#">
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
                    <button type="button" class="btn btn-sm btn-dual" id="page-header-notifications-dropdown"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="si si-bell"></i>
                        <span class="badge badge-primary badge-pill">0</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right p-0 border-0 font-size-sm"
                         aria-labelledby="page-header-notifications-dropdown">
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
    <main id="main-container">
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
    </main>
    <footer id="page-footer"></footer>
</div>
</body>
</html>
