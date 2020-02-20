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
            $(function () {
                function resetCampaignForm(frm) {
                    frm.s_id.value = '';
                    frm.s_name.value = '';
                    frm.s_status.value = 'active';
                }
                $('#addCampaignButton').on('click', function () {
                    let $dlgObj = $('#dialog-modal-edit-campaign');
                    $dlgObj.dialog('open');
                    $dlgObj.dialog('option', 'title','Adding Campaign');
                    $.ajax({
                        type: 'POST',
                        dataType: 'json',
                        url: 'api/api.php?get=campaigns&mode=json&action=getRowByID&campaign_id=0',
                        success: function (data) {
                            $('#adding_campaign').val(0);
                            $('#cmp_name').val('');
                            $('#ent_type').val('');
                            $('#cmp_status').val('');
                            $('#px_hidden').val('');
                            $('#vcmp_id').val('');
                            $('#mgr_trf').val('');
                            $('#wrm_trf').val('');
                            $('#cmp_vars').val('');
                            $('#prt_cmp_dd').html(data.parent_dd);
                        }
                    });
                });
                $("#dialog-modal-edit-campaign").dialog({
                    autoOpen: false,
                    width: 480,
                    height: 'auto',
                    modal: false,
                    draggable: true,
                    resizable: false,
                    position: {my: 'center', at: 'center'},
                    buttons: {
                        'Submit': function (e) {
                            e.preventDefault();
                            let frm = $(this).find('form')[0];
                            let params = getFormValues(frm, 'validateCampaignField');
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
                            } else {
                                $.ajax({
                                    type: "POST",
                                    cache: false,
                                    url: 'api/api.php?get=campaigns&mode=xml&action=edit',
                                    data: params,
                                    error: function (response) {
                                        alert('Campaign did not save');
                                    },
                                    success: function (msg) {
                                        var result = handleEditXML(msg);
                                        var res = result['result'];
                                        if (res <= 0) {
                                            alert(result['message']);
                                            return;
                                        }
                                        loadCampaigns();
                                        alert(result['message']);
                                    }
                                });
                                $(this).dialog('close');
                            }
                        },
                        'Reset': function (e) {
                            resetCampaignForm(frm);
                        }
                    }
                });

                $("#dialog-modal-edit-campaign").closest('.ui-dialog').draggable("option","containment","#main-container");
                
            });

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
                var <?=$this->order_prepend?>pagesize = $('#<?=$this->order_prepend?>pagesizeDD').val();
                return 'api/api.php' +
                    "?get=campaigns&" +
                    "mode=xml&" +
                    "index=" + (<?=$this->index_name?> * <?=$this->order_prepend?>pagesize
            )
                +"&pagesize=" + <?=$this->order_prepend?>pagesize + "&" +
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

            function validateCampaignField(name, value) {
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
            function handleCampaignListClick(id) {
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: 'api/api.php?get=campaigns&mode=json&action=getRowByID&campaign_id=' + id,
                    success: function (data) {
                        let $dlgObj = $('#dialog-modal-edit-campaign');
                        $dlgObj.dialog('open');
                        $('#adding_campaign').val(id);
                        $('#cmp_name').val(data.name);
                        $('#ent_type').val(data.type);
                        $('#cmp_status').val(data.status);
                        $('#px_hidden').val(data.px_hidden);
                        $('#vcmp_id').val(data.vici_campaign_id);
                        $('#mgr_trf').val(data.manager_transfer);
                        $('#wrm_trf').val(data.warm_transfers);
                        $('#cmp_vars').val(data.variables);
                        $('#prt_cmp_dd').html(data.parent_dd);
                    },
                    error: function () {
                        alert('Unable to get data for campaign id :: ' + id);
                    }
                });
            }
            loadCampaigns();
        </script>
        <!-- ****START**** THIS AREA REPLACES THE OLD TABLES WITH THE NEW ONEUI INTERFACE BASED ON BOOTSTRAP -->
        <div class="block">
            <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>" onsubmit="loadCampaigns();return false">
                <div class="block-header bg-primary-light">
                    <h4 class="block-title">Campaigns</h4>
                    <button type="button" value="Add" title="Add Campaign" class="btn btn-sm btn-primary" id="addCampaignButton">Add</button>
                    <div id="campaigns_prev_td" class="page_system_prev"></div>
                    <div id="campaigns_page_td" class="page_system_page"></div>
                    <div id="campaigns_next_td" class="page_system_next"></div>
                    <select title="Rows Per Page" class="custom-select-sm" name="<?=$this->order_prepend?>pagesize" id="<?=$this->order_prepend?>pagesizeDD" onchange="<?=$this->index_name?>=0;loadCampaigns(); return false;">
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
                <div class="block-content block-content-full">
                    <div id="DataTables_Campaign_Table_wrapper" class="dataTables_wrapper dt-bootstrap4 no-footer">
                        <div class="row">
                            <div class="col-12">
                                <table class="table table-sm table-striped" id="campaign_table">
                                    <caption id="current_time_span" class="small text-right">Server Time: <?=date("g:ia m/d/Y T")?></caption>
                                    <tr>
                                        <th class="row2 text-center"><?= $this->getOrderLink('id') ?>ID</a></th>
                                        <th class="row2 text-left"><?= $this->getOrderLink('name') ?>Name</a></th>
                                        <th class="row2 text-center"><?= $this->getOrderLink('status') ?>Status</a></th>
                                        <th class="row2">&nbsp;</th>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <!-- ****END**** THIS AREA REPLACES THE OLD TABLES WITH THE NEW ONEUI INTERFACE BASED ON BOOTSTRAP -->
        <div id="dialog-modal-edit-campaign" title="Editing Campaign" class="nod">
            <div class="block-content block-content-full text-left">
                <form method="POST" action="#" autocomplete="off" name="edit-campaign">
                    <input type="hidden" id="adding_campaign" name="adding_campaign" value="" />
                    <div class="form-row">
                        <div class="col">
                            <label class="col-form-label" for="name">Campaign Name</label>
                        </div>
                        <div class="col">
                            <input id="cmp_name" class="form-control" name="name" type="text" size="50" placeholder="Campaign name..." value="">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col">
                            <label class="col-form-label" for="name">Parent Campaign</label>
                        </div>
                        <div class="col">
                            <div id="prt_cmp_dd"></div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col">
                            <label class="col-form-label" for="name">Entity Type</label>
                        </div>
                        <div class="col">
                            <select class="form-control" id="ent_type" name="type">
                                <option value="charity">Charity</option>
                                <option value="pac">PAC</option>
                                <option value="verifier">Verifier</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col">
                            <label class="col-form-label" for="status">Status</label>
                        </div>
                        <div class="col">
                            <select id="cmp_status" name="status">
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                                <option value="deleted">Deleted</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col">
                            <label class="col-form-label" for="px_hidden">PX Hidden</label>
                        </div>
                        <div class="col">
                            <select id="px_hidden" name="px_hidden" tooltip="PX Hidden will remove the campaign from the PX login screen dropdown, but still appear in other places of the PX GUI.">
                                <option value="no">No</option>
                                <option value="yes">Yes</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col">
                            <label class="col-form-label" for="vici_campaign_id">VICI Campaign ID</label>
                        </div>
                        <div class="col">
                            <input id="vcmp_id" name="vici_campaign_id" type="text" class="form-control" size="50" value="" placeholder="VICI Campaign ID...">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col">
                            <label class="col-form-label" for="manager_transfer">Manager Transfer</label>
                        </div>
                        <div class="col">
                            <select id="mgr_trf" name="manager_transfer">
                                <option value="no">Disabled</option>
                                <option value="yes">Enabled</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col">
                            <label class="col-form-label" for="warm_transfers">Warm Transfers</label>
                        </div>
                        <div class="col">
                            <select id="wrm_trf" name="warm_transfers">
                                <option value="no">Disabled</option>
                                <option value="yes">Enabled</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col">
                            <label for="variables">Variables</label>
                        </div>
                        <div class="col">
                            <input id="cmp_vars" name="variables" type="text" class="form-control" size="50" value="" placeholder="Variables...">
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?

    }

    function getOrderLink($field)
    {
        $var = '<a href="#" onclick="setOrder(\'' . addslashes($this->order_prepend) . '\',\'' . addslashes($field) . '\',';
        $var .= "((" . $this->order_prepend . "orderdir == 'DESC')?'ASC':'DESC')";
        $var .= ");loadCampaigns();return false;\">";
        return $var;
    }
}