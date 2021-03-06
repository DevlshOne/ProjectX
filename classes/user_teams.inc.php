<? /***************************************************************
 *  User Team Manager - Vici User Team Management Tools
 *  Written By: Jonathan Will
 *  Mods: Dave Mednick
 ***************************************************************/

$_SESSION['user_teams'] = new UserTeamsClass;

class UserTeamsClass
{
    var $table = 'user_teams';            ## Classes main table to operate on
    var $orderby = 'team_name';        ## Default Order field
    var $orderdir = 'ASC';            ## Default order direction
    ## Page  Configuration
    var $pagesize = 20;
    var $index = 0;        ## You dont really want to mess with this variable. Index is adjusted by code, to change the pages
    var $index_name = 'userteam_index';    ## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
    var $frm_name = 'userteam_nextfrm';
    var $order_prepend = 'userteam_';                ## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

    function UserTeamsClass()
    {
        $this->handlePOST();
    }

    function handlePOST()
    {
        ## NO ACCESS FOR NONADMIN
        if (!checkAccess('users')) {
            //$_SESSION['api']->errorOut('Access denied to Users');
            return;
        }
    }

    function handleFLOW()
    {
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

    function getTeamName($teamid)
    {
        $res = queryROW("SELECT `team_name` FROM user_teams WHERE id = " . $teamid, 1);
        echo $res[0];
    }

    function listEntrys()
    {
        ?>
        <script>
            var userteam_delmsg = "Are you sure you want to delete this team?";
            var userteam_orderby = "<?=addslashes($this->orderby)?>";
            var userteam_orderdir = "<?=$this->orderdir?>";
            var userteam_index = 0;
            var userteam_pagesize = <?=$this->pagesize?>;
            var UserTeamsTableFormat = [
                ['team_name', 'text-left'],
                ['[get:num_users:id]', 'text-center'],
                ['[delete]', 'text-center']
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
                    "index=" + (<?=$this->index_name?> * <?=$this->order_prepend?>pagesize
            )
                +"&pagesize=" + <?=$this->order_prepend?>pagesize + "&" +
                "orderby=" + <?=$this->order_prepend?>orderby + "&orderdir=" + <?=$this->order_prepend?>orderdir;
            }

            var userteams_loading_flag = false;

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
                $('#total_count_div').html('<img src="images/ajax-loader.gif" height="20" border="0">');
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
                frm.reset();
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
                width: 'auto',
                height: 'auto',
                modal: false,
                draggable: true,
                resizable: false,
                title: 'Editing User Team',
                position: {my: 'center', at: 'center'}
            });

            $("#dialog-modal-edit-user-team").closest('.ui-dialog').draggable("option", "containment", "#main-container");

            $("#dialog-modal-add-user-team").dialog({
                autoOpen: false,
                width: 'auto',
                height: 'auto',
                modal: false,
                draggable: true,
                resizable: false,
                title: 'Add New User Team',
                position: {my: 'center', at: 'center'},
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
            $("#dialog-modal-add-user-team").closest('.ui-dialog').draggable("option", "containment", "#main-container");
            loadUserteams();
        </script>
        <div class="block">
            <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>#userteamsarea" onsubmit="loadUserteams();return false">
                <! ** BEGIN BLOCK HEADER -->
                <div class="block-header bg-primary-light">
                    <h4 class="block-title">User Teams</h4>
                    <button type="button" title="Add User Team" class="btn btn-sm btn-primary" onclick="displayAddUserTeamDialog(0)">Add</button>
                    <div id="userteams_prev_td" class="page_system_prev"></div>
                    <div id="userteams_page_td" class="page_system_page"></div>
                    <div id="userteams_next_td" class="page_system_next"></div>
                    <select title="Rows Per Page" class="custom-select-sm" name="<?= $this->order_prepend ?>pagesize" id="<?= $this->order_prepend ?>pagesizeDD" onchange="<?= $this->index_name ?>=0;loadUserteams(); return false;">
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                    <div class="d-inline-block ml-2">
                        <button class="btn btn-sm btn-dark" title="Total Found">
                            <i class="si si-list"></i>
                            <span class="badge badge-light badge-pill"><div id="total_count_div"></div></span>
                        </button>
                    </div>
                </div>
                <! ** END BLOCK HEADER -->
                <! ** BEGIN BLOCK SEARCH TABLE -->
                <div class="bg-info-light" id="userteam_search_table">
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="searching_userteams">
                        <input type="hidden" name="<?= $this->order_prepend ?>orderby" value="<?= htmlentities($this->orderby) ?>">
                        <input type="hidden" name="<?= $this->order_prepend ?>orderdir" value="<?= htmlentities($this->orderdir) ?>">
                        <input type="text" class="form-control" placeholder="Team Name.." name="s_team_name" value="<?= htmlentities($_REQUEST['s_team_name']) ?>"/>
                        <button type="submit" value="Search" title="Search Names" class="btn btn-sm btn-primary" name="the_Search_button" onclick="loadUsers();return false;">Search</button>
                        <button type="button" value="Reset" title="Reset Search Criteria" class="btn btn-sm btn-primary" onclick="resetUserTeamForm(this.form);resetPageSystem('<?= $this->index_name ?>');loadUserteams();return false;">Reset</button>
                    </div>
                </div>
                <! ** END BLOCK SEARCH TABLE -->
                <! ** BEGIN BLOCK LIST (DATATABLE) -->
                <div class="block-content">
                    <table class="table table-sm table-striped" id="userteam_table">
                        <caption id="current_time_span" class="small text-right">Server Time: <?= date("g:ia m/d/Y T") ?></caption>
                        <tr>
                            <th class="row2 text-left"><?= $this->getOrderLink('team_name') ?>Team Name</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('user_count') ?>Member Count</a></th>
                            <th class="row2">&nbsp;</th>
                        </tr>
                    </table>
                </div>
                <! ** END BLOCK LIST (DATATABLE) -->
            </form>
        </div>
        <?
    }

    function makeEdit($id)
    {
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
                            $('#team_member_adder').append('<li id="memberid_' + v.user_id + '" class="ui-state-default hand" onclick="removeUserFromTeam(' + v.user_id + ')" title="' + v.fullname + '">' + v.username + '</li>');
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
                            $('#team_members').append('<li id="userid_' + v.user_id + '" class="ui-state-highlight hand" title="' + v.fullname + '" onclick="addUserToTeam(' + v.user_id + ');">' + v.username + '</li>');
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
                            $('#team_members').append('<li id="userid_' + v.user_id + '" class="ui-state-highlight hand" title="' + v.fullname + '" onclick="addUserToTeam(' + v.user_id + ');">' + v.username + '</li>');
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

            function addUserToTeam(user_id) {

                console.log('Adding :: ' + user_id);


                $.ajax({
                    type: "POST",
                    cache: false,
                    async: false,
                    dataType: 'json',
                    crossDomain: false,
                    crossOrigin: false,
                    url: 'api/api.php?get=user_teams&mode=json&action=addTeamMember&team=' + team_id + '&userid=' + user_id,
                    done: function () {


                        loadTeamMembers(team_id);

                    }
                });

                loadUserteams();

                loadTeamMembers(team_id);
            }

            function removeUserFromTeam(user_id) {

                $.ajax({
                    type: "POST",
                    cache: false,
                    async: false,
                    dataType: 'json',
                    crossDomain: false,
                    crossOrigin: false,
                    url: 'api/api.php?get=user_teams&mode=json&action=removeTeamMember&team=' + team_id + '&userid=' + user_id,
                    success: function () {
                        //alert('Member has been removed from ' + team_name);
                        if (frontEnd_debug) {
                            console.log('Prefs have just been loaded :: ', tileDefs);
                            console.log('User Preferences loaded');
                        }

                        loadUserteams();

                        loadTeamMembers(team_id);
                    },
                    fail: function () {

                    }

                });

                //loadUserteams();
            }

            $('#team_member_adder').sortable({


                receive: function (e, ui) {

                    let user_id = ui.item[0].id.split('_')[1];

                    ui.item.removeClass('ui-state-highlight').addClass('ui-state-default');

                    $(ui.item).attr('id', 'memberid_' + user_id);

                    addUserToTeam(user_id);
                },
                remove: function (e, ui) {
                    let current_id = ui.item[0].id.split('_')[1];
                    console.log('Removing :: ' + current_id);

                    ui.item.removeClass('ui-state-default').addClass('ui-state-highlight');
                    $(ui.item).attr('id', 'userid_' + current_id);

                    removeUserFromTeam(current_id);

                }
            });
            loadUserGroups();
            loadUserListByName(' ');
        </script>
        <div class="container-fluid">
            <div class="row form-group">
                <label class="col-form-label col-2" for="team_name">Team Name</label>
                <input class="form-control col-6" id="team_name" name="team_name" type="text" maxlength="128"/>
                <div class="col-4">
                    <button class="btn btn-sm btn-primary" id="save_team_name" value="Save" title="Save Team Name">Save</button>
                </div>
            </div>
            <div class="row form-group">
                <div class="col-6 offset-6">
                    <input class="form-control" placeholder="Username search.." id="user_search" name="user_search" type="text" maxlength="128"/>
                </div>
            </div>
            <div class="row form-group">
                <label class="col-form-label col-2 offset-6" for=" group_select">Group Filter : </label>
                <div class="col-4">
                    <select class="form-control custom-select-sm" id="group_select" name="group_select"></select>
                </div>
            </div>
            <div class="row">
                <h4 class="col-6">Team Members</h4>
                <h4 class="col-6">User List</h4>
            </div>
            <div class="row">
                <div class="col-6">
                    <ul id="team_member_adder" class="userList"></ul>
                </div>
                <div class="col-6">
                    <ul id="team_members" class="userList"></ul>
                </div>
            </div>
        </div>
        <?
    }

    function getOrderLink($field)
    {
        $var = '<a href="#" onclick="setOrder(\'' . addslashes($this->order_prepend) . '\',\'' . addslashes($field) . '\',';
        $var .= "((" . $this->order_prepend . "orderdir == 'DESC')?'ASC':'DESC')";
        $var .= ");loadUserteams();return false;\">";
        return $var;
    }
}
