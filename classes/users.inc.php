<? /***************************************************************
 *    Users - CENTRAL USER MANAGEMENT - Managings adding/editing users
 *
 *
 * HOW TO EMERGENCY LOGOUT (hold my notes, you bastard)
 *http://10.101.1.11/vicidial/user_status.php?user=JPW&stage=log_agent_out&submit=EMERGENCY%20LOG%20AGENT%20OUT
 ***************************************************************/

$_SESSION['users'] = new UserClass;


class UserClass
{

    var $table = 'users';            ## Classes main table to operate on
    var $orderby = 'username';        ## Default Order field
    var $orderdir = 'ASC';            ## Default order direction


    ## Page  Configuration
    var $pagesize = 20;
    var $index = 0;        ## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

    var $index_name = 'usr_index';    ## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
    var $frm_name = 'usr_nextfrm';

    var $order_prepend = 'usr_';                ## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING


    ## PASSWORD COMPLEXITY REQUIREMENTS TOGGLE, SET FROM CHANGE PASSWORD CLASS
    var $pw_uppercase = '';
    var $pw_lowercase = '';
    var $pw_digits = '';
    var $pw_specialchars = '';
    var $pw_minlength = '';

    function UserClass()
    {

        include_once("db.inc.php");

        ## INCLUDED FOR ITS DROPDOWN
        include_once("classes/campaigns.inc.php");

        ## INCLUDED FEATURE CONTROL FOR ITS DROPDOWN AS WELL
        include_once("classes/feature_control.inc.php");

        ## INCLUDE CHANGE PASSWORD CLASS TO GRAB PASSWORD COMPLEXITY REQUIREMENT VARIABLE VALUES
        include_once("classes/change_password.inc.php");

        ## SET PASSWORD COMPLEXITY FLAGS FROM CHANGE PASSWORD CLASS
        $this->pw_uppercase = $_SESSION['change_password']->pw_uppercase;
        $this->pw_lowercase = $_SESSION['change_password']->pw_lowercase;
        $this->pw_digits = $_SESSION['change_password']->pw_digits;
        $this->pw_specialchars = $_SESSION['change_password']->pw_specialchars;
        $this->pw_minlength = $_SESSION['change_password']->pw_minlength;


        $this->handlePOST();

    }


    function handlePOST()
    {

        ## NO ACCESS FOR NONADMIN


        if (!checkAccess('users')) {


            //$_SESSION['api']->errorOut('Access denied to Users');

            return;
        }

//		if($_SESSION['user']['priv'] < 5){
//
//			return;
//		}


        # Ordering adjustments
        if ($_GET[$this->order_prepend . 'orderby'] && $_GET[$this->order_prepend . 'orderdir']) {
            if ($_GET[$this->order_prepend . 'orderdir'] == 'ASC')
                $this->orderdir = 'ASC';
            else    $this->orderdir = 'DESC';

            $this->orderby = $_GET[$this->order_prepend . 'orderby'];    # Or switch order by
        }

        # Page index adjustments
        if ($_REQUEST[$this->index_name]) {

            $this->index = $_REQUEST[$this->index_name] * $this->pagesize;

        }


    }


    function handleFLOW()
    {
        # Handle flow, based on query string


        ## SECURITY CHECK, MUST BE ADMIN!!
//		if($_SESSION['user']['priv'] < 5){
//
//				echo "Access to add a user DENIED.<br />";
//				return;
//
//		}else{


        if (!checkAccess('users')) {


            accessDenied("Users");

            return;

        } else {


            ## ADD/EDIT USER
            if (isset($_REQUEST['add_user'])) {

                $uid = intval($_REQUEST['add_user']);

                if ($uid && $_REQUEST['show_vici_add_form']) {

                    $this->makeAddToViciForm($uid);

                } else if ($uid && isset($_REQUEST['select_offices'])) {

                    $this->makeAddOffices($uid);

                } else {

                    $this->makeAdd($uid);

                }


            } else if (isset($_REQUEST['bulk_add'])) {


                //echo "coming soon!";


                $this->makeBulkAdd();


            } else if (isset($_REQUEST['bulk_tools'])) {


                $this->makeBulkTools();


                ## LIST USERS
            } else {

                if ($_REQUEST['show_vici_add_form'] && is_array($_REQUEST['user_checkbox'])) {

                    $this->makeAddToViciForm($_REQUEST['user_checkbox']);

                } else {

                    $this->listEntrys();

                }

            }

        }
    }


    function makeBulkTools()
    {

//print_r($_REQUEST);

        $res = $_SESSION['dbapi']->query("SELECT * FROM user_groups ORDER BY user_group ASC", 1);


        $align_offset = 100;


        ?>
        <script>

            var itemp = 0;
            var item_id = new Array();
            var item_name = new Array();
            var item_clusterid = new Array();

            <?    while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

                ?>item_id[itemp] = <?=intval($row['id'])?>;
            item_name[itemp] = "<?=addslashes($row['user_group'])?>";
            item_clusterid[itemp] = <?=intval($row['vici_cluster_id'])?>;
            itemp++;
            <?
            }

            ?>



            function buildGroupDD(selid, cluster_id, target_obj_name) {

                var obj = getEl(target_obj_name);
                var opt = obj.options;
                var catid = cluster_id;

                // Empty DD
                for (var x = 0; x < opt.length; x++) {
                    obj.remove(x);
                }
                obj.options.length = 0;

                var newopts = new Array();
//				newopts[0] = document.createElement("OPTION");
//
//				if(ie)	obj.add(newopts[0]);
//				else	obj.add(newopts[0],null);
//
//				newopts[0].innerText	= '';
//				newopts[0].value	= 0;
                var curid = 0;
                for (x = 0; x < item_id.length; x++) {
                    //curid=item_id[x];
                    curid = x;

                    //alert(catid+' '+item_name[curid]);

                    if (catid > 0 && item_clusterid[curid] != catid) {
                        continue;
                    }

                    newopts[x] = document.createElement("OPTION");

                    if (ie) obj.add(newopts[x]);
                    else obj.add(newopts[x], null);

                    newopts[x].value = item_name[curid];//item_id[curid];


                    if (ie) newopts[x].innerText = item_name[curid];
                    else newopts[x].innerHTML = item_name[curid];

                    //if(selid == item_id[curid])obj.value=item_id[curid];
                    if (selid == item_name[curid]) obj.value = item_name[curid];


                }


            }


            /**
             * The "submit" function essentially
             */
            function applyTheChanges(frm) {


                if (frm.bulk_group.checked) {

                    //if(!frm.cluster_id.value)return recheck('Please select a cluster then a group for that cluster.', frm.cluster_id);
                    if (!frm.new_group_dd.value) return recheck('Please select a cluster then a group for that cluster.', frm.new_group_dd);

                }


                if (frm.bulk_priv.checked) {

                    if (!frm.priv.value) return recheck('Please select the privilege level to set these users to.', frm.priv);

                }


                // AJAX POST
                // GATHER PARAMS INTO STRING
                var params = getFormValues(frm);

                $.ajax({
                    type: "POST",
                    cache: false,
                    url: 'api/api.php?get=users&mode=xml&action=bulk_operations',
                    data: params,
                    error: function () {
                        alert("Error saving bulk operations form. Please contact an admin.");
                    },
                    success: function (msg) {


                        var result = handleEditXML(msg);
                        var res = result['result'];

                        if (res <= 0) {

                            alert(result['message']);

                            return;

                        }

                        if (result['message']) {
                            alert(result['message']);
                        }

                        // CLOSE THE VICI ADD FRAME
                        $('#dialog-modal-bulk-tools').dialog("close");


                        // REFRESH LIST

                        loadUsers();
                    }


                });


                // CANCEL SENDING ACTUAL FORM SUBMIT
                return false;
            }


        </script>

        <form method="POST" action="<?= stripurl('') ?>" onsubmit="return applyTheChanges(this);">

            <input type="hidden" name="bulk_operations">


            <table border="0" width="100%">
                <tr>
                    <td colspan="2" align="center">
                        <table border="0" align="center" width="100%">
                            <tr>
                                <th colspan="4" class="row2" align="left">Username(s)</th>
                            </tr><?

                            $x = 0;
                            $cols = 4;
                            foreach ($_REQUEST['userchk'] as $user) {
                                ?><input type="hidden" name="add_to_users[]" value="<?= $user ?>"><?

                                if ($x % $cols == 0) echo "<tr>\n";

                                ?>
                                <td align="left"><?= $_SESSION['dbapi']->users->getName($user) ?></td>
                                <?

                                if (($x + 1) % $cols == 0) echo "</tr>\n";
                                $x++;
                            }

                            if ($x % $cols != 0) {
                                echo '<td colspan="' . ($cols - ($x % $cols)) . '">&nbsp;</td></tr>';
                            }

                            ?></table>
                        <br/>
                    </td>
                </tr>
                <tr>
                    <td width="<?= $align_offset ?>" align="right"><input type="checkbox" name="bulk_group" value="1" onclick="if(this.checked){$('#change_group_row').show();}else{$('#change_group_row').hide();}"></td>
                    <th align="left">Change Group</th>
                </tr>
                <tr id="change_group_row" class="nod">
                    <td colspan="2" style="padding-left:<?= $align_offset ?>px">
                        <table border="0">
                            <tr>
                                <th>Cluster</th>
                                <td><?

                                    echo $this->makeClusterDD('cluster_id', '', "", "buildGroupDD('', this.value,'new_group_dd')", 1);

                                    ?></td>
                            </tr>
                            <tr>
                                <th>Group</th>
                                <td><select name="new_group_dd" id="new_group_dd"></select></td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td>
                                    <input type="submit" value="Submit Changes">
                                </td>
                            </tr>
                        </table>

                        <script>

                            buildGroupDD('', $('#new_group_dd').val(), 'new_group_dd');

                        </script>
                    </td>
                </tr>


                <tr>
                    <td width="<?= $align_offset ?>" align="right"><input type="checkbox" name="bulk_addtovici" value="1" onclick="if(this.checked){$('#add_to_vici_row').show();}else{$('#add_to_vici_row').hide();}"></td>
                    <th align="left">Add Users to Vicidial</th>
                </tr>
                <tr id="add_to_vici_row" class="nod">
                    <td colspan="2" style="padding-left:<?= $align_offset ?>px">
                        <table border="0">
                            <tr>
                                <th align="left">Cluster</th>
                                <td><?

                                    echo $this->makeClusterDD('av_cluster_id', '', '', "buildGroupDD('', this.value,'av_main_group_dd')", -2);

                                    ?></td>
                            </tr>
                            <tr>
                                <th align="left">Main Group</th>
                                <td><select id="av_main_group_dd" name="av_main_group_dd"></select></td>
                            </tr>
                            <tr>
                                <th align="left">Office</th>
                                <td><?

                                    echo makeOfficeDD("av_office_id", $_REQUEST['av_office_id'], '', "", 0);


                                    ?></td>
                            </tr>
                            <tr>
                                <th align="left">Vici Settings Template</th>
                                <td><?

                                    echo $this->makeViciTemplateDD('av_template_id', $_REQUEST['av_template_id'], '', "", "[None]");

                                    ?></td>
                            </tr>
                            <tr>
                                <td colspan="2" align="center"><input type="submit" value="Add To Vici"></td>
                            </tr>
                        </table>
                        <script>

                            buildGroupDD('', $('#av_cluster_id').val(), 'av_main_group_dd')

                        </script>
                    </td>
                </tr>


                <tr>
                    <td width="<?= $align_offset ?>" align="right"><input type="checkbox" name="bulk_vicitemplate" value="1" onclick="if(this.checked){$('#change_vicitemplate_row').show();}else{$('#change_vicitemplate_row').hide();}"></td>
                    <th align="left">Change Vici-Settings Template</th>
                </tr>
                <tr id="change_vicitemplate_row" class="nod">
                    <td colspan="2" style="padding-left:<?= $align_offset ?>px">
                        <table border="0">
                            <tr>
                                <th align="right">Cluster:</th>
                                <td><?

                                    echo $this->makeClusterDD('template_cluster_id', '', "", "", 0);

                                    ?></td>
                            </tr>
                            <tr>
                                <th align="right">Vici Template:</th>
                                <td><?

                                    echo $this->makeViciTemplateDD('vici_template_id', $_REQUEST['vici_template_id'], '', "", 0);

                                    ?></td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td>
                                    <input type="submit" value="Submit Changes">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>


                <tr>
                    <td width="<?= $align_offset ?>" align="right"><input type="checkbox" name="bulk_featureset" value="1" onclick="if(this.checked){$('#change_featureset_row').show();}else{$('#change_featureset_row').hide();}"></td>
                    <th align="left">Change PX Feature Set</th>
                </tr>
                <tr id="change_featureset_row" class="nod">
                    <td colspan="2" style="padding-left:<?= $align_offset ?>px">
                        <table border="0">

                            <tr>
                                <td colspan="2" align="center">

                                    NOTE: Features only work on Managers.<br/>
                                    It will not work for any other level of access.


                                </td>
                            </tr>

                            <tr>
                                <th align="left">Feature Set: (<a href="#" onclick="alert('Feature Set - Determines the sections/areas of the system they will see, based on a template.');return false">help?</a>)</th>
                                <td><?

                                    echo $_SESSION['feature_control']->makeDD('feature_id', $row['feature_id'], '');

                                    ?></td>
                            </tr>


                            <tr>
                                <td>&nbsp;</td>
                                <td>
                                    <input type="submit" value="Submit Changes">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>


                <tr>
                    <td width="<?= $align_offset ?>" align="right"><input type="checkbox" name="bulk_priv" value="1" onclick="if(this.checked){$('#change_priv_row').show();}else{$('#change_priv_row').hide();}"></td>
                    <th align="left">Change Level of Access</th>
                </tr>
                <tr id="change_priv_row" class="nod">
                    <td colspan="2" style="padding-left:<?= $align_offset ?>px">
                        <table border="0">
                            <tr>
                                <th>New Priv.</th>
                                <td><select name="priv" id="priv">
                                        <option value="1">Trainee</option>
                                        <option value="2">Caller</option>
                                    </select></td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td>
                                    <input type="submit" value="Submit Changes">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>


                <tr>
                    <td width="<?= $align_offset ?>" align="right"><input type="checkbox" name="bulk_login_reset" value="1" onclick="if(this.checked){$('#change_loginreset_row').show();}else{$('#change_loginreset_row').hide();}"></td>
                    <th align="left">Reset Vicidial's Failed Login counter</th>
                </tr>
                <tr id="change_loginreset_row" class="nod">
                    <td colspan="2" style="padding-left:<?= $align_offset ?>px">

                        <input type="submit" value="Submit Changes">

                    </td>
                </tr>
                <? /**<tr>
                 * <td><input type="checkbox" name="bulk_vici_add" value="1"></td>
                 * <th align="left">Add user(s) to Vicidial Cluster</th>
                 * </tr>**/ ?>

        </form>
        </table>
        <script>

            buildGroupDD($('cluster_id').val());

        </script><?


    }


    function listEntrys()
    {


        ?>
        <script>

            var user_delmsg = 'Are you sure you want to delete this user?';

            var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
            var <?=$this->order_prepend?>orderdir = "<?=$this->orderdir?>";


            var <?=$this->index_name?> =
            0;
            var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;

            var UsersTableFormat = [
                ['[checkbox:userchk:id]', 'align_center'],
                ['id', 'align_center'],
                ['username', 'align_left'],
                ['first_name', 'align_left'],
                ['last_name', 'align_left'],

                ['[priv]', 'align_center'],
                ['[time:last_login]', 'align_center'],
                ['[delete]', 'align_center']
            ];

            /**
             * Build the URL for AJAX to hit, to build the list
             */
            function getUsersURL() {

                var frm = getEl('<?=$this->frm_name?>');

                return 'api/api.php' +
                    "?get=users&" +
                    "mode=xml&" +
                    //"account_id="+acc_id+"&"+

                    's_username=' + escape(frm.s_username.value) + "&" +
                    's_name=' + escape(frm.s_name.value) + "&" +


                    's_group_name=' + escape(frm.s_group_name.value) + "&" +

                    //'s_campaign_id='+escape(frm.s_campaign_id.value)+"&"+
                    's_cluster_id=' + escape(frm.s_cluster_id.value) + "&" +
                    's_priv=' + escape(frm.s_priv.value) + "&" +


                    's_feature_id=' + escape(frm.s_feature_id.value) + "&" +


                    "index=" + (<?=$this->index_name?> * <?=$this->order_prepend?>pagesize
            )
                +"&pagesize=" + <?=$this->order_prepend?>pagesize + "&" +
                "orderby=" + <?=$this->order_prepend?>orderby + "&orderdir=" + <?=$this->order_prepend?>orderdir;
            }


            var users_loading_flag = false;

            /**
             * Load the license data - make the ajax call, callback to the parse function
             */
            function loadUsers() {

                // ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
                var val = null;
                eval('val = users_loading_flag');


                // CHECK IF WE ARE ALREADY LOADING THIS DATA
                if (val == true) {

                    //console.log("USERS ALREADY LOADING (BYPASSED) \n");
                    return;
                } else {

                    eval('users_loading_flag = true');
                }

                <?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());

                $('#total_count_div').html('<img src="images/ajax-loader.gif" height="20" border="0">');

                loadAjaxData(getUsersURL(), 'parseUsers');

            }


            /**
             * CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
             */
            var <?=$this->order_prepend?>totalcount = 0;

            function parseUsers(xmldoc) {

                <?=$this->order_prepend?>totalcount = parseXMLData('user', UsersTableFormat, xmldoc);


                // ACTIVATE PAGE SYSTEM!
                if (<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize) {


                    makePageSystem('users',
                        '<?=$this->index_name?>',
                        <?=$this->order_prepend?>totalcount,
                        <?=$this->index_name?>,
                        <?=$this->order_prepend?>pagesize,
                        'loadUsers()'
                    );

                } else {

                    hidePageSystem('users');

                }

                eval('users_loading_flag = false');
            }


            function handleUserListClick(id) {

                displayAddUserDialog(id);

            }


            function displayAddUserDialog(userid, mode) {
                var objname = 'dialog-modal-add-user';
                if (userid > 0) {
                    $('#' + objname).dialog("option", "title", 'Editing User');
                } else {
                    $('#' + objname).dialog("option", "title", 'Adding new User' + ((mode == 1) ? 's' : ''));
                }
                // RESET HEIGHT
                $('#' + objname).dialog('option', 'height', 450);
                $('#' + objname).dialog("open");
                $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                // MODE = 1 : BULK USER ADD MODE
                if (mode == 1) {
                    // RESET WIDTH
                    $('#' + objname).dialog('option', 'height', 450);
                    $('#' + objname).dialog('option', 'width', 500);
                    $('#' + objname).load("index.php?area=users&bulk_add=" + userid + "&printable=1&no_script=1");
                    // MODE = 0 : SINGLE USER ADD MODE (normal add)
                } else {
                    // RESET WIDTH
                    $('#' + objname).dialog('option', 'width', 750);
                    $('#' + objname).load("index.php?area=users&add_user=" + userid + "&printable=1&no_script=1");
                }
                $('#' + objname).dialog('option', 'position', 'center');
            }

            function displayBulkToolsDialog(frm) {
                var objname = 'dialog-modal-bulk-tools';
                var user_urlstr = "";
                // GRAB ARRAY OF CHECKED USERS
                var obj = null;
                for (var x = 0, y = 0; (obj = getEl('userchk' + x)) != null; x++) {
                    if (!obj.checked) continue;
                    user_urlstr += (y++ > 0) ? '&' : '';
                    user_urlstr += 'userchk[' + x + ']=' + obj.value;
                }
                //alert(user_urlstr);
                $('#' + objname).dialog("open");
                $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');


                // BULK THE QUERY STRING AND LOAD
                //$('#'+objname).post("index.php?area=users&bulk_tools&printable=1&no_script=1",user_urlstr);

                $.post("index.php?area=users&bulk_tools&printable=1&no_script=1", user_urlstr, function (data) {
                    $('#' + objname).html(data);
                });

            }


            function toggleAllOnScreen(way) {

                // GRAB ARRAY OF CHECKED USERS
                var obj = null;
                for (var x = 0, y = 0; (obj = getEl('userchk' + x)) != null; x++) {


                    if (way == 0) {

                        obj.checked = false;
                    } else if (way == 1) {

                        obj.checked = true;
                    } else {
                        obj.checked = !obj.checked;
                    }

                }

                applyUniformity();

            }


            function resetUserForm(frm) {

                frm.s_name.value = '';
                frm.s_username.value = '';
                //frm.s_campaign_id.value = 0;
                frm.s_cluster_id.value = 0;
                frm.s_group_name.value = '';

                frm.s_priv.value = '';

                frm.s_feature_id.value = '';

            }


            function nukeAllUserLockouts() {

                // api/api.php?asdkfjsjfdk user bullshit-> nuke_all_lockouts


                $.post("api/api.php?get=users&action=nuke_all_lockouts", null, nukeSuccess);
            }

            function nukeSuccess(data) {
                alert("Successfully reset all vicidial failed login attempts");
            }

        </script>
        <div id="dialog-modal-add-user" title="Adding new User" class="nod"></div>
        <div id="dialog-modal-bulk-tools" title="Bulk Tools" class="nod"></div>


        <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>#usersarea" onsubmit="loadUsers();return false">
            <input type="hidden" name="searching_users">

            <input type="hidden" name="<?= $this->order_prepend ?>orderby" value="<?= htmlentities($this->orderby) ?>">
            <input type="hidden" name="<?= $this->order_prepend ?>orderdir" value="<?= htmlentities($this->orderdir) ?>">
            <a name="usersarea"></a>
            <table border="0" width="100%" class="lb" cellspacing="0">
                <tr>
                    <td height="40" class="pad_left ui-widget-header">

                        <table border="0" width="100%">
                            <tr>
                                <th width="500" align="left">
                                    Users
                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                    <input type="button" value="Add" onclick="displayAddUserDialog(0,0);<? /**,'_blank','width=500,height=400,scrollbars=1,resizable=1')**/ ?>">

                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                    <input type="button" value="Bulk Add" onclick="displayAddUserDialog(0,1);<? /**,'_blank','width=500,height=400,scrollbars=1,resizable=1')**/ ?>">


                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                    <input type="button" value="Fix all Lockouts" onclick="if(confirm('This option will reset the FAILED LOGIN ATTEMPTS number for all vicidial users, on all clusters.\nAre you sure?')){nukeAllUserLockouts();}">
                                </th>

                                <td width="150" align="center">PAGE SIZE: <select name="<?= $this->order_prepend ?>pagesizeDD" id="<?= $this->order_prepend ?>pagesizeDD" onchange="<?= $this->index_name ?>=0; loadUsers();return false">
                                        <option value="20">20</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                        <option value="500">500</option>
                                    </select></td>

                                <td align="right">
                                    <? /** PAGE SYSTEM CELLS -- INJECTED INTO, BY JAVASCRIPT AFTER AJAX CALL **/ ?>
                                    <table border="0" cellpadding="0" cellspacing="0" class="page_system_container">
                                        <tr>
                                            <td id="users_prev_td" class="page_system_prev"></td>
                                            <td id="users_page_td" class="page_system_page"></td>
                                            <td id="users_next_td" class="page_system_next"></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table border="0" id="usr_search_table">
                            <tr>
                                <td rowspan="2" width="100" align="center" style="border-right:1px solid #000">

                                    <span id="total_count_div"></span>

                                </td>
                                <th class="row2">Username</th>
                                <th class="row2">Name</th>
                                <th class="row2">Group</th>
                                <?/*<th class="row2">Campaign</th>*/ ?>
                                <th class="row2">Cluster</th>
                                <th class="row2">Access Level</th>
                                <th class="row2">Feature Set</th>
                                <td>
                                    <input type="submit" value="Search" onclick="<?= $this->index_name ?> = 0;">
                                </td>
                            </tr>
                            <tr>
                                <td><input type="text" name="s_username" size="10" value="<?= htmlentities($_REQUEST['s_username']) ?>"></td>
                                <td><input type="text" name="s_name" size="10" value="<?= htmlentities($_REQUEST['s_name']) ?>"></td>
                                <td><?

                                    echo $this->makeGroupDD('s_group_name', $_REQUEST['s_group_name'], '', "");

                                    ?></td>
                                <?/*<td><?

					echo $_SESSION['campaigns']->makeDD('s_campaign_id',$_REQUEST['s_campaign_id'],'',"",'',1);


				?></td>*/ ?>
                                <td><?

                                    echo $this->makeClusterDD('s_cluster_id', $_REQUEST['s_cluster_id'], '', "", 1);


                                    ?></td>
                                <td>
                                    <select name="s_priv">
                                        <option value="">[All]</option>
                                        <option value="1">Training</option>
                                        <option value="2">Caller</option>
                                        <option value="4">Manager</option>
                                        <option value="5">Admin</option>
                                    </select>
                                </td>
                                <td><?

                                    echo $_SESSION['feature_control']->makeDD('s_feature_id', $_REQUEST['s_feature_id'], '', "[ALL FEATURES]");

                                    ?></td>
                                <td>

                                    <input type="button" value="Reset" onclick="resetUserForm(this.form);loadUsers();">

                                </td>
                            </tr>


                        </table>
                    </td>
                </tr>


        </form>
        <tr>
            <td colspan="2">
                <table border="0" width="100%" id="user_table">
                    <tr>
                        <th class="row2">&nbsp;</th>
                        <th class="row2"><?= $this->getOrderLink('id') ?>ID</a></th>
                        <th class="row2" align="left"><?= $this->getOrderLink('username') ?>Username</a></th>
                        <th class="row2" align="left"><?= $this->getOrderLink('first_name') ?>First</a></th>
                        <th class="row2" align="left"><?= $this->getOrderLink('last_name') ?>Last</a></th>
                        <th class="row2"><?= $this->getOrderLink('priv') ?>Privilege</a></th>
                        <th class="row2"><?= $this->getOrderLink('last_login') ?>Last Login</a></th>
                        <th class="row2">&nbsp;</th>
                    </tr>
                    <tr>
                        <td colspan="8" align="center">
                            <i>Loading, please wait...</i>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2">

                <table border="0">
                    <tr>
                        <td height="30" nowrap>
                            <a href="#" onclick="toggleAllOnScreen(1);return false">[CHECK ALL]</a>
                            &nbsp;
                            <a href="#" onclick="toggleAllOnScreen(0);return false">[UNCHECK ALL]</a>
                            &nbsp;
                            <a href="#" onclick="toggleAllOnScreen(2);return false">[TOGGLE ALL]</a>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="button" value="Bulk Tools" onclick="displayBulkToolsDialog(this.form)"></td>

                    </tr>
                </table>

            </td>
        </tr>
        </table>

        <script>
            $(document).ready(function () {
                $("#dialog-modal-add-user").dialog({
                    autoOpen: false,
                    width: 'auto',
                    height: 450,
                    modal: false,
                    draggable: true,
                    resizable: true,
                    position: {my: 'center', at: 'center'},
                });
                $("#dialog-modal-bulk-tools").dialog({
                    autoOpen: false,
                    width: 'auto',
                    height: 320,
                    modal: false,
                    draggable: true,
                    resizable: true,
                    position: {my: 'center', at: 'center'},
                });

                $("#dialog-modal-add-user").dialog("widget").draggable("option","containment","#main-container");
                $("#dialog-modal-bulk-tools").dialog("widget").draggable("option","containment","#main-container");

                
                loadUsers();
                applyUniformity();
            });
        </script>
        <?


    }


    function makeAddToViciForm($users)
    {

        // CLUSTER DROPDOWN
        // MAIN GROUP (ON THAT CLUSTER)
        // OFFICE
        // IN GROUPS?
        // OR HANDLE WITH VICI TEMPLATE?

        // IF AN ARRAY IS NOT PASSED IN, MAKE IT AN ARRAY, WITH 1 ELEMENT
        if (!is_array($users)) {

            $users = array(intval($users));
        } else {

            /// FILTER ARRAY TO ONLY ALLOW NUMBERS
            // AND SKIP BLANKS TOO
            $tmparr = array();
            foreach ($users as $idx => $user) {

                if (!intval($user)) continue;

                $tmparr[] = intval($user);
            }

            $users = $tmparr;
        }


        $res = $_SESSION['dbapi']->query("SELECT * FROM user_groups ORDER BY user_group ASC", 1);


        ?>
        <script>

            var itemp = 0;
            var item_id = new Array();
            var item_name = new Array();
            var item_clusterid = new Array();

            <?    while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

                ?>item_id[itemp] = <?=intval($row['id'])?>;
            item_name[itemp] = "<?=addslashes($row['user_group'])?>";
            item_clusterid[itemp] = <?=intval($row['vici_cluster_id'])?>;
            itemp++;
            <?
            }

            ?>



            function buildGroupDD(selid) {

                var obj = getEl('main_group_dd');
                var opt = obj.options;
                var catid = getEl('vici_cluster_id').value;

                // Empty DD
                for (var x = 0; x < opt.length; x++) {
                    obj.remove(x);
                }
                obj.options.length = 0;

                var newopts = new Array();
//				newopts[0] = document.createElement("OPTION");
//
//				if(ie)	obj.add(newopts[0]);
//				else	obj.add(newopts[0],null);
//
//				newopts[0].innerText	= '';
//				newopts[0].value	= 0;
                var curid = 0;
                var medie = false;
                for (x = 0; x < item_id.length; x++) {
                    //curid=item_id[x];
                    curid = x;

                    //alert(which+' '+item_name[curid]);

                    if (catid && item_clusterid[curid] != catid) {
                        continue;
                    }

                    medie = false;
                    for (var i = 0; i < obj.length; ++i) {
                        if (obj.options[i].value == item_name[curid]) {
                            medie = true;
                            break;
                        }
                    }

                    if (medie) continue;

                    newopts[x] = document.createElement("OPTION");

                    if (ie) obj.add(newopts[x]);
                    else obj.add(newopts[x], null);

                    newopts[x].value = item_name[curid];//item_id[curid];


                    if (ie) newopts[x].innerText = item_name[curid];
                    else newopts[x].innerHTML = item_name[curid];

                    //if(selid == item_id[curid])obj.value=item_id[curid];
                    if (selid == item_name[curid]) obj.value = item_name[curid];


                }


            }


            function checkViciAddForm(frm) {


                //if(!frm.vici_cluster_id.value)return recheck('Please Specify a cluster to add them to.', frm.vici_cluster_id);

                if (!frm.main_group_dd.value) return recheck('Please Specify the users main group to add them to.', frm.main_group_dd);

                if (!frm.office_id.value) return recheck('Please Specify the users office', frm.office_id);


                // GATHER PARAMS INTO STRING
                var params = getFormValues(frm);

                $.ajax({
                    type: "POST",
                    cache: false,
                    url: 'api/api.php?get=users&mode=xml&action=add_to_vici',
                    data: params,
                    error: function () {
                        alert("Error saving vici-add form. Please contact an admin.");
                    },
                    success: function (msg) {


                        var result = handleEditXML(msg);
                        var res = result['result'];

                        if (res <= 0) {

                            alert(result['message']);

                            return;

                        }

                        // CLOSE THE VICI ADD FRAME
                        $('#dialog-modal-vici-add').dialog("close");

                        // REFRESH THE EDIT USER FRAME?
                        if ($('#adding_user').val()) {
                            displayAddUserDialog($('#adding_user').val(), 0);
                        }

                    }


                });


                return false;
            }

        </script>

        <form method="POST" action="<?= stripurl() ?>" onsubmit="return checkViciAddForm(this)">

            <input type="hidden" name="adding_user_to_vici">

            <table border="0" align="center">
                <tr>
                    <th class="row2" align="left">Username</th>
                </tr><?

                foreach ($users as $user) {
                    ?><input type="hidden" name="add_to_users[]" value="<?= $user ?>">
                    <tr>
                    <td align="left"><?= $_SESSION['dbapi']->users->getName($user) ?></td>
                    </tr><?
                }

                ?></table>
            <hr/>


            <table border="0" align="center">
                <tr>
                    <th align="left">Cluster</th>
                    <td><?

                        echo $this->makeClusterDD('vici_cluster_id', '', '', "buildGroupDD(this.value)", -2);

                        ?></td>
                </tr>
                <tr>
                    <th align="left">Main Group</th>
                    <td><select id="main_group_dd" name="main_group_dd"></select></td>
                </tr>
                <tr>
                    <th align="left">Office</th>
                    <td><?

                        echo makeOfficeDD("office_id", $_REQUEST['office_id'], '', "", 0);


                        ?></td>
                </tr>
                <tr>
                    <th align="left">Vici Settings Template</th>
                    <td><?

                        echo $this->makeViciTemplateDD('vici_template_id', $_REQUEST['vici_template_id'], '', "", 1);

                        ?></td>
                </tr>
                <tr>
                    <td colspan="2" align="center"><input type="submit" value="Add To Vici"></td>
                </tr>
        </form>
        </table>

        <script>

            buildGroupDD($('#vici_cluster_id').val());

        </script><?

    }


    function makeBulkAdd()
    {

        $res = $_SESSION['dbapi']->query("SELECT * FROM user_groups ORDER BY user_group ASC", 1);


        $align_offset = 100;

        ?>
        <script src="js/md5.js"></script>
        <script>

            var itemp = 0;
            var item_id = new Array();
            var item_name = new Array();
            var item_clusterid = new Array();

            <?    while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

                ?>item_id[itemp] = <?=intval($row['id'])?>;
            item_name[itemp] = "<?=addslashes($row['user_group'])?>";
            item_clusterid[itemp] = <?=intval($row['vici_cluster_id'])?>;
            itemp++;
            <?
            }

            ?>


            var resultcnt = 0, checkingcnt = 0, errorcnt = 0;

            var input_cnt = 0;

            // JS FUNCTIONS TO CHECK PASSWORD COMPLEXITY
            function pwCheckComplexity(pw) {

                // SET CHARACTER MATCH STRINGS
                var uppercase = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
                var lowercase = "abcdefghijklmnopqrstuvwxyz";
                var digits = "0123456789";
                var specialChars = "!@#$%&*()[]{}?><,.:;~`";

                // SET CHARACTER MATCH FLAGS BASED ON CLASS SETTINGS AND RUN PW THROUGH CHARACTER CHECKS
                <?=($this->pw_uppercase) ? 'var ucaseFlag = contains(pw, uppercase);' : 'var ucaseFlag = false;'?>
                <?=($this->pw_lowercase) ? 'var lcaseFlag = contains(pw, lowercase);' : 'var lcaseFlag = false;'?>
                <?=($this->pw_digits) ? 'var digitsFlag = contains(pw, digits);' : 'var digitsFlag = false;'?>
                <?=($this->pw_specialchars) ? 'var specialCharsFlag = contains(pw, specialChars);' : 'var specialCharsFlag = false;'?>

                // CHECK COMPLEXITY MATCH FLAGS
                if (pw.length >=<?=$this->pw_minlength?><?=($this->pw_uppercase) ? ' && ucaseFlag ' : ''?><?=($this->pw_lowercase) ? ' && lcaseFlag ' : ''?><?=($this->pw_digits) ? ' && digitsFlag ' : ''?><?=($this->pw_specialchars) ? ' && specialCharsFlag ' : ''?>)

                    return true;

                else

                    return false;


            }

            // RUN PASSWORD THROUGH COMPLEXITY CHECK
            function contains(pw, allowedChars) {

                for (i = 0; i < pw.length; i++) {

                    var char = pw.charAt(i);
                    if (allowedChars.indexOf(char) >= 0) {
                        return true;
                    }

                }

                return false;

            }


            function saveBulkAdd(frm) {
                var obj = null;

                if (!frm.newpass.value) {
                    alert("Error: Please enter a password first.");
                    frm.newpass.select();
                    return false;
                }

                if (!frm.confpass.value) {
                    alert("Error: Please confirm the password.");
                    frm.confpass.select();
                    return false;
                }

                if (frm.newpass.value != frm.confpass.value) {
                    alert("Error: The password doesn't match the confirm. password");
                    frm.newpass.select();
                    return false;

                }

                // ONLY CHECK PW COMPLEXITY FOR PRIV 4 OR HIGHER
                if (frm.priv.value >= 4) {

                    if (!pwCheckComplexity(frm.newpass.value)) {

                        alert("Error: Password doesn't meet the complexity requirements, please try again.");
                        frm.newpass.select();
                        return false;

                    }

                }

                resultcnt = 0;
                checkingcnt = 0;
                errorcnt = 0;

                for (var x = 0; (obj = getEl('bulk_username_input_' + x)) != null; x++) {

//alert("x="+x);

                    // SKIP BLANKS
                    if (!obj.value.trim()) continue;

                    checkingcnt++;

                    checkUserExists(x, obj.value);

                }


                return false;
            }


            function buildGroupDD(selid, cluster_id, target_obj_name) {

                var obj = getEl(target_obj_name);
                var opt = obj.options;
                var catid = cluster_id;

                // Empty DD
                for (var x = 0; x < opt.length; x++) {
                    obj.remove(x);
                }
                obj.options.length = 0;

                var newopts = new Array();
//				newopts[0] = document.createElement("OPTION");
//
//				if(ie)	obj.add(newopts[0]);
//				else	obj.add(newopts[0],null);
//
//				newopts[0].innerText	= '';
//				newopts[0].value	= 0;
                var curid = 0;
                for (x = 0; x < item_id.length; x++) {
                    //curid=item_id[x];
                    curid = x;

                    //alert(catid+' '+item_name[curid]);

                    if (catid > 0 && item_clusterid[curid] != catid) {
                        continue;
                    }

                    newopts[x] = document.createElement("OPTION");

                    if (ie) obj.add(newopts[x]);
                    else obj.add(newopts[x], null);

                    newopts[x].value = item_name[curid];//item_id[curid];


                    if (ie) newopts[x].innerText = item_name[curid];
                    else newopts[x].innerHTML = item_name[curid];

                    //if(selid == item_id[curid])obj.value=item_id[curid];
                    if (selid == item_name[curid]) obj.value = item_name[curid];


                }


            }


            function addAnotherUserInput() {
                $('#blkadd_userdiv').append('<input type="text" id="bulk_username_input_' + input_cnt + '" size="10" onchange="checkUserExists(' + input_cnt + ', this.value, true);"/><input type="text" size="10" id="bulk_firstname_input_' + input_cnt + '" /><span id="bulkusr_infospan_' + input_cnt + '"></span><br />');
                input_cnt++;
                applyUniformity();
            }

            function completeBulkUserPost() {
                var frm = getEl('blkusraddformtag');
                // FINAL CHECK BEFORE USING THE FIELD
                if (!frm.newpass.value) {
                    alert("Error: Please enter a password first.");
                    frm.newpass.select();
                    return false;
                }
                // ONLY CHECK PW COMPLEXITY FOR PRIV 4 OR HIGHER
                if (frm.priv.value >= 4) {
                    if (!pwCheckComplexity(frm.newpass.value)) {
                        alert("Error: Password doesn't meet the complexity requirements, please try again.");
                        frm.newpass.select();
                        return false;
                    }
                }
                if (frm.newpass.value) {
                    frm.md5sum.value = hex_md5(frm.newpass.value);
                } else {
                    frm.md5sum.value = '-1';
                }
                frm.newpass.value = frm.confpass.value = "";
                var obj = null;
                var output = "";
                for (var x = 0; (obj = getEl('bulk_username_input_' + x)) != null; x++) {
                    output += obj.value + "|";
                }
                $('#bulk_add_usernames').val(output);


                output = "";
                for (var x = 0; (obj = getEl('bulk_firstname_input_' + x)) != null; x++) {
                    output += obj.value + "|";
                }

                $('#bulk_add_firstnames').val(output);


                // AJAX POST
                // GATHER PARAMS INTO STRING
                var params = getFormValues(frm);

                $.ajax({
                    type: "POST",
                    cache: false,
                    url: 'api/api.php?get=users&mode=xml&action=bulk_add_users',
                    data: params,
                    error: function () {
                        alert("Error saving bulk operations form. Please contact an admin.");
                    },
                    success: function (msg) {


                        var result = handleEditXML(msg);
                        var res = result['result'];

                        if (res <= 0) {

                            alert(result['message']);

                            return;

                        }

                        if (result['message']) {
                            alert(result['message']);
                        }

                        // CLOSE THE VICI ADD FRAME
                        $('#dialog-modal-add-user').dialog("close");


                        // REFRESH LIST

                        loadUsers();
                    }


                });
            }


            function checkUserExists(idx, username, silent) {

                if (silent) {
                    resultcnt = 0;
                    checkingcnt = 0;
                    errorcnt = 0;
                }

                // AJAX POST TO SERVER TO CHECK
                $.ajax({
                    type: "POST",
                    cache: false,
                    url: 'ajax.php?mode=check_user_exists&username=' + username,
                    //data: params,
                    error: function () {
                        alert("Error checking if user exists. Please contact an admin.");
                    },
                    success: function (msg) {

                        resultcnt++;

                        var tmparr = msg.split(":");


                        // USER EXISTS
                        if (parseInt(tmparr[0]) > 0) {

                            $('#bulkusr_infospan_' + idx).html("<img src=\"images/circle-red.gif\" /><span style=\"background-color:red\" >" + tmparr[1] + "</span>");

                            errorcnt++;

                            // USER NOT FOUND
                        } else if (parseInt(tmparr[0]) == 0) {

                            $('#bulkusr_infospan_' + idx).html("<img src=\"images/circle-green.gif\" title=\"" + tmparr[1] + "\" /><span style=\"background-color:green\"></span>");

                            // SOMETHING BAD HAPPENED
                        } else {
                            // UT OHHHHH *POOPS*

                            $('#bulkusr_infospan_' + idx).html("FROM SERVER: <span style=\"background-color:red\" >" + msg + "</span>");
                        }


                        if (resultcnt >= checkingcnt) {

                            if (errorcnt > 0) {

                                if (!silent)
                                    alert("Some users were found to already exist.\nPlease correct to continue.");

                            } else {

                                if (!silent) completeBulkUserPost();

                            }
                        }


                    }
                });

            }


            function togglePriv(curpriv) {

                if (curpriv == 4) {

                    $('#feature_set_tr').show();

                } else {
                    $('#feature_set_tr').hide();

                    $('#feature_id').attr('value', 0);
                }


            }


        </script>

        <form id="blkusraddformtag" method="POST" action="<?= stripurl() ?>" onsubmit="return saveBulkAdd(this);">
            <input type="hidden" id="md5sum" name="md5sum">

            <table border="0" width="100%">

                <input type="hidden" name="bulk_add_usernames" id="bulk_add_usernames"/>
                <input type="hidden" name="bulk_add_firstnames" id="bulk_add_firstnames"/>
                <tr valign="top">
                    <th>Username/Name</th>
                    <td>

                        <div id="blkadd_userdiv">


                        </div>

                        <br/>
                        <a href="#" onclick="addAnotherUserInput();return false">[ADD 1 USER]</a> | <a href="#" onclick="for(var x=0;x < 5;x++){addAnotherUserInput();}">[ADD 5 MORE USERS]</a><br/>
                        <br/>
                    </td>
                </tr>
                <tr>
                    <th align="left">Privilege (<a href="#" onclick="alert('Determines what they have access to:\n\Trainee = Quiz Only\n\tCaller = PX Agent and Verifier systems\n\tManager and Admin = The PX admin tools/this.');return false">help?</a>):</th>
                    <td><select name="priv" id="priv" onchange="togglePriv(this.value)">
                            <option value="1">Trainee</option>
                            <option value="2">Caller</option>
                            <option value="4">Manager</option>

                        </select></td>
                </tr>

                <tr id="feature_set_tr">
                    <th align="left">Feature Set: (<a href="#" onclick="alert('Feature Set - Determines the sections/areas of the system they will see, based on a template.');return false">help?</a>)</th>
                    <td><?

                        echo $_SESSION['feature_control']->makeDD('feature_id', '', '');

                        ?></td>
                </tr>


                <tr>
                    <th align="left">Set Password:</th>
                    <td><input name="newpass" id="newpass" size="30" type="password"></td>
                </tr>
                <tr>
                    <th align="left">Confirm Password:</th>
                    <td><input name="confpass" id="confpass" size="30" type="password"></td>
                </tr>

                <tr>
                    <th align="left">Vicidial Password:</th>
                    <td>
                        <input type="text" name="vici_password" size="30" value="">
                    </td>
                </tr>
                <tr>
                    <th align="left">Primary Group:</th>
                    <td><?

                        echo makeViciUserGroupDD('primary_user_group', '', '', "$('#av_main_group_dd').val(this.value)", 0, "[None]");

                        ?></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="checkbox" name="bulk_addtovici" value="1" onclick="if(this.checked){$('#add_to_vici_row').show();}else{$('#add_to_vici_row').hide();}">
                        Add Users to Vicidial
                    </td>
                </tr>
                <tr id="add_to_vici_row" class="nod">
                    <td colspan="2" style="padding-left:100px">
                        <table border="0">
                            <tr>
                                <th align="left">Cluster</th>
                                <td><?

                                    echo $this->makeClusterDD('av_cluster_id', '', '', "buildGroupDD('', this.value,'av_main_group_dd')", 1);

                                    ?></td>
                            </tr>
                            <tr>
                                <th align="left">Main Group</th>
                                <td><select id="av_main_group_dd" name="av_main_group_dd"></select></td>
                            </tr>
                            <tr>
                                <th align="left">Office</th>
                                <td><?

                                    echo makeOfficeDD("av_office_id", $_REQUEST['av_office_id'], '', "", 0);


                                    ?></td>
                            </tr>
                            <tr>
                                <th align="left">Vici Settings Template</th>
                                <td><?

                                    echo $this->makeViciTemplateDD('av_template_id', $_REQUEST['av_template_id'], '', "", "[None]");

                                    ?></td>
                            </tr>

                        </table>
                        <script>


                        </script>
                    </td>
                </tr>
                <tr valign="bottom">

                    <td colspan="2" height="40">

                        <input type="submit" value="Add Users!">
                    </td>
                </tr>


        </form>
        </table>
        <script>


            togglePriv(getEl('priv').value);

            addAnotherUserInput();

            buildGroupDD('', $('#av_cluster_id').val(), 'av_main_group_dd')

        </script><?

    }


    function makeAddOffices($id)
    {

        $id = intval($id);

        if ($id) {

            $row = $_SESSION['dbapi']->users->getByID($id);

            if ($row) {

                // LOAD THE USERS SELECTED OFFICES, TO POPULATE THE DROPDOWN
                $re2 = $_SESSION['dbapi']->query("SELECT * FROM `users_offices` WHERE user_id='" . mysqli_real_escape_string($_SESSION['dbapi']->db, $row['id']) . "'");
                $offarr = array();
                while ($r2 = mysqli_fetch_array($re2, MYSQLI_ASSOC)) {

                    $offarr[] = $r2['office_id'];

                }
            } else {

                echo "User not found";
                return;
            }
        } else {

            echo "Please add the user first.";
            return;
        }


        ?>
        <script>


            function checkUserOfficeFrm(frm) {


                var params = getFormValues(frm);

                //alert("Form validated, posting");

                $.ajax({
                    type: "POST",
                    cache: false,
                    url: 'api/api.php?get=users&mode=xml&action=edit_offices',
                    data: params,
                    error: function () {
                        alert("Error saving user form. Please contact an admin.");
                    },
                    success: function (msg) {

                        try {

                            $('#offices_div').html(msg);

                            $('#dialog-modal-select_offices').dialog("close");

                        } catch (e) {

                            go('?area=users');

                        }


                    }


                });


                return false;

            }


        </script>

        <form method="POST" action="<?= stripurl('') ?>" autocomplete="off" onsubmit="return checkUserOfficeFrm(this)">

            <input type="hidden" id="adding_user_offices" name="adding_user_offices" value="<?= $id ?>">

            <table width="100%" border="0">
                <tr>

                    <th width="50" align="left">Offices</th>
                    <td align="right"><?

                        echo makeOfficeDD('sel_offices[]', $offarr, '', "", false, 7);

                        ?></td>
                </tr>
                <tr>
                    <td align="center" colspan="2" class="pad_top">

                        <input type="submit" value="Save Offices"/>

                    </td>
                </tr>
        </form>
        </table><?


    }


    function makeAdd($id)
    {

        $id = intval($id);

        if ($id) {

            $row = $_SESSION['dbapi']->users->getByID($id);

        }

        ?>
        <script src="js/md5.js"></script>
        <script>

            // JS FUNCTIONS TO CHECK PASSWORD COMPLEXITY
            function pwCheckComplexity(pw) {

                // SET CHARACTER MATCH STRINGS
                var uppercase = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
                var lowercase = "abcdefghijklmnopqrstuvwxyz";
                var digits = "0123456789";
                var specialChars = "!@#$%&*()[]{}?><,.:;~`";

                // SET CHARACTER MATCH FLAGS BASED ON CLASS SETTINGS AND RUN PW THROUGH CHARACTER CHECKS
                <?=($this->pw_uppercase) ? 'var ucaseFlag = contains(pw, uppercase);' : 'var ucaseFlag = false;'?>
                <?=($this->pw_lowercase) ? 'var lcaseFlag = contains(pw, lowercase);' : 'var lcaseFlag = false;'?>
                <?=($this->pw_digits) ? 'var digitsFlag = contains(pw, digits);' : 'var digitsFlag = false;'?>
                <?=($this->pw_specialchars) ? 'var specialCharsFlag = contains(pw, specialChars);' : 'var specialCharsFlag = false;'?>

                // CHECK COMPLEXITY MATCH FLAGS
                if (pw.length >=<?=$this->pw_minlength?><?=($this->pw_uppercase) ? ' && ucaseFlag ' : ''?><?=($this->pw_lowercase) ? ' && lcaseFlag ' : ''?><?=($this->pw_digits) ? ' && digitsFlag ' : ''?><?=($this->pw_specialchars) ? ' && specialCharsFlag ' : ''?>)

                    return true;

                else

                    return false;


            }

            // RUN PASSWORD THROUGH COMPLEXITY CHECK
            function contains(pw, allowedChars) {

                for (i = 0; i < pw.length; i++) {

                    var char = pw.charAt(i);
                    if (allowedChars.indexOf(char) >= 0) {
                        return true;
                    }

                }

                return false;

            }


            function validateUserField(name, value, frm) {

                //alert(name+","+value);


                switch (name) {
                    default:

                        // ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
                        return true;
                        break;

                    case 'username':


                        if (!value) return false;

                        return true;


                        break;

                    case 'newpass':

                        // EDITING
                        if (parseInt(frm.adding_user.value) > 0) {

                            if (frm.newpass.value) {
                                if (!frm.confpass.value) {
                                    alert("Error: Please confirm the password.");
                                    frm.confpass.select();
                                    return false;
                                }

                                if (frm.newpass.value != frm.confpass.value) {
                                    alert("Error: The password doesn't match the confirm. password");
                                    frm.newpass.select();
                                    return false;

                                }

                                // ONLY CHECK PW COMPLEXITY FOR PRIV 4 OR HIGHER
                                if (frm.priv.value >= 4) {

                                    if (!pwCheckComplexity(frm.newpass.value)) {

                                        alert("Error: Password doesn't meet the complexity requirements, please try again.");
                                        frm.newpass.select();
                                        return false;

                                    }

                                }

                                frm.md5sum.value = hex_md5(frm.newpass.value);
                                frm.newpass.value = frm.confpass.value = "";


                            }


                            // ADDING NEW
                        } else {

//						if(!frm.newpass.value){
//							alert("Error: Please enter a password first.");
//							frm.newpass.select();
//							return false;
//						}

//						if(!frm.confpass.value){
//							alert("Error: Please confirm the password.");
//							frm.confpass.select();
//							return false;
//						}

                            if (frm.newpass.value != frm.confpass.value) {
                                alert("Error: The password doesn't match the confirm. password");
                                frm.newpass.select();
                                return false;

                            }

                            // ONLY CHECK PW COMPLEXITY FOR PRIV 4 OR HIGHER
                            if (frm.priv.value >= 4) {

                                if (!pwCheckComplexity(frm.newpass.value)) {

                                    alert("Error: Password doesn't meet the complexity requirements, please try again.");
                                    frm.newpass.select();
                                    return false;

                                }

                            }

                            if (frm.newpass.value) {

                                frm.md5sum.value = hex_md5(frm.newpass.value);


                            } else {
                                frm.md5sum.value = '-1';

                            }
                            frm.newpass.value = frm.confpass.value = "";
                        }


                        break;
                }
                return true;
            }

            function validate_email(field) {

                with (field) {
                    apos = value.indexOf("@");
                    dotpos = value.lastIndexOf(".");
                    if (apos < 1 || dotpos - apos < 2) {
                        alert("Error: Please enter a valid email for the username.");
                        return false;
                    } else {
                        return true;
                    }
                }

            }


            function checkUserFrm(frm) {


                var params = getFormValues(frm, 'validateUserField');
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

                        case 'username':

                            alert("Please enter a username (valid email address).");
                            eval('try{frm.' + params[0] + '.select();}catch(e){}');
                            break;

                        case 'newpass':
                            break; // SILENT
                    }

                    // SUCCESS - POST AJAX TO SERVER
                } else {


                    //alert("Form validated, posting");

                    $.ajax({
                        type: "POST",
                        cache: false,
                        url: 'api/api.php?get=users&mode=xml&action=edit',
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

                            // IF ADDING
                            //if(parseInt(frm.adding_user.value) <= 0){

                            alert(result['message']);

                            try {

                                loadUsers();


                                displayAddUserDialog(res);
                            } catch (e) {

                                go('?area=users');

                            }


                        }


                    });

                }

                return false;

            }


            function togglePriv(curpriv) {

                if (curpriv >= 4) {
                    $('#pw_reset_tr').show();
                } else {
                    $('#pw_reset_tr').hide();
                }

                if (curpriv == 4) {

                    $('#feature_set_tr').show();
                    $('#office_set_tr').show();


                } else {
                    $('#feature_set_tr').hide();
                    $('#office_set_tr').hide();

                    $('#feature_id').attr('value', 0);
                }


            }


            function displayAddFeatureDialog(featureid) {

                var objname = 'dialog-modal-add-feature';


                if (featureid > 0) {
                    $('#' + objname).dialog("option", "title", 'Editing Feature Set');
                } else {
                    $('#' + objname).dialog("option", "title", 'Adding new Feature Set');
                }


                $('#' + objname).dialog("open");

                $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

                $('#' + objname).load("index.php?area=feature_control&add_feature=" + featureid + "&printable=1&no_script=1");

                $('#' + objname).dialog('option', 'position', 'center');
            }


            function displayViciAddDialog() {

                var objname = 'dialog-modal-vici-add';

                $('#' + objname).dialog("option", "title", 'Adding User to Vici');


                $('#' + objname).dialog("open");

                $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

                $('#' + objname).load("index.php?area=users&add_user=<?=$id?>&show_vici_add_form=1&printable=1&no_script=1");

                $('#' + objname).dialog('option', 'position', 'center');
            }

            function displaySelectOfficeDialog() {

                var objname = 'dialog-modal-select_offices';

                $('#' + objname).dialog("option", "title", 'Select Offices');

                $('#' + objname).dialog("open");

                $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

                $('#' + objname).load("index.php?area=users&add_user=<?=$id?>&select_offices=1&printable=1&no_script=1");

                $('#' + objname).dialog('option', 'position', 'center');

            }


            function eLogoutViciCluster(cluster_id) {

                // AJAX POST TO API, TELLING TO TRIGGER EMERGENCY LOGOUT
                $.ajax({

                    type: "POST",
                    cache: false,
                    url: 'api/api.php?get=users&mode=xml&action=emergency_logout_from_vici&user_id=<?=$id?>&cluster_id=' + cluster_id,
                    //data: params,
                    error: function () {
                        alert("Error saving user form. Please contact an admin.");
                    },
                    success: function (msg) {

                        //alert("Result: "+msg);

                        var tmp = msg.split(":");

                        if (tmp.length > 1) {


                            alert(tmp[1]);

                        } else {
                            alert(msg);
                        }

                        //displayAddUserDialog(<?=$id?>, 0);

                    }

                });

            }


            function deleteUserFromViciCluster(cluster_id) {

                // AJAX POST TO API, TELLING TO DELETE
                $.ajax({
                    type: "POST",
                    cache: false,
                    url: 'api/api.php?get=users&mode=xml&action=delete_from_vici&user_id=<?=$id?>&cluster_id=' + cluster_id,
                    //data: params,
                    error: function () {
                        alert("Error saving user form. Please contact an admin.");
                    },
                    success: function (msg) {

                        // PARSE RESULTS AND DO STUFFZ
                        try {
                            var xmldoc = getXMLDoc(msg);

                            var tag = xmldoc.getElementsByTagName("Error");

                            // LOWERCASE BUG PATCH
                            if (tag.length == 0) {

                                tag = xmldoc.getElementsByTagName("error");
                            }

                            if (tag.length > 0) {

                                // GET THE FIRST TAG
                                tag = tag[0];
                                var resultcode = tag.getAttribute("code");


                                //tmparr[x].textContent

                                alert("ERROR(" + resultcode + "): " + tag.textContent);

                                return;
                            }

                            // SUCCESS


                            displayAddUserDialog(<?=$id?>, 0);

                        } catch (ex) {
                        }


                    }
                });


            }


            function checkUserExists(frm) {

                var tmpuser = frm.username.value;

                // AJAX POST TO SERVER TO CHECK
                $.ajax({
                    type: "POST",
                    cache: false,
                    url: 'ajax.php?mode=check_user_exists&username=' + tmpuser,
                    //data: params,
                    error: function () {
                        alert("Error checking if user exists. Please contact an admin.");
                    },
                    success: function (msg) {


                        var tmparr = msg.split(":");


                        // USER EXISTS
                        if (parseInt(tmparr[0]) > 0) {

                            $('#result_of_user_exist_check_spn').html("<span style=\"background-color:red\" >" + tmparr[1] + "</span>");

                            // USER NOT FOUND
                        } else if (parseInt(tmparr[0]) == 0) {

                            $('#result_of_user_exist_check_spn').html("<span style=\"background-color:green\" >" + tmparr[1] + "</span>");

                            // SOMETHING BAD HAPPENED
                        } else {
                            // UT OHHHHH *POOPS*

                            $('#result_of_user_exist_check_spn').html("FROM SERVER: <span style=\"background-color:red\" >" + msg + "</span>");
                        }


                    }
                });

            }


            // SET TITLEBAR
            $('#dialog-modal-add-user').dialog("option", "title", '<?=($id) ? 'Editing User #' . $id . ' - ' . htmlentities($row['username']) : 'Adding new User'?>');


            function toggle_offices(show_all) {


                if (!show_all) {

                    $('#offices_div').show();
                    $('#add_remove_office_button').show();

                } else {

                    $('#offices_div').hide();
                    $('#add_remove_office_button').hide();
                }

            }

            function getAPIkey() {

                var result = "";

                // AJAX POST TO API, GETTING API KEY
                $.ajax({

                    type: "POST",
                    cache: false,
                    async: false,
                    url: 'api/api.php?get=users&mode=raw&action=create_api_key',
                    //data: params,
                    error: function () {
                        alert("Error generating API key. Please contact an admin.");
                    },
                    success: function (msg) {

                        result = msg;

                    }

                });

                return result;

            }


            function toggleApiKey(way) {

                if (way == 'enable') {

                    var apikey = getAPIkey();
                    $('#api_key_text').text(apikey);
                    $('#login_api_key').val(apikey);

                } else if (way == 'generate') {

                    var apikey = getAPIkey();
                    $('#api_key_text').text(apikey);
                    $('#login_api_key').val(apikey);


                } else if (way == 'disable') {

                    $('#api_key_text').text("Disabled, please Save Changes.");
                    $('#login_api_key').val('');

                } else {


                }

            }


        </script>
        <div id="dialog-modal-add-feature" title="Adding new Feature Set" class="nod"></div>
        <div id="dialog-modal-vici-add" title="Adding User(s) to Vici" class="nod"></div>
        <div id="dialog-modal-select_offices" title="Select Offices" class="nod"></div>
        <form method="POST" action="<?= stripurl('') ?>" autocomplete="off" onsubmit="checkUserFrm(this); return false">
            <input type="hidden" id="adding_user" name="adding_user" value="<?= $id ?>">
            <table border="0" width="100%">
                <tr>
                    <td colspan="2" class="ui-widget-header pad_left" height="40">Add User</td>
                </tr>
                <tr valign="top">
                    <td>
                        <table border="0" align="center">
                            <tr>
                                <th align="left">Username:</th>
                                <td>
                                    <input name="username" id="username" type="text" size="30" value="<?= htmlentities($row['username']) ?>" autocomplete="new-password">
                                    <?
                                    if (!$id) {
                                        ?>
                                        <input type="button" value="Check if exists" onclick="checkUserExists(this.form)"><br/>
                                        <span id="result_of_user_exist_check_spn"></span>
                                        <?
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th align="left">Privilege (<a href="#" onclick="alert('Determines what they have access to:\n\Trainee = Quiz Only\n\tCaller = PX Agent and Verifier systems\n\tManager and Admin = The PX admin tools/this.');return false">help?</a>):</th>
                                <td><select name="priv" id="priv" onchange="togglePriv(this.value)">
                                        <option value="1">Trainee</option>
                                        <option value="2"<?= ($row['priv'] == 2) ? ' SELECTED ' : '' ?>>Caller</option>
                                        <option value="4"<?= ($row['priv'] == 4) ? ' SELECTED ' : '' ?>>Manager</option>
                                        <option value="5"<?= ($row['priv'] == 5) ? ' SELECTED ' : '' ?>>Administrator</option>
                                    </select></td>
                            </tr>
                            <tr id="feature_set_tr">
                                <th align="left">Feature Set: (<a href="#" onclick="alert('Feature Set - Determines the sections/areas of the system they will see, based on a template.');return false">help?</a>)</th>
                                <td>
                                    <?
                                    echo $_SESSION['feature_control']->makeDD('feature_id', $row['feature_id'], '');
                                    ?>
                                    <input type="button" value="View/Edit" style="font-size:11px" onclick="displayAddFeatureDialog(this.form.feature_id.value)">
                                    <input type="button" value="Add" style="font-size:11px" onclick="displayAddFeatureDialog(0)">
                                </td>
                            </tr>
                            <tr id="office_set_tr">
                                <th align="left">Offices: (<a href="#" onclick="alert('Feature Set - Determines the sections/areas of the system they will see, based on a template.');return false">help?</a>)</th>
                                <td>
                                    <input type="checkbox" name="allow_all_offices" <?= ($row['allow_all_offices'] == 'yes') ? ' CHECKED ' : '' ?> value="yes" onclick="toggle_offices(this.checked)"/> Allow all Offices?<br/>
                                    <br/>
                                    <div id="offices_div"><?
                                        $re2 = $_SESSION['dbapi']->query("SELECT * FROM `users_offices` WHERE user_id='" . mysqli_real_escape_string($_SESSION['dbapi']->db, $row['id']) . "'");
                                        while ($r2 = mysqli_fetch_array($re2, MYSQLI_ASSOC)) {

                                            list($ofcname) = $_SESSION['dbapi']->queryROW("SELECT `name` FROM `offices` WHERE id='" . mysqli_real_escape_string($_SESSION['dbapi']->db, $r2['office_id']) . "'");

                                            echo $r2['office_id'] . ' - ' . $ofcname . '<br />';

                                        }

                                        ?></div>

                                    <input type="button" id="add_remove_office_button" value="Add/Remove Offices" onclick="displaySelectOfficeDialog()"/><?


                                    ?>
                                    <script>
                                        <?
                                        if ($row['allow_all_offices'] == 'yes') {
                                            echo 'toggle_offices(true);';
                                        } else {
                                            echo 'toggle_offices(false);';
                                        }
                                        ?>
                                    </script>

                                </td>
                            </tr>
                            <tr>
                                <th align="left">Default TimeZone:</th>
                                <td><select name="default_timezone">
                                        <option value="America/Puerto_Rico"<?= ($row['default_timezone'] == 'America/Puerto_Rico') ? " SELECTED " : "" ?>>America/Puerto_Rico</option>
                                        <option value="America/New_York"<?= ($row['default_timezone'] == 'America/New_York') ? " SELECTED " : "" ?>>America/New_York</option>
                                        <option value="America/Chicago"<?= ($row['default_timezone'] == 'America/Chicago') ? " SELECTED " : "" ?>>America/Chicago</option>
                                        <option value="America/Boise"<?= ($row['default_timezone'] == 'America/Boise') ? " SELECTED " : "" ?>>America/Boise</option>
                                        <option value="America/Phoenix"<?= ($row['default_timezone'] == 'America/Phoenix') ? " SELECTED " : "" ?>>America/Phoenix</option>
                                        <option value="America/Los_Angeles"<?= (!$id || $row['default_timezone'] == 'America/Los_Angeles') ? " SELECTED " : "" ?>>America/Los_Angeles</option>
                                        <option value="America/Juneau"<?= ($row['default_timezone'] == 'America/Juneau') ? " SELECTED " : "" ?>>America/Juneau</option>
                                        <option value="Pacific/Honolulu"<?= ($row['default_timezone'] == 'Pacific/Honolulu') ? " SELECTED " : "" ?>>Pacific/Honolulu</option>
                                        <option value="Pacific/Guam"<?= ($row['default_timezone'] == 'Pacific/Guam') ? " SELECTED " : "" ?>>Pacific/Guam</option>
                                        <option value="Pacific/Samoa"<?= ($row['default_timezone'] == 'Pacific/Samoa') ? " SELECTED " : "" ?>>Pacific/Samoa</option>
                                        <option value="Pacific/Wake"<?= ($row['default_timezone'] == 'Pacific/Wake') ? " SELECTED " : "" ?>>Pacific/Wake</option>
                                    </select></td>
                            </tr>
                            <?

                            if ($id) {
                                ?>
                                <tr>
                                    <th align="left">Change Password:</th>
                                    <td><input name="newpass" id="newpass" size="30" type="password" autocomplete="new-password"></td>
                                </tr>
                                <tr>
                                    <th align="left">Confirm Password:</th>
                                    <td><input name="confpass" id="confpass" size="30" type="password" autocomplete="new-password"></td>
                                </tr><?
                            } else {

                                ?>
                                <tr>
                                    <th align="left">Set Password:</th>
                                    <td><input name="newpass" id="newpass" size="30" type="password" autocomplete="new-password"></td>
                                </tr>
                                <tr>
                                    <th align="left">Confirm Password:</th>
                                    <td><input name="confpass" id="confpass" size="30" type="password" autocomplete="new-password"></td>
                                </tr>
                                <?
                            }
                            ?>
                            <input type="hidden" id="md5sum" name="md5sum">
                            <tr id="pw_reset_tr">
                                <th align="center" colspan="2" title="Force the user to change there password when they login next.">
                                    <input type="checkbox" name="force_change_password" <?= (intval($row['changedpw_time']) == 0) ? " CHECKED " : '' ?> /> Force Change Password
                                </th>
                            </tr>
                            <tr>
                                <th align="left">Vicidial Password:</th>
                                <td>
                                    <input type="text" name="vici_password" size="30" value="<?= htmlentities($row['vici_password']) ?>">
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="border-top:1px dotted #000000">&nbsp;</td>
                            </tr>
                            <tr>
                                <th align="left">First Name:</th>
                                <td><input name="first_name" type="text" size="30" value="<?= htmlentities($row['first_name']) ?>"></td>
                            </tr>
                            <tr>
                                <th align="left">Last Name:</th>
                                <td><input name="last_name" type="text" size="30" value="<?= htmlentities($row['last_name']) ?>"></td>
                            </tr>
                            <tr>
                                <td colspan="2" style="border-top:1px dotted #000000">&nbsp;</td>
                            </tr>


                            <tr>
                                <th align="left">Primary User Group:</th>
                                <td><?= $row['user_group'] ?></td>
                            </tr>
                            <?

                            if ($id && $_SESSION['user']['priv'] >= 5) {

                                # DISPLAY API KEY INFO IF EDITING AND LOGGED IN USER PRIV >= 5

                                ?>
                                <tr>
                                <th align="left">API Key:</th>
                                <td>
                                    <div id="api_key_text">
                                        <?

                                        if ($row['login_code']) {

                                            ?><?= $row['login_code'] ?>&nbsp;&nbsp;<a href="#" onclick="toggleApiKey('generate'); return true;">[ Generate ]</a> <a href="#" onclick="toggleApiKey('disable'); return true;">[ Disable ]</a><?

                                        } else {

                                            ?><a href="#" onclick="toggleApiKey('enable'); return true;">[ Enable ]</a><?

                                        }

                                        ?></div>
                                    <input type="hidden" name="login_api_key" id="login_api_key">
                                </td>
                                </tr>
                                <?
                            }
                            ?>
                            <tr>
                                <th colspan="2"><input type="submit" value="Save Changes"></th>
                            </tr>
                        </table>
                    </td>
                    <td><?
                        if (!$id) {
                            echo "Finish adding user to manage vicidial settings.";
                        } else {
                            ?>
                            <table border="0" width="100%">
                            <tr>
                                <td colspan="5" class="big">Vicidial Cluster Access</td>
                            </tr>
                            <tr>

                                <th class="row2" align="left">Cluster</th>
                                <th class="row2">Group</th>
                                <th class="row2">Office</th>
                                <td class="row2" colspan="2">&nbsp;</td>
                            </tr><?

                            $cluster_stack = array();


                            $cres = $_SESSION['dbapi']->query("SELECT * FROM vici_clusters WHERE `status`='enabled' ORDER BY `name` ASC", 1);
                            $x = 0;
                            while ($cluster = mysqli_fetch_array($cres, MYSQLI_ASSOC)) {

                                $group = $_SESSION['dbapi']->querySQL("SELECT * FROM user_group_translations WHERE user_id='" . $row['id'] . "' AND cluster_id='" . $cluster['id'] . "' ");

                                ?>
                                <tr>
                                <th height="20" align="left"><?= $cluster['name'] ?></th>
                                <td align="center"><?

                                    if ($group) {

                                        echo $group['group_name'];
                                    } else {

                                        echo '-';
                                    }
                                    ?></td>
                                <td align="center"><?= $group['office'] ?></td>
                                <td align="center" title="Emergency Logout of Vicidial"><?

                                    if ($group) {

                                        ?><a href="#" class="red" onclick="if(confirm('Are you sure you want to Emergency Logout User from Vicidial?')){eLogoutViciCluster('<?= $cluster['id'] ?>');}return false">[E-Logout]</a><?

                                    } else {
                                        echo '-';
                                    }


                                    ?></td>
                                <td align="center"><?

                                    if ($group) {

                                        ?><a href="#" class="red" onclick="deleteUserFromViciCluster('<?= $cluster['id'] ?>');return false">[DELETE]</a><?

                                    } else {
                                        echo '-';
                                    }


                                    ?></td>
                                </tr><?


                                /*$re2 = $_SESSION['dbapi']->query("SELECT * FROM user_group_translations WHERE user_id='".$row['id']."' ORDER BY cluster_id ASC ");
					while($r2 = mysqli_fetch_array($re2, MYSQLi_ASSOC)){

						?><tr>
							<td><?=$r2['cluster_id']?></td>
							<td><?=$r2['group_name']?></td>
							<td><?=$r2['office']?></td>
						</tr><?

					}*/

                            }


                            ?>
                            <tr>
                                <td colspan="4" height="42">
                                    <input type="button" value="Add to Vici" onclick="displayViciAddDialog()">
                                </td>
                            </tr>
                            </table><?
                        }
                        ?></td>
                </tr>
            </table>


            </div>
            <script>

                togglePriv($('#priv').val());


                $("#dialog-modal-add-feature").dialog({
                    autoOpen: false,
                    width: 430,
                    height: 420,
                    modal: false,
                    draggable: true,
                    resizable: false
                });


                $("#dialog-modal-vici-add").dialog({
                    autoOpen: false,
                    width: 430,
                    height: 220,
                    modal: false,
                    draggable: true,
                    resizable: false
                });


                $("#dialog-modal-select_offices").dialog({
                    autoOpen: false,
                    width: 250,
                    height: 200,
                    modal: true,
                    draggable: true,
                    resizable: false
                });


                $("#dialog-modal-add-feature").dialog("widget").draggable("option","containment","#main-container");
                $("#dialog-modal-vici-add").dialog("widget").draggable("option","containment","#main-container");
                $("#dialog-modal-select_offices").dialog("widget").draggable("option","containment","#main-container");

            </script>
        </form><?

    } // END OF makeAdd()


    function makeClusterDD($name, $sel, $css, $onchange, $blank_option = 1)
    {

        $out = '<select name="' . $name . '" id="' . $name . '" ';

        $out .= ($css) ? ' class="' . $css . '" ' : '';
        $out .= ($onchange) ? ' onchange="' . $onchange . '" ' : '';
        $out .= '>';

        //$out .= '<option value="">[All]</option>';

        if ($blank_option > 0) {
            $out .= '<option value="" ' . (($sel == '') ? ' SELECTED ' : '') . '>' . ((!is_numeric($blank_option)) ? $blank_option : "[All]") . '</option>';
        }


        $res = query("SELECT id,name FROM vici_clusters WHERE `status`='enabled' ORDER BY `name` ASC", 1);


        while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {


            $out .= '<option value="' . htmlentities($row['id']) . '" ';
            $out .= ($sel == $row['id']) ? ' SELECTED ' : '';
            $out .= '>' . htmlentities($row['name']) . '</option>';


        }

        if ($blank_option == -2) {
            //'.(($sel == '')?' SELECTED ':'').'
            $out .= '<option value="" >' . ((!is_numeric($blank_option)) ? $blank_option : "[All]") . '</option>';
        }


        $out .= '</select>';

        return $out;
    }


    function makeViciTemplateDD($name, $sel, $class, $onchange, $blank_option = 1)
    {

        $out = '<select name="' . $name . '" id="' . $name . '" ';
        $out .= ($class) ? ' class="' . $class . '" ' : '';
        $out .= ($onchange) ? ' onchange="' . $onchange . '" ' : '';
        $out .= '>';


        $res = query("SELECT * FROM vici_user_templates ORDER BY `name` ASC", 1);


        while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {


            $out .= '<option value="' . htmlentities($row['id']) . '" ';
            $out .= ($sel == $row['id']) ? ' SELECTED ' : '';
            $out .= '>' . htmlentities($row['name']) . '</option>';


        }

        if ($blank_option) {
            //'.(($sel == '')?' SELECTED ':'').'
            $out .= '<option value="" >' . ((!is_numeric($blank_option)) ? $blank_option : "[NONE]") . '</option>';
        }

        $out .= '</select>';
        return $out;
    }


    function makeGroupDD($name, $sel, $class, $onchange, $blank_option = 1)
    {

        $out = '<select name="' . $name . '" id="' . $name . '" ';
        $out .= ($class) ? ' class="' . $class . '" ' : '';
        $out .= ($onchange) ? ' onchange="' . $onchange . '" ' : '';
        $out .= '>';

        if ($blank_option) {
            $out .= '<option value="" ' . (($sel == '') ? ' SELECTED ' : '') . '>' . ((!is_numeric($blank_option)) ? $blank_option : "[All]") . '</option>';
        }

        $res = query("SELECT DISTINCT(`user_group`) AS `user_group` FROM user_groups_master ORDER BY `user_group` ASC", 1);


        while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {


            $out .= '<option value="' . htmlentities($row['user_group']) . '" ';
            $out .= ($sel == $row['user_group']) ? ' SELECTED ' : '';
            $out .= '>' . htmlentities($row['user_group']) . '</option>';


        }

        $out .= '</select>';
        return $out;
    }


    function makeUserDD($account_id, $name, $sel, $class)
    {

        $out = '<select name="' . $name . '" id="' . $name . '" ';
        $out .= ($class) ? ' class="' . $class . '" ' : '';
        ##$out.= ($onchange)?' onchange="'.$onchange.'" ':'';
        $out .= '>';


        $account_id = ($account_id && $_SESSION['account']['class'] != 'customer') ? $account_id : $_SESSION['user']['account_id'];


        $dat = array();
        $dat['account_id'] = $account_id;
        $dat['enabled'] = 'yes';

        if ($_SESSION['user']['priv'] < 5) {
            $dat['priv_lte'] = $_SESSION['user']['priv'];
        }

        $dat['order'] = array('username' => 'ASC');

        $res = $_SESSION['dbapi']->users->getResults($dat);


        while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {

            $out .= '<option value="' . $row['id'] . '" ';
            $out .= ($sel == $row['id']) ? ' SELECTED ' : '';
            $out .= '>' . htmlentities($row['username']) . '</option>';
        }


        $out .= '</select>';

        return $out;
    }


    function getOrderLink($field)
    {

        $var = '<a href="#" onclick="setOrder(\'' . addslashes($this->order_prepend) . '\',\'' . addslashes($field) . '\',';

        $var .= "((" . $this->order_prepend . "orderdir == 'DESC')?'ASC':'DESC')";

        $var .= ");loadUsers();return false;\">";

        return $var;
    }
}
