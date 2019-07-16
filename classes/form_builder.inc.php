<? /***************************************************************
 *    Names - Handles list/search/import names
 *    Written By: Jonathan Will
 ***************************************************************/

    $_SESSION['form_builder'] = new FormBuilder;

    /* @TODO:
     * - list campaigns / # of screens for selection
     * - generate droppable area
     * - draggable / sortable fields
     * - database dump on save
     *
     */
    class FormBuilder
    {

        var $table = 'custom_fields';            ## Classes main table to operate on
        var $orderby = 'campaign_id';        ## Default Order field
        var $orderdir = 'ASC';                ## Default order direction

        ## Page  Configuration
        var $pagesize = 20;    ## Adjusts how many items will appear on each page
        var $index = 0;        ## You dont really want to mess with this variable. Index is adjusted by code, to change the pages
        var $index_name = 'forms_list';    ## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
        var $frm_name = 'formnextfrm';
        var $order_prepend = 'form_';                ## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

        function FormBuilder()
        {
            ## REQURES DB CONNECTION!
            $this->handlePOST();
        }

        function handlePOST()
        {
            // THIS SHIT IS MOTHER FUCKING AJAX'D TO THE TEETH
            // SEE api/names.api.php FOR POST HANDLING!
            // <3 <3 -Jon
        }

        function handleFLOW()
        {
            # Handle flow, based on query string
            if (!checkAccess('campaigns')) {
                accessDenied("Campaigns");
                return;
            } else {
                if (isset($_REQUEST['add_form'])) {
                    $this->makeAdd($_REQUEST['add_form']);
                } elseif (isset($_REQUEST['copy_form_builder'])) {
                    $this->makeCopy($_REQUEST['copy_form_builder']);
                } else {
                    $this->listForms();
                }
            }
        }

        function listForms()
        {
            ?>
            <script>
                var form_builder_delmsg = 'Are you sure you want to delete this form?';
                var form_builder_copymsg = 'Copying forms and custom fields';
                var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
                var <?=$this->order_prepend?>orderdir = "<?=$this->orderdir?>";
                var <?=$this->index_name?> = 0;
                var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;
                var FormBuildersTableFormat = [
                    ['[get:campaign_name:campaign_id]', 'align-left'],
                    ['[get:num_screens:campaign_id]', 'align_center'],
                    ['[get:num_fields:campaign_id]', 'align_center'],
                    ['[copy:campaign_id]', 'align_center']
                ];

                /**
                 * Build the URL for AJAX to hit, to build the list
                 */
                function getFormsURL() {
                    let frm = getEl('<?=$this->frm_name?>');
                    return 'api/api.php' +
                        "?get=form_builder&" +
                        "mode=xml&" +
                        "index=" + (<?=$this->index_name?> * <?=$this->order_prepend?>pagesize) +
                        "&pagesize=" + <?=$this->order_prepend?>pagesize + "&" +
                        "orderby=" + <?=$this->order_prepend?>orderby + "&orderdir=" + <?=$this->order_prepend?>orderdir;
                }

                let forms_loading_flag = false;

                /**
                 * Load the name data - make the ajax call, callback to the parse function
                 */
                function loadForm_builders() {
                    // ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
                    var val = null;
                    eval('val = forms_loading_flag');
                    // CHECK IF WE ARE ALREADY LOADING THIS DATA
                    if (val == true) {
                        //console.log("NAMES ALREADY LOADING (BYPASSED) \n");
                        return;
                    } else {
                        eval('forms_loading_flag = true');
                    }
                    <?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());
                    loadAjaxData(getFormsURL(), 'parseFormBuilders');
                }

                /**
                 * CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
                 */
                var <?=$this->order_prepend?>totalcount = 0;
                function parseFormBuilders(xmldoc) {
                    <?=$this->order_prepend?>totalcount = parseXMLData('form_builder', FormBuildersTableFormat, xmldoc);
                    // ACTIVATE PAGE SYSTEM!
                    if (<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize) {
                        makePageSystem('form_builder',
                            '<?=$this->index_name?>',
                            <?=$this->order_prepend?>totalcount,
                            <?=$this->index_name?>,
                            <?=$this->order_prepend?>pagesize,
                            'loadForm_builders()'
                        );
                    } else {
                        hidePageSystem('form_builder');
                    }
                    eval('forms_loading_flag = false');
                }

                function handleFormBuilderCopyClick(id) {
                    displayCopyFormBuilderDialog(id);
                }

                function handleFormBuilderListClick(id) {
                    displayAddFormBuilderDialog(id);
                }

                function displayCopyFormBuilderDialog(id) {
                    var objname = 'dialog-modal-copy-form-builder';
                    $('#' + objname).dialog("option", "title", 'Copying forms and custom fields');
                    $('#' + objname).dialog("open");
                    $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                    $('#' + objname).load("index.php?area=form_builder&copy_form_builder=" + id + "&printable=1&no_script=1");
                    $('#' + objname).dialog('option', 'position', 'center');
                }

                function displayAddFormBuilderDialog(id) {
                    var objname = 'dialog-modal-add-name';
                    if (id > 0) {
                        $('#' + objname).dialog("option", "title", 'Editing form');
                    } else {
                        $('#' + objname).dialog("option", "title", 'Adding new form');
                    }
                    $('#' + objname).dialog("open");
                    $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                    $('#' + objname).load("index.php?area=form_builder&add_form=" + id + "&printable=1&no_script=1");
                    $('#' + objname).dialog('option', 'position', 'center');
                }

                var namesrchtog = false;

                function toggleNameSearch() {
                    namesrchtog = !namesrchtog;
                    ieDisplay('form_search_table', namesrchtog);
                }

            </script>
            <script type="text/javascript" src="js/form_builder.js"></script>
            <div id="dialog-modal-add-form" title="Adding new Name" class="nod"></div>
            <div id="dialog-modal-copy-form-builder" title="Copying form and custom fields" class="nod"></div>
            <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST"
                  action="<?= $_SERVER['REQUEST_URI'] ?>" onsubmit="loadForm_builders();return false">
                <input type="hidden" name="searching_name">
                <table border="0" width="100%" class="lb" cellspacing="0">
                    <tr>
                        <td height="40" class="pad_left ui-widget-header">
                            <table border="0" width="100%">
                                <tr>
                                    <td width="500">
                                        Form Builder&nbsp;
                                        <?
                                            /**
                                        <input type="button" value="Add" onclick="displayAddNameDialog(0)">
                                        <input type="button" value="Search" onclick="toggleFormSearch()">
                                         **/
                                        ?>
                                    </td>
                                    <td width="150" align="center">PAGE SIZE: <select
                                                name="<?= $this->order_prepend ?>pagesizeDD"
                                                id="<?= $this->order_prepend ?>pagesizeDD"
                                                onchange="<?= $this->index_name ?>=0; loadForm_builders();return false">
                                            <option value="20">20</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                            <option value="500">500</option>
                                        </select></td>
                                    <td align="right"><?
                                            /** PAGE SYSTEM CELLS -- INJECTED INTO, BY JAVASCRIPT AFTER AJAX CALL **/ ?>
                                        <table border="0" cellpadding="0" cellspacing="0" class="page_system_container">
                                            <tr>
                                                <td id="form_builder_prev_td" class="page_system_prev"></td>
                                                <td id="form_builder_page_td" class="page_system_page"></td>
                                                <td id="form_builder_next_td" class="page_system_next"></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
            </form>
            <tr>
                <td colspan="2">
                    <table border="0" width="100%" id="form_builder_table">
                        <tr>
                            <th class="row2" align="left"><?= $this->getOrderLink('name') ?>Name</a></th>
                            <th class="row2"><?= $this->getOrderLink('screen_count') ?>Total Screens</a></th>
                            <th class="row2"><?= $this->getOrderLink('field_count') ?>Total Fields</a></th>
                            <th class="row2">&nbsp;</th>
                        </tr>
                    </table>
                </td>
            </tr>
            </table>
            <script>
                $("#dialog-modal-add-form").dialog({
                    autoOpen: false,
                    width: 500,
                    height: 200,
                    modal: false,
                    draggable: true,
                    resizable: false
                });
                $("#dialog-modal-copy-form-builder").dialog({
                    autoOpen: false,
                    width: 500,
                    height: 160,
                    modal: false,
                    draggable: true,
                    resizable: false
                });
                loadForm_builders();
            </script><?

        }

        function makeCopy($id) {
            $id = intval($id);
            $sourceName = $_SESSION['dbapi']->campaigns->getName($id);
            ?>
            <form method=""POST" action="<?=stripurl('')?>" autocomplete="off" onsubmit="checkTargetCampaign(this); return false;">
            <table border="0" align="center">
                <tr>
                    <th class="lefty pct50 ht30">Copying from : </th>
                    <td class="righty pct50 ht30" style="font-weight:700;"><?=$sourceName;?></td>
                </tr>
                <tr>
                    <th class="lefty pct50 ht30" height="30">To campaign :</th>
                    <td class="righty pct50 ht30"><?=makeCampaignDD('targetCampaign', null, null, null, null);?></td>
                </tr>
                <tr>
                    <th colspan="2" class="centery"><input type="submit" value="Copy"></th>
                </tr>
            </table>
            </form>
<?
            }

        function makeAdd($id)
        {
            $id = intval($id);
            if ($id) {
                $row = $_SESSION['dbapi']->form_builder->getByID($id);
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
                            url: 'api/api.php?get=form_builder&mode=xml&action=edit',
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
                                loadForm_builders();
                                displayAddNameDialog(res);
                                alert(result['message']);
                            }
                        });
                    }
                    return false;
                }
            </script>
            <form method="POST" action="<?= stripurl('') ?>" autocomplete="off"
                  onsubmit="checkNameFrm(this); return false">
                <table border="0" align="center">
                    <tr>
                        <th align="left" height="30">Name:</th>
                        <td><input name="name" type="text" size="50" value="<?= htmlentities($row['name']) ?>"></td>
                    </tr>
                    <tr>
                        <th align="left" height="30">Filename:</th>
                        <td><input name="filename" type="text" size="50" value="<?= htmlentities($row['filename']) ?>">
                        </td>
                    </tr>
                    <tr>
                        <th align="left" height="30">Voice:</th>
                        <td><?= makeVoiceDD(0, 'voice_id', $row['voice_id']) ?></td>
                    </tr>
                    <tr>
                        <th colspan="2" align="center"><input type="submit" value="Save Changes"></th>
                    </tr>
                </table>
            </form>
            <?
        }
        function getOrderLink($field)
        {
            $var = '<a href="#" onclick="setOrder(\'' . addslashes($this->order_prepend) . '\',\'' . addslashes($field) . '\',';
            $var .= "((" . $this->order_prepend . "orderdir == 'DESC')?'ASC':'DESC')";
            $var .= ");loadNames();return false;\">";
            return $var;
        }
    }