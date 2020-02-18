<? /***************************************************************
 *    Process Tracker Schedules GUI
 ***************************************************************/

$_SESSION['process_tracker_schedules'] = new ProcessTrackerSchedules;


class ProcessTrackerSchedules
{


    var $table = 'process_tracker_schedules';        ## Class main table to operate on
    var $orderby = 'id';                            ## Default Order field
    var $orderdir = 'DESC';                            ## Default order direction


    ## Page  Configuration
    var $pagesize = 20;                        ## Adjusts how many items will appear on each page
    var $index = 0;                        ## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

    var $index_name = 'pts_list';        ## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
    var $frm_name = 'ptsnextfrm';

    var $order_prepend = 'pts_';        ## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING


    function ProcessTrackerSchedule()
    {

        include_once("db.inc.php");


        $this->handlePOST();

    }


    function handlePOST()
    {

    }

    function handleFLOW()
    {


        if (!checkAccess('process_tracker_schedules')) {


            accessDenied("Process Tracker Schedules GUI");

            return;

        } else {

            # Handle flow, based on query string
            if (isset($_REQUEST['add_schedule'])) {

                $this->makeAdd($_REQUEST['add_schedule']);

            } else {

                $this->listEntrys();

            }

        }

    }

    function makeProcessCodeDD($name, $sel, $class, $onchange, $required = 0, $blank_option = 1)
    {

        $out = '<select name="' . $name . '" id="' . $name . '" ';
        $out .= ($class) ? ' class="' . $class . '" ' : '';
        $out .= ($onchange) ? ' onchange="' . $onchange . '" ' : '';
        $out .= ($required) ? ' required' : '';
        $out .= '>';

        if ($blank_option) {
            $out .= '<option value="" ' . (($sel == '') ? ' SELECTED ' : '') . '>' . ((!is_numeric($blank_option)) ? $blank_option : "") . '</option>';
        }

        $res = query("SELECT DISTINCT(`process_code`) AS `process_code` FROM `process_tracker` ORDER BY `process_code` ASC", 1);


        while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {


            $out .= '<option value="' . htmlentities($row['process_code']) . '" ';
            $out .= ($sel == $row['process_code']) ? ' SELECTED ' : '';
            $out .= '>' . htmlentities($row['process_code']) . '</option>';


        }

        $out .= '</select>';
        return $out;
    }


    function listEntrys()
    {


        ?>
        <script>

            var schedule_delmsg = 'Are you sure you want to delete this schedule?';

            var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
            var <?=$this->order_prepend?>orderdir = "<?=$this->orderdir?>";


            var <?=$this->index_name?> =
            0;
            var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;


            var SchedulesTableFormat = [
                ['id', 'align_left'],
                ['enabled', 'align_left'],
                ['schedule_name', 'align_left'],
                ['script_process_code', 'align_left'],
                ['script_frequency', 'align_left'],
                ['[time:last_success]', 'align_left'],
                ['[time:last_failed]', 'align_left'],
                //['[time:time_start]','align_left'],
                ['[delete]', 'align_center']
            ];

            /**
             * Build the URL for AJAX to hit, to build the list
             */
            function getSchedulesURL() {

                var frm = getEl('<?=$this->frm_name?>');

                return 'api/api.php' +
                    "?get=process_tracker_schedules&" +
                    "mode=xml&" +

                    's_id=' + escape(frm.s_id.value) + "&" +
                    's_enabled=' + escape(frm.s_enabled.value) + "&" +
                    's_schedule_name=' + escape(frm.s_schedule_name.value) + "&" +
                    's_script_process_code=' + escape(frm.s_script_process_code.value) + "&" +
                    's_script_frequency=' + escape(frm.s_script_frequency.value) + "&" +

                    "index=" + (<?=$this->index_name?> * <?=$this->order_prepend?>pagesize
            )
                +"&pagesize=" + <?=$this->order_prepend?>pagesize + "&" +
                "orderby=" + <?=$this->order_prepend?>orderby + "&orderdir=" + <?=$this->order_prepend?>orderdir;
            }


            var schedules_loading_flag = false;

            /**
             * Load the Process Tracker Schedules data - make the ajax call, callback to the parse function
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

                $('#total_count_div').html('<img src="images/ajax-loader.gif" height="20" border="0">');

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

                displayViewScheduleDialog(id);

            }


            function displayViewScheduleDialog(id) {
                var objname = 'dialog-modal-view-schedule';
                if (id > 0) {
                    $('#' + objname).dialog("option", "title", 'Editing Schedule');
                } else {
                    $('#' + objname).dialog("option", "title", 'Adding new Schedule');
                }
                $('#' + objname).dialog("open");
                $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                $('#' + objname).load("index.php?area=process_tracker_schedules&add_schedule=" + id + "&printable=1&no_script=1");
            }

            function resetSchedulesForm(frm) {
                // SET FORM VALUES TO BLANK
                frm.s_id.value = '';
                frm.s_enabled.value = '';
                frm.s_schedule_name.value = '';
                frm.s_script_process_code.value = '';
                frm.s_script_frequency.value = '';


            }


        </script>
        <div id="dialog-modal-view-schedule" title="Viewing Schedule" class="nod">
        </div>
        <div class="block">
            <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>" onsubmit="loadSchedules();return false">
                <div class="block-header bg-primary-light">
                    <h4 class="block-title">Process Tracker Schedules</h4>
                    <button type="button" value="Search" title="Add New Schedule" class="btn btn-sm btn-primary" onclick="displayViewScheduleDialog();">Add</button>
                    <div id="schedules_prev_td" class="page_system_prev"></div>
                    <div id="schedules_page_td" class="page_system_page"></div>
                    <div id="schedules_next_td" class="page_system_next"></div>
                    <select title="Rows Per Page" class="custom-select-sm" name="<?= $this->order_prepend ?>pagesize" id="<?= $this->order_prepend ?>pagesizeDD" onchange="<?= $this->index_name ?>=0;loadSchedules(); return false;">
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
                <div class="bg-info-light" id="schedule_search_table">
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="searching_schedule"/>
                        <input type="text" class="form-control" placeholder="ID.." name="s_id" size="2" value="<?= htmlentities($_REQUEST['s_id']) ?>">
                        <select class="form-control custom-select-sm" name="s_enabled" id="s_enabled">
                            <option value="">[Enabled?]</option>
                            <option value="yes">yes</option>
                            <option value="no">no</option>
                        </select>
                        <input type="text" class="form-control" placeholder="Schedule Name.." name="s_schedule_name" size="15" value="<?= htmlentities($_REQUEST['s_schedule_name']) ?>">
                        <?= $this->makeProcessCodeDD('s_script_process_code', $_REQUEST['s_script_process_code'], 'form-control custom-select-sm', '', "", "[Select Process Code]"); ?>
                        <select class="form-control custom-select-sm" name="s_script_frequency" id="s_script_frequency">
                            <option value="">[Select Frequency]</option>
                            <option value="hourly">hourly</option>
                            <option value="daily">daily</option>
                            <option value="weekly">weekly</option>
                            <option value="monthly">monthly</option>
                        </select>
                        <button type="submit" value="Search" title="Search Schedules" class="btn btn-sm btn-primary" name="the_Search_button" onclick="loadSchedules();return false;">Search</button>
                        <button type="button" value="Reset" title="Reset Search Criteria" class="btn btn-sm btn-primary" onclick="resetSchedulesForm(this.form);resetPageSystem('<?= $this->index_name ?>');loadSchedules();return false;">Reset</button>
                    </div>
                </div>
                <div class="block-content">
                    <table class="table table-sm table-striped" id="schedule_table">
                        <caption id="current_time_span" class="small text-right">Server Time: <?= date("g:ia m/d/Y T") ?></caption>
                        <tr>
                            <th class="row2" align="left"><?= $this->getOrderLink('id') ?>ID</a></th>
                            <th class="row2" align="left"><?= $this->getOrderLink('enabled') ?>Enabled</a></th>
                            <th class="row2" align="left"><?= $this->getOrderLink('schedule_name') ?>Schedule Name</a></th>
                            <th class="row2" align="left"><?= $this->getOrderLink('script_process_code') ?>Script Process Code</a></th>
                            <th class="row2" align="left"><?= $this->getOrderLink('script_frequency') ?>Script Frequency</a></th>
                            <th class="row2" align="left"><?= $this->getOrderLink('last_success') ?>Last Success</a></th>
                            <th class="row2" align="left"><?= $this->getOrderLink('last_failed') ?>Last Failed</a></th>
                        </tr>
                    </table>
                </div>
            </form>
        </div>
        <script>

            $("#dialog-modal-view-schedule").dialog({
                autoOpen: false,
                width: 550,
                height: 'auto',
                modal: false,
                draggable: true,
                resizable: false,
                position: {my: 'center', at: 'center'},
                containment: '#main-container'
            });
            loadSchedules();

        </script><?


    }


    function makeAdd($id)
    {

        $id = intval($id);


        if ($id) {

            $row = $_SESSION['dbapi']->process_tracker->getScheduleByID($id);

        }

        ?>
        <script>

            $(function () {
                var requiredCheckboxes = $('.dayofweek_options :checkbox[required]');
                requiredCheckboxes.change(function () {
                    if (requiredCheckboxes.is(':checked')) {
                        requiredCheckboxes.removeAttr('required');
                    } else {
                        requiredCheckboxes.attr('required', 'required');
                    }
                });
            });

            function submitScheduleFrm(frm) {

                var params = getFormValues(frm, '');

                $.ajax({
                    type: "POST",
                    cache: false,
                    url: 'api/api.php?get=process_tracker_schedules&mode=xml&action=edit',
                    data: params,
                    error: function () {
                        alert("Error saving process tracker schedule form. Please contact an admin.");
                    },
                    success: function (msg) {

                        var result = handleEditXML(msg);
                        var res = result['result'];

                        if (res <= 0) {

                            alert(result['message']);

                            return;

                        }

                        loadSchedules();

                        displayViewScheduleDialog(res);

                        alert(result['message']);

                    }


                });

                return false;

            }

            function toggleDayMode(way) {

                var requiredCheckboxes = $('.dayofweek_options :checkbox');

                if (way == 'hourly') {
                    $('#day_of_week_tr').hide();
                    $('#day_of_month_tr').hide();
                    $('#time_end_tr').hide();
                    requiredCheckboxes.removeAttr('required');

                } else if (way == 'daily') {

                    $('#day_of_week_tr').hide();
                    $('#day_of_month_tr').hide();
                    $('#time_end_tr').show();
                    requiredCheckboxes.removeAttr('required');

                } else if (way == 'weekly') {

                    $('#day_of_week_tr').show();
                    $('#day_of_month_tr').hide();
                    $('#time_end_tr').show();
                    if (requiredCheckboxes.is(':checked')) {
                        requiredCheckboxes.removeAttr('required');
                    } else {
                        requiredCheckboxes.attr('required', 'required');
                    }

                } else {

                    $('#day_of_week_tr').hide();
                    $('#day_of_month_tr').show();
                    $('#time_end_tr').show();
                    requiredCheckboxes.removeAttr('required');

                }

            }

            // SET TITLEBAR
            $('#dialog-modal-view-schedule').dialog("option", "title", '<?=($id) ? 'Editing Schedule #' . $id . ' - ' . htmlentities($row['schedule_name']) : 'Adding new Schedule'?>');

            <?=($id) ? 'toggleDayMode(\'' . $row['script_frequency'] . '\');' : ''?>


        </script>

        <form method="POST" id="pts_add_frm" action="<?= stripurl('') ?>" autocomplete="off" onsubmit="submitScheduleFrm(this); return false">

            <input type="hidden" id="adding_schedule" name="adding_schedule" value="<?= $id ?>">

            <table border="0" width="100%">
                <tr>
                    <th align="left" height="30">Enabled:</th>
                    <td><input type="checkbox" name="enabled" value="yes" <?= ($row['enabled'] == 'yes') ? " CHECKED " : '' ?>></td>
                </tr>
                <tr>
                    <th align="left" height="30">Schedule Name:</th>
                    <td><input name="schedule_name" type="text" size="50" value="<?= htmlentities($row['schedule_name']) ?>" required placeholder="Enter a name for this schedule."></td>
                </tr>
                <tr>
                    <th align="left" height="30">Script Process Code:</th>
                    <td><?

                        echo $this->makeProcessCodeDD('script_process_code', $row['script_process_code'], '', '', 1);

                        ?></td>
                </tr>
                <tr>
                    <th align="left" height="30">Script Frequency:</th>
                    <td>
                        <input type="radio" name="script_frequency" value="hourly" onchange="toggleDayMode(this.value);" <?= ($row['script_frequency'] == 'hourly') ? " CHECKED " : '' ?> required>Hourly
                        <input type="radio" name="script_frequency" value="daily" onchange="toggleDayMode(this.value);" <?= ($row['script_frequency'] == 'daily') ? " CHECKED " : '' ?>>Daily
                        <input type="radio" name="script_frequency" value="weekly" onchange="toggleDayMode(this.value);" <?= ($row['script_frequency'] == 'weekly') ? " CHECKED " : '' ?>>Weekly
                        <input type="radio" name="script_frequency" value="monthly" onchange="toggleDayMode(this.value);" <?= ($row['script_frequency'] == 'monthly') ? " CHECKED " : '' ?>>Monthly
                    </td>
                </tr>
                <tr class="nod" id="day_of_week_tr">
                    <th align="left" height="30">Day(s) of Week:</th>
                    <td>
                        <div class="form-group dayofweek_options">
                            <input type="checkbox" name="time_dayofweek[]" value="mon" <?= (in_array('mon', explode(",", $row['time_dayofweek']))) ? " CHECKED " : "" ?> required>Mon
                            <input type="checkbox" name="time_dayofweek[]" value="tue" <?= (in_array('tue', explode(",", $row['time_dayofweek']))) ? " CHECKED " : "" ?> required>Tue
                            <input type="checkbox" name="time_dayofweek[]" value="wed" <?= (in_array('wed', explode(",", $row['time_dayofweek']))) ? " CHECKED " : "" ?> required>Wed
                            <input type="checkbox" name="time_dayofweek[]" value="thu" <?= (in_array('thu', explode(",", $row['time_dayofweek']))) ? " CHECKED " : "" ?> required>Thu
                            <input type="checkbox" name="time_dayofweek[]" value="fri" <?= (in_array('fri', explode(",", $row['time_dayofweek']))) ? " CHECKED " : "" ?> required>Fri
                            <input type="checkbox" name="time_dayofweek[]" value="sat" <?= (in_array('sat', explode(",", $row['time_dayofweek']))) ? " CHECKED " : "" ?> required>Sat
                            <input type="checkbox" name="time_dayofweek[]" value="sun" <?= (in_array('sun', explode(",", $row['time_dayofweek']))) ? " CHECKED " : "" ?> required>Sun
                        </div>
                    </td>
                </tr>
                <tr class="nod" id="day_of_month_tr">
                    <th align="left" height="30">Day Of Month:</th>
                    <td><?

                        echo getDayDD("time_dayofmonth", $row['time_dayofmonth'], " onchange=\"\" ");

                        ?></td>
                </tr>
                <tr>
                    <th align="left" height="30">Start Time:</th>
                    <td><?

                        if (isset($row['time_start'])) {

                            $time_start_sel = explode(":", $row['time_start']);
                            echo makeTimebar("time_start", 2, $time_start_sel, false, 0, " onchange=\"\" ");

                        } else {

                            echo makeTimebar("time_start", 2, null, false, time(), " onchange=\"\" ");

                        }


                        ?></td>
                </tr>
                <tr class="nod" id="time_end_tr">
                    <th align="left" height="30">End Time:</th>
                    <td><?
                        if (isset($row['time_end'])) {

                            $time_end_sel = explode(":", $row['time_end']);
                            echo makeTimebar("time_end", 2, $time_end_sel, false, 0, " onchange=\"\" ");

                        } else {

                            echo makeTimebar("time_end", 2, null, false, time() + 3600, " onchange=\"\" ");

                        }


                        ?></td>
                </tr>
                <tr>
                    <th align="left" height="30">Time Margin:</th>
                    <td><select name="time_margin">
                            <option value="0" <?= (intval($row['time_margin']) == 0) ? " SELECTED " : '' ?>>0 minutes</option>
                            <option value="5" <?= (intval($row['time_margin']) == 5) ? " SELECTED " : '' ?>>+5 minutes</option>
                            <option value="10" <?= intval(($row['time_margin']) == 10) ? " SELECTED " : '' ?>>+10 minutes</option>
                            <option value="15" <?= (intval($row['time_margin']) == 15) ? " SELECTED " : '' ?>>+15 minutes</option>
                            <option value="30" <?= (intval($row['time_margin']) == 30) ? " SELECTED " : '' ?>>+30 minutes</option>
                        </select></td>
                </tr>
                <tr>
                    <th align="left" height="30">Alert Email:</th>
                    <td><input name="notification_email" type="email" size="50" value="<?= htmlentities($row['notification_email']) ?>" required placeholder="Enter a valid email address."></td>
                </tr>
                <tr>
                    <th colspan="2" align="center" height="50">

                        <input type="submit" value="Save Changes">

                    </th>
                </tr>
            </table>

        </form>

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


