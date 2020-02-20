<?php
/***************************************************************
 *    Extensions - handles listing and editing of extensions/mappign to stations
 *    Written By: Jonathan Will
 ***************************************************************/

$_SESSION['extensions'] = new Extensions;


class Extensions
{

    var $table = 'extensions';    ## Classes main table to operate on
    var $orderby = 'id';        ## Default Order field
    var $orderdir = 'DESC';    ## Default order direction


    var $max_bulk_add_size = 200;


    ## Page  Configuration
    var $pagesize = 20;    ## Adjusts how many items will appear on each page
    var $index = 0;        ## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

    var $index_name = 'ext_list';    ## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
    var $frm_name = 'extnextfrm';

    var $order_prepend = 'ext_';                ## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

    function Extensions()
    {

        ## REQURES DB CONNECTION!
        $this->handlePOST();
    }


    function handlePOST()
    {


    }

    function handleFLOW()
    {
        # Handle flow, based on query string

        if (!checkAccess('extensions')) {


            accessDenied("Extensions");

            return;

        } else {

            if (isset($_REQUEST['add_extension'])) {

                $this->makeAdd($_REQUEST['add_extension']);

            } else if (isset($_REQUEST['bulk_tools'])) {

                //echo "BULK TOOLZZZ EXT";

                //print_r($_REQUEST);
                $this->makeBulkTools();

            } else {
                $this->listEntrys();
            }

        }

    }

    function makeBulkAdd()
    {


        ?>
        <script>
            var current_ext_count = 0;

            function countExtensions() {

                let frm = getEl('blkextfrm');
                let start = parseInt(frm.start_number.value);
                let end = parseInt(frm.end_number.value);

                current_ext_count = 1 + ((start > end) ? start - end : end - start);

                $('#exten_cnt_spn').html("" + current_ext_count);

                //alert(current_ext_count);
            }

            function validateBulkExtensionField(name, value, frm) {

                //alert(name+","+value);


                switch (name) {
                    default:

                        // ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
                        return true;
                        break;

                    case 'start_number':
                    case 'end_number':

                        value = parseInt(value);

                        if (!value || value < 1000 || value > 999999) return false;

                        return true;


                        break;
                    case 'iax_password':
                    case 'sip_password':

                        if (!value || (value.length < 8)) return false;

                        return true;


                    case 'iax_host':

                        // 1.1.1.1 is LENGTH=7
                        if (!value || (value.length < 7)) return false;

                        return true;

                    case 'phone_password':
                        if (!value) return false;

                        return true;

//				case 'port_num':
//
//					if(value%2 != 0)return false;
//
//					value = parseInt(value);
//
//					if(value <= 0)return false;
//
//					break;
                }


                return true;
            }


            function checkBulkExtensionFrm(frm) {

                // FINAL RECOUNT
                countExtensions();


                if (current_ext_count > <?=$this->max_bulk_add_size?>) {


                    alert("ERROR: Attempting to add too many extensions at once. Maximum is <?=$this->max_bulk_add_size?> per run.");
                    eval('try{frm.start_number.select();}catch(e){}');
                    return false;
                }

                var params = getFormValues(frm, 'validateBulkExtensionField');


                // FORM VALIDATION FAILED!
                // param[0] == field name
                // param[1] == field value
                if (typeof params == "object") {

                    switch (params[0]) {
                        default:

                            alert("Error submitting form. Check your values");

                            break;

                        case 'sip_password':

                            alert("Please enter a SIP Password. Minimum 8 letters.");
                            eval('try{frm.' + params[0] + '.select();}catch(e){}');
                            break;
                        case 'iax_password':

                            alert("Please enter an IAX Password. Minimum 8 letters.");
                            eval('try{frm.' + params[0] + '.select();}catch(e){}');
                            break;
                        case 'iax_host':

                            alert("Please enter a valid IP address for the IAX Registry.");
                            eval('try{frm.' + params[0] + '.select();}catch(e){}');
                            break;


                        case 'phone_password':

                            alert("Please enter the password for agent to login to the phone in Vicidial.");
                            eval('try{frm.' + params[0] + '.select();}catch(e){}');
                            break;

                        case 'start_number':

                            alert("Please enter a valid starting number for this extension range");
                            eval('try{frm.' + params[0] + '.select();}catch(e){}');
                            break;
                        case 'end_number':

                            alert("Please enter a valid ending number for this extension range");
                            eval('try{frm.' + params[0] + '.select();}catch(e){}');
                            break;

                    }

                    // SUCCESS - POST AJAX TO SERVER
                } else {


                    //alert("Form validated, posting");
//return false;

                    $.ajax({
                        type: "POST",
                        cache: false,
                        url: 'api/api.php?get=extensions&mode=xml&action=bulk_add',
                        data: params,
                        error: function () {
                            alert("Error saving form. Please contact an admin.");
                        },
                        success: function (msg) {

///alert(msg);

                            var result = handleEditXML(msg);
                            var res = result['result'];

                            if (res <= 0) {

                                alert(result['message']);

                                return;

                            }


                            loadExtensions();


                            //displayAddExtensionDialog(res);
                            $('#dialog-modal-add-bulk-extension').dialog("close");
                            //alert(result['message']);

                        }


                    });

                }

                return false;

            }


            // SET TITLEBAR
            ///$('#dialog-modal-add-bulk-extension').dialog( "option", "title", 'Adding Bulk Extensions');


        </script>
        <form id="blkextfrm" method="POST" action="<?= stripurl('') ?>" autocomplete="off" onsubmit="checkBulkExtensionFrm(this); return false">
            <input type="hidden" id="adding_bulk_extension" name="adding_bulk_extension" value="<?= $id ?>">


            <table border="0" align="center">
                <tr>
                    <th align="left" height="30">PX Server:</th>
                    <td><?

                        echo $this->makeServerDD('server_id', $row['server_id']);

                        ?></td>
                </tr>
                <tr>
                    <th align="left" height="30">Extension Start/End:</th>
                    <td>
                        <input name="start_number" type="text" size="5" minlength="4" maxlength="6" value="00000" onkeyup="this.value=this.value.replace(/[^0-9]/g,'');countExtensions()"/>
                        to
                        <input name="end_number" type="text" size="5" minlength="4" maxlength="6" value="00000" onkeyup="this.value=this.value.replace(/[^0-9]/g,'');countExtensions()"/>

                    </td>
                </tr>
                <tr>
                    <th align="left" height="30">Description:</th>
                    <td><input type="text" name="description" size="30" value=""/></td>
                </tr>
                <tr>
                    <th align="left" height="30"># Ext. to add:</th>
                    <td><span id="exten_cnt_spn">0</span></td>
                </tr>
                <tr>
                    <th align="left" height="30">IAX Host</th>
                    <td><input name="iax_host" type="text" size="20" value=""></td>
                </tr>
                <tr>
                    <th align="left" height="30">IAX Password</th>
                    <td><input name="iax_password" type="text" size="20" value=""></td>
                </tr>
                <tr>
                    <th align="left" height="30">SIP Password</th>
                    <td><input name="sip_password" type="text" size="20" value="<?= generateRandomString(16) ?>"></td>
                </tr>
                <? /**if(intval($row['port_num']) <= 0){
                 * ?><input type="button" value="Suggest port" onclick="suggestPort()"><?
                 * }**
                 * ?></td>
                 * </tr>
                 **/

                ?>
                <tr>
                    <th align="left" height="30">VICI Phone Password</th>
                    <td><input name="phone_password" type="text" size="10" value="drlv"></td>
                </tr>


                <tr>
                    <th align="left" height="30">PX Register as User</th>
                    <td><input name="register_as" type="text" size="30" value=""><br/>(Blank means use default)</td>
                </tr>
                <tr>
                    <th align="left" height="30">PX Register Password</th>
                    <td><input name="register_pass" type="text" size="30" value=""><br/>(Blank means use default)</td>
                </tr>
                <tr>
                    <th colspan="2" align="center"><input type="submit" value="Save Changes"></th>
                </tr>
        </form>
        </table><?


    }


    function makeBulkTools()
    {

        ?>
        <script>


            /**
             * The "submit" function essentially
             */
            function applyTheChanges(frm) {


                if (frm.bulk_sip.checked) {

                    //if(!frm.cluster_id.value)return recheck('Please select a cluster then a group for that cluster.', frm.cluster_id);
                    if (!frm.new_sip_password.value) return recheck('Please enter the new SIP password.', frm.new_sip_password);

                }


                if (frm.bulk_iax.checked) {

                    if (!frm.new_iax_password.value) return recheck('Please enter the new IAX password.', frm.new_iax_password);

                }

                if (frm.bulk_iax_host.checked) {

                    if (!frm.new_iax_host.value) return recheck('Please enter the new IAX host.', frm.new_iax_host);

                }

                // AJAX POST
                // GATHER PARAMS INTO STRING
                var params = getFormValues(frm);

                $.ajax({
                    type: "POST",
                    cache: false,
                    url: 'api/api.php?get=extensions&mode=xml&action=bulk_operations',
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
                        loadExtensions();
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

                        <div style="height:150px;overflow:scroll">
                            <table border="0" align="center" width="100%">
                                <tr>
                                    <th colspan="4" class="row2" align="left">Extensions(s)</th>
                                </tr><?

                                $x = 0;
                                $cols = 4;
                                foreach ($_REQUEST['extchk'] as $ext_id) {


                                    $extrow = $_SESSION['dbapi']->extensions->getByID($ext_id);
                                    list($servername) = $_SESSION['dbapi']->queryROW("SELECT name FROM servers WHERE id='" . intval($extrow['server_id']) . "' ");

                                    ?><input type="hidden" name="editing_extensions[]" value="<?= $ext_id ?>"><?

                                    if ($x % $cols == 0) echo "<tr>\n";

                                    ?>
                                    <td align="left"><?= $extrow['number'] ?>@<?= $servername ?></td>
                                    <?

                                    if (($x + 1) % $cols == 0) echo "</tr>\n";
                                    $x++;
                                }

                                if ($x % $cols != 0) {
                                    echo '<td colspan="' . ($cols - ($x % $cols)) . '">&nbsp;</td></tr>';
                                }

                                ?></table>
                        </div>
                        <br/>
                    </td>
                </tr>


                <tr>
                    <td width="<?= $align_offset ?>" align="right"><input type="checkbox" name="bulk_sip" value="1" onclick="if(this.checked){$('#change_sippw_row').show();}else{$('#change_sippw_row').hide();}"></td>
                    <th align="left">Change SIP Password</th>
                </tr>
                <tr id="change_sippw_row" class="nod">
                    <td colspan="2" style="padding-left:<?= $align_offset ?>px">
                        <table border="0">
                            <tr>
                                <th>SIP Password</th>
                                <td><input type="text" name="new_sip_password" size="30" value=""/></td>
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
                    <td width="<?= $align_offset ?>" align="right"><input type="checkbox" name="bulk_iax" value="1" onclick="if(this.checked){$('#change_iaxpw_row').show();}else{$('#change_iaxpw_row').hide();}"></td>
                    <th align="left">Change IAX Password</th>
                </tr>
                <tr id="change_iaxpw_row" class="nod">
                    <td colspan="2" style="padding-left:<?= $align_offset ?>px">
                        <table border="0">
                            <tr>
                                <th>IAX Password</th>
                                <td><input type="text" name="new_iax_password" size="30" value=""/></td>
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
                    <td width="<?= $align_offset ?>" align="right"><input type="checkbox" name="bulk_iax_host" value="1" onclick="if(this.checked){$('#change_iaxhost_row').show();}else{$('#change_iaxhost_row').hide();}"></td>
                    <th align="left">Change IAX Host</th>
                </tr>
                <tr id="change_iaxhost_row" class="nod">
                    <td colspan="2" style="padding-left:<?= $align_offset ?>px">
                        <table border="0">
                            <tr>
                                <th>IAX Host</th>
                                <td><input type="text" name="new_iax_host" size="30" value=""/></td>
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


                <? /**
                 * <tr>
                 * <td width="<?=$align_offset?>" align="right"><input type="checkbox" name="bulk_login_reset" value="1" onclick="if(this.checked){$('#change_loginreset_row').show();}else{$('#change_loginreset_row').hide();}"></td>
                 * <th align="left">Reset Vicidial's Failed Login counter</th>
                 * </tr>
                 * <tr id="change_loginreset_row" class="nod">
                 * <td colspan="2" style="padding-left:<?=$align_offset?>px">
                 *
                 * <input type="submit" value="Submit Changes">
                 *
                 * </td>
                 * </tr>**/ ?>

        </form>
        </table><?
    }

    function listEntrys()
    {


        ?>
        <script>

            var extension_delmsg = 'Are you sure you want to delete this extension?';

            var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
            var <?=$this->order_prepend?>orderdir = "<?=$this->orderdir?>";


            var <?=$this->index_name?> =
            0;
            var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;

            var ExtensionsTableFormat = [
                ['[checkbox:extchk:id]', 'align_center'],
                ['number', 'align_center'],
                ['[get:server_name:server_id]', 'align_left'],
                ['iax_host', 'align_center'],
                ['in_use', 'align_center'],
                ['[get:username:in_use_by_userid]', 'align_center'],
                ['status', 'align_center'],
                ['[delete]', 'align_center']
            ];

            /**
             * Build the URL for AJAX to hit, to build the list
             */
            function getExtensionsURL() {

                var frm = getEl('<?=$this->frm_name?>');

                return 'api/api.php' +
                    "?get=extensions&" +
                    "mode=xml&" +

                    's_id=' + escape(frm.s_id.value) + "&" +
                    's_number=' + escape(frm.s_number.value) + "&" +
                    's_status=' + escape(frm.s_status.value) + "&" +

                    's_in_use=' + escape(frm.s_in_use.value) + "&" +


                    's_server_id=' + escape(frm.s_server_id.value) + "&" +

                    "index=" + (<?=$this->index_name?> * <?=$this->order_prepend?>pagesize
            )
                +"&pagesize=" + <?=$this->order_prepend?>pagesize + "&" +
                "orderby=" + <?=$this->order_prepend?>orderby + "&orderdir=" + <?=$this->order_prepend?>orderdir;
            }


            var extensions_loading_flag = false;

            /**
             * Load the data - make the ajax call, callback to the parse function
             */
            function loadExtensions() {

                // ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
                var val = null;
                eval('val = extensions_loading_flag');


                // CHECK IF WE ARE ALREADY LOADING THIS DATA
                if (val == true) {

                    //console.log("extensions ALREADY LOADING (BYPASSED) \n");
                    return;
                } else {

                    eval('extensions_loading_flag = true');
                }

                // PAGE SIZE SUPPORT!
                <?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());

                $('#total_count_div').html('<img src="images/ajax-loader.gif" height="20" border="0">');


                loadAjaxData(getExtensionsURL(), 'parseExtensions');

            }


            /**
             * CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
             */
            var <?=$this->order_prepend?>totalcount = 0;

            function parseExtensions(xmldoc) {

                <?=$this->order_prepend?>totalcount = parseXMLData('extension', ExtensionsTableFormat, xmldoc);


                // ACTIVATE PAGE SYSTEM!
                if (<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize) {


                    makePageSystem('extensions',
                        '<?=$this->index_name?>',
                        <?=$this->order_prepend?>totalcount,
                        <?=$this->index_name?>,
                        <?=$this->order_prepend?>pagesize,
                        'loadExtensions()'
                    );

                } else {

                    hidePageSystem('extensions');

                }

                eval('extensions_loading_flag = false');
            }


            function handleExtensionListClick(id) {

                displayAddExtensionDialog(id);

            }


            function displayAddExtensionDialog(extensionid) {

                var objname = 'dialog-modal-add-extension';


                if (extensionid > 0) {
                    $('#' + objname).dialog("option", "title", 'Editing Extension');
                } else {
                    $('#' + objname).dialog("option", "title", 'Adding new Extension');
                }


                $('#' + objname).dialog("open");

                $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

                $('#' + objname).load("index.php?area=extensions&add_extension=" + extensionid + "&printable=1&no_script=1");

                $('#' + objname).dialog('option', 'position', 'center');
            }


            function displayBulkAddExtensionDialog() {

                var objname = 'dialog-modal-add-bulk-extension';


                $('#' + objname).dialog("open");

// 				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
// 				$('#'+objname).load("index.php?area=extensions&add_extension="+extensionid+"&printable=1&no_script=1");

                $('#' + objname).dialog('option', 'position', 'center');
            }


            function resetExtensionForm(frm) {

                frm.s_id.value = '';
                frm.s_number.value = '';
                frm.s_status.value = 'enabled';
                frm.s_server_id.value = '';
                frm.s_in_use.value = '';
            }


            //			name="extchk0"

            function displayBulkToolsDialog(frm) {

                var objname = 'dialog-modal-bulk-tools';


                var ext_urlstr = "";

                // GRAB ARRAY OF CHECKED USERS
                var obj = null;
                for (var x = 0, y = 0; (obj = getEl('extchk' + x)) != null; x++) {

                    if (!obj.checked) continue;

                    ext_urlstr += (y++ > 0) ? '&' : '';
                    ext_urlstr += 'extchk[' + x + ']=' + obj.value;

                }

                //alert(user_urlstr);

                $('#' + objname).dialog("open");
                $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');


                // BULK THE QUERY STRING AND LOAD
                //$('#'+objname).post("index.php?area=users&bulk_tools&printable=1&no_script=1",user_urlstr);

                $.post("index.php?area=extensions&bulk_tools&printable=1&no_script=1", ext_urlstr, function (data) {
                    $('#' + objname).html(data);
                });

            }


            function toggleAllOnScreen(way) {
                // GRAB ARRAY OF CHECKED USERS
                var obj = null;
                for (var x = 0, y = 0; (obj = getEl('extchk' + x)) != null; x++) {
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


        </script>
        <div id="dialog-modal-add-extension" title="Adding new Extension" class="nod"></div>
        <div id="dialog-modal-bulk-tools" title="Bulk Tools" class="nod"></div>
        <div id="dialog-modal-add-bulk-extension" title="Adding Bulk Extensions" class="nod">
            <?
            $this->makeBulkAdd();
            ?>
        </div>


        <div class="block">
            <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>" onsubmit="loadExtensions();return false">
                <input type="hidden" name="searching_quiz">
                <div class="block-header bg-primary-light">
                    <h4 class="block-title">Extensions</h4>
                    <button class="btn btn-sm btn-primary" type="button" title="Add Extension" onclick="displayAddExtensionDialog(0)">Add</button>
                    <button class="btn btn-sm btn-danger" type="button" title="Bulk Add Extension" onclick="displayBulkAddExtensionDialog(0)">Bulk Add</button>

                    <div id="extensions_prev_td" class="page_system_prev"></div>
                    <div id="extensions_page_td" class="page_system_page"></div>
                    <div id="extensions_next_td" class="page_system_next"></div>
                    <select title="Rows Per Page" class="custom-select-sm" name="<?= $this->order_prepend ?>pagesize" id="<?= $this->order_prepend ?>pagesizeDD" onchange="<?= $this->index_name ?>=0;loadExtensions(); return false;">
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
                <div class="bg-info-light" id="extension_search_table">
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="searching_extensions"/>
                        <input type="text" name="s_number" class="form-control" placeholder="Extension.." value="<?= htmlentities($_REQUEST['s_number']) ?>">
                        <?= $this->makeServerDD('s_server_id', $_REQUEST['s_server_id'], true); ?>
                        <select class="custom-select-sm" name="s_in_use">
                            <option value="">[In Use?]</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                        <select class="custom-select-sm" name="s_status">
                            <option value="">[Select Status]</option>
                            <option value="enabled">Enabled</option>
                            <option value="suspended">Suspended</option>
                            <option value="deleted">Deleted</option>
                        </select>
                        <input type="text" class="form-control" name="s_id" placeholder="ID.." value="<?= htmlentities($_REQUEST['s_id']) ?>">
                        <button type="submit" value="Search" title="Search Sales" class="btn btn-sm btn-primary" name="the_Search_button" onclick="loadExtensions();return false;">Search</button>
                        <button type="button" value="Reset" title="Reset Search Criteria" class="btn btn-sm btn-primary" onclick="resetExtensionForm(this.form);resetPageSystem('<?= $this->index_name ?>');loadExtensions();return false;">Reset</button>
                    </div>
                </div>
                <div class="block-content">
                    <table class="table table-sm table-striped" id="extension_table">
                        <caption id="current_time_span" class="small text-right">Server Time: <?= date("g:ia m/d/Y T") ?></caption>
                        <tr>
                            <th class="row2 text-center">Select</th>
                            <th class="row2 text-center"><?= $this->getOrderLink('number') ?>Extension</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('server_id') ?>Server</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('iax_host') ?>Dialer Host</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('in_use') ?>In Use</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('in_use_by_userid') ?>In Use By</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('status') ?>Status</a></th>
                            <th class="row2 text-center">&nbsp;</th>
                        </tr>
                    </table>
                    <div class="text-center">
                        <div class="input-group input-group-sm">
                            <button type="button" class="btn btn-sm btn-success" onclick="toggleAllOnScreen(1);return false">Check All</button>
                            <button type="button" class="btn btn-sm btn-success" onclick="toggleAllOnScreen(0);return false">Uncheck All</button>
                            <button type="button" class="btn btn-sm btn-success" onclick="toggleAllOnScreen(2);return false">Toggle All</button>
                            <button class="btn btn-sm btn-warning" type="button" value="Bulk Tools" onclick="displayBulkToolsDialog(this.form)">Bulk Tools</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <script>

            $(document).ready(function () {

                $("#dialog-modal-add-extension").dialog({
                    autoOpen: false,
                    width: 'auto',
                    height: 'auto',
                    modal: false,
                    draggable: true,
                    position: {my: 'center', at: 'center'},
                    resizable: false
                });

                $("#dialog-modal-add-bulk-extension").dialog({
                    autoOpen: false,
                    width: 'auto',
                    height: 'auto',
                    modal: false,
                    draggable: true,
                    position: {my: 'center', at: 'center'},
                    resizable: false
                });

                $("#dialog-modal-bulk-tools").dialog({
                    autoOpen: false,
                    width: 'auto',
                    height: 'auto',
                    modal: false,
                    draggable: true,
                    position: {my: 'center', at: 'center'},
                    resizable: true
                });

                $("#dialog-modal-add-extension").closest('.ui-dialog').draggable("option","containment","#main-container");
                $("#dialog-modal-add-bulk-extension").closest('.ui-dialog').draggable("option","containment","#main-container");
                $("#dialog-modal-bulk-tools").closest('.ui-dialog').draggable("option","containment","#main-container");


                loadExtensions();
                applyUniformity();
            });

        </script><?

    }


    function makeAdd($id)
    {

        $id = intval($id);


        if ($id) {

            $row = $_SESSION['dbapi']->extensions->getByID($id);


        }

        ?>
        <script>

            function validateExtensionField(name, value, frm) {

                //alert(name+","+value);


                switch (name) {
                    default:

                        // ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
                        return true;
                        break;

                    case 'number':


                        if (!value) return false;

                        return true;


                        break;
//				case 'port_num':
//
//					if(value%2 != 0)return false;
//
//					value = parseInt(value);
//
//					if(value <= 0)return false;
//
//					break;
                }


                return true;
            }


            function checkExtensionFrm(frm) {


                var params = getFormValues(frm, 'validateExtensionField');


                // FORM VALIDATION FAILED!
                // param[0] == field name
                // param[1] == field value
                if (typeof params == "object") {

                    switch (params[0]) {
                        default:

                            alert("Error submitting form. Check your values");

                            break;

                        case 'number':

                            alert("Please enter a number for this extension.");
                            eval('try{frm.' + params[0] + '.select();}catch(e){}');
                            break;
//					case 'port_num':
//						alert("The Port number must be an EVEN and UNUSED port number for the specified server.");
//						eval('try{frm.'+params[0]+'.select();}catch(e){}');
//						break;
                    }

                    // SUCCESS - POST AJAX TO SERVER
                } else {


                    //alert("Form validated, posting");

                    $.ajax({
                        type: "POST",
                        cache: false,
                        url: 'api/api.php?get=extensions&mode=xml&action=edit',
                        data: params,
                        error: function () {
                            alert("Error saving user form. Please contact an admin.");
                        },
                        success: function (msg) {

//alert(msg);

                            var result = handleEditXML(msg);
                            var res = result['result'];

                            if (res <= 0) {

                                alert(result['message']);

                                return;

                            }


                            loadExtensions();


                            displayAddExtensionDialog(res);

                            alert(result['message']);

                        }


                    });

                }

                return false;

            }


            function suggestPort() {

                // AJAX PULL THE DATA


            }

            // SET TITLEBAR
            $('#dialog-modal-add-extension').dialog("option", "title", '<?=($id) ? 'Editing Extension #' . $id . ' - ' . htmlentities($row['number']) : 'Adding new Extension'?>');
        </script>
        <form method="POST" action="<?= stripurl('') ?>" autocomplete="off" onsubmit="checkExtensionFrm(this); return false">
            <input type="hidden" id="adding_extension" name="adding_extension" value="<?= $id ?>">
            <table border="0" align="center">
                <tr>
                    <th align="left" height="30">Server ID:</th>
                    <td>
                        <?

                        echo $this->makeServerDD('server_id', $row['server_id']);

                        ?>
                    </td>
                </tr>
                <tr>
                    <th align="left" height="30">Extension Number</th>
                    <td><input name="number" type="text" size="30" value="<?= htmlentities($row['number']) ?>"></td>
                </tr>
                <? /**        <tr>
                 * <th align="left" height="30">Station ID</th>
                 * <td><input name="station_id" type="text" size="30" value="<?=htmlentities($row['station_id'])?>"></td>
                 * </tr>
                 **/ ?>
                <tr>
                    <th align="left" height="30">IAX Host</th>
                    <td><input name="iax_host" type="text" size="30" value="<?= htmlentities($row['iax_host']) ?>"></td>
                </tr>
                <tr>
                    <th align="left" height="30">IAX Password</th>
                    <td><input name="iax_password" type="text" size="30" value="<?= htmlentities($row['iax_password']) ?>"></td>
                </tr>

                <tr>
                    <th align="left" height="30">SIP Password</th>
                    <td><input name="sip_password" type="text" size="30" value="<?= htmlentities($row['sip_password']) ?>"></td>
                </tr>
                <? /**if(intval($row['port_num']) <= 0){
                 * ?><input type="button" value="Suggest port" onclick="suggestPort()"><?
                 * }**
                 * ?></td>
                 * </tr>
                 **/

                ?>
                <tr>
                    <th align="left" height="30">PX Register as User</th>
                    <td><input name="register_as" type="text" size="30" value="<?= htmlentities($row['register_as']) ?>"></td>
                </tr>
                <tr>
                    <th align="left" height="30">PX Register Password</th>
                    <td><input name="register_pass" type="text" size="30" value="<?= htmlentities($row['register_pass']) ?>"></td>
                </tr>

                <tr>
                    <th align="left" height="30">Status</th>
                    <td><select name="status">
                            <option value="enabled">Enabled</option>
                            <option value="suspended"<?= ($row['status'] == 'suspended') ? ' SELECTED ' : '' ?>>Suspended</option>
                            <option value="deleted"<?= ($row['status'] == 'deleted') ? ' SELECTED ' : '' ?>>Deleted</option>
                        </select></td>
                </tr><?

                if ($id) {
                    ?>
                    <tr>
                        <th align="left" height="30">In Use</th>
                        <td><?= htmlentities($row['in_use']) ?></td>
                    </tr>
                    <tr>
                        <th align="left" height="30">In Use By User:</th>
                        <td><?= htmlentities($row['in_use']) ?></td>
                    </tr>
                    <tr>
                    <th align="left" height="30">Time Started:</th>
                    <td><?= ($row['time_started'] > 0) ? date("g:ia m/d/Y", $row['time_started']) : 'n/a' ?></td>
                    </tr><?

                }

                ?>
                <tr>
                    <th colspan="2" align="center"><input type="submit" value="Save Changes"></th>
                </tr>
        </form>
        </table>
        <?
    }

    function makeServerDD($name, $sel, $blank_field = false)
    {
        $out = '<select class="custom-select-sm" name="' . $name . '" id="' . $name . '">';
        $res = $_SESSION['dbapi']->query("SELECT * FROM servers ORDER BY name ASC");
        if ($blank_field) {
            $out .= '<option value="">[Select Server]</option>';
        }
        while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
            $out .= '<option value="' . $row['id'] . '"';
            $out .= ($row['id'] == $sel) ? ' SELECTED ' : '';
            $out .= '>' . $row['name'] . '</option>';
        }
        $out .= '</select>';
        return $out;
    }

    function getOrderLink($field)
    {
        $var = '<a href="#" onclick="setOrder(\'' . addslashes($this->order_prepend) . '\',\'' . addslashes($field) . '\',';
        $var .= "((" . $this->order_prepend . "orderdir == 'DESC')?'ASC':'DESC')";
        $var .= ");loadExtensions();return false;\">";
        return $var;
    }
}
