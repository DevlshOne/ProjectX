<?php
/***************************************************************
 *    Schedules - Handles special pay schedules
 *    Written By: Dave Mednick
 ***************************************************************/

$_SESSION['schedules'] = new Schedules;


class Schedules
{

    var $table = 'schedules';            ## Classes main table to operate on
    var $orderby = 'id';        ## Default Order field
    var $orderdir = 'DESC';    ## Default order direction
    ## Page  Configuration
    var $pagesize = 20;    ## Adjusts how many items will appear on each page
    var $index = 0;        ## You dont really want to mess with this variable. Index is adjusted by code, to change the pages
    var $index_name = 'schedules_list';    ## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
    var $frm_name = 'schedulesnextfrm';
    var $order_prepend = 'schedules_';                ## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

    function Schedules()
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
        if (!checkAccess('schedules')) {
            accessDenied("Schedules");
            return;
        } else {
            if (isset($_REQUEST['add_schedule'])) {
                $this->makeAdd($_REQUEST['add_schedule']);
            } else {
                $this->listEntrys();
            }
        }
    }

    function listEntrys()
    {
        ?>
        <script>
            var schedule_delmsg = 'Are you sure you want to delete this rule?';
            var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
            var <?=$this->order_prepend?>orderdir = "<?=$this->orderdir?>";
            var <?=$this->index_name?> = 0;
            var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;
            var SchedulesTableFormat = [
                ['name', 'text-left'],
                ['[get:company_name:company_id]', 'text-left'],
                ['[get:office_name:office_id]', 'text-left'],
                // ['[get:start_offset:start_time]', 'text-left'],
                // ['[get:end_offset:end_time]', 'text-left'],
                ['[delete]', 'text-center']
            ];

            /**
             * Build the URL for AJAX to hit, to build the list
             */
            function getSchedulesURL() {

                var frm = getEl('<?=$this->frm_name?>');
                var <?=$this->order_prepend?>pagesize = $('#<?=$this->order_prepend?>pagesizeDD').val();
                return 'api/api.php' +
                    "?get=schedules&" +
                    "mode=xml&" +
                    's_company_id=' + escape(frm.s_company_id.value) + "&" +
                    's_office_id=' + escape(frm.s_office_id.value) + "&" +
                    "index=" + (<?=$this->index_name?> * <?=$this->order_prepend?>pagesize
            )
                +"&pagesize=" + <?=$this->order_prepend?>pagesize + "&" +
                "orderby=" + <?=$this->order_prepend?>orderby + "&orderdir=" + <?=$this->order_prepend?>orderdir;
            }


            var schedules_loading_flag = false;
            /**
             * Load the companies rules data - make the ajax call, callback to the parse function
             */
            function loadSchedules() {
                // ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
                var val = null;
                eval('val = schedules_loading_flag');
                // CHECK IF WE ARE ALREADY LOADING THIS DATA
                if (val == true) {
                    return;
                } else {
                    eval('schedules_loading_flag = true');
                }
                <?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());
                loadAjaxData(getSchedulesURL(), 'parseSchedules');
            }

            /**
             * CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
             */
            var <?=$this->order_prepend?>totalcount = 0;
            function parseSchedules(xmldoc) {
                <?=$this->order_prepend?>totalcount = parseXMLData('schedule', SchedulesTableFormat, xmldoc);
                // ACTIVATE PAGE SYSTEM!
                if (<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize) {
                    makePageSystem('schedules',
                        '<?=$this->index_name?>',
                        <?=$this->order_prepend?>totalcount,
                        <?=$this->index_name?>,
                        <?=$this->order_prepend?>pagesize,
                        'loadSchedules()'
                    );
                } else {
                    hidePageSystem('schedules');
                }
                eval('schedules_loading_flag = false');
            }


            function handleScheduleListClick(id) {
                displayAddScheduleDialog(id);
            }

            function displayAddScheduleDialog(id) {
                var objname = 'dialog-modal-add-schedule';
                if (id > 0) {
                    $('#' + objname).dialog("option", "title", 'Editing schedule # ' + id + ' ');
                } else {
                    $('#' + objname).dialog("option", "title", 'Adding new schedule');
                }
                $('#' + objname).dialog("open");
                $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                // TODO - where is this coming from?
                $('#' + objname).load("index.php?area=employee_hours&sub_area=schedules&add_schedule=" + id + "&printable=1&no_script=1");
            }

            function resetSchedulesSearchForm(frm) {
                $('frm').trigger('reset');
            }

            var schedulessrchtog = true;
            function toggleSchedulesSearch() {
                schedulessrchtog = !schedulessrchtog;
                ieDisplay('schedules_search_table', schedulessrchtog);
            }
        </script>
        <! *** BEGIN ONEUI STYLING REWORK -->
        <div class="block">
            <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>" onsubmit="loadSchedules();return false;">
                <! ** BEGIN BLOCK HEADER -->
                <div class="block-header bg-primary-light">
                    <h4 class="block-title">Schedules</h4>
                    <button type="button" value="Add" title="Add Rules" class="btn btn-sm btn-primary" onclick="displayAddScheduleDialog(0)">Add</button>
                    <button type="button" value="Search" title="Toggle Search" class="btn btn-sm btn-primary" onclick="toggleSchedulesSearch();">Toggle Search</button>
                    <div id="schedules_prev_td" class="page_system_prev"></div>
                    <div id="schedules_page_td" class="page_system_page"></div>
                    <div id="schedules_next_td" class="page_system_next"></div>
                    <select title="Rows Per Page" class="custom-select-sm" name="<?=$this->order_prepend?>pagesize" id="<?=$this->order_prepend?>pagesizeDD" onchange="<?=$this->index_name?>=0;loadSchedules(); return false;">
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
                <div class="bg-info-light" id="schedules_search_table">
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="searching_schedules"/>
                        <?= makeCompanyDD('s_company_id', htmlentities($_REQUEST['s_company_id']), 'loadSchedules();', '[Select Company]') ?>
                        <?= makeOfficeDD('s_office_id', htmlentities($_REQUEST['s_office_id']), 'form-control custom-select-sm', 'loadSchedules();','[Select Office]') ?>
                        <button type="submit" value="Search" title="Search Rules" class="btn btn-sm btn-primary" name="the_Search_button" onclick="loadSchedules();return false;">Search</button>
                        <button type="button" value="Reset" title="Reset Search Criteria" class="btn btn-sm btn-primary" onclick="resetSchedulesSearchForm(this.form);resetPageSystem('<?= $this->index_name ?>');loadSchedules();return false;">Reset</button>
                    </div>
                </div>
                <! ** END BLOCK SEARCH TABLE -->
                <! ** BEGIN BLOCK LIST (DATATABLE) -->
                <div class="block-content">
                    <table class="table table-sm table-striped" id="schedule_table">
                        <caption id="current_time_span" class="small text-right">Server Time: <?=date("g:ia m/d/Y T")?></caption>
                        <tr>
                            <th class="row2 text-left"><?= $this->getOrderLink('name') ?>Schedule</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('company_name') ?>Company</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('office_name') ?>Office</a></th>
<!--                            <th class="row2 text-left">--><?//= $this->getOrderLink('start_offset') ?><!--Start Time</a></th>-->
<!--                            <th class="row2 text-left">--><?//= $this->getOrderLink('end_offset') ?><!--End Time</a></th>-->
                            <th class="row2 text-center">&nbsp;</th>
                        </tr>
                    </table>
                </div>
                <! ** END BLOCK LIST (DATATABLE) -->
            </form>
        </div>
        <! *** END ONEUI STYLING REWORK -->
        <div id="dialog-modal-add-schedule" title="Adding new schedule" class="nod"></div>
        <script>
            $("#dialog-modal-add-schedule").dialog({
                autoOpen: false,
                width: 'auto',
                modal: false,
                draggable: true,
                resizable: false,
                position: {my: 'center', at: 'center'},
                containment: '#main-container'
            });
            $("#dialog-modal-add-schedule").closest('.ui-dialog').draggable("option","containment","#main-container");
            loadSchedules();
        </script>
        <?
    }


    function makeAdd($id)
    {
        $id = intval($id);
        if ($id) {
            $row = $_SESSION['dbapi']->schedules->getByID($id);
        }
        ?>
        <script>
            function validateSchedulesField(name, value, frm) {
                // console.log(name, value);
                // alert(name+","+value);
                switch (name) {
                    default:
                        // ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
                        return true;
                        break;
                }
                return true;
            }

            function checkSchedulesFrm(frm) {
                var params = getFormValues(frm, 'validateSchedulesField');
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
                        url: 'api/api.php?get=schedules&mode=xml&action=edit',
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
                            loadSchedules();
                            displayAddScheduleDialog(res);
                            alert(result['message']);
                        }
                    });
                }
                return false;
            }
            // SET TITLEBAR
            $('#dialog-modal-add-schedule').dialog("option", "title", '<?=($id) ? 'Editing schedule # ' . htmlentities($row['id']) : 'Adding new schedule'?>');
        </script>
        <form method="POST" action="<?= stripurl('') ?>" autocomplete="off" onsubmit="checkSchedulesFrm(this); return false">
            <input type="hidden" id="adding_schedule" name="adding_schedule" value="<?= $id ?>">
            <table border="0" align="center">
                <tr>
                    <th colspan="2" align="left" height="30">Name:</th>
                    <td colspan="2">
                        <input type="text" name="name" id="name" value="<?=htmlentities($row['name']);?>" />
                    </td>
                </tr>
                <tr>
                    <th colspan="2" align="left" height="30">Company:</th>
                    <td colspan="2"><?=makeCompanyDD('company_id', intval($row['company_id']), '', 'Default [All]')?></td>
                </tr>
                <tr>
                    <th colspan="2" align="left" height="30">Office:</th>
                    <td colspan="2"><?=makeOfficeDD('office_id', intval($row['office_id']), 'form-control custom-select-sm', '','Default [All]')?></td>
                </tr>
                <tr>
                    <th colspan="2" align="left" height="30">User Group(s):</th>
                    <td colspan="2"><?= makeUserGroupDD('user_groups[]', htmlentities($row['user_groups']), 'form-control custom-select-sm', '', 10, false); ?></td>
                </tr>
                <tr>
                    <th colspan="2" align="left" height="30">Start Time:</th>
                    <td colspan="2"><?php echo makeTimebar("start_offset_", 2, NULL, false, intval($row['start_time'])); ?></td>
                </tr>
                <tr>
                    <th align="left" height="30" colspan="2">End Time:</th>
                    <td colspan="2"><?php echo makeTimebar("end_offset_", 2, NULL, false, intval($row['end_time'])); ?></td>
                </tr>
                <tr>
                    <th align="left" height="84" colspan="2" valign="top">Days:</th>
                    <td align="left" valign="top" height="84">
                        Sunday<br/>
                        Monday<br/>
                        Tuesday<br/>
                        Wednesday<br/>
                        Thursday<br/>
                        Friday<br/>
                        Saturday<br/>
                    </td>
                    <td align="left" valign="top" height="84">
                        <input type="checkbox" value="yes" name="work_sun" <?=($row['work_sun'] == 'yes' ? ' checked' : '')?> /><br/>
                        <input type="checkbox" value="yes" name="work_mon" <?=($row['work_mon'] == 'yes' ? ' checked' : '')?> /><br/>
                        <input type="checkbox" value="yes" name="work_tues" <?=($row['work_tues'] == 'yes' ? ' checked' : '')?> /><br/>
                        <input type="checkbox" value="yes" name="work_wed" <?=($row['work_wed'] == 'yes' ? ' checked' : '')?> /><br/>
                        <input type="checkbox" value="yes" name="work_thurs" <?=($row['work_thurs'] == 'yes' ? ' checked' : '')?> /><br/>
                        <input type="checkbox" value="yes" name="work_fri" <?=($row['work_fri'] == 'yes' ? ' checked' : '')?> /><br/>
                        <input type="checkbox" value="yes" name="work_sat" <?=($row['work_sat'] == 'yes' ? ' checked' : '')?> /><br/>
                    </td>
                </tr>
                <tr>
                    <th colspan="4" class="text-left">
                        <button type="button" title="Configure Additional Hours Rules" class="btn btn-sm btn-warning" onclick="$('#dialog-modal-add-schedule').dialog('close');loadSection('?area=employee_hours&sub_area=companies_rules&s_company_id=<?=intval($row['company_id']);?>&no_script=1');return false;">Edit Company Rules</button>
                    </th>
                </tr>
                <tr>
                    <th colspan="4" class="text-center"><button class="btn btn-sm btn-primary" type="submit">Save Changes</button></th>
                </tr>
        </form>
        </table>
        <?
    }

    function getOrderLink($field)
    {
        $var = '<a href="#" onclick="setOrder(\'' . addslashes($this->order_prepend) . '\',\'' . addslashes($field) . '\',';
        $var .= "((" . $this->order_prepend . "orderdir == 'DESC')?'ASC':'DESC')";
        $var .= ");loadSchedules();return false;\">";
        return $var;
    }
}