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
                            $uid = intval($_REQUEST['team_id']);
                            $this->makeEdit($uid);
                            break;
                        case 'add':
                            $this->makeAdd();
                            break;
                    }
                } else {
                    ## LIST USERS
                    $this->listEntrys();
                }
            }
        }

        function listEntrys() {
            ?>
            <script>
                let userteam_delmsg = "Are you sure you want to delete this team?";
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
                    $dlgObj.html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                    $dlgObj.load("index.php?area=user_teams&action=add&printable=1&no_script=1");
                }

                function resetUserTeamForm(frm) {
                    frm.s_name.value = '';
                    frm.s_group_name.value = '';
                }
            </script>
            <div id="dialog-modal-edit-user-team" class="nod">
                <table class="tightTable pct100">
                    <thead>
                    <tr>
                        <th class="pct33">Team Name</th>
                        <th>&nbsp;</th>
                        <th>Select Users</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>
                            <div id="team_member_adder"></div>
                        </td>
                        <td>
                            <div id="team_member_remover"></div>
                        </td>
                        <td>
                            <div>
                                <select id="group_select" name="group_select">
                                    // group ids, names as options
                                </select>
                            </div>
                            <div>
                                <input id="user_autocomplete" name="user_autocomplete" type="text" maxlength="128"
                            </div>
                            <div id="team_members"></div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div id="dialog-modal-add-user-team" class="nod"></div>
            <script>
                let user_groups = '';
                $('#user_autocomplete').autocomplete({
                    minlength: 3,
                    source: user_groups
                });
                $("#dialog-modal-edit-user-team").dialog({
                    autoOpen: false,
                    width: 800,
                    height: 'auto',
                    modal: false,
                    draggable: true,
                    resizable: false,
                    title: 'Editing User Team'
                });
                $("#dialog-modal-add-user-team").dialog({
                    autoOpen: false,
                    width: 500,
                    height: 160,
                    modal: false,
                    draggable: true,
                    resizable: false,
                    title: 'Add User Team'
                });
                loadUserteams();
            </script>
            <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>#userteamsarea" onsubmit="loadUserteams();return false">
                <input type="hidden" name="searching_userteams">
                <input type="hidden" name="<?= $this->order_prepend ?>orderby" value="<?= htmlentities($this->orderby) ?>">
                <input type="hidden" name="<?= $this->order_prepend ?>orderdir" value="<?= htmlentities($this->orderdir) ?>">
                <a name="usersarea"></a>
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
                                                <td id="usergroups_prev_td" class="page_system_prev"></td>
                                                <td id="usergroups_page_td" class="page_system_page"></td>
                                                <td id="usergroups_next_td" class="page_system_next"></td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td>
                                        <div class="righty"><input type="button" value="Add" onclick="displayAddUserTeamDialog(0);"></div>
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
            return;
        }

        function makeAdd($id) {
            $id = intval($id);
            if ($id) {
                $row = $_SESSION['dbapi']->user_teams->getByID($id);
            }
            ?>
            <script src="js/md5.js"></script>
            <script>
                function validateUserTeamField(name, value, frm) {
                    //alert(name+","+value);
                    switch (name) {
                        default:
                            return true;
                            break;
                        case 'team_name':
                            if (!value) return false;
                            return true;
                            break;
                    }
                    return true;
                }

                function checkUserTeamFrm(frm) {
                    let params = getFormValues(frm, 'validateUserTeamField');
                    if (typeof params == "object") {
                        switch (params[0]) {
                            default:
                                alert("Error submitting form. Check your values");
                                break;
                            case 'team_name':
                                alert("Please enter the team name");
                                eval('try{frm.' + params[0] + '.select();}catch(e){}');
                                break;
                        }
                    } else {
                        $.ajax({
                            type: "POST",
                            cache: false,
                            url: 'api/api.php?get=user_teams&mode=xml&action=edit',
                            data: params,
                            error: function () {
                                alert("Error saving user team form. Please contact an admin.");
                            },
                            success: function (msg) {
                                let result = handleEditXML(msg);
                                let res = result['result'];
                                if (res <= 0) {
                                    alert(result['message']);
                                    return;
                                }
                                alert(result['message']);
                                try {
                                    loadUserteams();
                                    displayAddUserTeamDialog(res);
                                } catch (e) {
                                    go('?area=user_teams');
                                }
                            }
                        });
                    }
                    return false;
                }
            </script>
            <form method="POST" action="<?= stripurl('') ?>" autocomplete="off" onsubmit="checkUserTeamFrm(this); return false">
                <input type="hidden" id="adding_user_team" name="adding_user_team" value="<?= $id ?>">
                <table class="tightTable">
                    <tr valign="top">
                        <td align="center">
                            <table border="0" align="center">
                                <tr>
                                    <th align="left">Team Name:</th>
                                    <td><input name="team_name" type="text" size="30" value="<?= htmlentities($row['team_name']) ?>"></td>
                                </tr>
                                <tr>
                                    <th colspan="2"><input type="submit" value="Save Changes"></th>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </form>
            <?
        }

        function getOrderLink($field) {
            $var = '<a href="#" onclick="setOrder(\'' . addslashes($this->order_prepend) . '\',\'' . addslashes($field) . '\',';
            $var .= "((" . $this->order_prepend . "orderdir == 'DESC')?'ASC':'DESC')";
            $var .= ");loadUserteams();return false;\">";
            return $var;
        }
    }
