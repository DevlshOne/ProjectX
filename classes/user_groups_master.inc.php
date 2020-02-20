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

    var $orderby = 'user_group';                ## Default Order field
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
        // THIS SHIT IS MOTHER-FUCKING AJAX'D TO THE TEETH
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
            var user_groups_master_delmsg = 'Are you sure you want to delete this Master group?';
            var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
            var <?=$this->order_prepend?>orderdir = "<?=$this->orderdir?>";
            var <?=$this->index_name?> = 0;
            var <?=$this->order_prepend?>totalcount = 0;
            var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;
            var UserGroupsMasterTableFormat = [
                ['user_group', 'text-left'],
                ['group_name', 'text-left'],
                ['[get:company_name:company_id]', 'text-center'],
                ['[get:office_name:office]', 'text-center'],
                ['time_shift', 'text-center'],
                ['agent_type', 'text-center'],
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
                    's_company_id=' + encodeURI(frm.s_company_id.value) + '&' +
                    's_time_shift=' + encodeURI(frm.s_time_shift.value) + '&' +
                    's_agent_type=' + encodeURI(frm.s_agent_type.value) + '&' +
                    'index=' + (<?=$this->index_name?> * <?=$this->order_prepend?>pagesize
            )
                +
                    '&pagesize=' + <?=$this->order_prepend?>pagesize + '&' +
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

            function handleUser_groups_masterListClick(id) {
                displayAddUserGroupsMasterDialog(id);
            }

            function displayAddUserGroupsMasterDialog(id) {
                let objname = 'dialog-modal-add-user-groups-master';
                if (id > 0) {
                    $('#' + objname).dialog("option", "title", 'Editing Master User Group');
                } else {
                    $('#' + objname).dialog("option", "title", 'Adding new Master User Group');
                }
                $('#' + objname).dialog("open");
                $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                $('#' + objname).load("index.php?area=user_groups_master&add_user_groups_master=" + id + "&printable=1&no_script=1");
            }

            function resetUserGroupsMasterForm(frm) {
                frm.reset();
                frm.s_group_name.value = '';
                frm.s_user_group.value = '';
                frm.s_company_id.value = '';
                frm.s_time_shift.value = '';
                frm.s_agent_type.value = '';
                frm.s_office.value = '';
            }

            let usrgrpsmstrsrchtog = true;
            function toggleUserGroupsMasterSearch() {
                usrgrpsmstrsrchtog = !usrgrpsmstrsrchtog;
                ieDisplay('user_groups_master_search_table', usrgrpsmstrsrchtog);
            }
        </script>
        <div id="dialog-modal-add-user-groups-master" title="Adding new Master User Group" class="nod"></div>
        <div class="block">
            <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>" onsubmit="loadUser_groups_master();return false">
                <! ** BEGIN BLOCK HEADER -->
                <div class="block-header bg-primary-light">
                    <h4 class="block-title">Master User Groups</h4>
                    <button type="button" value="Search" title="Toggle Search" class="btn btn-sm btn-primary" onclick="toggleUserGroupsMasterSearch();">Toggle Search</button>
                    <div id="user_groups_master_prev_td" class="page_system_prev"></div>
                    <div id="user_groups_master_page_td" class="page_system_page"></div>
                    <div id="user_groups_master_next_td" class="page_system_next"></div>
                    <select title="Rows Per Page" class="custom-select-sm" name="<?= $this->order_prepend ?>pagesize" id="<?= $this->order_prepend ?>pagesizeDD" onchange="<?= $this->index_name ?>=0;loadUser_groups_master(); return false;">
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
                <div class="bg-info-light" id="user_groups_master_search_table">
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="searching_user_groups_master">
                        <input type="hidden" name="<?= $this->order_prepend ?>orderby" value="<?= htmlentities($this->orderby) ?>">
                        <input type="hidden" name="<?= $this->order_prepend ?>orderdir" value="<?= htmlentities($this->orderdir) ?>">
                        <input type="text" class="form-control" placeholder="User Group.." name="s_user_group" value="<?= htmlentities($_REQUEST['s_user_group']) ?>"/>
                        <input type="text" class="form-control" placeholder="Group Name.." name="s_group_name" value="<?= htmlentities($_REQUEST['s_group_name']) ?>"/>
                        <?= makeCompanyDD('s_company_id', htmlentities($_REQUEST['s_company_id']), null, '[Select Company]') ?>
                        <select class="form-control custom-select-sm" name="s_time_shift">
                            <option value="" selected>[Select Shift]</option>
                            <option<?= htmlentities($_REQUEST['s_time_shift']) == 'AM' ? ' selected' : '' ?>>AM</option>
                            <option<?= htmlentities($_REQUEST['s_time_shift']) == 'PM' ? ' selected' : '' ?>>PM</option>
                        </select>
                        <select class="form-control custom-select-sm" name="s_agent_type">
                            <option value="" selected>[Select Agent Type]</option>
                            <option value="cold" <?= htmlentities($_REQUEST['s_agent_type']) == 'cold' ? ' selected' : '' ?>>Cold</option>
                            <option value="taps" <?= htmlentities($_REQUEST['s_agent_type']) == 'taps' ? ' selected' : '' ?>>Taps</option>
                            <option value="verifier" <?= htmlentities($_REQUEST['s_agent_type']) == 'verifier' ? ' selected' : '' ?>>Verifier</option>
                            <option value="manager" <?= htmlentities($_REQUEST['s_agent_type']) == 'manager' ? ' selected' : '' ?>>Manager</option>
                            <option value="monitor" <?= htmlentities($_REQUEST['s_agent_type']) == 'monitor' ? ' selected' : '' ?>>Monitor</option>
                            <option value="coldtaps" <?= htmlentities($_REQUEST['s_agent_type']) == 'coldtaps' ? ' selected' : '' ?>>Cold Taps</option>
                            <option value="training" <?= htmlentities($_REQUEST['s_agent_type']) == 'training' ? ' selected' : '' ?>>Training</option>
                            <option value="admin" <?= htmlentities($_REQUEST['s_agent_type']) == 'admin' ? ' selected' : '' ?>>Admin</option>
                            <option value="all" <?= htmlentities($_REQUEST['s_agent_type']) == 'all' ? ' selected' : '' ?>>All</option>
                        </select>
                        <?= makeOfficeDD('s_office', htmlentities($_REQUEST['s_office']), "form-control custom-select-sm", null, '[Select Office]', null) ?>
                        <button type="submit" value="Search" title="Search Names" class="btn btn-sm btn-primary" name="the_Search_button" onclick="loadUsers();return false;">Search</button>
                        <button type="button" value="Reset" title="Reset Search Criteria" class="btn btn-sm btn-primary" onclick="resetUserGroupsMasterForm(this.form);resetPageSystem('<?= $this->index_name ?>');loadUser_groups_master();return false;">Reset</button>
                    </div>
                </div>
                <! ** END BLOCK SEARCH TABLE -->
                <! ** BEGIN BLOCK LIST (DATATABLE) -->
                <div class="block-content">
                    <table class="table table-sm table-striped" id="user_groups_master_table">
                        <caption id="current_time_span" class="small text-right">Server Time: <?= date("g:ia m/d/Y T") ?></caption>
                        <tr>
                            <th class="row2 text-left"><?= $this->getOrderLink('user_group') ?>User Group</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('group_name') ?>Group Name</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('company_id') ?>Company ID</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('office') ?>Office</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('time_shift') ?>Shift</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('agent_type') ?>Agent Type</a></th>
                        </tr>
                    </table>
                </div>
                <! ** END BLOCK LIST (DATATABLE) -->
            </form>
        </div>
        <script>
            $("#dialog-modal-add-user-groups-master").dialog({
                autoOpen: false,
                width: 'auto',
                height: 260,
                modal: false,
                draggable: true,
                resizable: false,
                position: {my: 'center', at: 'center', of: '#main-container'}
            });

            $("#dialog-modal-add-user-groups-master").closest('.ui-dialog').draggable("option", "containment", "#main-container");

            loadUser_groups_master();
        </script>
        <?
    }

    /**
     * @param $id
     */
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
                    case 'group_name':
                        if (!value) return false;
                        return true;
                        break;
                    case 'user_group':
                        if (!value) return false;
                        return true;
                        break;
                    case 'office':
                        if (!value) return false;
                        return true;
                        break;
                    case 'company_id':
                        if (!value) return false;
                        return true;
                        break;
                    case 'time_shift':
                        if (!value) return false;
                        return true;
                        break;
                    case 'agent_type':
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
                        case 'user_group':
                            alert("Please enter the name for this group.");
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
            $(function () {
                $('#group_name').on('keyup', function () {
                    let gnStr = this.value.toString();
                    let ugFormal = gnStr.replace(/\s+/g, '-').toUpperCase();
                    //console.log(ugFormal);
                    //$('#user_group').val(ugFormal);
                });
                $('#user_group').on('keyup', function () {
                    this.value = this.value.replace(/\s+/g, '-').toUpperCase();
                })
            })
        </script>
        <form method="POST" action="<?= stripurl('') ?>" autocomplete="off"
              onsubmit="checkGroupNameFrm(this); return false">
            <input type="hidden" id="adding_user_groups_master" name="adding_user_groups_master" value="<?= $id ?>">
            <table border="0" align="center">
                <tr>
                    <th align="left" height="30">Name:</th>
                    <td><input id="group_name" name="group_name" type="text" size="50" value="<?= htmlentities($row['group_name']) ?>">
                    </td>
                </tr>
                <tr>
                    <th align="left" height="30">Group:</th>
                    <td><input id="user_group" name="user_group" type="text" size="50" value="<?= htmlentities($row['user_group']) ?>">
                    </td>
                </tr>
                <tr>
                    <th align="left" height="30">Company:</th>
                    <td><?= makeCompanyDD('company_id', $row['company_id']) ?></td>
                </tr>
                <tr>
                    <th align="left" height="30">Office:</th>
                    <td><?= makeOfficeDD('office', $row['office'], null, null, null, null) ?></td>
                </tr>
                <tr>
                    <th align="left" height="30">Shift:</th>
                    <td>
                        <select name="time_shift">
                            <option>AM</option>
                            <option <?= ($row['time_shift'] == 'PM') ? " SELECTED " : '' ?>>PM</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th align="left" height="30">Agent Type:</th>
                    <td>
                        <select name="agent_type">
                            <option <?= ($row['agent_type'] == 'cold') ? " SELECTED " : '' ?>>cold</option>
                            <option <?= ($row['agent_type'] == 'taps') ? " SELECTED " : '' ?>>taps</option>
                            <option <?= ($row['agent_type'] == 'verifier') ? " SELECTED " : '' ?>>verifier</option>
                            <option <?= ($row['agent_type'] == 'manager') ? " SELECTED " : '' ?>>manager</option>
                            <option <?= ($row['agent_type'] == 'monitor') ? " SELECTED " : '' ?>>monitor</option>
                            <option <?= ($row['agent_type'] == 'coldtaps') ? " SELECTED " : '' ?>>coldtaps</option>
                            <option <?= ($row['agent_type'] == 'training') ? " SELECTED " : '' ?>>training</option>
                            <option <?= ($row['agent_type'] == 'admin') ? " SELECTED " : '' ?>>admin</option>
                            <option <?= ($row['agent_type'] == 'all') ? " SELECTED " : '' ?>>all</option>
                        </select>
                    </td>
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
        $var .= "((" . $this->order_prepend . "orderdir == 'ASC')?'DESC':'ASC')";
        $var .= ");loadUser_groups_master();return false;\">";
        return $var;
    }
}
