<? /***************************************************************
 *  User Team Manager - Vici User Team Management Tools
 *  Written By: Jonathan Will
 *  Mods: Dave Mednick
 ***************************************************************/

    $_SESSION['user_teams'] = new UserTeamsClass;

    class UserTeamsClass {
        var $table = 'user_teams';            ## Classes main table to operate on
        var $orderby = 'team_name';        ## Default Order field
        var $orderdir = 'ASC';            ## Default order direction
        ## Page  Configuration
        var $pagesize = 20;
        var $index = 0;        ## You dont really want to mess with this variable. Index is adjusted by code, to change the pages
        var $index_name = 'userteam_index';    ## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
        var $frm_name = 'userteam_nextfrm';
        var $order_prepend = 'userteam_';                ## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

        function UserTeamsClass() {
            $this->handlePOST();
        }

        function handlePOST() {
            ## NO ACCESS FOR NONADMIN
            if (!checkAccess('users')) {
                //$_SESSION['api']->errorOut('Access denied to Users');
                return;
            }
        }

        function handleFLOW() {
            # Handle flow, based on query string
            if (!checkAccess('users')) {
                accessDenied("Users");
                return;
            } else {
                ## USER TEAM ACTIONS
                if (isset($_REQUEST['action'])) {
                    switch ($_REQUEST['action']) {
                        default:
                            break;
                        case 'edit':
                            $teamid = intval($_REQUEST['team_id']);
                            $this->makeEdit($teamid);
                            break;
                    }
                } else {
                    ## LIST USERS
                    $this->listEntrys();
                }
            }
        }

        function getTeamName($teamid) {
            $res = queryROW("SELECT `team_name` FROM user_teams WHERE id = " . $teamid, 1);
            echo $res[0];
        }

        function listEntrys() {
            ?>
            <script>
                var userteam_delmsg = "Are you sure you want to delete this team?";
                let <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
                let <?=$this->order_prepend?>orderdir = "<?=$this->orderdir?>";
                let <?=$this->index_name?> = 0;
                let <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;
                let UserTeamsTableFormat = [
                    ['team_name', 'align_left'],
                    ['[get:num_users:id]', 'align_center'],
                    ['[delete]', 'align_center']
                ];

                /**
                 * Build the URL for AJAX to hit, to build the list
                 */
                function getUserTeamsURL() {
                    let frm = getEl('<?=$this->frm_name?>');
                    return 'api/api.php' +
                        "?get=user_teams&" +
                        "mode=xml&" +
                        's_team_name=' + escape(frm.s_team_name.value) + "&" +
                        "index=" + (<?=$this->index_name?> * <?=$this->order_prepend?>pagesize) + "&pagesize=" + <?=$this->order_prepend?>pagesize + "&" +
                        "orderby=" + <?=$this->order_prepend?>orderby + "&orderdir=" + <?=$this->order_prepend?>orderdir;
                }

                let userteams_loading_flag = false;

                /**
                 * Load the license data - make the ajax call, callback to the parse function
                 */
                function loadUserteams() {
                    // ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
                    let val = null;
                    eval('val = userteams_loading_flag');
                    // CHECK IF WE ARE ALREADY LOADING THIS DATA
                    if (val == true) {
                        return;
                    } else {
                        eval('userteams_loading_flag = true');
                    }
                    <?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());
                    $('#total_count_div').html('<img src="images/ajax-loader.gif" border="0">');
                    loadAjaxData(getUserTeamsURL(), 'parseUserTeams');
                }

                /**
                 * CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
                 */
                var <?=$this->order_prepend?>totalcount = 0;

                function parseUserTeams(xmldoc) {
                    <?=$this->order_prepend?>totalcount = parseXMLData('userteam', UserTeamsTableFormat, xmldoc);
                    // ACTIVATE PAGE SYSTEM!
                    if (<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize) {
                        makePageSystem('userteams',
                            '<?=$this->index_name?>',
                            <?=$this->order_prepend?>totalcount,
                            <?=$this->index_name?>,
                            <?=$this->order_prepend?>pagesize,
                            'loadUserteams()'
                        );
                    } else {
                        hidePageSystem('userteams');
                    }
                    eval('userteams_loading_flag = false');
                }

                function handleUserteamListClick(id) {
                    displayEditUserTeamDialog(id);
                }

                function displayEditUserTeamDialog(id) {
                    let $dlgObj = $('#dialog-modal-edit-user-team');
                    $dlgObj.dialog("open");
                    $dlgObj.html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                    $dlgObj.load("index.php?area=user_teams&action=edit&team_id=" + id + "&printable=1&no_script=1");
                }

                function displayAddUserTeamDialog() {
                    let $dlgObj = $('#dialog-modal-add-user-team');
                    $dlgObj.dialog("option", "title", 'Adding New User Team');
                    $dlgObj.dialog("open");
                }

                function resetUserTeamForm(frm) {
                    frm.s_team_name.value = '';
                }
            </script>
            <div id="dialog-modal-edit-user-team" class="nod"></div>
            <div id="dialog-modal-add-user-team" class="nod">
                <table class="tightTable">
                    <tr>
                        <td class="righty">
                            <table class="centery">
                                <tr>
                                    <th class="lefty">Team Name:</th>
                                    <td><input id="new_team_name" name="new_team_name" type="text" size="30"/></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
            <script>
                $("#dialog-modal-edit-user-team").dialog({
                    autoOpen: false,
                    width: 800,
                    height: 'auto',
                    modal: false,
                    draggable: true,
                    resizable: false,
                    title: 'Editing User Team',
                    position: 'center'
                });
                $("#dialog-modal-add-user-team").dialog({
                    autoOpen: false,
                    width: 500,
                    height: 160,
                    modal: false,
                    draggable: true,
                    resizable: false,
                    title: 'Add New User Team',
                    position: 'center',
                    buttons: {
                        'Save': function () {
                            if ($('#new_team_name').val().length) {
                                $.ajax({
                                    type: "POST",
                                    cache: false,
                                    async: false,
                                    dataType: 'json',
                                    crossDomain: false,
                                    crossOrigin: false,
                                    url: 'api/api.php?get=user_teams&mode=json&action=addNewTeam&name=' + $('#new_team_name').val(),
                                    done: function () {
                                        if (frontEnd_debug) {
                                        }
                                        
                                        // NO NEED TO RELOAD THE PAGE, JUST REFRESH TEAM LIST
                                        loadUserteams();
                                    }
                                });
                                
                                $(this).dialog('close');
                                //location.href = 'index.php?area=user_teams';
                                
                                $('#new_team_name').val("");
                                


                                
                            } else {
                                alert('Team name may not be empty!');
                            }
                        },
                        'Cancel': function () {
                            $(this).dialog('close');
                        }
                    }
                });
                loadUserteams();
            </script>
            <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>#userteamsarea" onsubmit="loadUserteams();return false">
                <input type="hidden" name="searching_userteams">
                <input type="hidden" name="<?= $this->order_prepend ?>orderby" value="<?= htmlentities($this->orderby) ?>">
                <input type="hidden" name="<?= $this->order_prepend ?>orderdir" value="<?= htmlentities($this->orderdir) ?>">
                <table class="lb tightTable pct100">
                    <tr>
                        <td height="40" class="pad_left ui-widget-header">
                            <table class="tightTable">
                                <tr>
                                    <th width="15%">
                                        <div class="lefty">User Teams</div> &nbsp;&nbsp;&nbsp;&nbsp;
                                    </th>
                                    <td width="50%" class="centery">PAGE SIZE: <select name="<?= $this->order_prepend ?>pagesizeDD" id="<?= $this->order_prepend ?>pagesizeDD" onchange="<?= $this->index_name ?>=0; loadUsergroups();return false">
                                            <option value="20">20</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                            <option value="500">500</option>
                                        </select></td>
                                    <td width="15%" class="righty">
                                        <table class="page_system_container tightTable">
                                            <tr>
                                                <td id="userteams_prev_td" class="page_system_prev"></td>
                                                <td id="userteams_page_td" class="page_system_page"></td>
                                                <td id="userteams_next_td" class="page_system_next"></td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td>
                                        <div class="righty"><input type="button" value="Add" onclick="displayAddUserTeamDialog();"></div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <table border="0" id="userteam_search_table">
                                <tr>
                                    <td rowspan="2" width="100" align="center" style="border-right:1px solid #000">
                                        <span id="total_count_div"></span>
                                    </td>
                                    <th class="row2">Team Name</th>
                                    <td>
                                        <input type="submit" value="Search" onclick="<?= $this->index_name ?> = 0;">
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="text" name="s_team_name" size="10" value="<?= htmlentities($_REQUEST['s_team_name']) ?>"></td>
                                    <td>
                                        <input type="button" value="Reset" onclick="resetUserTeamForm(this.form);loadUserteams();">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
            </form>
            <tr>
                <td colspan="2">
                    <table border="0" width="100%" id="userteam_table">
                        <tr>
                            <th class="row2" align="left"><?= $this->getOrderLink('team_name') ?>Team Name</a></th>
                            <th class="row2" align="center"><?= $this->getOrderLink('user_count') ?>Member Count</a></th>
                            <th class="row2">&nbsp;</th>
                        </tr>
                        <tr>
                            <td colspan="5" align="center">
                                <i>Loading, please wait...</i>
                            </td>
                        </tr>

                    </table>
                </td>
            </tr>
            </table>
            <?
        }

        function makeEdit($id) {
            ?>
            <script>
                let frontEnd_debug = false;
                let team_id = <?=$id;?>;
                let team_name = '<?=$this->getTeamName($id);?>';
                $('#team_name').val(team_name);

                function loadTeamMembers(tid) {
                    $.ajax({
                        type: "POST",
                        cache: false,
                        async: false,
                        dataType: 'json',
                        crossDomain: false,
                        crossOrigin: false,
                        url: 'api/api.php?get=user_teams&mode=json&action=getTeamMembers&team=' + tid,
                        success: function (teamMembers) {
                            $('#team_member_adder').empty();
                            $(teamMembers).each(function (i, v) {
                                $('#team_member_adder').append('<li id="memberid_' + v.user_id + '" class="ui-state-default" title="' + v.fullname + '">' + v.username + '</li>');
                                $('#userid_' + v.user_id).remove();
                            });
                            if (frontEnd_debug) {
                                console.log('Prefs have just been loaded :: ', tileDefs);
                                console.log('User Preferences loaded');
                            }
                        }
                    });
                }

                function loadUserGroups() {
                    $.ajax({
                        type: "POST",
                        cache: false,
                        async: false,
                        dataType: 'json',
                        crossDomain: false,
                        crossOrigin: false,
                        url: 'api/api.php?get=user_teams&mode=json&action=getUserGroups',
                        success: function (userGroups) {
                            $('#group_select').empty();
                            $(userGroups).each(function (i, v) {
                                $('#group_select').append('<option value="' + v.id + '">' + v.group_name + '</option>');
                            });
                            if (frontEnd_debug) {
                                console.log('Prefs have just been loaded :: ', tileDefs);
                                console.log('User Preferences loaded');
                            }
                        }
                    });
                }

                function loadUserListByGroup(group_name) {
                    $.ajax({
                        type: "POST",
                        cache: false,
                        async: false,
                        dataType: 'json',
                        crossDomain: false,
                        crossOrigin: false,
                        url: 'api/api.php?get=user_teams&mode=json&action=getGroupUserList&group=' + group_name,
                        success: function (userList) {
                            $('#team_members').empty();
                            $(userList).each(function (i, v) {
                                $('#team_members').append('<li id="userid_' + v.user_id + '" class="ui-state-highlight" title="' + v.fullname + '">' + v.username + '</li>');
                            });
                            loadTeamMembers(team_id);
                            if (frontEnd_debug) {
                                console.log('Prefs have just been loaded :: ', tileDefs);
                                console.log('User Preferences loaded');
                            }
                        }
                    });
                }

                function loadUserListByName(user_name) {
                    $.ajax({
                        type: "POST",
                        cache: false,
                        async: false,
                        dataType: 'json',
                        crossDomain: false,
                        crossOrigin: false,
                        url: 'api/api.php?get=user_teams&mode=json&action=getUserList&user=' + user_name,
                        success: function (userList) {
                            $('#team_members').empty();
                            $(userList).each(function (i, v) {
                                $('#team_members').append('<li id="userid_' + v.user_id + '" class="ui-state-highlight" title="' + v.fullname + '">' + v.username + '</li>');
                            });
                            loadTeamMembers(team_id);
                            if (frontEnd_debug) {
                                console.log('Prefs have just been loaded :: ', tileDefs);
                                console.log('User Preferences loaded');
                            }
                        }
                    });
                }

                $('#group_select').on('change', function (e, ui) {
                    loadUserListByGroup($(":selected", this).text());
                });
                $('#user_search').on('keyup', function (e, ui) {
                    loadUserListByName($('#user_search').val());
                });
                $('#team_member_adder, #team_members').sortable({
                    connectWith: '.userList'
                }).disableSelection();
                $('#save_team_name').on('click', function (e, ui) {
                    if ($('#team_name').val().length) {
                        $.ajax({
                            type: "POST",
                            cache: false,
                            async: false,
                            dataType: 'json',
                            crossDomain: false,
                            crossOrigin: false,
                            url: 'api/api.php?get=user_teams&mode=json&action=changeTeamName&team=' + team_id + '&name=' + $('#team_name').val(),
                            done: function () {
                                alert('Team name has been changed');
                                team_name = $('#team_name').val();
                                if (frontEnd_debug) {
                                    console.log('Team name has been changed to :: ', team_name);
                                }
                                //location.href = 'index.php?area=user_teams';
                                
                            }
                        });

                        loadUserteams();
                    } else {
                        alert('Team name may not be empty');
                    }
                });
                $('#team_member_adder').sortable({
                    receive: function (e, ui) {
                        let current_id = ui.item[0].id.split('_')[1];
                        console.log('Adding :: ' + current_id);
                        ui.item.removeClass('ui-state-highlight').addClass('ui-state-default');
                        $(ui.item).attr('id', 'memberid_' + current_id);
                        $.ajax({
                            type: "POST",
                            cache: false,
                            async: false,
                            dataType: 'json',
                            crossDomain: false,
                            crossOrigin: false,
                            url: 'api/api.php?get=user_teams&mode=json&action=addTeamMember&team=' + team_id + '&userid=' + current_id,
                            done: function () {
                                //alert('Member has been added to ' + team_name);
                                if (frontEnd_debug) {
                                    console.log('Prefs have just been loaded :: ', tileDefs);
                                    console.log('User Preferences loaded');
                                }

                                //loadUserteams();
                            }
                        });

                        loadUserteams();
                    },
                    remove: function (e, ui) {
                        let current_id = ui.item[0].id.split('_')[1];
                        console.log('Removing :: ' + current_id);
                        
                        ui.item.removeClass('ui-state-default').addClass('ui-state-highlight');
                        $(ui.item).attr('id', 'userid_' + current_id);
                        $.ajax({
                            type: "POST",
                            cache: false,
                            async: false,
                            dataType: 'json',
                            crossDomain: false,
                            crossOrigin: false,
                            url: 'api/api.php?get=user_teams&mode=json&action=removeTeamMember&team=' + team_id + '&userid=' + current_id,
                            success: function () {
                                //alert('Member has been removed from ' + team_name);
                                if (frontEnd_debug) {
                                    console.log('Prefs have just been loaded :: ', tileDefs);
                                    console.log('User Preferences loaded');
                                }

                                loadUserteams();
                            },
                            fail: function () {

                            }

                        });

                        //loadUserteams();
                    }
                });
                loadUserGroups();
                loadUserListByName(' ');
            </script>
            <table class="tightTable pct100">
                <tr>
                    <td colspan="2">
                        <div class="pct100">
                            <label for="team_name">Edit team name : </label>
                            <input id="team_name" name="team_name" type="text" maxlength="128"/>
                            <button id="save_team_name" value="Save" title="Save Team Name">Save</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>Team Members</th>
                    <th>User List</th>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>
                        <div class="pct100">
                            <label for="user_search">Enter username to search : </label>
                            <input id="user_search" name="user_search" type="text" maxlength="128"/>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top;">
                        <ul id="team_member_adder" class="userList"></ul>
                    </td>
                    <td style="vertical-align: top;">
                        <div class="pct100">
                            <label for="group_select">Group Filter : </label>
                            <select id="group_select" name="group_select"></select>
                        </div>
                        <div class="pct100">
                            <ul id="team_members" class="userList"></ul>
                        </div>
                    </td>
                </tr>
            </table>
            <?
        }

        function getOrderLink($field) {
            $var = '<a href="#" onclick="setOrder(\'' . addslashes($this->order_prepend) . '\',\'' . addslashes($field) . '\',';
            $var .= "((" . $this->order_prepend . "orderdir == 'DESC')?'ASC':'DESC')";
            $var .= ");loadUserteams();return false;\">";
            return $var;
        }
    }
