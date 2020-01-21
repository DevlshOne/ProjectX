<?php
/***************************************************************
 *    Campaigns - handles listing and editing campaigns
 *    Written By: Jonathan Will
 ***************************************************************/

$_SESSION['campaigns'] = new Campaigns;

class Campaigns
{
    var $table = 'campaigns';            ## Classes main table to operate on
    var $orderby = 'id';        ## Default Order field
    var $orderdir = 'DESC';    ## Default order direction
    ## Page  Configuration
    var $pagesize = 20;    ## Adjusts how many items will appear on each page
    var $index = 0;        ## You dont really want to mess with this variable. Index is adjusted by code, to change the pages
    var $index_name = 'cmpgn_list';    ## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
    var $frm_name = 'cpgnnextfrm';
    var $order_prepend = 'cpgn_';                ## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

    function __construct()
    {
        require_once("classes/cmpgn_parents.inc.php");
    }

    function Campaigns()
    {
        ## REQURES DB CONNECTION!
        $this->handlePOST();
    }

    function makeDD($name, $sel, $class, $onchange, $size, $blank_entry = 1, $extra_where = NULL)
    {
        $names = 'name';    ## or Array('field1','field2')
        $value = 'id';
        $seperator = '';        ## If $names == Array, this will be the seperator between fields
        $fieldstring = '';
        if (is_array($names)) {
            $x = 0;
            foreach ($names as $name) {
                $fieldstring .= $name . ',';
            }
        } else {
            $fieldstring .= $names . ',';
        }
        $fieldstring .= $value;
        $sql = "SELECT $fieldstring FROM " . $this->table . " WHERE status='active' " . (($extra_where != NULL) ? $extra_where : '');
        $DD = new genericDD($sql, $names, $value, $seperator);
        return $DD->makeDD($name, $sel, $class, $blank_entry, $onchange, $size);
    }

    function makeDDByCode($name, $sel, $class, $onchange, $size, $blank_entry = 1, $extra_where = NULL)
    {
        $names = 'vici_campaign_id';    ## or Array('field1','field2')
        $value = 'id';
        $seperator = '';        ## If $names == Array, this will be the seperator between fields
        $fieldstring = '';
        if (is_array($names)) {
            $x = 0;
            foreach ($names as $name) {
                $fieldstring .= $name . ',';
            }
        } else {
            $fieldstring .= $names . ',';
        }
        $fieldstring .= $value;
        $sql = "SELECT $fieldstring FROM " . $this->table . " WHERE status='active' " . (($extra_where != NULL) ? $extra_where : '');
        $DD = new genericDD($sql, $names, $value, $seperator);
        return $DD->makeDD($name, $sel, $class, $blank_entry, $onchange, $size);
    }

    function handlePOST()
    {

        // THIS SHIT IS MOTHERFUCKIGN AJAXED TO THE TEETH
        // SEE api/campaigns.api.php FOR POST HANDLING!
        // <3 <3 -Jon

    }

    function handleFLOW()
    {
        # Handle flow, based on query string

        if (!checkAccess('campaigns')) {

            accessDenied("Campaigns");

            return;

        } else {
            if (isset($_REQUEST['add_campaign'])) {

                $this->makeAdd($_REQUEST['add_campaign']);

            } else {
                $this->listEntrys();
            }
        }
    }

    function listEntrys()
    {

        ?>
        <script>
            var campaign_delmsg = 'Are you sure you want to delete this campaign?';
            var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
            var <?=$this->order_prepend?>orderdir = "<?=$this->orderdir?>";
            var <?=$this->index_name?> = 0;
            var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;
            var CampaignsTableFormat = [
                ['id', 'text-center'],
                ['name', 'text-left'],
                ['status', 'text-center'],
                ['[delete]', 'text-center']
            ];

            /**
             * Build the URL for AJAX to hit, to build the list
             */
            function getCampaignsURL() {
                var frm = getEl('<?=$this->frm_name?>');
                return 'api/api.php' +
                    "?get=campaigns&" +
                    "mode=xml&" +
                    "index=" + (<?=$this->index_name?> * <?=$this->order_prepend?>pagesize) + "&pagesize=" + <?=$this->order_prepend?>pagesize + "&" +
                    "orderby=" + <?=$this->order_prepend?>orderby + "&orderdir=" + <?=$this->order_prepend?>orderdir;
            }

            var campaigns_loading_flag = false;

            /**
             * Load the campaign data - make the ajax call, callback to the parse function
             */
            function loadCampaigns() {
                // ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
                var val = null;
                eval('val = campaigns_loading_flag');
                // CHECK IF WE ARE ALREADY LOADING THIS DATA
                if (val == true) {
                    //console.log("CAMPAIGNS ALREADY LOADING (BYPASSED) \n");
                    return;
                } else {
                    eval('campaigns_loading_flag = true');
                }
                loadAjaxData(getCampaignsURL(), 'parseCampaigns');
            }

            /**
             * CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
             */
            var <?=$this->order_prepend?>totalcount = 0;

            function parseCampaigns(xmldoc) {
                <?=$this->order_prepend?>totalcount = parseXMLData('campaign', CampaignsTableFormat, xmldoc);
                // ACTIVATE PAGE SYSTEM!
                if (<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize) {
                    makePageSystem('campaigns',
                        '<?=$this->index_name?>',
                        <?=$this->order_prepend?>totalcount,
                        <?=$this->index_name?>,
                        <?=$this->order_prepend?>pagesize,
                        'loadCampaigns()'
                    );
                } else {
                    hidePageSystem('campaigns');
                }
                eval('campaigns_loading_flag = false');
            }

            function handleCampaignListClick(id) {
                displayAddCampaignDialog(id);
            }

            function displayAddCampaignDialog(campaignid) {
                var objname = 'dialog-modal-add-campaign';
                if (campaignid > 0) {
                    $('#' + objname).dialog("option", "title", 'Editing Campaign');
                } else {
                    $('#' + objname).dialog("option", "title", 'Adding new Campaign');
                }
                $('#' + objname).dialog("open");
                $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                $('#' + objname).load("index.php?area=campaigns&add_campaign=" + campaignid + "&printable=1&no_script=1");
            }

            function resetCampaignForm(frm) {
                frm.s_id.value = '';
                frm.s_name.value = '';
                frm.s_status.value = 'active';
            }
            $("#dialog-modal-add-campaign").dialog({
                autoOpen: false,
                width: 480,
                height: 320,
                modal: false,
                draggable: true,
                resizable: false,
                position: 'center'
            });
        </script>
        <div id="dialog-modal-add-campaign" title="Adding new Campaign" class="nod"></div>
        <div class="block">
            <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>" onsubmit="loadCampaigns();return false">
                <div class="block-header bg-primary-light">
                    <h4 class="block-title">Campaigns</h4>
                    <button type="button" value="Add" title="Add Campaign" class="btn btn-sm btn-primary" onclick="displayAddCampaignDialog(0)">Add</button>
                        <div id="campaigns_prev_td" class="page_system_prev"></div>
                        <div id="campaigns_page_td" class="page_system_page"></div>
                        <div id="campaigns_next_td" class="page_system_next"></div>
                </div>
                <div class="block-content">
                    <table class="table table-sm" id="campaign_table">
                        <tr>
                            <th class="row2 text-center"><?= $this->getOrderLink('id') ?>ID</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('name') ?>Name</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('status') ?>Status</a></th>
                            <th class="row2">&nbsp;</th>
                        </tr>
                    </table>
                </div>
            </form>
        </div>
        <script>
            loadCampaigns();
        </script>
        <?

    }

    function makeAdd($id)
    {
        $id = intval($id);
        if ($id) {
            $row = $_SESSION['dbapi']->campaigns->getByID($id);
        }
        ?>
        <script>
            function validateCampaignField(name, value, frm) {
                //alert(name+","+value);
                switch (name) {
                    default:
                        // ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
                        return true;
                        break;
                    case 'vici_campaign_id':
                    case 'name':
                        if (!value) return false;
                        return true;
                        break;
                }
                return true;
            }

            function checkCampaignFrm(frm) {
                var params = getFormValues(frm, 'validateCampaignField');
                // FORM VALIDATION FAILED!
                // param[0] == field name
                // param[1] == field value
                if (typeof params == "object") {
                    switch (params[0]) {
                        default:
                            alert("Error submitting form. Check your values");
                            break;
                        case 'vici_campaign_id':
                            alert("Please enter the exact campaign ID field from vici\nExample: BCRSFC");
                            eval('try{frm.' + params[0] + '.select();}catch(e){}');
                            break;
                        case 'name':
                            alert("Please enter a name for this campaign.");
                            eval('try{frm.' + params[0] + '.select();}catch(e){}');
                            break;
                    }
                    // SUCCESS - POST AJAX TO SERVER
                } else {
                    //alert("Form validated, posting");
                    $.ajax({
                        type: "POST",
                        cache: false,
                        url: 'api/api.php?get=campaigns&mode=xml&action=edit',
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
                            loadCampaigns();
                            displayAddCampaignDialog(res);
                            alert(result['message']);
                        }
                    });
                }
                return false;
            }

            // SET TITLEBAR
            $('#dialog-modal-add-campaign').dialog("option", "title", '<?=($id) ? 'Editing Campaign #' . $id . ' - ' . htmlentities($row['name']) : 'Adding new Campaign'?>');
        </script>
        <form method="POST" action="<?= stripurl('') ?>" autocomplete="off"
              onsubmit="checkCampaignFrm(this); return false">
            <input type="hidden" id="adding_campaign" name="adding_campaign" value="<?= $id ?>">
            <table border="0" align="center">
                <tr>
                    <th align="left" height="30">Name</th>
                    <td><input name="name" type="text" size="50" value="<?= htmlentities($row['name']) ?>"></td>
                </tr>
                <tr>
                    <th align="left" height="30">Parent Campaign:</th>
                    <td>
                        <? echo $_SESSION['cmpgn_parents']->makeDDvalIDtxtCODE($row['parent_campaign_id']); ?>
                    </td>
                </tr>
                <tr>
                    <th align="left" height="30">Entity Type:</th>
                    <td><select name="type">
                            <option value="charity">Charity</option>
                            <option value="pac" <?= ($row['type'] == 'pac') ? ' SELECTED ' : '' ?>>PAC</option>
                            <option value="verifier" <?= ($row['type'] == 'verifier') ? ' SELECTED ' : '' ?>>Verifier</option>
                            <option value="other" <?= ($row['type'] == 'other') ? ' SELECTED ' : '' ?>>Other</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th align="left" height="30">Status</th>
                    <td>
                        <select name="status">
                            <option value="active">Active</option>
                            <option value="suspended" <?= ($row['status'] == 'suspended') ? ' SELECTED ' : '' ?>>Suspended</option>
                            <option value="deleted" <?= ($row['status'] == 'deleted') ? ' SELECTED ' : '' ?>>Deleted</option>
                        </select>
                        &nbsp;&nbsp;PX Hidden (<a href="#" onclick="alert('PX Hidden will remove the campaign from the PX login screen dropdown, but still appear in other places of the PX GUI.');return false">help?</a>):&nbsp;&nbsp;
                        <select name="px_hidden">
                            <option value="no">No</option>
                            <option value="yes" <?= ($row['px_hidden'] == 'yes') ? ' SELECTED ' : '' ?>>Yes</option>
                        </select>

                    </td>
                </tr>
                <tr>
                    <th align="left" height="30">Vici Campaign ID</th>
                    <td><input name="vici_campaign_id" type="text" size="50"
                               value="<?= htmlentities($row['vici_campaign_id']) ?>"></td>
                </tr>
                <tr>
                    <th align="left" height="30">Manager Transfer:</th>
                    <td><select name="manager_transfer">
                            <option value="no">Disabled</option>
                            <option value="yes" <?= ($row['manager_transfer'] == 'yes') ? ' SELECTED ' : '' ?>>Enabled
                            </option>
                        </select></td>
                </tr>
                <tr>
                    <th align="left" height="30">Warm Transfers:</th>
                    <td><select name="warm_transfers">
                            <option value="no">Disabled</option>
                            <option value="yes" <?= ($row['warm_transfers'] == 'yes') ? ' SELECTED ' : '' ?>>Enabled
                            </option>
                        </select></td>
                </tr>
                <tr>
                    <th align="left" height="30">Variables</th>
                    <td><input name="variables" type="text" size="50" value="<?= htmlentities($row['variables']) ?>">
                    </td>
                </tr>
                <tr>

                    <th colspan="2" align="center"><input type="submit" value="Save Changes"></th>
                </tr>
        </form>
        </table><?

    }

    function getOrderLink($field)
    {
        $var = '<a href="#" onclick="setOrder(\'' . addslashes($this->order_prepend) . '\',\'' . addslashes($field) . '\',';
        $var .= "((" . $this->order_prepend . "orderdir == 'DESC')?'ASC':'DESC')";
        $var .= ");loadCampaigns();return false;\">";
        return $var;
    }
}