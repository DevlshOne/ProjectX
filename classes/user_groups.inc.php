<? /***************************************************************
 *User GROUPS - Vici User Group Management Tools
 * Written By: Jonathan Will
 ***************************************************************/

$_SESSION['user_groups'] = new UserGroupsClass;


class UserGroupsClass
{

    var $table = 'user_groups';            ## Classes main table to operate on
    var $orderby = 'user_group';        ## Default Order field
    var $orderdir = 'ASC';            ## Default order direction


    ## Page  Configuration
    var $pagesize = 20;
    var $index = 0;        ## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

    var $index_name = 'usrgrp_index';    ## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
    var $frm_name = 'usrgrp_nextfrm';

    var $order_prepend = 'usrgrp_';                ## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

    function UserGroupsClass()
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


            ## ADD/EDIT USER
            if (isset($_REQUEST['add_user_group'])) {

                $uid = intval($_REQUEST['add_user_group']);


                $this->makeAdd($uid);


                ## LIST USERS
            } else {


                /*			if(!$_REQUEST['group_sub']){

                                $this->makeTabInterface();

                            }else{

                                switch($_REQUEST['group_sub']){
                                default:
                                case 'cluster':*/

                $this->listEntrys();

                /*						break;
                                    case 'master':


                                        echo "Master controls coming soon!";

                                        break;

                                    }

                                }*/

                //$this->listEntrys();

            }

        }
    }


    function makeTabInterface()
    {

        ?>
        <div id="grouptabs" style="position: absolute">

            <ul>
                <li><a href="<?= stripurl('group_sub') ?>group_sub=master">Master Group List</a></li>
                <li><a href="<?= stripurl('group_sub') ?>group_sub=cluster">Group Cluster Assignment</a></li>
            </ul>

        </div>
        <script>
            $(function () {
                $("#grouptabs").tabs({
                    beforeLoad: function (event, ui) {
                        ui.jqXHR.fail(function () {
                            ui.panel.html("Couldn't load this tab. We'll try to fix this as soon as possible. ");
                        });
                    }
                });
            });
        </script><?
    }

    /**
     * Jon, All verifiers groups are GT unless the group specifies 94/98
     * so they would be allied
     */
    function listEntrys()
    {


        ?>
        <script>

            var usergroup_delmsg = "THIS WILL DELETE THE GROUP FROM THE VICIDIAL CLUSTER AS WELL!\nAre you sure you want to delete this user group?";

            var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
            var <?=$this->order_prepend?>orderdir = "<?=$this->orderdir?>";


            var <?=$this->index_name?> =
            0;
            var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;

            var UserGroupsTableFormat = [

                ['user_group', 'align_left'],
                ['name', 'align_left'],
                ['[get:cluster_name:vici_cluster_id]', 'align_center'],
                ['office', 'align_center'],

                ['[delete]', 'align_center']
            ];

            /**
             * Build the URL for AJAX to hit, to build the list
             */
            function getUserGroupsURL() {

                var frm = getEl('<?=$this->frm_name?>');

                return 'api/api.php' +
                    "?get=user_groups&" +
                    "mode=xml&" +

                    's_name=' + escape(frm.s_name.value) + "&" +
                    's_group_name=' + escape(frm.s_group_name.value) + "&" +
                    's_cluster_id=' + escape(frm.s_cluster_id.value) + "&" +


                    "index=" + (<?=$this->index_name?> * <?=$this->order_prepend?>pagesize
            )
                +"&pagesize=" + <?=$this->order_prepend?>pagesize + "&" +
                "orderby=" + <?=$this->order_prepend?>orderby + "&orderdir=" + <?=$this->order_prepend?>orderdir;
            }


            var usergroups_loading_flag = false;

            /**
             * Load the license data - make the ajax call, callback to the parse function
             */
            function loadUsergroups() {

                // ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
                var val = null;
                eval('val = usergroups_loading_flag');


                // CHECK IF WE ARE ALREADY LOADING THIS DATA
                if (val == true) {

                    //console.log("USERGROUPS ALREADY LOADING (BYPASSED) \n");
                    return;
                } else {

                    eval('usergroups_loading_flag = true');
                }

                <?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());

                $('#total_count_div').html('<img src="images/ajax-loader.gif" height="20" border="0">');

                loadAjaxData(getUserGroupsURL(), 'parseUserGroups');

            }


            /**
             * CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
             */
            var <?=$this->order_prepend?>totalcount = 0;

            function parseUserGroups(xmldoc) {

                <?=$this->order_prepend?>totalcount = parseXMLData('usergroup', UserGroupsTableFormat, xmldoc);


                // ACTIVATE PAGE SYSTEM!
                if (<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize) {


                    makePageSystem('usergroups',
                        '<?=$this->index_name?>',
                        <?=$this->order_prepend?>totalcount,
                        <?=$this->index_name?>,
                        <?=$this->order_prepend?>pagesize,
                        'loadUsergroups()'
                    );

                } else {

                    hidePageSystem('usergroups');

                }

                eval('usergroups_loading_flag = false');
            }


            function handleUsergroupListClick(id) {

                displayAddUserGroupDialog(id);

            }


            function displayAddUserGroupDialog(id) {
                var objname = 'dialog-modal-add-user-group';
                if (id > 0) {
                    $('#' + objname).dialog("option", "title", 'Editing User Group');
                } else {
                    $('#' + objname).dialog("option", "title", 'Adding new User Group');
                }
                $('#' + objname).dialog("open");
                $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                $('#' + objname).load("index.php?area=user_groups&add_user_group=" + id + "&printable=1&no_script=1");
            }


            function resetUserGroupForm(frm) {
                frm.reset();
                frm.s_name.value = '';
                frm.s_cluster_id.value = '';
                frm.s_group_name.value = '';
            }
        </script>
        <div id="dialog-modal-add-user-group" title="Adding new User Group" class="nod"></div>
        <div class="block">
            <a name="usersarea"></a>
            <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>#usergroupsarea" onsubmit="loadUsergroups();return false">
                <! ** BEGIN BLOCK HEADER -->
                <div class="block-header bg-primary-light">
                    <h4 class="block-title">User Groups</h4>
                    <button type="button" title="Add User Group" class="btn btn-sm btn-primary" onclick="displayAddUserGroupDialog(0)">Add</button>
                    <div id="usergroups_prev_td" class="page_system_prev"></div>
                    <div id="usergroups_page_td" class="page_system_page"></div>
                    <div id="usergroups_next_td" class="page_system_next"></div>
                    <select title="Rows Per Page" class="custom-select-sm" name="<?= $this->order_prepend ?>pagesize" id="<?= $this->order_prepend ?>pagesizeDD" onchange="<?= $this->index_name ?>=0;loadUsergroups(); return false;">
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
                <div class="bg-info-light" id="usrgrp_search_table">
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="searching_usergroups">
                        <input type="hidden" name="<?= $this->order_prepend ?>orderby" value="<?= htmlentities($this->orderby) ?>">
                        <input type="hidden" name="<?= $this->order_prepend ?>orderdir" value="<?= htmlentities($this->orderdir) ?>">
                        <input type="text" class="form-control" placeholder="Name.." name="s_name" value="<?= htmlentities($_REQUEST['s_name']) ?>"/>
                        <input type="text" class="form-control" placeholder="Group Name.." name="s_group_name" value="<?= htmlentities($_REQUEST['s_group_name']) ?>"/>
                        <?= $this->makeClusterDD('s_cluster_id', $_REQUEST['s_cluster_id'], 'form-control custom-select-sm', "", "[Select Cluster]"); ?>
                        <button type="submit" value="Search" title="Search Names" class="btn btn-sm btn-primary" name="the_Search_button" onclick="loadUsergroups();return false;">Search</button>
                        <button type="button" value="Reset" title="Reset Search Criteria" class="btn btn-sm btn-primary" onclick="resetUserGroupForm(this.form);resetPageSystem('<?= $this->index_name ?>');loadUsergroups();return false;">Reset</button>
                    </div>
                </div>
                <! ** END BLOCK SEARCH TABLE -->
                <! ** BEGIN BLOCK LIST (DATATABLE) -->
                <div class="block-content">
                    <table class="table table-sm table-striped" id="usergroup_table">
                        <caption id="current_time_span" class="small text-right">Server Time: <?= date("g:ia m/d/Y T") ?></caption>
                        <tr>
                            <th class="row2 text-left"><?= $this->getOrderLink('user_group') ?>User Group</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('name') ?>Name</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('vici_cluster_id') ?>Cluster</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('office') ?>Office</a></th>
                            <th class="row2">&nbsp;</th>
                        </tr>
                    </table>
                </div>
            </form>
        </div>
        <script>
            $(document).ready(function () {
                $("#dialog-modal-add-user-group").dialog({
                    autoOpen: false,
                    width: 'auto',
                    height: 'auto',
                    modal: false,
                    draggable: true,
                    resizable: true,
                    position: {my: 'center', at: 'center', of: '#main-container'}
                });
                $("#dialog-modal-add-user-group").closest('.ui-dialog').draggable("option", "containment", "#main-container");
                loadUsergroups();
            });
        </script>
        <?


    }


    function makeAdd($id)
    {

        $id = intval($id);

        if ($id) {

            $row = $_SESSION['dbapi']->user_groups->getByID($id);

        }

        ?>
        <script src="js/md5.js"></script>
        <script>

            function validateUserGroupField(name, value, frm) {

                //alert(name+","+value);


                switch (name) {
                    default:

                        // ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
                        return true;
                        break;

                    case 'group_name':


                        if (!value) return false;

                        return true;

                    case 'name':


                        if (!value) return false;

                        return true;

                    case 'office':


                        if (!value) return false;

                        return true;

                    case 'vici_cluster_id':


                        if (!value) return false;

                        return true;


                        break;
                }
                return true;
            }


            function checkUserGroupFrm(frm) {


                var params = getFormValues(frm, 'validateUserGroupField');
//alert(params);
//return;

                // FORM VALIDATION FAILED!
                // param[0] == field name
                // param[1] == field value
                if (typeof params == "object") {

                    switch (params[0]) {
                        default:

                            alert("Error submitting form. Check your values");

                            break;

                        case 'name':

                            alert("Please enter a name for this group");
                            eval('try{frm.' + params[0] + '.select();}catch(e){}');
                            break;
                        case 'user_group':

                            alert("Please enter the user group");
                            eval('try{frm.' + params[0] + '.select();}catch(e){}');
                            break;
                        case 'office':

                            alert("Please select the office for this group");
                            eval('try{frm.' + params[0] + '.select();}catch(e){}');
                            break;
                        case 'vici_cluster_id':

                            alert("Please select the cluster for this group");
                            eval('try{frm.' + params[0] + '.select();}catch(e){}');
                            break;
                    }

                    // SUCCESS - POST AJAX TO SERVER
                } else {


                    //alert("Form validated, posting");

                    $.ajax({
                        type: "POST",
                        cache: false,
                        url: 'api/api.php?get=user_groups&mode=xml&action=edit',
                        data: params,
                        error: function () {
                            alert("Error saving user form. Please contact an admin.");
                        },
                        success: function (msg) {


                            var result = handleEditXML(msg);
                            var res = result['result'];

                            if (res <= 0) {

                                alert(result['message']);

                                return;

                            }

                            alert(result['message']);

                            try {

                                loadUsergroups();


                                displayAddUserGroupDialog(res);
                            } catch (e) {

                                go('?area=user_groups');

                            }


                        }


                    });

                }

                return false;

            }

            // SET TITLEBAR
            $('#dialog-modal-add-user').dialog("option", "title", '<?=($id) ? 'Editing User #' . $id . ' - ' . htmlentities($row['username']) : 'Adding new User'?>');
        </script>
        <form method="POST" action="<?= stripurl('') ?>" autocomplete="off" onsubmit="checkUserGroupFrm(this); return false">
            <input type="hidden" id="adding_user_group" name="adding_user_group" value="<?= $id ?>">
            <table class="tightTable">
                <tr valign="top">
                    <td align="center">
                        <table border="0" align="center">
                            <tr>
                                <th align="left">Cluster:</th>
                                <td><?
                                    echo $this->makeClusterDD('vici_cluster_id', $row['vici_cluster_id'], '', "", 0); //(($id > 0)?0:1) );// DISABLED THE [ALL] OPTION FOR NOW, SINCE WE DONT TUNE IN ALL THE PARAMS AND HAVE TO LINK THEM TO VICI TO EDIT
                                    ?></td>
                            </tr>
                            <tr>
                                <th align="left">User Group:</th>
                                <td><?
                                    if ($id) {
                                        echo htmlentities($row['user_group']);
                                        $url = "http://" . getClusterWebHost($row['vici_cluster_id']) . "/vicidial/admin.php?ADD=311111&user_group=" . $row['user_group'];
                                        ?><input type="button" value="EDIT IN VICIDIAL" onclick="window.open('<?= $url ?>')"><?
                                    } else {
                                        ?><input class="form-control" name="user_group" type="text" size="30" maxlength="20" value="<?= htmlentities($row['user_group']) ?>"><?
                                    }
                                    ?></td>
                            </tr>
                            <tr>
                                <th align="left">Name:</th>
                                <td><input class="form-control" name="name" type="text" size="30" value="<?= htmlentities($row['name']) ?>"></td>
                            </tr>
                            <tr>
                                <th align="left">Office:</th>
                                <td><?
                                    echo makeOfficeDD('office', $row['office'], '', "", 0);
                                    ?></td>
                            </tr>
                            <tr>
                                <th colspan="2">
                                    <button class="btn btn-sm btn-primary" value="Save Changes">Save Changes</button>
                                </th>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            </div>
        </form>
        <?

    }


    function makeClusterDD($name, $sel, $css, $onchange, $blank_option = 1)
    {

        $out = '<select name="' . $name . '" id="' . $name . '" ';

        $out .= ($css) ? ' class="' . $css . '" ' : '';
        $out .= ($onchange) ? ' onchange="' . $onchange . '" ' : '';
        $out .= '>';

        //$out .= '<option value="">[All]</option>';

        if ($blank_option) {
            $out .= '<option value="" ' . (($sel == '') ? ' SELECTED ' : '') . '>' . ((!is_numeric($blank_option)) ? $blank_option : "[All]") . '</option>';
        }


        $res = query("SELECT id,name FROM vici_clusters WHERE `status`='enabled' ORDER BY `name` ASC", 1);


        while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {


            $out .= '<option value="' . htmlentities($row['id']) . '" ';
            $out .= ($sel == $row['id']) ? ' SELECTED ' : '';
            $out .= '>' . htmlentities($row['name']) . '</option>';


        }


        $out .= '</select>';

        return $out;
    }


    function getOrderLink($field)
    {

        $var = '<a href="#" onclick="setOrder(\'' . addslashes($this->order_prepend) . '\',\'' . addslashes($field) . '\',';

        $var .= "((" . $this->order_prepend . "orderdir == 'DESC')?'ASC':'DESC')";

        $var .= ");loadUsergroups();return false;\">";

        return $var;
    }
}
