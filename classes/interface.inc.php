<? /***************************************************************
 *    Interface class - handles generic interface stuff, like menus, navigation, etc
 *    Written By: Jonathan Will
 ***************************************************************/

    $_SESSION['interface'] = new InterfaceClass;

    class InterfaceClass
    {
        ?><header class="cd-main-header">


		<a href="index.php" class="cd-logo"><img src="images/cci-logo-200-2.png" height="55" border="0"></a>
		<?


            if ($_SESSION['user']['username'] == 'phreak') {
                echo '<a href="px_status.php">UBERSTATUS</a>';
            } ?>

		<!--<a href="#0" class="cd-logo"><img src="http://10.100.0.65/reports/graph.php?area=user_charts&max_mode=1&time_frame=day&width=650&height=100" border="0" height="100" width="650"></a>-->

		<a href="#0" class="cd-nav-trigger">Menu<span></span></a>




		<nav class="cd-nav">



			<ul class="cd-top-nav">



				<li><a href="#0">Support</a></li>
				<li class="has-children account">
					<a href="#0">

						Account
					</a>

					<ul>

						<li><a href="#0">My Account</a></li>
						<li><a href="#" onclick="loadChangePassword();return false">Change Password</a></li>
						<li><a href="?o">Logout</a></li>
					</ul>
				</li>
			</ul>
		</nav>
	</header> <!-- .cd-main-header -->

	<main class="cd-main-content">
		<nav class="cd-side-nav">
			<ul>
				<li class="cd-label">Navigation</li>
<?

                if (checkAccess('campaigns') ||
                    checkAccess('voices') ||
                    checkAccess('names') ||
                    checkAccess('scripts')
                    ) {
                    ?><li class="has-children comments">
						<a href="#0">Campaign Setup</a>

<<<<<<< classes/interface.inc.php
						<ul><?

                            if (checkAccess('campaigns')) {
                                ?>
								<li><a href="?area=campaigns&no_script=1" onclick="loadSection(this.href);return false">Campaigns</a></li>
								<li><a href="?area=campaign_parents&no_script=1" onclick="loadSection(this.href);return false">Campaign Parents</a></li>
								<?
                            }

                    if (checkAccess('voices')) {
                        ?><li><a href="?area=voices&no_script=1" onclick="loadSection(this.href);return false">Voices</a></li><?
                    }

                    if (checkAccess('names')) {
                        ?><li><a href="?area=names&no_script=1" onclick="loadSection(this.href);return false">Names</a></li><?
                    }

                    if (checkAccess('scripts')) {
                        ?><li><a href="?area=scripts&no_script=1" onclick="loadSection(this.href);return false">Scripts</a></li><?
                    }

                    if (checkAccess('quiz_questions')) {
                        ?><li><a href="?area=quiz_questions&no_script=1" onclick="loadSection(this.href);return false">Quiz Questions</a></li><?
                    } ?></ul>
					</li><?
                }


        if (checkAccess('lead_management')	||
                    checkAccess('employee_hours')	||
                    checkAccess('ringing_calls')	||
                    checkAccess('messages')			||
                    checkAccess('server_status')	||
                    checkAccess('extensions')
                    ) {
            ?><li class="has-children bookmarks">
=======
						<ul><?

							if(checkAccess('campaigns')){
								?><li><a href="?area=campaigns&no_script=1" onclick="loadSection(this.href);return false">Campaigns</a></li><?
							}

							if(checkAccess('voices')){
								?><li><a href="?area=voices&no_script=1" onclick="loadSection(this.href);return false">Voices</a></li><?
							}

							if(checkAccess('names')){
								?><li><a href="?area=names&no_script=1" onclick="loadSection(this.href);return false">Names</a></li><?
							}

							if(checkAccess('scripts')){
								?><li><a href="?area=scripts&no_script=1" onclick="loadSection(this.href);return false">Scripts</a></li><?
							}

							if(checkAccess('quiz_questions')){
								?><li><a href="?area=quiz_questions&no_script=1" onclick="loadSection(this.href);return false">Quiz Questions</a></li><?
							}
						?></ul>
					</li><?

				}


				if(	checkAccess('lead_management')	||
					checkAccess('employee_hours')	||
					checkAccess('ringing_calls')	||
					checkAccess('messages')			||
					checkAccess('login_tracker')	||
					checkAccess('server_status')	||
					checkAccess('extensions')
					){


					?><li class="has-children bookmarks">
>>>>>>> classes/interface.inc.php
						<a href="#0">Management Tools</a>

						<ul><?


        public function makeNewHeader()
        {
            ?>
            <header class="cd-main-header">


                <a href="index.php" class="cd-logo"><img src="images/cci-logo-200-2.png" height="55" border="0"></a>
                <?

                    if ($_SESSION['user']['username'] == 'phreak') {
                        echo '<a href="px_status.php">UBERSTATUS</a>';
                    } ?>

                <!--<a href="#0" class="cd-logo"><img src="http://10.100.0.65/reports/graph.php?area=user_charts&max_mode=1&time_frame=day&width=650&height=100" border="0" height="100" width="650"></a>-->

                <a href="#0" class="cd-nav-trigger">Menu<span></span></a>


                <nav class="cd-nav">


                    <ul class="cd-top-nav">


                        <li><a href="#0">Support</a></li>
                        <li class="has-children account">
                            <a href="#0">

                                Account
                            </a>

                            <ul>

                                <li><a href="#0">My Account</a></li>
                                <li><a href="#" onclick="loadChangePassword();return false">Change Password</a></li>
                                <li><a href="?o">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </nav>
            </header> <!-- .cd-main-header -->

            <main class="cd-main-content">
                <nav class="cd-side-nav">
                    <ul>
                        <li class="cd-label">Navigation</li>
                        <?

                            if (checkAccess('campaigns') ||
                                checkAccess('voices') ||
                                checkAccess('names') ||
                                checkAccess('scripts')
                            ) {
                                ?>
                                <li class="has-children comments">
                                <a href="#0">Campaign Setup</a>

                                <ul><?

                                        if (checkAccess('campaigns')) {
                                            ?>
                                            <li><a href="?area=campaigns&no_script=1"
                                                   onclick="loadSection(this.href);return false">Campaigns</a></li>
                                            <li><a href="?area=campaign_parents&no_script=1"
                                                   onclick="loadSection(this.href);return false">Campaign Parents</a>
                                            </li>
                                            <?
                                        }

                                        if (checkAccess('voices')) {
                                            ?>
                                            <li><a href="?area=voices&no_script=1"
                                                   onclick="loadSection(this.href);return false">Voices</a></li><?
                                        }

                                        if (checkAccess('names')) {
                                            ?>
                                            <li><a href="?area=names&no_script=1"
                                                   onclick="loadSection(this.href);return false">Names</a></li><?
                                        }

                                        if (checkAccess('scripts')) {
                                            ?>
                                            <li><a href="?area=scripts&no_script=1"
                                                   onclick="loadSection(this.href);return false">Scripts</a></li><?
                                        }

                                        if (checkAccess('quiz_questions')) {
                                            ?>
                                            <li><a href="?area=quiz_questions&no_script=1"
                                                   onclick="loadSection(this.href);return false">Quiz Questions</a>
                                            </li><?
                                        } ?></ul>
                                </li><?
                            }

                            if (checkAccess('lead_management') ||
                                checkAccess('employee_hours') ||
                                checkAccess('ringing_calls') ||
                                checkAccess('messages') ||
                                checkAccess('server_status') ||
                                checkAccess('extensions')
                            ) {
                                ?>
                                <li class="has-children bookmarks">
                                <a href="#0">Management Tools</a>

                                <ul><?

                                        if (checkAccess('lead_management')) {
                                            ?>
                                            <li><a href="?area=lead_management&no_script=1"
                                                   onclick="loadSection(this.href);return false">Lead Management</a>
                                            </li><?
                                        }

                                        if (checkAccess('employee_hours')) {
                                            ?>
                                            <li><a href="?area=employee_hours&no_script=1"
                                                   onclick="loadSection(this.href);return false">Employee Hours</a>
                                            </li><?
                                        }

                                        if (checkAccess('phone_lookup')) {
                                            ?>
                                            <li><a href="?area=phone_lookup&no_script=1"
                                                   onclick="loadSection(this.href);return false">DRIPP Phone lookup</a>
                                            </li><?
                                        }

                                        if (checkAccess('quiz_results')) {
                                            ?>
                                            <li><a href="?area=quiz_results&no_script=1"
                                                   onclick="loadSection(this.href);return false">Quiz Results</a></li><?
                                        }

                                        if (checkAccess('ringing_calls')) {
                                            ?>
                                            <li><a href="?area=ringing_calls&no_script=1"
                                                   onclick="loadSection(this.href);return false">Ring Report</a></li><?
                                        }

                                        if (checkAccess('messages')) {
                                            ?>
                                            <li><a href="?area=messages&no_script=1"
                                                   onclick="loadSection(this.href);return false">Agent Messages</a>
                                            </li><?
                                        }

                                        if (checkAccess('server_status')) {
                                            ?>
                                            <li><a href="?area=server_status&no_script=1"
                                                   onclick="loadSection(this.href);return false">Server Status</a>
                                            </li><?
                                        }

                                        if (checkAccess('extensions')) {
                                            ?>
                                            <li><a href="?area=extensions&no_script=1"
                                                   onclick="loadSection(this.href);return false">Extensions</a></li><?
                                        } ?></ul>
                                </li><?
                            }

                            if (checkAccess('list_tools')) {
                                ?>
                                <li class="has-children bookmarks">
                                <a href="#0">List Tools</a>

                                <ul><?
                                    ?>
                                    <li><a href="?area=list_tools&tool=build_list&no_script=1"
                                           onclick="loadSection(this.href);return false">Build List</a></li><?
                                    ?>
                                    <li><a href="?area=list_tools&tool=dnc_tools&no_script=1"
                                           onclick="loadSection(this.href);return false">Manage DNC</a></li><?
                                    ?>
                                    <li><a href="?area=list_tools&tool=manage_lists&no_script=1"
                                           onclick="loadSection(this.href);return false">Manage Vici Lists</a></li><?
                                    ?>
                                    <li><a href="?area=list_tools&tool=tasks&no_script=1"
                                           onclick="loadSection(this.href);return false">Task List/Status</a></li><?
                                    ?>
                                    <li><a href="?area=list_tools&tool=load_list&no_script=1"
                                           onclick="loadSection(this.href);return false">Import Leads</a></li><?
                                    ?>
                                    <li><a href="?area=list_tools&tool=view_imports&no_script=1"
                                           onclick="loadSection(this.href);return false">List Imports/Counts</a></li><?

                                    ?>
                                    <li><a href="?area=list_tools&tool=vici_report&no_script=1"
                                           onclick="loadSection(this.href);return false">Vicidial List Count</a></li><?

                                    ?></ul>
                                </li><?
                            }

                            if (checkAccess('fronter_closer') ||
                                checkAccess('sales_analysis') ||
                                checkAccess('agent_call_stats') ||
                                checkAccess('user_charts') ||
                                checkAccess('recent_hangups') ||
                                checkAccess('script_statistics') ||
                                checkAccess('dispo_log')

                            ) {
                                ?>
                                <li class="has-children overview">
                                <a href="#0">Reports</a>

                                <ul><?

                                        if (checkAccess('fronter_closer')) {
                                            ?>
                                            <li><a href="?area=fronter_closer&no_script=1"
                                                   onclick="loadSection(this.href);return false">Fronter/Closer</a>
                                            </li><?
                                        }

                                        if (checkAccess('sales_analysis')) {
                                            ?>
                                            <li><a href="?area=sales_analysis&no_script=1"
                                                   onclick="loadSection(this.href);return false">Sales Analysis</a>
                                            </li><?
                                        }

                                        if (checkAccess('agent_call_stats')) {
                                            ?>
                                            <li><a href="?area=agent_call_stats&no_script=1"
                                                   onclick="loadSection(this.href);return false">Verifier Call Stats</a>
                                            </li><?
                                        }

                                        if (checkAccess('rouster_report')) {
                                            ?>
                                            <li><a href="?area=rouster_report&no_script=1"
                                                   onclick="loadSection(this.href);return false">Rouster Call Stats</a>
                                            </li><?
                                        }

                                        if ($_SESSION['user']['priv'] >= 5) {
                                            ?>
                                            <li><a href="?area=summary_report&no_script=1"
                                                   onclick="loadSection(this.href);return false">Summary Report</a>
                                            </li><?
                                        }

                                        if (checkAccess('user_charts')) {
                                            ?>
                                            <li><a href="?area=user_charts&no_script=1"
                                                   onclick="loadSection(this.href);return false">User Charts</a></li><?
                                        }

                                        if (checkAccess('recent_hangups')) {
                                            ?>
                                            <li><a href="?area=recent_hangups&no_script=1"
                                                   onclick="loadSection(this.href);return false">Recent Hangups</a>
                                            </li><?
                                        }

                                        if (checkAccess('script_statistics')) {
                                            ?>
                                            <li><a href="?area=script_statistics&no_script=1"
                                                   onclick="loadSection(this.href);return false">Script Statistics</a>
                                            </li><?
                                        }

                                        if (checkAccess('dispo_log')) {
                                            ?>
                                            <li><a href="?area=dispo_log&no_script=1"
                                                   onclick="loadSection(this.href);return false">Dispo Log</a></li><?
                                        }

                                        if (checkAccess('report_emails')) {
                                            ?>
                                            <li><a href="?area=report_emails&no_script=1"
                                                   onclick="loadSection(this.href);return false">Report Email Setup</a>
                                            </li><?
                                        } ?></ul>
                                </li><?
                            }

                            if ($_SESSION['user']['priv'] >= 5) {
                                ?>
                                <li class="has-children comments">
                                <a href="#0">PACs Maintenance </a>

                                <ul><?

                                        if ($_SESSION['user']['priv'] >= 5) {
                                            ?>
                                            <li><a href="?area=pac_reports&no_script=1"
                                                   onclick="loadSection(this.href);return false">Web Donations</a>
                                            </li><?

                                            /**?><li><a href="fec_filer.php" target="_blank">FEC Filer</a></li><?**/
                                        } ?></ul>
                                </li><?
                            }

                            if (checkAccess('users')) {
                                ?>
                                <li class="has-children users">
                                <a href="#0">Users</a>

                                <ul>
                                    <li><a href="?area=users&no_script=1" onclick="loadSection(this.href);return false">Search/List
                                            Users</a></li>
                                    <li><a href="?area=user_groups&no_script=1"
                                           onclick="loadSection(this.href);return false">Group Manager</a></li>
                                    <li><a href="?area=user_groups_master&no_script=1"
                                           onclick="loadSection(this.href);return false">Master User Groups</a></li>

                                    <? /**<li><a href="?area=users&add_user&no_script=1" onclick="loadSection(this.href);return false">Add User</a></li>
                                     * <li><a href="?area=users&bulk_add&no_script=1" onclick="loadSection(this.href);return false">Bulk Add</a></li>**/ ?>

                                    <?

                                        if (checkAccess('feature_control')) {
                                            ?>
                                            <li><a href="?area=feature_control&no_script=1"
                                                   onclick="loadSection(this.href);return false">Feature Control</a>
                                            </li><?
                                        }

                                        if (checkAccess('action_log')) {//if($_SESSION['user']['priv'] >= 5){
                                            ?>
                                            <li><a href="?area=action_log&no_script=1"
                                                   onclick="loadSection(this.href);return false">Action Log!</a></li><?
                                        } ?></ul>
                                </li><?
                            } ?></ul>


                    <!--
                                <ul>
                                    <li class="cd-label">Secondary</li>
                                    <li class="has-children bookmarks">
                                        <a href="#0">Bookmarks</a>

                                        <ul>
                                            <li><a href="#0">All Bookmarks</a></li>
                                            <li><a href="#0">Edit Bookmark</a></li>
                                            <li><a href="#0">Import Bookmark</a></li>
                                        </ul>
                                    </li>
                                    <li class="has-children images">
                                        <a href="#0">Images</a>

                                        <ul>
                                            <li><a href="#0">All Images</a></li>
                                            <li><a href="#0">Edit Image</a></li>
                                        </ul>
                                    </li>

                                    <li class="has-children users">
                                        <a href="#0">Users</a>

                                        <ul>
                                            <li><a href="#0">All Users</a></li>
                                            <li><a href="#0">Edit User</a></li>
                                            <li><a href="#0">Add User</a></li>
                                        </ul>
                                    </li>
                                </ul>

                                <ul>
                                    <li class="cd-label">Action</li>
                                    <li class="action-btn"><a href="#0">+ Button</a></li>
                                </ul>

                    -->

                </nav>


                <div class="content-wrapper" id="main_content">

                    <center>
                        <img src="graph.php?area=user_charts&max_mode=1&time_frame=day&width=650&height=300" border="0"
                             height="300" width="650">
                        <br/>
                        <br/>
                        <img src="graph.php?area=user_charts&max_mode=1&time_frame=week&start_time=<?= (time() - 604800) ?>&width=650&height=300"
                             border="0" height="300" width="650">
                    </center>

                </div> <!-- .content-wrapper -->
            </main> <!-- .cd-main-content -->

            <div id="dialog-modal-view_change_history" title="View Change History" class="nod"></div>
            <div id="change-password-div" title="Change Password"></div>
            <script>


                $('#change-password-div').dialog({
                    autoOpen: false,
                    width: 370,
                    height: 220,
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

        public function makeHeader()
        {
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

        if (checkAccess('users')) {
            ?><li class="has-children users">
						<a href="#0">Users</a>

						<ul>
							<li><a href="?area=users&no_script=1" onclick="loadSection(this.href);return false">Search/List Users</a></li>
							<li><a href="?area=user_groups&no_script=1" onclick="loadSection(this.href);return false">Group Manager</a></li>

							<?/**<li><a href="?area=users&add_user&no_script=1" onclick="loadSection(this.href);return false">Add User</a></li>
							<li><a href="?area=users&bulk_add&no_script=1" onclick="loadSection(this.href);return false">Bulk Add</a></li>**/?>

<<<<<<< classes/interface.inc.php
							<?

            </div>
            <script>
                <?

            if (checkAccess('action_log')) {//if($_SESSION['user']['priv'] >= 5){
                                ?><li><a href="?area=action_log&no_script=1" onclick="loadSection(this.href);return false">Action Log!</a></li><?
                            } ?></ul>
					</li><?
        } ?></ul>
=======
							<?

							if(checkAccess('feature_control')){
								?><li><a href="?area=feature_control&no_script=1" onclick="loadSection(this.href);return false">Feature Control</a></li><?
							}

							if(checkAccess('login_tracker')){
								?><li><a href="?area=login_tracker&no_script=1" onclick="loadSection(this.href);return false">Login Tracker</a></li><?
							}

							if(checkAccess('action_log')){//if($_SESSION['user']['priv'] >= 5){
								?><li><a href="?area=action_log&no_script=1" onclick="loadSection(this.href);return false">Action Log!</a></li><?
							}

						?></ul>
					</li><?
				}

			?></ul>
>>>>>>> classes/interface.inc.php



<!--
			<ul>
				<li class="cd-label">Secondary</li>
				<li class="has-children bookmarks">
					<a href="#0">Bookmarks</a>

					<ul>
						<li><a href="#0">All Bookmarks</a></li>
						<li><a href="#0">Edit Bookmark</a></li>
						<li><a href="#0">Import Bookmark</a></li>
					</ul>
				</li>
				<li class="has-children images">
					<a href="#0">Images</a>

					<ul>
						<li><a href="#0">All Images</a></li>
						<li><a href="#0">Edit Image</a></li>
					</ul>
				</li>

				<li class="has-children users">
					<a href="#0">Users</a>

					<ul>
						<li><a href="#0">All Users</a></li>
						<li><a href="#0">Edit User</a></li>
						<li><a href="#0">Add User</a></li>
					</ul>
				</li>
			</ul>

			<ul>
				<li class="cd-label">Action</li>
				<li class="action-btn"><a href="#0">+ Button</a></li>
			</ul>

-->

		</nav>



		<div class="content-wrapper" id="main_content">

			<center>
				<img src="graph.php?area=user_charts&max_mode=1&time_frame=day&width=650&height=300" border="0" height="300" width="650">
				<br />
				<br />
				<img src="graph.php?area=user_charts&max_mode=1&time_frame=week&start_time=<?=(time()-604800)?>&width=650&height=300" border="0" height="300" width="650">
			</center>

		</div> <!-- .content-wrapper -->
	</main> <!-- .cd-main-content -->

	<div id="dialog-modal-view_change_history" title="View Change History" class="nod"></div>
	<div id="change-password-div" title="Change Password" ></div>
	<script>




		$('#change-password-div').dialog({
			autoOpen: false,
			width:370,
			height: 220,
			modal: false,
			draggable:true,
			resizable: true
		});

		$("#dialog-modal-view_change_history").dialog({
				autoOpen: false,
				width: 560,
				height: 360,
				modal: false,
				draggable:true,
				resizable: true
			});



		function loadChangePassword(){
			$('#change-password-div').dialog("open");

			$('#change-password-div').html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');


			$('#change-password-div').load("index.php?area=change_password&printable=1&no_script=1");

		}


	</script><?
    }


                ?>
                $(function () {

                    $("#tabs").tabs({
                        heightStyle: "fill"
                    });

                });

            </script><?
        }
    }
