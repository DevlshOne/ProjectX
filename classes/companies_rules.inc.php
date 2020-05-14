<?php
/***************************************************************
 *    Company Rules - Handles company rules for additional hours
 *    Written By: Dave Mednick
 ***************************************************************/

$_SESSION['companies_rules'] = new CompaniesRules;


class CompaniesRules
{

    var $table = 'companies_rules';            ## Classes main table to operate on
    var $orderby = 'id';        ## Default Order field
    var $orderdir = 'DESC';    ## Default order direction
    ## Page  Configuration
    var $pagesize = 20;    ## Adjusts how many items will appear on each page
    var $index = 0;        ## You dont really want to mess with this variable. Index is adjusted by code, to change the pages
    var $index_name = 'companiesrules_list';    ## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
    var $frm_name = 'companiesrulesnextfrm';
    var $order_prepend = 'companiesrules_';                ## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

    function CompaniesRules()
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
        if (!checkAccess('companiesrules')) {
            accessDenied("CompaniesRules");
            return;
        } else {
            if (isset($_REQUEST['add_rule'])) {
                $this->makeAdd($_REQUEST['add_rule']);
            } else {
                $this->listEntrys();
            }
        }
    }

    function listEntrys()
    {
        ?>
        <script>
            var companiesrules_delmsg = 'Are you sure you want to delete this rule?';
            var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
            var <?=$this->order_prepend?>orderdir = "<?=$this->orderdir?>";
            var <?=$this->index_name?> = 0;
            var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;
            var CompaniesrulesTableFormat = [
                ['id', 'text-left'],
                ['[get:company_name:company_id]', 'text-left'],
                ['rule_type', 'text-left'],
                ['trigger_name', 'text-left'],
                ['trigger_value', 'text-left'],
                ['action', 'text-left'],
                ['action_value', 'text-left'],
                ['[delete]', 'text-center']
            ];

            /**
             * Build the URL for AJAX to hit, to build the list
             */
            function getCompaniesRulesURL() {

                var frm = getEl('<?=$this->frm_name?>');
                var <?=$this->order_prepend?>pagesize = $('#<?=$this->order_prepend?>pagesizeDD').val();
                return 'api/api.php' +
                    "?get=companiesrules&" +
                    "mode=xml&" +
                    's_id=' + escape(frm.s_id.value) + "&" +
                    "index=" + (<?=$this->index_name?> * <?=$this->order_prepend?>pagesize
            )
                +"&pagesize=" + <?=$this->order_prepend?>pagesize + "&" +
                "orderby=" + <?=$this->order_prepend?>orderby + "&orderdir=" + <?=$this->order_prepend?>orderdir;
            }


            var companiesrules_loading_flag = false;
            /**
             * Load the companies rules data - make the ajax call, callback to the parse function
             */
            function loadCompaniesRules() {
                // ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
                var val = null;
                eval('val = companiesrules_loading_flag');
                // CHECK IF WE ARE ALREADY LOADING THIS DATA
                if (val == true) {
                    return;
                } else {
                    eval('companiesrules_loading_flag = true');
                }
                <?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());
                loadAjaxData(getCompaniesRulesURL(), 'parseCompaniesRules');
            }

            /**
             * CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
             */
            var <?=$this->order_prepend?>totalcount = 0;
            function parseCompaniesRules(xmldoc) {
                <?=$this->order_prepend?>totalcount = parseXMLData('companiesrule', CompaniesrulesTableFormat, xmldoc);
                // ACTIVATE PAGE SYSTEM!
                if (<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize) {
                    makePageSystem('companiesrules',
                        '<?=$this->index_name?>',
                        <?=$this->order_prepend?>totalcount,
                        <?=$this->index_name?>,
                        <?=$this->order_prepend?>pagesize,
                        'loadCompaniesRules()'
                    );
                } else {
                    hidePageSystem('companiesrules');
                }
                eval('companiesrules_loading_flag = false');
            }


            function handleCompaniesruleListClick(id) {
                displayAddRuleDialog(id);
            }

            function displayAddRuleDialog(id) {
                var objname = 'dialog-modal-add-rule';
                if (id > 0) {
                    $('#' + objname).dialog("option", "title", 'Editing rule # ' + id + ' ');
                } else {
                    $('#' + objname).dialog("option", "title", 'Adding new rule');
                }
                $('#' + objname).dialog("open");
                $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                $('#' + objname).load("index.php?area=employee_hours&sub_area=config&add_rule=" + id + "&printable=1&no_script=1");
            }

            function resetCompaniesRulesForm(frm) {
                frm.s_id.value = '';
            }

            var companiesrulessrchtog = true;
            function toggleCompaniesRulesSearch() {
                companiesrulessrchtog = !companiesrulessrchtog;
                ieDisplay('companiesrules_search_table', companiesrulessrchtog);
            }
        </script>
        <! *** BEGIN ONEUI STYLING REWORK -->
        <div class="block">
            <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>" onsubmit="loadCompaniesRules();return false;">
                <! ** BEGIN BLOCK HEADER -->
                <div class="block-header bg-primary-light">
                    <h4 class="block-title">Companies Additional Hours Rules</h4>
                    <button type="button" value="Add" title="Add Rules" class="btn btn-sm btn-primary" onclick="displayAddRuleDialog(0)">Add</button>
                    <button type="button" value="Search" title="Toggle Search" class="btn btn-sm btn-primary" onclick="toggleCompaniesRulesSearch();">Toggle Search</button>
                    <div id="companiesrules_prev_td" class="page_system_prev"></div>
                    <div id="companiesrules_page_td" class="page_system_page"></div>
                    <div id="companiesrules_next_td" class="page_system_next"></div>
                    <select title="Rows Per Page" class="custom-select-sm" name="<?=$this->order_prepend?>pagesize" id="<?=$this->order_prepend?>pagesizeDD" onchange="<?=$this->index_name?>=0;loadCompaniesRules(); return false;">
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
                <div class="bg-info-light" id="companiesrules_search_table">
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="searching_companiesrules"/>
                        <input type="text" class="form-control" placeholder="Rule ID.." name="s_id" value="<?= htmlentities($_REQUEST['s_id']) ?>"/>
                        <button type="submit" value="Search" title="Search Rules" class="btn btn-sm btn-primary" name="the_Search_button" onclick="loadCompaniesRules();return false;">Search</button>
                        <button type="button" value="Reset" title="Reset Search Criteria" class="btn btn-sm btn-primary" onclick="resetCompaniesRulesForm(this.form);resetPageSystem('<?= $this->index_name ?>');loadCompaniesRules();return false;">Reset</button>
                    </div>
                </div>
                <! ** END BLOCK SEARCH TABLE -->
                <! ** BEGIN BLOCK LIST (DATATABLE) -->
                <div class="block-content">
                    <table class="table table-sm table-striped" id="companiesrule_table">
                        <caption id="current_time_span" class="small text-right">Server Time: <?=date("g:ia m/d/Y T")?></caption>
                        <tr>
                            <th class="row2 text-left"><?= $this->getOrderLink('id') ?>ID</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('company_id') ?>Company</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('rule_type') ?>Rule Type</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('trigger_name') ?>Trigger</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('trigger_value') ?>Trigger Value</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('action') ?>Action</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('action_value') ?>Action Value</a></th>
                            <th class="row2 text-center">&nbsp;</th>
                        </tr>
                    </table>
                </div>
                <! ** END BLOCK LIST (DATATABLE) -->
            </form>
        </div>
        <! *** END ONEUI STYLING REWORK -->
        <div id="dialog-modal-add-rule" title="Adding new rule" class="nod"></div>
        <script>
            $("#dialog-modal-add-rule").dialog({
                autoOpen: false,
                width: 'auto',
                modal: false,
                draggable: true,
                resizable: false,
                position: {my: 'center', at: 'center'},
                containment: '#main-container'
            });
            $("#dialog-modal-add-rule").closest('.ui-dialog').draggable("option","containment","#main-container");
            loadCompaniesRules();
        </script>
        <?
    }


    function makeAdd($id)
    {
        $id = intval($id);
        if ($id) {
            $row = $_SESSION['dbapi']->companies_rules->getByID($id);
        }
        ?>
        <script>
            function validateCompaniesRulesField(name, value, frm) {
                //alert(name+","+value);
                switch (name) {
                    default:
                        // ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
                        return true;
                        break;
                    case 'company_id':
                        if (!value) return false;
                        return true;
                        break;
                    case 'trigger_value':
                        if (!value) return false;
                        return true;
                        break;
                    case 'action_value':
                        if (!value) return false;
                        return true;
                        break;
                }
                return true;
            }

            function checkCompaniesRulesFrm(frm) {
                var params = getFormValues(frm, 'validateCompaniesRulesField');
                // FORM VALIDATION FAILED!
                // param[0] == field name
                // param[1] == field value
                if (typeof params == "object") {
                    switch (params[0]) {
                        default:
                           alert("Error submitting form. Check your values");
                            break;
                    }

                    // SUCCESS - POST AJAX TO SERVER
                } else {
                    //alert("Form validated, posting");
                    $.ajax({
                        type: "POST",
                        cache: false,
                        url: 'api/api.php?get=companiesrules&mode=xml&action=edit',
                        data: params,
                        error: function () {
                            alert("Error saving user form. Please contact an admin.");
                        },
                        success: function (msg) {
                        // alert(msg);
                            var result = handleEditXML(msg);
                            var res = result['result'];
                            if (res <= 0) {
                                alert(result['message']);
                                return;
                            }
                            loadCompaniesRules();
                            displayAddRuleDialog(res);
                            alert(result['message']);
                        }
                    });
                }
                return false;
            }
            // SET TITLEBAR
            $('#dialog-modal-add-rule').dialog("option", "title", '<?=($id) ? 'Editing rule # ' . htmlentities($row['id']) : 'Adding new rule'?>');
        </script>
        <form method="POST" action="<?= stripurl('') ?>" autocomplete="off" onsubmit="checkCompaniesRulesFrm(this); return false">
            <input type="hidden" id="adding_rule" name="adding_rule" value="<?= $id ?>">
            <table border="0" align="center">
                <tr>
                    <th align="left" height="30">Company ID:</th>
                    <td><input name="company_id" type="text" size="12" value="<?= htmlentities($row['company_id']) ?>"></td>
                </tr>
                <tr>
                    <th align="left" height="30">Rule Type:</th>
                    <td>
                        <select name="rule_type">
                            <option <?=htmlentities($row['rule_type'] == 'hours' ? 'selected' : '');?> value="hours">Hours</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th align="left" height="30">Trigger Type:</th>
                    <td>
                        <select name="trigger_name">
                            <option <?=htmlentities($row['trigger_name'] == 'greater_than' ? 'selected' : '');?> value="greater_than">&gt;</option>
                            <option <?=htmlentities($row['trigger_name'] == 'greater_equal' ? 'selected' : '');?> value="greater_equal">&#8925;</option>
                            <option <?=htmlentities($row['trigger_name'] == 'no_paid_breaks' ? 'selected' : '');?> value="no_paid_breaks">No Paid Breaks</option>
                        </select>
                </tr>
                <tr>
                    <th align="left" height="30">Trigger Value:</th>
                    <td><input name="trigger_value" type="number" min="0.00" max="40.00" step="0.01" value="<?= htmlentities($row['trigger_value']) ?>"></td>
                </tr>
                <tr>
                    <th align="left" height="30">Action Type:</th>
                    <td>
                        <select name="action_type">
                            <option <?=htmlentities($row['action'] == 'paid_lunch' ? 'selected' : '');?> value="paid_lunch">Paid Lunch</option>
                            <option <?=htmlentities($row['action'] == 'paid_break' ? 'selected' : '');?> value="paid_break">Paid Break</option>
                            <option <?=htmlentities($row['action'] == '' ? 'selected' : '');?> value="">None</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th align="left" height="30">Action Value:</th>
                    <td><input name="action_value" type="number" min="0.00" max="40.00" step="0.01" value="<?= htmlentities($row['action_value']) ?>"></td>
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
        $var .= "((" . $this->order_prepend . "orderdir == 'DESC')?'ASC':'DESC')";
        $var .= ");loadCompaniesRules();return false;\">";
        return $var;
    }
}
