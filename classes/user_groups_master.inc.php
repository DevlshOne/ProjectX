<?
    /***************************************************************
     *    User Groups Master - Handles user grouping en masse
     *    Written By: Dave Mednick
     *  Date Created: 20190708
     ***************************************************************/

    $_SESSION['user_groups_master'] = new UserGroupsMaster;

    class UserGroupsMaster
    {
        var $table = 'user_groups_master';            ## Classes main table to operate on
        var $orderby = 'group_name';                ## Default Order field
        var $orderdir = 'ASC';                    ## Default order direction
        ## Page  Configuration
        var $pagesize = 100;                        ## Adjusts how many items will appear on each page
        var $index = 0;                            ## You dont really want to mess with this variable. Index is adjusted by code, to change the pages
        var $index_name = 'user_groups_master_list';    ## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
        var $frm_name = 'usrgrpsmstrnextfrm';
        var $order_prepend = 'usrgrpsmstr_';            ## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

        function UserGroupsMaster()
        {
            ## REQURES DB CONNECTION!
            $this->handlePOST();
        }

        function handlePOST()
        {
            // THIS SHIT IS MOTHERFUCKIGN AJAXED TO THE TEETH
            // SEE api/names.api.php FOR POST HANDLING!
            // <3 <3 -Jon
        }

        function handleFLOW()
        {
            # Handle flow, based on query string
            if (!checkAccess('users')) {
                accessDenied("Users");
                return;
            } else {
                if (isset($_REQUEST['add_user_groups_master'])) {
                    $this->makeAdd($_REQUEST['add_user_groups_master']);
                } else {
                    $this->listEntrys();
                }
            }
        }

        function listEntrys()
        {
            ?>
            <script>
                var name_delmsg = 'Are you sure you want to delete this name?';
                var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
                var <?=$this->order_prepend?>orderdir = "<?=$this->orderdir?>";
                var <?=$this->index_name?> =
                0;
                var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;
                var UserGroupsMasterTableFormat = [
                    ['group_name', 'align_left'],
                    ['user_group', 'align_left'],
                    ['company_id', 'align_center'],
                    ['office', 'align_center'],
                    ['time_shift', 'align_center'],
                    ['agent_type', 'align_left'],
                ];

                /**
                 * Build the URL for AJAX to hit, to build the list
                 */
                function getUserGroupsMasterURL() {
                    let frm = getEl('<?=$this->frm_name?>');
                    return 'api/api.php' +
                        '?get=user_groups_master&' +
                        'mode=xml&' +
                        's_user_group=' + encodeURI(frm.s_user_group.value) + '&' +
                        's_group_name=' + encodeURI(frm.s_group_name.value) + '&' +
                        's_office=' + encodeURI(frm.s_office.value) + '&' +
                        'index=' + (<?=$this->index_name?> * <?=$this->order_prepend?>pagesize
                )
                    +'&pagesize=' + <?=$this->order_prepend?>pagesize + '&' +
                    'orderby=' + <?=$this->order_prepend?>orderby + '&orderdir=' + <?=$this->order_prepend?>orderdir;
                }

                var user_groups_master_loading_flag = false;

                /**
                 * Load the name data - make the ajax call, callback to the parse function
                 */
                function loadUser_groups_master() {
                    // ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
                    let val = null;
                    eval('val = user_groups_master_loading_flag');
                    // CHECK IF WE ARE ALREADY LOADING THIS DATA
                    if (val == true) {
                        //console.log("USER GROUPS MASTER ALREADY LOADING (BYPASSED) \n");
                        return;
                    } else {
                        eval('user_groups_master_loading_flag = true');
                    }
                    <?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());
                    loadAjaxData(getUserGroupsMasterURL(), 'parseUserGroupsMaster');
                }

                /**
                 * CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
                 */
                let <?=$this->order_prepend?>totalcount = 0;
                function parseUserGroupsMaster(xmldoc) {
                    <?=$this->order_prepend?>totalcount = parseXMLData('user_groups_master', UserGroupsMasterTableFormat, xmldoc);
                    // ACTIVATE PAGE SYSTEM!
                    if (<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize) {
                        makePageSystem('user_groups_master',
                            '<?=$this->index_name?>',
                            <?=$this->order_prepend?>totalcount,
                            <?=$this->index_name?>,
                            <?=$this->order_prepend?>pagesize,
                            'loadUser_groups_master()'
                        );
                    } else {
                        hidePageSystem('user_groups_master');
                    }
                    eval('user_groups_master_loading_flag = false');
                }

                function handleUserGroupsMasterListClick(id) {
                    displayAddUserGroupsMasterDialog(id);
                }

                function displayAddUserGroupsMasterDialog(id) {
                    var objname = 'dialog-modal-add-user-groups-master';
                    if (id > 0) {
                        $('#' + objname).dialog("option", "title", 'Editing Master User Group');
                    } else {
                        $('#' + objname).dialog("option", "title", 'Adding new Master User Group');
                    }
                    $('#' + objname).dialog("open");
                    $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                    $('#' + objname).load("index.php?area=user_groups_master&add_users_group_master=" + id + "&printable=1&no_script=1");
                    $('#' + objname).dialog('option', 'position', 'center');
                }

                function resetUserGroupsMasterForm(frm) {
                    frm.s_group_name.value = '';
                    frm.s_user_group.value = '';
                    frm.s_company_id.value = '';
                    frm.s_time_shift.value = '';
                    frm.s_agent_type.value = '';
                    frm.s_office.value = '';
                }

                let usrgrpsmstrsrchtog = false;
                function toggleUserGroupsMasterSearch() {
                    usrgrpsmstrsrchtog = !usrgrpsmstrsrchtog;
                    ieDisplay('user_groups_master_search_table', usrgrpsmstrsrchtog);
                }
            </script>
            <div id="dialog-modal-add-user-groups-master" title="Adding new Master User Group" class="nod">
            </div>
            <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST"
                  action="<?= $_SERVER['REQUEST_URI'] ?>" onsubmit="loadUser_groups_master();return false">
                <input type="hidden" name="searching_user_groups_master">
                <table border="0" width="100%" class="lb" cellspacing="0">
                    <tr>
                        <td height="40" class="pad_left ui-widget-header">
                            <table border="0" width="100%">
                                <tr>
                                    <td width="500">Master User Groups&nbsp;
                                        <!-- <input type="button" value="Add" onclick="displayAddUserGroupsMasterDialog(0)"> -->
                                        <input type="button" value="Search" onclick="toggleUserGroupsMasterSearch()">
                                    </td>
                                    <td width="150" align="center">PAGE SIZE:&nbsp;
                                        <select name="<?= $this->order_prepend ?>pagesizeDD"
                                                id="<?= $this->order_prepend ?>pagesizeDD"
                                                onchange="<?= $this->index_name ?>=0; loadUser_groups_master();return false">
                                            <option value="20">20</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                            <option value="500">500</option>
                                        </select>
                                    </td>
                                    <td align="right">
                                        <?
                                            /** PAGE SYSTEM CELLS -- INJECTED INTO, BY JAVASCRIPT AFTER AJAX CALL **/
                                        ?>
                                        <table border="0" cellpadding="0" cellspacing="0" class="page_system_container">
                                            <tr>
                                                <td id="user_groups_master_prev_td" class="page_system_prev"></td>
                                                <td id="user_groups_master_page_td" class="page_system_page"></td>
                                                <td id="user_groups_master_next_td" class="page_system_next"></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <table border="0" width="100%" id="user_groups_master_search_table" class="nod">
                                <tr>
                                    <td rowspan="2">
                                        <font size="+1">SEARCH</font>
                                    </td>
                                    <th class="row2">Group Name</th>
                                    <th class="row2">User Group</th>
                                    <th class="row2">Company ID</th>
                                    <th class="row2">Time Shift</th>
                                    <th class="row2">Agent Type</th>
                                    <th class="row2">Office</th>
                                    <td><input type="submit" value="Search" name="the_Search_button"></td>
                                </tr>
                                <tr>
                                    <td align="center"><input type="text" name="s_group_name" size="30"
                                                              value="<?= htmlentities($_REQUEST['s_group_name']) ?>">
                                    </td>
                                    <td align="center"><input type="text" name="s_user_group" size="30"
                                                              value="<?= htmlentities($_REQUEST['s_nuser_group']) ?>">
                                    </td>
                                    <td align="center"><input type="text" name="s_company_id" size="12"
                                                              value="<?= htmlentities($_REQUEST['s_company_id']) ?>">
                                    </td>
                                    <td align="center">
                                        <select name="s_time_shift">
                                            <option<?= htmlentities($_REQUEST['s_time_shift']) == 'AM' ? ' selected' : '' ?>>
                                                AM
                                            </option>
                                            <option<?= htmlentities($_REQUEST['s_time_shift']) == 'PM' ? ' selected' : '' ?>>
                                                PM
                                            </option>
                                        </select>
                                    </td>
                                    <td align="center">
                                        <select name="s_agent_type">
                                            <option<?= htmlentities($_REQUEST['s_agent_type']) == 'cold' ? ' selected' : '' ?>>
                                                cold
                                            </option>
                                            <option<?= htmlentities($_REQUEST['s_agent_type']) == 'taps' ? ' selected' : '' ?>>
                                                taps
                                            </option>
                                            <option<?= htmlentities($_REQUEST['s_agent_type']) == 'verifier' ? ' selected' : '' ?>>
                                                verifier
                                            </option>
                                            <option<?= htmlentities($_REQUEST['s_agent_type']) == 'manager' ? ' selected' : '' ?>>
                                                manager
                                            </option>
                                            <option<?= htmlentities($_REQUEST['s_agent_type']) == 'monitor' ? ' selected' : '' ?>>
                                                monitor
                                            </option>
                                            <option<?= htmlentities($_REQUEST['s_agent_type']) == 'coldtaps' ? ' selected' : '' ?>>
                                                coldtaps
                                            </option>
                                            <option<?= htmlentities($_REQUEST['s_agent_type']) == 'training' ? ' selected' : '' ?>>
                                                training
                                            </option>
                                            <option<?= htmlentities($_REQUEST['s_agent_type']) == 'admin' ? ' selected' : '' ?>>
                                                admin
                                            </option>
                                            <option<?= htmlentities($_REQUEST['s_agent_type']) == 'all' ? ' selected' : '' ?>>
                                                all
                                            </option>
                                        </select>
                                    <td align="center"><input type="text" name="s_office" size="10"
                                                              value="<?= htmlentities($_REQUEST['s_office']) ?>"></td>
                                    <td><input type="button" value="Reset"
                                               onclick="resetUserGroupsMasterForm(this.form);resetPageSystem('<?= $this->index_name ?>');loadUser_groups_master();">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
            </form>
            <tr>
                <td colspan="2">
                    <table border="0" width="100%" id="user_groups_master_table">
                        <tr>
                            <th class="row2" align="left"><?= $this->getOrderLink('group_name') ?>Group Name</a></th>
                            <th class="row2"><?= $this->getOrderLink('user_group') ?>User Group</a></th>
                            <th class="row2"><?= $this->getOrderLink('company_id') ?>Company ID</a></th>
                            <th class="row2"><?= $this->getOrderLink('office') ?>Office</a></th>
                            <th class="row2"><?= $this->getOrderLink('time_shift') ?>Shift</a></th>
                            <th class="row2"><?= $this->getOrderLink('agent_type') ?>Agent Type</a></th>
                        </tr>
                    </table>
                </td>
            </tr>
            </table>
            <script>
                $("#dialog-modal-add-user-groups-master").dialog({
                    autoOpen: false,
                    width: 500,
                    height: 200,
                    modal: false,
                    draggable: true,
                    resizable: false
                });
                loadUser_groups_master();
            </script>
            <?
        }

        function makeAdd($id)
        {
            $id = intval($id);
            if ($id) {
                $row = $_SESSION['dbapi']->user_groups_master->getByID($id);
            }
            ?>
            <script>
                function validateGroupNameField(name, value, frm) {
                    //alert(name+","+value);
                    switch (name) {
                        default:
                            // ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
                            return true;
                            break;
                        case 'filename':
                            if (!value) return false;
                            return true;
                            break;
                    }
                    return true;
                }

                function checkGroupNameFrm(frm) {
                    var params = getFormValues(frm, 'validateGroupNameField');
                    // FORM VALIDATION FAILED!
                    // param[0] == field name
                    // param[1] == field value
                    if (typeof params == "object") {
                        switch (params[0]) {
                            default:
                                alert("Error submitting form. Check your values");
                                break;
                            case 'filename':
                                alert("Please enter the filename for this name.");
                                eval('try{frm.' + params[0] + '.select();}catch(e){}');
                                break;
                        }
                        // SUCCESS - POST AJAX TO SERVER
                    } else {
                        //alert("Form validated, posting");
                        $.ajax({
                            type: "POST",
                            cache: false,
                            url: 'api/api.php?get=user_groups_master&mode=xml&action=edit',
                            data: params,
                            error: function () {
                                alert("Error saving user form. Please contact an admin.");
                            },
                            success: function (msg) {
                                //alert(msg);
                                let result = handleEditXML(msg);
                                let res = result['result'];
                                if (res <= 0) {
                                    alert(result['message']);
                                    return;
                                }
                                loadUser_groups_master();
                                displayAddUserGroupsMasterDialog(res);
                                alert(result['message']);
                            }
                        });
                    }
                    return false;
                }

                // SET TITLEBAR
                $('#dialog-modal-add-user-groups-master').dialog("option", "title", '<?=($id) ? 'Editing Master User Group #' . $id . ' - ' . htmlentities($row['group_name']) : 'Adding new Mater User Group'?>');
            </script>
            // This is the edit / add form
            <form method="POST" action="<?= stripurl('') ?>" autocomplete="off"
                  onsubmit="checkGroupNameFrm(this); return false">
                <input type="hidden" id="adding_user_groups_master" name="adding_user_groups_master" value="<?= $id ?>">
                <table border="0" align="center">
                    <tr>
                        <th align="left" height="30">Name:</th>
                        <td><input name="name" type="text" size="50" value="<?= htmlentities($row['group_name']) ?>">
                        </td>
                    </tr>
                    <tr>
                        <th align="left" height="30">Office:</th>
                        <td><input name="office" type="text" size="50" value="<?= htmlentities($row['office']) ?>">
                        </td>
                    </tr>
                    <tr>
                        <th align="left" height="30">Voice:</th>
                        <td><?= makeVoiceDD(0, 'voice_id', $row['voice_id']) ?></td>
                    </tr>
                    <tr>
                        <th colspan="2" align="center"><input type="submit" value="Save Changes"></th>
                    </tr>
            </form>
            </table>
            <?
        }
        function getOrderLink($field)
        {
            $var = '<a href="#" onclick="setOrder(\'' . addslashes($this->order_prepend) . '\',\'' . addslashes($field) . '\',';
            $var .= "((" . $this->order_prepend . "orderdir == 'ASC')?'ASC':'DESC')";
            $var .= ");loadUser_groups_master();return false;\">";
            return $var;
        }
    }
