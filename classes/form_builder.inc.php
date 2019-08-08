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
    class FormBuilder {

        var $table = 'custom_fields';            ## Classes main table to operate on
        var $orderby = 'campaign_id';        ## Default Order field
        var $orderdir = 'ASC';                ## Default order direction

        ## Page  Configuration
        var $pagesize = 20;    ## Adjusts how many items will appear on each page
        var $index = 0;        ## You dont really want to mess with this variable. Index is adjusted by code, to change the pages
        var $index_name = 'forms_list';    ## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
        var $frm_name = 'formnextfrm';
        var $order_prepend = 'form_';                ## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

        function FormBuilder() {
            ## REQURES DB CONNECTION!
            $this->handlePOST();
        }

        function handlePOST() {
            // THIS SHIT IS MOTHER FUCKING AJAX'D TO THE TEETH
            // SEE api/names.api.php FOR POST HANDLING!
            // <3 <3 -Jon
        }

        function handleFLOW() {
            # Handle flow, based on query string
            if (!checkAccess('campaigns')) {
                accessDenied("Campaigns");
                return;
            } else {
                if (isset($_REQUEST['add'])) {
                    $this->makeAdd($_REQUEST['add']);
                } elseif (isset($_REQUEST['copy'])) {
                    $this->makeCopy($_REQUEST['copy']);
                } elseif (isset($_REQUEST['new'])) {
                    $this->makeNew();
                } else {
                    $this->listForms();
                }
            }
        }

        function listForms() {
            ?>
            <script>
                var form_builder_delmsg = 'Are you sure you want to delete this form?';
                var form_builder_copymsg = 'Copying forms and custom fields';
                var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
                var <?=$this->order_prepend?>orderdir = "<?=$this->orderdir?>";
                var <?=$this->index_name?> =
                0;
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
                        "index=" + (<?=$this->index_name?> * <?=$this->order_prepend?>pagesize
                )
                    +
                        "&pagesize=" + <?=$this->order_prepend?>pagesize + "&" +
                    "orderby=" + <?=$this->order_prepend?>orderby + "&orderdir=" + <?=$this->order_prepend?>orderdir;
                }

                function getFieldsURL(c, s) {
                    return 'api/api.php' +
                        '?get=form_builder&action=getScreen' +
                        '&campaign_id=' + c + '&screen_number=' + s +
                        '&mode=xml';
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

                function handleForm_builderCopyClick(id) {
                    displayCopyFormBuilderDialog(id);
                }

                function handleForm_builderListClick(id) {
                    displayAddFormBuilderDialog(id);
                }

                function displayCopyFormBuilderDialog(id) {
                    var objname = 'dialog-modal-copy-form-builder';
                    $('#' + objname).dialog("option", "title", 'Copying forms and custom fields');
                    $('#' + objname).dialog("open");
                    $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                    $('#' + objname).load("index.php?area=form_builder&copy=" + id + "&printable=1&no_script=1");
                    $('#' + objname).dialog('option', 'position', 'center');
                }

                function displayNewFormBuilderDialog() {
                    var objname = 'dialog-modal-add-form-builder';
                    $('#' + objname).dialog("option", "title", 'Create new form');
                    $('#' + objname).dialog("open");
                    $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                    $('#' + objname).load("index.php?area=form_builder&new=1&printable=1&no_script=1");
                    $('#' + objname).dialog('option', 'position', 'center');
                }

                function displayAddFormBuilderDialog(id) {
                    let objname = 'main_content';
                    $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                    $('#' + objname).load("index.php?area=form_builder&add=" + id + "&printable=1&no_script=1");
                }
            </script>
            <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST"
                  action="<?= $_SERVER['REQUEST_URI'] ?>" onsubmit="loadForm_builders();return false">
                <input type="hidden" name="searching_name">
                <table border="0" width="100%" class="lb" cellspacing="0">
                    <tr>
                        <td height="40" class="pad_left ui-widget-header">
                            <table border="0" width="100%">
                                <tr>
                                    <td width="500">Form Builder</td>
                                    <td width="150" align="center">PAGE SIZE: <select
                                                name="<?= $this->order_prepend ?>pagesizeDD"
                                                id="<?= $this->order_prepend ?>pagesizeDD"
                                                onchange="<?= $this->index_name ?>=0; loadForm_builders();return false">
                                            <option value="20">20</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                            <option value="500">500</option>
                                        </select></td>
                                    <td align="right">
<!--                                        <input type="button" value="New" onclick="displayNewFormBuilderDialog(); return false;">-->
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
            <div id="dialog-modal-add-form-builder" title="Creating new form" class="nod"></div>
            <div id="dialog-modal-copy-form-builder" title="Copying form and custom fields" class="nod"></div>
            <script>
                $("#dialog-modal-copy-form-builder").dialog({
                    autoOpen: false,
                    width: 500,
                    height: 160,
                    modal: false,
                    draggable: true,
                    resizable: false,
                });
                $("#dialog-modal-add-form-builder").dialog({
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
            <script>
                $(function() {
                    $('#btnMakeCopy').on('click', function() {
                        let targetID = $('#targetCampaign').val();
                        let sourceID = $('#sourceCampaign').val();
                        $.post('api/api.php?get=form_builder&mode=json&action=copyFields&sourceID=' + sourceID + '&targetID=' + targetID, function() {
                            $('#dialog-modal-copy-form-builder').dialog('close');
                            confirm('Campaign copied');
                            loadForm_builders();
                        });
                    });
                });
            </script>
            <form method="POST" action="<?= stripurl('') ?>" autocomplete="off">
            <table border="0" style="width:100%;text-align:center;">
                <tr>
                    <th class="lefty pct50 ht30">Copying from :</th>
                    <td class="righty pct50 ht30" style="font-weight:700;">
                        <input type="hidden" id="sourceCampaign" value="<?= $id; ?>" />
                        <?= $sourceName; ?>
                    </td>
                </tr>
                <tr>
                    <th class="lefty pct50 ht30" height="30">To campaign :</th>
                    <td class="righty pct50 ht30"><?= makeNoFormsCampaignDD('targetCampaign', NULL, NULL, NULL, NULL); ?></td>
                </tr>
                <tr>
                    <th colspan="2" class="centery">
                        <input id="btnMakeCopy" type="button" value="Make Copy">
                    </th>
                </tr>
            </table>
            </form>
            <?
        }

        function makeNew() {
            ?>
            <form method="POST" action="<?= stripurl('new') . 'add=0' ?>" autocomplete="off">
            <table border="0" style="text-align:center;">
                <tr>
                    <th class="lefty pct50 ht30">Choose campaign for new form :</th>
                    <td class="righty pct50 ht30"><?= makeNoFormsCampaignDD('targetCampaign', NULL, NULL, NULL, NULL); ?></td>
                </tr>
                <tr>
                    <th colspan="2" class="centery"><input type="submit" value="Go" onclick="displayAddFormBuilderDialog(0)"></th>
                </tr>
            </table>
            </form>
            <?
        }

        function makeAdd($id) {
            $id = intval($id);
            $sourceName = $_SESSION['dbapi']->campaigns->getName($id);
            if ($id) {
                $row = $_SESSION['dbapi']->form_builder->getByID($id);
            }
            ?>
            <script type="text/javascript" src="js/form_builder.js"></script>
            <script>
                var formID = '<?=$id;?>';
                var formFields = [];
                $("#dialog-modal-preview-form-builder").dialog({
                    autoOpen: false,
                    width: 660,
                    height: 600,
                    modal: true,
                    draggable: true,
                    resizable: true
                });
                $('#dropZone').droppable();
                $('li.fldMaker').draggable({
                    containment: "#dropZone",
                    helper: 'clone',
                    // cursor: 'move',
                    // class: 'hand',
                    // cursorAt: {
                    //     top: 25,
                    //     left: 25
                    // },
                    // snap: true,
                    scroll: false,
                    // snapMode: 'inner',
                    revert: 'invalid'
                });
                $('.fldTitle').on('click', function () {
                    expandField($(this).closest('li'));
                });
                $("ul, li").disableSelection();

                function renderField(f) {
                    // console.log(f);
                    f.create();
                    f.populate();
                }

                function loadNewScreen(jsondata) {
                    // console.log(jsondata);
                    if (jsondata.length == 0 || jsondata === undefined) {
                        console.log('No data found');
                    } else {
                        // console.log(jsondata);
                        jQuery.each(jsondata, function (i, v) {
                            let formField = new frmField(i, v);
                            formFields.push(formField);
                            renderField(formField);
                        });
                    }
                }

                function expandField(i) {
                    // el.show();
                }

                function clearDropZone() {
                    $('#dropZone').empty();
                }

                function changeScreen(c, s) {
                    // is existing screen saved?
                    // load new screen fields
                    // console.log('Changing screen to ' + c + ':' + s);
                    clearDropZone();
                    $.getJSON('api/api.php?get=form_builder&mode=json&action=getScreen&campaign_id=' + c + '&screen_number=' + s, function(data) {
                        loadNewScreen(data);
                    });
                }

                function saveField(i) {
                    let f = formFields[i];
                    console.log(JSON.stringify(f));
                    f.saveToDB();
                }

                function saveForm() {
                    for (let i = 0; i < formFields.length; i++) {
                        let f = formFields[i];
                        f.saveToDB();
                    }
                }

                function deleteField(i) {
                    let f = formFields[i];
                    $.post('api/api.php?get=form_builder&mode=json&action=markDeleted&id=' + f.dbID, function() {
                        changeScreen(f.campID, f.screenNum);
                    });
                }

                function editField(i) {
                    let f = formFields[i];
                    let objname = 'dialog-modal-preview-form-builder';
                    $('#' + objname).dialog("open");
                    $('#' + objname).html('<div id="editBox" class="pct100"></div>');
                    $('#' + objname).dialog('option', 'title', 'Editing field');
                    $('#' + objname).dialog('option', 'buttons', [
                        {
                            text: 'Delete',
                            title: 'Remove field from this form',
                            icon: 'ui-icon-trash',
                            click: function() {
                                deleteField(i);
                                $(this).dialog('close');
                            }
                        },
                        {
                            text: 'Save',
                            title: 'Finish editing and save',
                            icon: 'ui-icon-disk',
                            click: function() {
                                $.each($('#fieldAsForm' + i).serializeArray(), function(index, field) {
                                    f[field.name] = field.value;
                                    console.log('Setting f.' + field.name + ' to ' + field.value);
                                });
                                f.saveToDB();
                                // saveField(f);
                                $(this).dialog('close');
                            }
                        },
                        {
                            text: 'Cancel',
                            title: 'Cancel editing',
                            icon: 'ui-icon-cancel',
                            click: function() {
                                $(this).dialog('close');
                            }
                        }
                    ]);
                    f.edit();
                }

                function changeFieldType(i, v) {
                    switch (v) {
                        default :
                        case '0' :
                            $('#options' + i).closest('tr').hide();
                            break;
                        case '1' :
                            $('#options' + i).closest('tr').show();
                            break;
                        case '2' :
                            $('#options' + i).closest('tr').hide();
                            break;
                    }
                }

                function previewField(i) {
                    let f = formFields[i];
                    let objname = 'dialog-modal-preview-form-builder';
                    $('#' + objname).dialog("open");
                    $('#' + objname).html('<div id="previewBox" class="pct100"></div>');
                    f.preview();
                }

                function previewForm() {
                    let objname = 'dialog-modal-preview-form-builder';
                    let currXPos = 0;
                    let currYPos = 0;
                    $('#' + objname).dialog("open");
                    $('#' + objname).html('<div id="previewBox" class="pct100"></div>');
                    // $('#' + objname).load("index.php?area=form_builder&preview=" + id + "&printable=1&no_script=1");
                    // $('#' + objname).dialog('option', 'position', 'center');
                    formFields.forEach((ff) => {
                        ff.preview();
                    });
                }

                $(function () {
                    $('#screenTabs').tabs({
                        heightStyle: 'content'
                    }).css({
                        'min-height': '400px',
                        'overflow': 'auto'
                    });
                    changeScreen(formID, 0);
                });
            </script>
            <div class="pct100">
                <div class="ht40 pd10 ui-widget-header">Editing Form for Campaign : <?=$sourceName;?></div>
                <div id="screenTabs">
                    <ul>
                        <li><a href="#mainPanel" class="loadScreen" onclick="changeScreen(formID, 0); return false;">Screen 0</a></li>
                        <li><a href="#mainPanel" class="loadScreen" onclick="changeScreen(formID, 1); return false;">Screen 1</a></li>
                        <li><a href="#mainPanel" class="loadScreen" onclick="changeScreen(formID, 2); return false;">Screen 2</a></li>
                        <li><a href="#mainPanel" class="loadScreen" onclick="changeScreen(formID, 3); return false;">Screen 3</a></li>
                        <li><a href="#mainPanel" class="loadScreen" onclick="changeScreen(formID, 4); return false;">Screen 4</a></li>
                        <li><a href="#mainPanel" class="loadScreen" onclick="changeScreen(formID, 5); return false;">Screen 5</a></li>
                    </ul>
                    <div id="mainPanel" class="pct100">
                        <input type="button" value="Save Form" onclick="saveForm(); return false;" class="frmActionButton"/>
                        <input type="button" value="Preview Form" onclick="previewForm(); return false;" class="frmActionButton"/>
                        <ul id="dragZone" class="lefty pct100">
                            <li class="ui-state-highlight ui-widget-content fldMaker" data-fldType="0">TEXT Field</li>
                            <li class="ui-state-highlight ui-widget-content fldMaker" data-fldType="1">SELECT Field</li>
                            <li class="ui-state-highlight ui-widget-content fldMaker" data-fldType="2">TEXTAREA Field</li>
<!--                            <li class="ui-state-highlight ui-widget-content fldMaker" data-fldType="99">EMPTY Filler</li>-->
                        </ul>
                        <div id="dropZone" class="lefty pct100">
                            <div class="ui-state-default fldHolder"></div>
                        </div>
                    </div>
                    <div id="dialog-modal-preview-form-builder" title="Previewing Form" class="nod"></div>
                </div>
            </div>
            <?
        }

        function getOrderLink($field) {
            $var = '<a href="#" onclick="setOrder(\'' . addslashes($this->order_prepend) . '\',\'' . addslashes($field) . '\',';
            $var .= "((" . $this->order_prepend . "orderdir == 'DESC')?'ASC':'DESC')";
            $var .= ");loadNames();return false;\">";
            return $var;
        }
    }