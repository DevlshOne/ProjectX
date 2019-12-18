<?php
    /***************************************************************
     *    Interface class - handles generic interface stuff, like menus, navigation, etc
     *    Written By: Jonathan Will
     ***************************************************************/

    $_SESSION['interface'] = new InterfaceClass;

    class InterfaceClass {
        public function InterfaceClass() {
        }

        public function makeNewHeader() {
            ?>
            <script>
                $('nav.navbar').ready(function () {
                    $('nav.navbar a.dropdown-item').each(function (i, v) {
                        $(this).on('click', function () {
                            loadSection($(this).attr('href'));
                            return false;
                        });
                    });
                });
            </script>
            <header class="cd-main-header">
                <nav class="navbar fixed-top navbar-expand-lg navbar-light bg-primary">
                    <div class="collapse navbar-collapse" id="navbarNavDropdown">
                        <ul class="navbar-nav">
                            <li class="nav-item active">
                                <a class="navbar-brand" href="index.php"><img src="images/cci-logo-200-2.png" height="30" border="0"></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link dropdown-toggle" id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Campaigns
                                </a>
                                <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                    <a class="dropdown-item" href="?area=campaigns&no_script=1">Setup Campaigns</a>
                                    <a class="dropdown-item" href="?area=campaign_parents&no_script=1">Parent Campaigns</a>
                                    <a class="dropdown-item" href="?area=form_builder&no_script=1">Form Builder</a>
                                    <a class="dropdown-item" href="?area=voices&no_script=1">Voices</a>
                                    <a class="dropdown-item" href="?area=names&no_script=1">Names</a>
                                    <a class="dropdown-item" href="?area=scripts&no_script=1">Quiz Questions</a>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Management Tools
                                </a>
                                <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                    <a class="dropdown-item" href="?area=lead_management">Lead Management</a>
                                    <a class="dropdown-item" href="?area=sales_management">Sales Management</a>
                                    <a class="dropdown-item" href="?area=employee_hours">Employee Hours</a>
                                    <a class="dropdown-item" href="?area=phone_lookup">DRIPP Lookup</a>
                                    <a class="dropdown-item" href="?area=quiz_results">Quiz Results</a>
                                    <a class="dropdown-item" href="?area=ringing_calls">Ring Report</a>
                                    <a class="dropdown-item" href="?area=messages">Agent Messages</a>
                                    <a class="dropdown-item" href="?area=dialer_status">Dialer Status</a>
                                    <a class="dropdown-item" href="?area=server_status">Server Status</a>
                                    <a class="dropdown-item" href="?area=extensions">Extensons</a>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    List Tools
                                </a>
                                <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                    <a class="dropdown-item" href="?area=list_tools&tool=build_list&no_script=1">List Builder</a>
                                    <a class="dropdown-item" href="?area=list_tools&tool=dnc_tools&no_script=1">DNC Management</a>
                                    <a class="dropdown-item" href="#">Vici List Management</a>
                                    <a class="dropdown-item" href="#">Task / Status Management</a>
                                    <a class="dropdown-item" href="#">Import Leads</a>
                                    <a class="dropdown-item" href="#">List Imports / Counts</a>
                                    <a class="dropdown-item" href="#">List Performance Report</a>
                                    <a class="dropdown-item" href="#">Vicidial List Count</a>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Reports
                                </a>
                                <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                    <a class="dropdown-item" href="#">List Builder</a>
                                    <a class="dropdown-item" href="#">DNC Management</a>
                                    <a class="dropdown-item" href="#">Vici List Management</a>
                                    <a class="dropdown-item" href="#">Task / Status Management</a>
                                    <a class="dropdown-item" href="#">Import Leads</a>
                                    <a class="dropdown-item" href="#">List Imports / Counts</a>
                                    <a class="dropdown-item" href="#">List Performance Report</a>
                                    <a class="dropdown-item" href="#">Vicidial List Count</a>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    PACs Maintenance
                                </a>
                                <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                    <a class="dropdown-item" href="#">List Builder</a>
                                    <a class="dropdown-item" href="#">DNC Management</a>
                                    <a class="dropdown-item" href="#">Vici List Management</a>
                                    <a class="dropdown-item" href="#">Task / Status Management</a>
                                    <a class="dropdown-item" href="#">Import Leads</a>
                                    <a class="dropdown-item" href="#">List Imports / Counts</a>
                                    <a class="dropdown-item" href="#">List Performance Report</a>
                                    <a class="dropdown-item" href="#">Vicidial List Count</a>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Users
                                </a>
                                <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                    <a class="dropdown-item" href="#">List Builder</a>
                                    <a class="dropdown-item" href="#">DNC Management</a>
                                    <a class="dropdown-item" href="#">Vici List Management</a>
                                    <a class="dropdown-item" href="#">Task / Status Management</a>
                                    <a class="dropdown-item" href="#">Import Leads</a>
                                    <a class="dropdown-item" href="#">List Imports / Counts</a>
                                    <a class="dropdown-item" href="#">List Performance Report</a>
                                    <a class="dropdown-item" href="#">Vicidial List Count</a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </nav>
            </header> <!-- .cd-main-header -->
            <main class="cd-main-content">
                <div class="content-wrapper" id="main_content">
                    <?
                        include_once("classes/home.inc.php");
                        $_SESSION['home']->handleFLOW();

                        ## CHECK IF PASSWORD IS OLDER THAN 6 MONTHS FOR PRIV 4 OR GREATER
                        if ($_SESSION['user']['priv'] >= 4) {

                            $sixmonthsago = strtotime("-6 months");

                            if ($_SESSION['user']['changedpw_time'] < $sixmonthsago) {

                                ?>
                                <div id="change-password-expired-div" title="Password Expired - Change Required"></div>
                                <script>

                                    $('#change-password-expired-div').dialog({
                                        dialogClass: "no-close",
                                        autoOpen: false,
                                        width: 400,
                                        height: 280,
                                        modal: true,
                                        draggable: false,
                                        resizable: false
                                    });

                                    function loadChangeExpiredPassword() {

                                        $('#change-password-expired-div').dialog("open");

                                        $('#change-password-expired-div').html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

                                        $('#change-password-expired-div').load("index.php?area=change_expired_password&printable=1&no_script=1");

                                    }

                                    loadChangeExpiredPassword();

                                </script><?

                            }

                        }

                        /**
                         *<center>
                         * <img src="graph.php?area=user_charts&max_mode=1&time_frame=day&width=650&height=300" border="0"
                         * height="300" width="650">
                         * <br/>
                         * <br/>
                         * <img src="graph.php?area=user_charts&max_mode=1&time_frame=week&start_time=<?= (time() - 604800) ?>&width=650&height=300"
                         * border="0" height="300" width="650">
                         * </center>
                         **/ ?>

                </div> <!-- .content-wrapper -->
            </main> <!-- .cd-main-content -->

            <div id="dialog-modal-view_change_history" title="View Change History" class="nod"></div>
            <div id="change-password-div" title="Change Password"></div>
            <script>


                $('#change-password-div').dialog({
                    autoOpen: false,
                    width: 400,
                    height: 280,
                    modal: false,
                    draggable: true,
                    resizable: true
                });

                $("#dialog-modal-view_change_history").dialog({
                    autoOpen: false,
                    width: 560,
                    height: 360,
                    modal: false,
                    draggable: true,
                    resizable: true
                });


                function loadChangePassword() {
                    $('#change-password-div').dialog("open");

                    $('#change-password-div').html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');


                    $('#change-password-div').load("index.php?area=change_password&printable=1&no_script=1");

                }


            </script><?
        }

        public function makeHeader() {
            ?>
            <table style="border:0;width:100%">
                <tr>
                    <td><a href="index.php"><img src="images/cci-logo-300.png" width="200" border="0"/></a></td>
                    <th align="left"><h1>Project X - Administration</h1></th>
                    <td valign="top" align="right">
                        Logged in as: <?= $_SESSION['user']['username'] ?> |
                        <input type="button" value="Logout" onclick="go('?o')">

                    </td>
                </tr>
                <tr>
                    <td colspan="3" class="bl">
                        &nbsp;
                    </td>
                </tr>
            </table>


            <div id="tabs">

                <ul>

                    <li><a href="?area=home&no_script">Home</a></li><?

                        if ($_SESSION['user']['priv'] >= 5 || ($_SESSION['user']['priv'] == 4 && $_SESSION['user']['feat_advanced'] == 'yes')) {
                            ?>
                            <li><a href="?area=campaigns&no_script">Campaigns</a></li>
                            <li><a href="?area=campaign_parents&no_script">Campaign Parents</a></li>
                            <li><a href="?area=form_builder&no_script=1">Form Builder</a></li>
                            <li><a href="?area=voices&no_script">Voices</a></li>
                            <li><a href="?area=names&no_script">Names</a></li>

                            <li><a href="?area=users&no_script">Users</a></li>
                            <li><a href="?area=extensions&no_script">Extensions</a></li><?
                        }

                        if ($_SESSION['user']['priv'] >= 5 || ($_SESSION['user']['priv'] == 4 && ($_SESSION['user']['feat_advanced'] == 'yes' || $_SESSION['user']['feat_config'] == 'yes'))) {
                            ?>
                            <li><a href="?area=scripts&no_script">Scripts</a></li><?
                        }

                        if ($_SESSION['user']['priv'] >= 5 || ($_SESSION['user']['priv'] == 4 && $_SESSION['user']['feat_messages'] == 'yes')) {
                            ?>
                            <li><a href="?area=messages&no_script">Messages</a></li><?
                        }

                        if ($_SESSION['user']['priv'] >= 5 || ($_SESSION['user']['priv'] == 4 && $_SESSION['user']['feat_problems'] == 'yes')) {
                            ?>
                            <li><a href="?area=problems&no_script">Problems</a></li><?
                        }

                        if ($_SESSION['user']['priv'] >= 5 || ($_SESSION['user']['priv'] == 4 && $_SESSION['user']['feat_reports'] == 'yes')) {
                            ?>
                            <li><a href="?area=reports&no_script">Reports</a></li><?
                        }

                        if ($_SESSION['user']['priv'] >= 5) {
                            ?>
                            <li><a href="?area=server_status&no_script">Server Status</a></li><?

                            ?>
                            <li><a href="?area=activity_log&no_script">Activity Log</a></li><?
                        } ?></ul>

            </div>
            <script>
                <?



                ?>
                $(function () {

                    $("#tabs").tabs({
                        heightStyle: "fill"
                    });

                });

            </script><?
        }
    }

