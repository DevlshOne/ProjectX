<?php
/***************************************************************
 *    Company Rules - Handles company rules for additional hours
 *    Written By: Dave Mednick
 ***************************************************************/

$_SESSION['co_rules'] = new CompanyRules;


class Names
{

    var $table = 'companies_rules';            ## Classes main table to operate on
    var $orderby = 'company_id';        ## Default Order field
    var $orderdir = 'DESC';    ## Default order direction
    ## Page  Configuration
    var $pagesize = 20;    ## Adjusts how many items will appear on each page
    var $index = 0;        ## You dont really want to mess with this variable. Index is adjusted by code, to change the pages
    var $index_name = 'corules_list';    ## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
    var $frm_name = 'corulesnextfrm';
    var $order_prepend = 'corules_';                ## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

    function CoRules()
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
        if (!checkAccess('corules')) {
            accessDenied("CoRules");
            return;
        } else {
            if (isset($_REQUEST['add_name'])) {
                $this->makeAdd($_REQUEST['add_name']);
            } else {
                $this->listEntrys();
            }
        }
    }

    function listEntrys()
    {
        ?>
        <script>
            var corules_delmsg = 'Are you sure you want to delete this rule?';
            var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
            var <?=$this->order_prepend?>orderdir = "<?=$this->orderdir?>";
            var <?=$this->index_name?> = 0;
            var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;
            var CorulesTableFormat = [
                ['name', 'align_left'],
                ['[get:voice_name:voice_id]', 'align_center'],
                ['filename', 'align_center'],
                ['[delete]', 'align_center']
            ];

            /**
             * Build the URL for AJAX to hit, to build the list
             */
            function getCoRulesURL() {

                var frm = getEl('<?=$this->frm_name?>');
                var <?=$this->order_prepend?>pagesize = $('#<?=$this->order_prepend?>pagesizeDD').val();
                return 'api/api.php' +
                    "?get=corules&" +
                    "mode=xml&" +
                    's_id=' + escape(frm.s_id.value) + "&" +
                    's_name=' + escape(frm.s_name.value) + "&" +
                    's_filename=' + escape(frm.s_filename.value) + "&" +
                    "index=" + (<?=$this->index_name?> * <?=$this->order_prepend?>pagesize
            )
                +"&pagesize=" + <?=$this->order_prepend?>pagesize + "&" +
                "orderby=" + <?=$this->order_prepend?>orderby + "&orderdir=" + <?=$this->order_prepend?>orderdir;
            }


            var corules_loading_flag = false;

            /**
             * Load the copany rules data - make the ajax call, callback to the parse function
             */
            function loadCoRules() {
                // ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
                var val = null;
                eval('val = corules_loading_flag');


                // CHECK IF WE ARE ALREADY LOADING THIS DATA
                if (val == true) {
                    return;
                } else {
                    eval('corules_loading_flag = true');
                }

                <?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());

                loadAjaxData(getCoRulesURL(), 'parseCoRules');

            }


            /**
             * CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
             */
            var <?=$this->order_prepend?>totalcount = 0;
            function parseNames(xmldoc) {
                <?=$this->order_prepend?>totalcount = parseXMLData('rule', CorulesTableFormat, xmldoc);
                // ACTIVATE PAGE SYSTEM!
                if (<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize) {
                    makePageSystem('corules',
                        '<?=$this->index_name?>',
                        <?=$this->order_prepend?>totalcount,
                        <?=$this->index_name?>,
                        <?=$this->order_prepend?>pagesize,
                        'loadNames()'
                    );
                } else {
                    hidePageSystem('corules');
                }
                eval('corules_loading_flag = false');
            }


            function handleRuleListClick(id) {
                displayAddRuleDialog(id);
            }

            function displayAddRuleDialog(id) {
                var objname = 'dialog-modal-add-rule';
                if (id > 0) {
                    $('#' + objname).dialog("option", "title", 'Editing rule');
                } else {
                    $('#' + objname).dialog("option", "title", 'Adding new rule');
                }
                $('#' + objname).dialog("open");
                $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                $('#' + objname).load("index.php?area=corules&add_rule=" + id + "&printable=1&no_script=1");
            }

            function resetRuleForm(frm) {
                frm.s_id.value = '';
                frm.s_type.value = '';
                frm.s_.value = '';
            }

            var rulesrchtog = true;
            function toggleRuleSearch() {
                namesrchtog = !namesrchtog;
                ieDisplay('name_search_table', namesrchtog);
            }
        </script>
        <! *** BEGIN ONEUI STYLING REWORK -->
        <div class="block">
            <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>" onsubmit="loadCoRules();return false;">
                <! ** BEGIN BLOCK HEADER -->
                <div class="block-header bg-primary-light">
                    <h4 class="block-title">Company Rules</h4>
                    <button type="button" value="Add" title="Add Rules" class="btn btn-sm btn-primary" onclick="displayAddRuleDialog(0)">Add</button>
                    <button type="button" value="Search" title="Toggle Search" class="btn btn-sm btn-primary" onclick="toggleRuleSearch();">Toggle Search</button>
                    <div id="corules_prev_td" class="page_system_prev"></div>
                    <div id="corules_page_td" class="page_system_page"></div>
                    <div id="corules_next_td" class="page_system_next"></div>
                    <select title="Rows Per Page" class="custom-select-sm" name="<?=$this->order_prepend?>pagesize" id="<?=$this->order_prepend?>pagesizeDD" onchange="<?=$this->index_name?>=0;loadCoRules(); return false;">
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
                <div class="bg-info-light" id="corules_search_table">
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="searching_rule"/>
                        <input type="text" class="form-control" placeholder="Company ID.." name="s_id" value="<?= htmlentities($_REQUEST['s_id']) ?>"/>
                        <input type="text" class="form-control" placeholder="Name.." name="s_name" value="<?= htmlentities($_REQUEST['s_name']) ?>"/>
                        <input type="text" class="form-control" placeholder="Filename.." name="s_filename" value="<?= htmlentities($_REQUEST['s_filename']) ?>"/>
                        <button type="submit" value="Search" title="Search Rules" class="btn btn-sm btn-primary" name="the_Search_button" onclick="loadCoRules();return false;">Search</button>
                        <button type="button" value="Reset" title="Reset Search Criteria" class="btn btn-sm btn-primary" onclick="resetRuleForm(this.form);resetPageSystem('<?= $this->index_name ?>');loadCoRules();return false;">Reset</button>
                    </div>
                </div>
                <! ** END BLOCK SEARCH TABLE -->
                <! ** BEGIN BLOCK LIST (DATATABLE) -->
                <div class="block-content">
                    <table class="table table-sm table-striped" id="corules_table">
                        <caption id="current_time_span" class="small text-right">Server Time: <?=date("g:ia m/d/Y T")?></caption>
                        <tr>
                            <th class="row2 text-left"><?= $this->getOrderLink('name') ?>Name</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('voice_id') ?>Voice</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('filename') ?>Filename</a></th>
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
            loadCoRules();
        </script>
        <?
    }


    function makeAdd($id)
    {

        $id = intval($id);


        if ($id) {

            $row = $_SESSION['dbapi']->names->getByID($id);


        }

        ?>
        <script>

            function validateNameField(name, value, frm) {

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


            function checkNameFrm(frm) {


                var params = getFormValues(frm, 'validateNameField');


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
                        url: 'api/api.php?get=names&mode=xml&action=edit',
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


                            loadNames();


                            displayAddNameDialog(res);

                            alert(result['message']);

                        }


                    });

                }

                return false;

            }


            // SET TITLEBAR
            $('#dialog-modal-add-name').dialog("option", "title", '<?=($id) ? 'Editing Name #' . $id . ' - ' . htmlentities($row['name']) : 'Adding new Name'?>');


        </script>
        <form method="POST" action="<?= stripurl('') ?>" autocomplete="off" onsubmit="checkNameFrm(this); return false">
            <input type="hidden" id="adding_name" name="adding_name" value="<?= $id ?>">


            <table border="0" align="center">
                <tr>
                    <th align="left" height="30">Name:</th>
                    <td><input name="name" type="text" size="50" value="<?= htmlentities($row['name']) ?>"></td>
                </tr>
                <tr>
                    <th align="left" height="30">Filename:</th>
                    <td><input name="filename" type="text" size="50" value="<?= htmlentities($row['filename']) ?>"></td>
                </tr>
                <tr>
                    <th align="left" height="30">Voice:</th>
                    <td><?= makeVoiceDD(0, 'voice_id', $row['voice_id']) ?></td>
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

        $var .= ");loadNames();return false;\">";

        return $var;
    }
}
