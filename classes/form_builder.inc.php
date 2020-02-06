<script type="text/javascript" src="js/form_builder.js"></script>
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
                var <?=$this->index_name?> = 0;
                var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;
                var forms_loading_flag = false;
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
                    var frm = getEl('<?=$this->frm_name?>');
                    var <?=$this->order_prepend?>pagesize = $('#<?=$this->order_prepend?>pagesizeDD').val();
                    return 'api/api.php' +
                        "?get=form_builder&" +
                        "mode=xml&" +
                        "index=" + <?=$this->index_name?> * <?=$this->order_prepend?>pagesize + "&pagesize=" + <?=$this->order_prepend?>pagesize + "&" + "orderby=" + <?=$this->order_prepend?>orderby + "&orderdir=" + <?=$this->order_prepend?>orderdir;
                }

                function getFieldsURL(c, s) {
                    return 'api/api.php' +
                        '?get=form_builder&action=getScreen' +
                        '&campaign_id=' + c + '&screen_number=' + s +
                        '&mode=xml';
                }


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

                function handleForm_builderNewClick(id) {
                    displayAddFormBuilderDialog(id);
                }

                function handleForm_builderListClick(id) {
                    displayAddFormBuilderDialog(id);
                }

                function displayCopyFormBuilderDialog(id) {
                    let objname = 'dialog-modal-copy-form-builder';
                    $('#' + objname).dialog("option", "title", 'Copying forms and custom fields');
                    $('#' + objname).dialog("open");
                    $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                    $('#' + objname).load("index.php?area=form_builder&copy=" + id + "&printable=1&no_script=1");
                    $('#' + objname).dialog('option', 'position', 'center');
                }

                function displayNewFormBuilderDialog() {
                    let objname = 'dialog-modal-add-form-builder';
                    $('#' + objname).dialog("open");
                }

                function displayAddFormBuilderDialog(id) {
                    let objname = 'main-container';
                    $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                    $('#' + objname).load("index.php?area=form_builder&add=" + id + "&printable=1&no_script=1");
                }

                function addNewField(c, s) {
                    let newIndex = formFields.length;
                    let newObj = {};
                    newObj.campID = c;
                    newObj.campaign_id = c;
                    newObj.screenNum = s;
                    newObj.screen_num = s;
                    // let's set some default values per Jon
                    newObj.name = 'New Field';
                    newObj.label_x = 0;
                    newObj.label_y = 0;
                    newObj.field_x = 100;
                    newObj.field_y = 0;
                    newObj.label_height = 30;
                    newObj.label_width = 100;
                    newObj.field_height = 30;
                    newObj.field_width = 100;
                    newObj.field_type = 0;
                    newObj.max_length = 50;
                    newObj.field_step = -1;
                    newObj.is_hidden = 0;
                    newObj.is_locked = 0;
                    newObj.value = '';
                    let formField = new frmField(newIndex, newObj);
                    formFields.push(formField);
                    formField.saveToDB();
                }
                $("#dialog-modal-copy-form-builder").dialog({
                    autoOpen: false,
                    width: 500,
                    height: 160,
                    modal: false,
                    draggable: true,
                    resizable: false,
                    position: {my: 'center', at: 'center', of: '#main-container'},
                });
                $("#dialog-modal-add-form-builder").dialog({
                    autoOpen: false,
                    width: 'auto',
                    height: 160,
                    modal: false,
                    draggable: true,
                    resizable: false,
                    title: 'Create New Form',
                    buttons: {
                        'Create': function () {
                            let targetID = $('#targetCampaign').val();
                            addNewField(targetID, 0);
                            confirm('New form created');
                            $(this).dialog('close');
                            loadForm_builders();
                        },
                        'Cancel': function () {
                            $(this).dialog('close');
                        }
                    },
                    position: {my: 'center', at: 'center', of: '#main-container'},
                });
                $("#dialog-modal-delete-last-field").dialog({
                    autoOpen: false,
                    width: 500,
                    height: 160,
                    modal: false,
                    draggable: true,
                    resizable: false,
                    title: 'CONFIRM - Delete Last Field',
                    position: {my: 'center', at: 'center', of: '#main-container'},
                    buttons: {
                        'Confirm': function () {
                            let i = $(this).data('fieldID');
                            deleteField(i);
                            $(this).dialog('close');
                        },
                        'Cancel': function () {
                            $(this).dialog('close');
                        }
                    }
                });
            </script>
            <div class="block">
                <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>" onsubmit="loadForm_builders();return false">
                    <input type="hidden" name="searching_name">
                    <div class="block-header bg-primary-light">
                        <h4 class="block-title">Form Builder</h4>
                        <button type="button" value="Add" title="Add Form" class="btn btn-sm btn-primary" onclick="displayNewFormBuilderDialog(); return false;">Add</button>
                        <div id="form_builder_prev_td" class="page_system_prev"></div>
                        <div id="form_builder_page_td" class="page_system_page"></div>
                        <div id="form_builder_next_td" class="page_system_next"></div>
                        <select title="Rows Per Page" class="custom-select-sm" name="<?=$this->order_prepend?>pagesize" id="<?=$this->order_prepend?>pagesizeDD" onchange="<?=$this->index_name?>=0;loadForm_builders()s(); return false;">
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                    <div class="block-content">
                        <table class="table table-sm table-striped" id="form_builder_table">
                            <tr>
                                <th class="row2 text-left"><?= $this->getOrderLink('name') ?>Name</a></th>
                                <th class="row2 text-center">Total Screens</th>
                                <th class="row2 text-center">Total Fields</th>
                                <th class="row2 text-center">Action</th>
                            </tr>
                        </table>
                    </div>
                </form>
            </div>
            <div id="dialog-modal-add-form-builder" title="Creating new form" class="nod"><?= $this->makeNew(); ?></div>
            <div id="dialog-modal-copy-form-builder" title="Copying form and custom fields" class="nod"></div>
            <div id="dialog-modal-delete-last-field" title="CONFIRM - Deleting Last Field" class="nod">
                <div class="warning">Are you sure you want to delete the last field of this form?</div>
            </div>
            <script>
                loadForm_builders();
            </script>
            <?

        }

        function makeCopy($id) {
            $id = intval($id);
            $sourceName = $_SESSION['dbapi']->campaigns->getName($id);
            ?>
            <script>
                $(function () {
                    $('#btnMakeCopy').on('click', function () {
                        let targetID = $('#targetCampaign').val();
                        let sourceID = $('#sourceCampaign').val();
                        $.post('api/api.php?get=form_builder&mode=json&action=copyFields&sourceID=' + sourceID + '&targetID=' + targetID, function () {
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
                            <input type="hidden" id="sourceCampaign" value="<?= $id; ?>"/>
                            <?= $sourceName; ?>
                        </td>
                    </tr>
                    <tr>
                        <th class="lefty pct50 ht30" height="30">To campaign :</th>
                        <td class="righty pct50 ht30"><?= makeNoFormsCampaignDD('targetCampaign', NULL, NULL, NULL, NULL); ?></td>
                    </tr>
                    <tr>
                        <th colspan="2" class="centery">
                            <button id="btnMakeCopy" class="btn btn-small btn-primary" type="button" value="Make Copy">Copy</button>
                        </th>
                    </tr>
                </table>
            </form>
            <?
        }

        function makeNew() {
            ?>
            <table border="0" style="text-align:center;">
                <tr>
                    <th class="lefty pct50 ht30">Choose campaign for new form :</th>
                    <td class="righty pct50 ht30"><?= makeNoFormsCampaignDD('targetCampaign', NULL, NULL, NULL, NULL); ?></td>
                </tr>
            </table>
            <?
        }

        function makeAdd($id) {
            $id = intval($id);
            $sourceName = $_SESSION['dbapi']->campaigns->getName($id);
            if ($id) {
                $row = $_SESSION['dbapi']->form_builder->getByID($id);
            }
            ?>
            <script>
                var formID = '<?=$id;?>';
                // var formFields = [];
                var currentScreen = 0;
                $("#dialog-modal-preview-form-builder").dialog({
                    autoOpen: false,
                    width: 660,
                    height: 600,
                    modal: true,
                    draggable: true,
                    resizable: true
                });
                $('#dropZone').droppable({
                    accept: '.dragMe',
                    tolerance: 'pointer',
                    greedy: true,
                    hoverClass: 'highlight',
                    drop: function (e, ui) {
                        $(ui.draggable).detach().css(
                            {
                                position: 'absolute',
                            }
                        ).appendTo(this);
                    }
                });
                $('li.fldMaker').draggable({
                    containment: "#dropZone",
                    helper: 'clone',
                    refreshPositions: true,
                    scroll: false,
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
                    formFields = [];
                    $.getJSON('api/api.php?get=form_builder&mode=json&action=getScreen&campaign_id=' + c + '&screen_number=' + s, function (data) {
                        loadNewScreen(data);
                    });
                    currentScreen = s;
                }

                function saveField(i) {
                    let f = formFields[i];
                    f.saveToDB();
                }

                function saveForm() {
                    for (let i = 0; i < formFields.length; i++) {
                        let f = formFields[i];
                        let campID = f.campID;
                        let screenNum = f.screenNum;
                        f.saveToDB();
                    }
                    window.alert('Form saved');
                    changeScreen(f.campID, f.screenNum);
                }

                function deleteField(i) {
                    let f = formFields[i];
                    $.post('api/api.php?get=form_builder&mode=json&action=markDeleted&id=' + f.dbID, function () {
                        changeScreen(f.campID, f.screenNum);
                    });
                }

                function addField(c, s) {
                    let newIndex = formFields.length;
                    let newObj = {};
                    newObj.campID = c;
                    newObj.campaign_id = c;
                    newObj.screenNum = s;
                    newObj.screen_num = s;
                    // let's set some default values per Jon
                    newObj.name = 'New Field';
                    newObj.label_x = 0;
                    newObj.label_y = 0;
                    newObj.field_x = 100;
                    newObj.field_y = 0;
                    newObj.label_height = 30;
                    newObj.label_width = 100;
                    newObj.field_height = 30;
                    newObj.field_width = 100;
                    newObj.field_type = 0;
                    newObj.max_length = 50;
                    newObj.field_step = -1;
                    newObj.is_hidden = 0;
                    newObj.is_locked = 0;
                    newObj.value = '';
                    let formField = new frmField(newIndex, newObj);
                    formFields.push(formField);
                    editField(newIndex);
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
                            click: function () {
                                if (formFields.length === 1) {
                                    let $dlgObj = $('#dialog-modal-delete-last-field');
                                    $dlgObj.data('fieldID', i);
                                    $dlgObj.dialog('open');
                                } else {
                                    deleteField(i);
                                }
                                $(this).dialog('close');
                            }
                        },
                        {
                            text: 'Save',
                            title: 'Finish editing and save',
                            icon: 'ui-icon-disk',
                            click: function () {
                                $.each($('#fieldAsForm' + i).serializeArray(), function (index, field) {
                                    f[field.name] = field.value;
                                    // console.log('Setting f.' + field.name + ' to ' + field.value);
                                });
                                let resp = f.saveToDB();
                                window.alert('Field saved!');
                                $(this).dialog('close');
                                changeScreen(f.campID, f.screenNum);
                            }
                        },
                        {
                            text: 'Cancel',
                            title: 'Cancel editing',
                            icon: 'ui-icon-cancel',
                            click: function () {
                                $(this).dialog('close');
                            }
                        }
                    ]);
                    f.edit();
                }

                function changeFieldType(i, v) {
                    let f = formFields[i];
                    f.fldType = $('option:selected', '#field_type' + i).val();
                    switch (v) {
                        case '1' :
                            $('#options' + i).closest('tr').show();
                            $('#value' + i).closest('label').text('Default Value : ');
                            break;
                        case '3' :
                            $('#options' + i).closest('tr').hide();
                            $('#value' + i).closest('label').text('Src URL : ');
                            break;
                        default :
                            $('#options' + i).closest('tr').hide();
                            $('#value' + i).closest('label').text('Default Value : ');
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
            <div class="block block-themed">
                <div class="block-header bg-primary-light">
                    <div class="block-title ">Editing Form for Campaign : <?= $sourceName; ?></div>
                    <div class="block-options">
                        <button type="button" title="Back" class="btn-block-option" onclick="loadSection('?area=form_builder&no_script=1');">
                            <i class="fa fa-backward"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content">
                    <ul class="nav nav-tabs nav-tabs-alt" data-toggle="tabs" role="tablist">
                        <li class="nav-item"><a href="#mainPanel" class="nav-link loadScreen" onclick="changeScreen(formID, 0); return false;">All Screens</a></li>
                        <li class="nav-item"><a href="#mainPanel" class="nav-link loadScreen" onclick="changeScreen(formID, 1); return false;">Screen 1</a></li>
                        <li class="nav-item"><a href="#mainPanel" class="nav-link loadScreen" onclick="changeScreen(formID, 2); return false;">Screen 2</a></li>
                        <li class="nav-item"><a href="#mainPanel" class="nav-link loadScreen" onclick="changeScreen(formID, 3); return false;">Screen 3</a></li>
                        <li class="nav-item"><a href="#mainPanel" class="nav-link loadScreen" onclick="changeScreen(formID, 4); return false;">Screen 4</a></li>
                        <li class="nav-item"><a href="#mainPanel" class="nav-link loadScreen" onclick="changeScreen(formID, 5); return false;">Screen 5</a></li>
                    </ul>
                    <div id="mainPanel" class="pct100">
                        <div class="ht40 block-options" style="margin-bottom:10px;">
                            <button type="button" class="btn btn-sm btn-primary" title="Add" value="Add Field" onclick="addField(formID, currentScreen); return false;" style="float:left;" class="frmActionButton">Add</button>
                            <button type="button" class="btn btn-sm btn-primary" title="Preview" value="Preview Form" onclick="previewForm(); return false;" class="frmActionButton">Preview</button>
                            <button type="button" class="btn btn-sm btn-primary" title="Refresh" value="Refresh Form" onclick="changeScreen(formID, currentScreen); return false;" class="frmActionButton">Refresh</button>
                            <button type="button" class="btn btn-sm btn-primary" title="Save" value="Save Form" onclick="saveForm(); return false;" class="frmActionButton">Save</button>
                        </div>
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
            $var .= ");loadForm_builders();return false;\">";
            return $var;
        }
    }