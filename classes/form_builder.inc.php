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
                        '&mode=json';
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

                function displayAddFormBuilderDialog(id) {
                    let objname = 'main_content';
                    // if (id > 0) {
                    //     $('#' + objname).dialog("option", "title", 'Editing form');
                    // } else {
                    //     $('#' + objname).dialog("option", "title", 'Adding new form');
                    // }
                    // $('#' + objname).dialog("open");
                    $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                    $('#' + objname).load("index.php?area=form_builder&add=" + id + "&printable=1&no_script=1");
                }

                var namesrchtog = false;

                function toggleNameSearch() {
                    namesrchtog = !namesrchtog;
                    ieDisplay('form_search_table', namesrchtog);
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
                                    <td width="500">
                                        Form Builder&nbsp;
                                        <?
                                            /**
                                             * <input type="button" value="Add" onclick="displayAddNameDialog(0)">
                                             * <input type="button" value="Search" onclick="toggleFormSearch()">
                                             **/ ?>
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
            <!--            <div id="dialog-modal-add-form-builder" title="Editing form" class="nod"></div>-->
            <div id="dialog-modal-copy-form-builder" title="Copying form and custom fields" class="nod"></div>
            <script>
                // $("#dialog-modal-add-form-builder").dialog({
                //     autoOpen: false,
                //     width: 990,
                //     height: 'auto',
                //     position: {
                //         my: 'left top',
                //         at: 'left+50 top+50'
                //     },
                //     modal: false,
                //     draggable: true,
                //     resizable: true
                // });
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
            <form method=""
                  POST" action="<?= stripurl('') ?>" autocomplete="off" onsubmit="checkTargetCampaign(this); return false;">
            <table border="0" align="center">
                <tr>
                    <th class="lefty pct50 ht30">Copying from :</th>
                    <td class="righty pct50 ht30" style="font-weight:700;"><?= $sourceName; ?></td>
                </tr>
                <tr>
                    <th class="lefty pct50 ht30" height="30">To campaign :</th>
                    <td class="righty pct50 ht30"><?= makeCampaignDD('targetCampaign', NULL, NULL, NULL, NULL); ?></td>
                </tr>
                <tr>
                    <th colspan="2" class="centery"><input type="submit" value="Copy"></th>
                </tr>
            </table>
            </form>
            <?
        }

        function makeAdd($id) {
            $id = intval($id);
            if ($id) {
                $row = $_SESSION['dbapi']->form_builder->getByID($id);
            }
            ?>
            <script type="text/javascript" src="js/form_builder.js"></script>
            <script>
                var formID = '<?=$id;?>';
                var formFields = [];
                // console.log('Editing form id = ' + formID);
                // let formBuilder = new _formBuilder(formID);
                $('#dropZone').droppable();
                $("#dropZone").sortable({
                    revert: true
                });
                $(".fldMaker").draggable({
                    connectToSortable: "#dropZone",
                    helper: 'clone',
                    cursor: 'move',
                    cursorAt: {
                        top: 25,
                        left: 25
                    },
                    snap: true,
                    stop: function (e, ui) {
                        console.log('Dropped at X:' + ui.position.top + ', Y:' + ui.position.left);
                    },
                    revert: 'invalid'
                });
                $('.fldTitle').on('click', function () {
                    expandField($(this).closest('li'));
                })
                $("ul, li").disableSelection();

                function renderField(f) {
                    // console.log(f);
                    f.create();
                    f.populate();
                    f.reposition();
                }

                function loadNewScreen(jsondata) {
                    let testData = [{
                        "id": "87",
                        "deleted": "no",
                        "campaign_id": "315",
                        "screen_num": "0",
                        "field_step": "0",
                        "field_type": "1",
                        "label_x": "0",
                        "label_y": "0",
                        "label_width": "150",
                        "label_height": "30",
                        "field_x": "150",
                        "field_y": "0",
                        "field_width": "200",
                        "field_height": "30",
                        "max_length": "0",
                        "name": "Amount",
                        "value": null,
                        "special_mode": "update_amount",
                        "options": "$1000;$950;$900;$850;$800;$750;$700;$650;$600;$550;$500;$450;$400;$350;$300;$250;$200;$190;$180;$170;$160;$150;$140;$130;$120;$110;$100;$95;$90;$85;$80;$75;$70;$65;$60;$55;$50;$45;$40;$35;$30;$25;$20;$15;$0",
                        "db_table": "lead_tracking",
                        "db_field": "amount",
                        "variables": "validation_options=nonzero",
                        "is_required": "0",
                        "tool_tip": null,
                        "place_holder": null,
                        "css_class": null,
                        "is_hidden": "0",
                        "is_locked": "0",
                        "field_name": null
                    }, {
                        "id": "89",
                        "deleted": "no",
                        "campaign_id": "315",
                        "screen_num": "0",
                        "field_step": "2",
                        "field_type": "0",
                        "label_x": "0",
                        "label_y": "30",
                        "label_width": "150",
                        "label_height": "30",
                        "field_x": "150",
                        "field_y": "30",
                        "field_width": "140",
                        "field_height": "30",
                        "max_length": "0",
                        "name": "Last Name",
                        "value": null,
                        "special_mode": null,
                        "options": null,
                        "db_table": "lead_tracking",
                        "db_field": "last_name",
                        "variables": "validation_options=nonempty;force_lookup_key=2",
                        "is_required": "0",
                        "tool_tip": null,
                        "place_holder": null,
                        "css_class": null,
                        "is_hidden": "0",
                        "is_locked": "0",
                        "field_name": null
                    }, {
                        "id": "91",
                        "deleted": "no",
                        "campaign_id": "315",
                        "screen_num": "0",
                        "field_step": "3",
                        "field_type": "0",
                        "label_x": "320",
                        "label_y": "30",
                        "label_width": "100",
                        "label_height": "30",
                        "field_x": "410",
                        "field_y": "30",
                        "field_width": "140",
                        "field_height": "30",
                        "max_length": "0",
                        "name": "First Name",
                        "value": null,
                        "special_mode": null,
                        "options": null,
                        "db_table": "lead_tracking",
                        "db_field": "first_name",
                        "variables": "validation_options=nonempty;force_lookup_key=2",
                        "is_required": "0",
                        "tool_tip": null,
                        "place_holder": null,
                        "css_class": null,
                        "is_hidden": "0",
                        "is_locked": "0",
                        "field_name": null
                    }, {
                        "id": "93",
                        "deleted": "no",
                        "campaign_id": "315",
                        "screen_num": "0",
                        "field_step": "5",
                        "field_type": "0",
                        "label_x": "0",
                        "label_y": "120",
                        "label_width": "150",
                        "label_height": "30",
                        "field_x": "150",
                        "field_y": "120",
                        "field_width": "400",
                        "field_height": "30",
                        "max_length": "0",
                        "name": "Address",
                        "value": null,
                        "special_mode": null,
                        "options": null,
                        "db_table": "lead_tracking",
                        "db_field": "address1",
                        "variables": "filter_options=letters,numbers,symbols;validation_options=nonempty",
                        "is_required": "0",
                        "tool_tip": null,
                        "place_holder": null,
                        "css_class": null,
                        "is_hidden": "0",
                        "is_locked": "0",
                        "field_name": null
                    }, {
                        "id": "95",
                        "deleted": "no",
                        "campaign_id": "315",
                        "screen_num": "0",
                        "field_step": "-1",
                        "field_type": "0",
                        "label_x": "0",
                        "label_y": "150",
                        "label_width": "150",
                        "label_height": "30",
                        "field_x": "150",
                        "field_y": "150",
                        "field_width": "400",
                        "field_height": "30",
                        "max_length": "0",
                        "name": "Address 2",
                        "value": null,
                        "special_mode": null,
                        "options": null,
                        "db_table": "lead_tracking",
                        "db_field": "address2",
                        "variables": "filter_options=letters,numbers,symbols",
                        "is_required": "0",
                        "tool_tip": null,
                        "place_holder": null,
                        "css_class": null,
                        "is_hidden": "0",
                        "is_locked": "0",
                        "field_name": null
                    }, {
                        "id": "97",
                        "deleted": "no",
                        "campaign_id": "315",
                        "screen_num": "0",
                        "field_step": "-1",
                        "field_type": "0",
                        "label_x": "0",
                        "label_y": "180",
                        "label_width": "150",
                        "label_height": "30",
                        "field_x": "150",
                        "field_y": "180",
                        "field_width": "400",
                        "field_height": "30",
                        "max_length": "0",
                        "name": "Address 3",
                        "value": null,
                        "special_mode": null,
                        "options": null,
                        "db_table": "lead_tracking",
                        "db_field": "address3",
                        "variables": "filter_options=letters,numbers,symbols",
                        "is_required": "0",
                        "tool_tip": null,
                        "place_holder": null,
                        "css_class": null,
                        "is_hidden": "0",
                        "is_locked": "0",
                        "field_name": null
                    }, {
                        "id": "99",
                        "deleted": "no",
                        "campaign_id": "315",
                        "screen_num": "0",
                        "field_step": "6",
                        "field_type": "0",
                        "label_x": "0",
                        "label_y": "210",
                        "label_width": "150",
                        "label_height": "30",
                        "field_x": "150",
                        "field_y": "210",
                        "field_width": "140",
                        "field_height": "30",
                        "max_length": "12",
                        "name": "Zipcode",
                        "value": null,
                        "special_mode": "zip_autolookup",
                        "options": null,
                        "db_table": "lead_tracking",
                        "db_field": "zip_code",
                        "variables": "filter_options=numbers;validation_options=nonzero,minlength:5",
                        "is_required": "0",
                        "tool_tip": null,
                        "place_holder": null,
                        "css_class": null,
                        "is_hidden": "0",
                        "is_locked": "0",
                        "field_name": null
                    }, {
                        "id": "101",
                        "deleted": "no",
                        "campaign_id": "315",
                        "screen_num": "0",
                        "field_step": "7",
                        "field_type": "0",
                        "label_x": "0",
                        "label_y": "240",
                        "label_width": "150",
                        "label_height": "30",
                        "field_x": "150",
                        "field_y": "240",
                        "field_width": "140",
                        "field_height": "30",
                        "max_length": "0",
                        "name": "City",
                        "value": null,
                        "special_mode": null,
                        "options": null,
                        "db_table": "lead_tracking",
                        "db_field": "city",
                        "variables": "filter_options=letters,numbers;validation_options=nonempty",
                        "is_required": "0",
                        "tool_tip": null,
                        "place_holder": null,
                        "css_class": null,
                        "is_hidden": "0",
                        "is_locked": "0",
                        "field_name": null
                    }, {
                        "id": "103",
                        "deleted": "no",
                        "campaign_id": "315",
                        "screen_num": "0",
                        "field_step": "-1",
                        "field_type": "1",
                        "label_x": "330",
                        "label_y": "240",
                        "label_width": "150",
                        "label_height": "30",
                        "field_x": "410",
                        "field_y": "240",
                        "field_width": "140",
                        "field_height": "30",
                        "max_length": "0",
                        "name": "State",
                        "value": null,
                        "special_mode": null,
                        "options": "AL;AK;AZ;AR;CA;CO;CT;DE;FL;GA;HI;ID;IL;IN;IA;KS;KY;LA;ME;MD;MA;MI;MN;MS;MO;MT;NE;NV;NH;NJ;NM;NY;NC;ND;OH;OK;OR;PA;RI;SC;SD;TN;TX;UT;VT;VA;WA;WV;WI;WY;AS;DC;FM;GU;MH;MP;PW;PR;VI",
                        "db_table": "lead_tracking",
                        "db_field": "state",
                        "variables": null,
                        "is_required": "0",
                        "tool_tip": null,
                        "place_holder": null,
                        "css_class": null,
                        "is_hidden": "0",
                        "is_locked": "0",
                        "field_name": null
                    }, {
                        "id": "105",
                        "deleted": "no",
                        "campaign_id": "315",
                        "screen_num": "0",
                        "field_step": "-1",
                        "field_type": "0",
                        "label_x": "0",
                        "label_y": "300",
                        "label_width": "150",
                        "label_height": "30",
                        "field_x": "150",
                        "field_y": "300",
                        "field_width": "400",
                        "field_height": "30",
                        "max_length": "0",
                        "name": "Comments",
                        "value": null,
                        "special_mode": null,
                        "options": null,
                        "db_table": "lead_tracking",
                        "db_field": "comments",
                        "variables": "filter_options=letters,numbers,symbols",
                        "is_required": "0",
                        "tool_tip": null,
                        "place_holder": null,
                        "css_class": null,
                        "is_hidden": "0",
                        "is_locked": "0",
                        "field_name": null
                    }, {
                        "id": "107",
                        "deleted": "no",
                        "campaign_id": "315",
                        "screen_num": "0",
                        "field_step": "-1",
                        "field_type": "1",
                        "label_x": "355",
                        "label_y": "0",
                        "label_width": "75",
                        "label_height": "30",
                        "field_x": "430",
                        "field_y": "0",
                        "field_width": "100",
                        "field_height": "30",
                        "max_length": "0",
                        "name": "Gender",
                        "value": "",
                        "special_mode": null,
                        "options": ";Female;Male",
                        "db_table": "lead_tracking",
                        "db_field": "gender",
                        "variables": null,
                        "is_required": "0",
                        "tool_tip": null,
                        "place_holder": null,
                        "css_class": null,
                        "is_hidden": "0",
                        "is_locked": "0",
                        "field_name": null
                    }, {
                        "id": "109",
                        "deleted": "no",
                        "campaign_id": "315",
                        "screen_num": "0",
                        "field_step": "9",
                        "field_type": "0",
                        "label_x": "0",
                        "label_y": "270",
                        "label_width": "150",
                        "label_height": "30",
                        "field_x": "150",
                        "field_y": "270",
                        "field_width": "150",
                        "field_height": "30",
                        "max_length": "0",
                        "name": "Occupation",
                        "value": null,
                        "special_mode": null,
                        "options": null,
                        "db_table": "lead_tracking",
                        "db_field": "occupation",
                        "variables": null,
                        "is_required": "0",
                        "tool_tip": null,
                        "place_holder": null,
                        "css_class": null,
                        "is_hidden": "0",
                        "is_locked": "0",
                        "field_name": null
                    }, {
                        "id": "111",
                        "deleted": "no",
                        "campaign_id": "315",
                        "screen_num": "0",
                        "field_step": "10",
                        "field_type": "0",
                        "label_x": "315",
                        "label_y": "270",
                        "label_width": "75",
                        "label_height": "30",
                        "field_x": "410",
                        "field_y": "270",
                        "field_width": "150",
                        "field_height": "30",
                        "max_length": "0",
                        "name": "Employer",
                        "value": null,
                        "special_mode": null,
                        "options": null,
                        "db_table": "lead_tracking",
                        "db_field": "employer",
                        "variables": null,
                        "is_required": "0",
                        "tool_tip": null,
                        "place_holder": null,
                        "css_class": null,
                        "is_hidden": "0",
                        "is_locked": "0",
                        "field_name": null
                    }, {
                        "id": "113",
                        "deleted": "no",
                        "campaign_id": "315",
                        "screen_num": "0",
                        "field_step": "112",
                        "field_type": "0",
                        "label_x": "100",
                        "label_y": "60",
                        "label_width": "80",
                        "label_height": "30",
                        "field_x": "180",
                        "field_y": "60",
                        "field_width": "150",
                        "field_height": "30",
                        "max_length": "20",
                        "name": "CC #",
                        "value": null,
                        "special_mode": "cc_number",
                        "options": null,
                        "db_table": "",
                        "db_field": "cc_number",
                        "variables": "force_lookup_key=2",
                        "is_required": "0",
                        "tool_tip": null,
                        "place_holder": null,
                        "css_class": null,
                        "is_hidden": "0",
                        "is_locked": "0",
                        "field_name": null
                    }, {
                        "id": "115",
                        "deleted": "no",
                        "campaign_id": "315",
                        "screen_num": "0",
                        "field_step": "113",
                        "field_type": "1",
                        "label_x": "100",
                        "label_y": "90",
                        "label_width": "80",
                        "label_height": "30",
                        "field_x": "180",
                        "field_y": "90",
                        "field_width": "80",
                        "field_height": "30",
                        "max_length": "0",
                        "name": "CC EXP",
                        "value": null,
                        "special_mode": null,
                        "options": "Jan (01);Feb (02);Mar (03);Apr (04);May (05);Jun (06);Jul (07);Aug (08);Sep (09);Oct (10);Nov (11);Dec (12)",
                        "db_table": "",
                        "db_field": "cc_exp_month",
                        "variables": "force_lookup_key=2",
                        "is_required": "0",
                        "tool_tip": null,
                        "place_holder": null,
                        "css_class": null,
                        "is_hidden": "0",
                        "is_locked": "0",
                        "field_name": null
                    }, {
                        "id": "117",
                        "deleted": "no",
                        "campaign_id": "315",
                        "screen_num": "0",
                        "field_step": "-1",
                        "field_type": "1",
                        "label_x": "-100",
                        "label_y": "-100",
                        "label_width": "0",
                        "label_height": "0",
                        "field_x": "260",
                        "field_y": "90",
                        "field_width": "100",
                        "field_height": "30",
                        "max_length": "0",
                        "name": "CC EXP Year",
                        "value": null,
                        "special_mode": null,
                        "options": "2018;2019;2020;2021;2022;2023;2024;2025;2026;2027;2028;2029;2030;2031;2032;2033;2034;2035;2036;2037;2038;2039;2040",
                        "db_table": "",
                        "db_field": "cc_exp_year",
                        "variables": null,
                        "is_required": "0",
                        "tool_tip": null,
                        "place_holder": null,
                        "css_class": null,
                        "is_hidden": "0",
                        "is_locked": "0",
                        "field_name": null
                    }, {
                        "id": "119",
                        "deleted": "no",
                        "campaign_id": "315",
                        "screen_num": "0",
                        "field_step": "-1",
                        "field_type": "5",
                        "label_x": "-100",
                        "label_y": "-100",
                        "label_width": "0",
                        "label_height": "0",
                        "field_x": "360",
                        "field_y": "90",
                        "field_width": "150",
                        "field_height": "30",
                        "max_length": "0",
                        "name": "PROCESS CC",
                        "value": null,
                        "special_mode": null,
                        "options": null,
                        "db_table": "",
                        "db_field": "process_cc_button",
                        "variables": "process_cc_button=1",
                        "is_required": "0",
                        "tool_tip": null,
                        "place_holder": null,
                        "css_class": null,
                        "is_hidden": "0",
                        "is_locked": "0",
                        "field_name": null
                    }, {
                        "id": "121",
                        "deleted": "no",
                        "campaign_id": "315",
                        "screen_num": "0",
                        "field_step": "-1",
                        "field_type": "4",
                        "label_x": "-100",
                        "label_y": "-100",
                        "label_width": "0",
                        "label_height": "0",
                        "field_x": "340",
                        "field_y": "60",
                        "field_width": "200",
                        "field_height": "30",
                        "max_length": "0",
                        "name": "CC Type",
                        "value": null,
                        "special_mode": null,
                        "options": null,
                        "db_table": "",
                        "db_field": "cc_type_lbl",
                        "variables": null,
                        "is_required": "0",
                        "tool_tip": null,
                        "place_holder": null,
                        "css_class": null,
                        "is_hidden": "0",
                        "is_locked": "0",
                        "field_name": null
                    }, {
                        "id": "123",
                        "deleted": "no",
                        "campaign_id": "315",
                        "screen_num": "0",
                        "field_step": "-1",
                        "field_type": "1",
                        "label_x": "0",
                        "label_y": "60",
                        "label_width": "50",
                        "label_height": "30",
                        "field_x": "50",
                        "field_y": "60",
                        "field_width": "50",
                        "field_height": "30",
                        "max_length": "0",
                        "name": "CC",
                        "value": "Yes",
                        "special_mode": null,
                        "options": "Yes;No",
                        "db_table": "",
                        "db_field": "use_cc",
                        "variables": null,
                        "is_required": "0",
                        "tool_tip": null,
                        "place_holder": null,
                        "css_class": null,
                        "is_hidden": "0",
                        "is_locked": "0",
                        "field_name": null
                    }];
                    jsondata = testData;
                    if (jsondata.length == 0 || jsondata === undefined) {
                        console.log('No data found');
                    } else {
                        // console.log(jsondata);
                        jQuery.each(jsondata, function (i, v) {
                            // console.log('Rendering field # ' + i + ' with value ' + v);
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
                    $('ul#dropZone').empty();
                }

                function changeScreen(c, s) {
                    // is existing screen saved?
                    // load new screen fields
                    // console.log('Changing screen to ' + c + ':' + s);
                    clearDropZone();
                    loadAjaxData(getFieldsURL(c, s), 'loadNewScreen');
                }

                function removeField(i) {

                }

                function editField(i) {
                    let f = formFields[i];
                    f.edit();
                }

                function previewField(i) {
                    expandField(i);
                }

                $(function () {
                    changeScreen(formID, $('#screenNumber option:selected').val());
                });
            </script>
                <div class="pct100">
                    <div class="ui-widget-header">Editing Form for Campaign :</div>
                    <label for="screeNumber">Select screen : </label>
                    <select name="screenNumber" id="screenNumber" onchange="changeScreen(formID, this.value); return false;">
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                    <input type="button" value="Save Form" onclick="saveForm(); return false;" class="frmActionButton"/>
                    <input type="button" value="Preview Form" onclick="previewForm(); return false;" class="frmActionButton"/>
                </div>
                <div class="pct100">
                    <ul id="dragZone" class="lefty pct100">
                        <li class="ui-state-highlight ui-widget-content fldMaker" data-fldType="0">Text Field Draggable</li>
                        <li class="ui-state-highlight ui-widget-content fldMaker" data-fldType="1">DropDown Field
                            Draggable
                        </li>
                        <li class="ui-state-highlight ui-widget-content fldMaker">Textarea Field Draggable</li>
                    </ul>
                    <ul id="dropZone" class="lefty pct100">
                        <li class="ui-state-default fldHolder"></li>
                    </ul>
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