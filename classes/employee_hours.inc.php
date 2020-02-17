<? /***************************************************************
*    Employee hours - A quick tool for whipping employees hours ass, with a belt
*    Written By: Jonathan Will
***************************************************************/

$_SESSION['employee_hours'] = new EmployeeHours;


class EmployeeHours
{
	
	
	var $table = 'employee_hours';            ## Classes main table to operate on
	var $orderby = 'username';        ## Default Order field
	var $orderdir = 'ASC';    ## Default order direction
	
	
	## Page  Configuration
	var $frm_name = 'empnextfrm';
	var $index_name = 'emp_list';
	var $order_prepend = 'emp_';                ## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING
	
	## Page  Configuration
	var $pagesize = 50;    ## Adjusts how many items will appear on each page
	var $index = 0;        ## You dont really want to mess with this variable. Index is adjusted by code, to change the pages
	
	
	function EmployeeHours()
	{
		
		
		## REQURES DB CONNECTION!
		include_once($_SESSION['site_config']['basedir'] . "/utils/db_utils.php");
		
		
		$this->handlePOST();
	}
	
	
	function handlePOST()
	{
		
		// THIS SHIT IS MOTHERFUCKIGN AJAXED TO THE TEETH
		// SEE api/employee_hour.api.php FOR POST HANDLING!
		// <3 <3 -Jon
		
	}
	
	function handleFLOW()
	{
		# Handle flow, based on query string
		
		if (!checkAccess('employee_hours')) {
			
			
			accessDenied("Employee Hours");
			
			return;
			
		} else {
			if (isset($_REQUEST['edit_hours'])) {
				
				$this->makeEdit(intval($_REQUEST['edit_hours']));
				
			} else {
				$this->listEntrys();
			}
		}
		
	}
	
	
	function makeEdit($id, $sub = '')
	{
		
		$id = intval($id);
		
		// RING DING DONG, RINGA DING DING DONG, west side.
		
		
		?>
        <script>

            function validateHourField(name, value, frm) {

                //alert(name+","+value);


                switch (name) {
                    default:

                        // ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
                        return true;
                        break;

                    case 'hours':

                        var tmpval = parseFloat(value);

                        if (tmpval <= 0) return false;

                        return true;
                        break;

                    //case 'agent_id':
                    case 'campaign_id':
                    case 'cluster_id':
                    case 'office_id':
                    case 'user_group':


                        if (!value) return false;

                        return true;
                        break;


                }
                return true;
            }


            function checkHourForm(frm) {


                var params = getFormValues(frm, 'validateHourField');


                // FORM VALIDATION FAILED!
                // param[0] == field name
                // param[1] == field value
                if (typeof params == "object") {

                    switch (params[0]) {
                        default:

                            alert("Error submitting form. Check your values");

                            break;

                        case 'cluster_id':

                            alert("Please select the cluster for this agent.");
                            eval('try{frm.' + params[0] + '.select();}catch(e){}');
                            break;


                        case 'hours':

                            alert("Please enter the hours for this agent.");
                            eval('try{frm.' + params[0] + '.select();}catch(e){}');
                            break;

                        case 'agent_id':

                            alert("Please select the agent for these hours.");
                            eval('try{frm.' + params[0] + '.select();}catch(e){}');
                            break;

                        case 'office_id':
                            alert("Please select the office for the agent.");
                            eval('try{frm.' + params[0] + '.select();}catch(e){}');
                            break;

                        case 'user_group':
                            alert("Please select the user group for the agent.");
                            eval('try{frm.' + params[0] + '.select();}catch(e){}');
                            break;


                        case 'campaign_id':

                            alert("Please select the campaign for this entry.");
                            eval('try{frm.' + params[0] + '.select();}catch(e){}');
                            break;
                    }

                    // SUCCESS - POST AJAX TO SERVER
                } else {


                    //alert("Form validated, posting");

                    $.ajax({
                        type: "POST",
                        cache: false,
                        url: 'api/api.php?get=employee_hours&mode=xml&action=add',
                        data: params,
                        error: function () {
                            alert("Error saving hours form. Please contact an admin.");
                        },
                        success: function (msg) {


                            var result = handleEditXML(msg);
                            var res = result['result'];

                            if (res <= 0) {

                                alert(result['message']);

                                return;

                            }


                            loadEmps();


                            $('#dialog-modal-edit_emp').dialog("close");

                            //displayEditLeadDialog(res);

                            alert(result['message']);

                        }


                    });

                }

                return false;

            }


            function addMoreUsers(cnt) {

                for (var x = 0; x < cnt; x++) {

                    $('#additional_users_span').append('<input type="text" size="5" name="agent_id[]" id="agent_id" /><br />');

                }

                applyUniformity();
            }


        </script>
        <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>" onsubmit="checkHourForm(this);return false">
            <input type="hidden" name="adding_hours" value="<?= $id ?>">
            <table border="0" align="center">
                <tr>
                    <th align="left">Agent:</th>
                    <td>
                        <input type="text" size="5" name="agent_id[]" id="agent_id"/>&nbsp;<input type="button" value="Add more users" onclick="addMoreUsers(5)"/><br/>
                        <span id="additional_users_span"></span></td>
                </tr>
                <tr>
                    <th align="left">Cluster:</th>
                    <td><?= makeClusterDD('cluster_id', $_REQUEST['cluster_id'], '', "", " "); ?></td>
                </tr>
                <tr>
                    <th align="left">Office:</th>
                    <td><?= makeOfficeDD("office_id", $_REQUEST['office_id'], '', "", " "); ?></td>
                </tr>
                <tr>
                    <th align="left">Campaign:</th>
                    <td><?= makeCampaignDD('campaign_id', $_REQUEST['campaign_id'], '', "", " "); ?></td>
                </tr>
                <tr>
                    <th align="left">Vici Group:</th>
                    <td><?= makeViciUserGroupDD("user_group", $_REQUEST['user_group'], '', "", 0, " "); ?></td>
                </tr>
                <tr>
                    <th align="left">Date:</th>
                    <td><?= makeTimebar("date_", 1, null, false, time(), ""); ?></td>
                </tr>
                <tr>
                    <th align="left">Paid Hours:</th>
                    <td><input type="text" name="hours" value="0.0" size="5"></td>
                </tr>
                <tr>
                    <th align="left">Notes:</th>
                    <td><input type="text" name="notes" value="" size="30"></td>
                </tr>
                <tr>
                    <td colspan="2" align="center">
                        <input type="submit" value="Add Hours">
                    </td>
                </tr>
            </table>
        </form>
        </table>
        <?
    }


    function listEntrys()
    {

        ?>
        <script>

            var emp_delmsg = 'Are you sure you want to delete this record?';

            var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
            var <?=$this->order_prepend?>orderdir = "<?=$this->orderdir?>";
            var <?=$this->index_name?> = 0;
            var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;


            var EmpsTableFormat = [
                ['[date:time_started]', 'align_center'],
                ['username', 'align_left'],
                ['office', 'align_center'],
                ['call_group', 'align_center'],
                ['[render:hours_from_min:activity_time]', 'align_center'],
                ['[render:hours_from_sec:seconds_INCALL:seconds_READY:seconds_QUEUE]', 'align_center'],
                ['[render:breakdown_hours_from_sec:In Call,seconds_INCALL:Ready,seconds_READY:Queue,seconds_QUEUE:Paused,seconds_PAUSED]', 'align_center'],
                    <?
                    if(!checkAccess('employee_hours_edit')){
                    ?>['[render:hours_from_min:paid_time]', 'align_center'],
                ['note_data', 'align_left'], <?
                    }else{
                    ?>['[render:editable_hours_from_min:paid_time]', 'align_center'],
                ['[textfield:notes:note_data:30]', 'align_left'],<?

                }
                ?>
            ];


            /**
             * Build the URL for AJAX to hit, to build the list
             */
            function getEmpsURL(csv_mode, report_mode) {

                var frm = getEl('<?=$this->frm_name?>');

                var user_group_txt = "";

                var x = 0;
                $('#s_user_group :selected').each(function (i, selected) {
                    if (x++ > 0) user_group_txt += "|";
                    user_group_txt += $(selected).val();
                });
                return 'api/api.php' +
                    "?get=employee_hours&" +
                    "mode=" + ((csv_mode) ? "csv" : "xml") + "&" +
                    "report_mode=" + report_mode + "&" +
                    's_main_users=' + escape(frm.s_main_users.checked) + "&" +
                    's_username=' + escape(frm.s_agent_id.value) + "&" +
                    's_office_id=' + escape(frm.s_office_id.value) + "&" +
                    's_user_group=' + escape(user_group_txt) + "&" + //frm.s_user_group.value
                    'stime_month=' + escape(frm.stime_month.value) + "&" + 'stime_day=' + escape(frm.stime_day.value) + "&" + 'stime_year=' + escape(frm.stime_year.value) + "&" +
                    'etime_month=' + escape(frm.etime_month.value) + "&" + 'etime_day=' + escape(frm.etime_day.value) + "&" + 'etime_year=' + escape(frm.etime_year.value) + "&" +
                    's_date_mode=' + escape(frm.s_date_mode.value) + "&" +
                    's_show_problems=' + escape(frm.s_show_problems.checked) + "&" +
                    "index=" + (<?=$this->index_name?> * <?=$this->order_prepend?>pagesize
            )
                +"&pagesize=" + <?=$this->order_prepend?>pagesize + "&" +
                "orderby=" + <?=$this->order_prepend?>orderby + "&orderdir=" + <?=$this->order_prepend?>orderdir;
            }


            var emps_loading_flag = false;
            var page_load_start;

            /**
             * Load the name data - make the ajax call, callback to the parse function
             */
            function loadEmps() {

                // ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
                var val = null;
                eval('val = emps_loading_flag');


                // CHECK IF WE ARE ALREADY LOADING THIS DATA
                if (val == true) {

                    //console.log("Employee hours ALREADY LOADING (BYPASSED) \n");
                    return;
                } else {

                    eval('emps_loading_flag = true');
                }

                page_load_start = new Date();


                $('#total_count_div').html('<img src="images/ajax-loader.gif" height="20" border="0">');


                loadAjaxData(getEmpsURL(), 'parseEmps');


            }


            /**
             * CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
             */
            var <?=$this->order_prepend?>totalcount = 0;

            function parseEmps(xmldoc) {

                <?=$this->order_prepend?>totalcount = parseXMLData('emp', EmpsTableFormat, xmldoc);


                var enddate = new Date();

                var loadtime = enddate - page_load_start;

                $('#page_load_time').html("Load and render time: " + loadtime + "ms");


                // ACTIVATE PAGE SYSTEM!
                //if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){


                makePageSystem('emps',
                    '<?=$this->index_name?>',
                    <?=$this->order_prepend?>totalcount,
                    <?=$this->index_name?>,
                    <?=$this->order_prepend?>pagesize,
                    'loadEmps()'
                );

                //}else{

                //	hidePageSystem('emps');

                //}


                eval('emps_loading_flag = false');


                highlightHoursProblems();

                setActivityHoursTotals();

                setPaidHoursTotals();
            }


            function handleEmpListClick(id) {

                //displayEditEmpDialog(id);

            }

            function displayEditEmpDialog(id, sub) {

                var objname = 'dialog-modal-edit_emp';


                if (id > 0) {
                    $('#' + objname).dialog("option", "title", 'Editing Record #' + id);
                } else {
                    $('#' + objname).dialog("option", "title", 'Adding new Record');
                }


                $('#' + objname).dialog("open");

                $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');


                if (sub) {

                    $('#' + objname).load("index.php?area=employee_hours&edit_hours=" + id + "&sub=" + sub + "&printable=1&no_script=1");
                } else {

                    $('#' + objname).load("index.php?area=employee_hours&edit_hours=" + id + "&printable=1&no_script=1");
                }

                $('#' + objname).dialog('option', 'position', 'center');

            }

            function resetEmpForm(frm) {
                frm.reset();
                frm.s_agent_id.value = '';
                frm.s_office_id.value = '';
                frm.s_user_group.value = '';
                toggleDateMode(frm.s_date_mode.value);
            }

            var empsrchtog = false;

            function toggleEmpSearch() {
                empsrchtog = !empsrchtog;
                ieDisplay('emp_search_table', empsrchtog);
            }

            function processListSubmit(frm) {
                var obj = null;
                var id_data = "";
                var hours_data = "";
                var notes_data = "";
                $('#total_count_div').html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                window.location = '#header_anchor';
                for (var x = 0; (obj = getEl('activity_id_' + x)) != null; x++) {
                    id_data += obj.value + "\t";
                    hours_data += getEl('paid_hour_' + x).value + ":" + getEl('paid_min_' + x).value + "\t";
                    notes_data += getEl('note_data_' + x).value + "||";
                }
                frm.activity_ids.value = id_data;
                frm.activity_hours.value = hours_data;
                frm.activity_notes.value = notes_data;
                var params = getFormValues(frm, '');
                $.ajax({
                    type: "POST",
                    cache: false,
                    url: 'api/api.php?get=employee_hours&mode=xml&action=edit',
                    data: params,
                    error: function () {
                        alert("Error saving lead form. Please contact an admin.");
                    },
                    success: function (msg) {
                        var result = handleEditXML(msg);
                        var res = result['result'];

                        if (res <= 0) {

                            alert(result['message']);

                            return;

                        }


                        loadEmps();

                        alert(result['message']);


                    }


                });


                //alert(params);


                return false;

            }


            function highlightHoursProblems() {
                var obj = null;

                var paid_hours = 0, detected_hours = 0, activity_id;
                for (var x = 0; (obj = getEl('activity_id_' + x)) != null; x++) {

                    activity_id = obj.value;

                    //paid_hours = getEl('paid_time_'+x).value;

                    var hrs = parseInt(getEl('paid_hour_' + x).value);
                    var min = parseInt(getEl('paid_min_' + x).value);

                    min += (hrs * 60);

                    paid_hours = min / 60;

                    detected_hours = getEl('activity_hours_' + x).value;

                    if (paid_hours > detected_hours) {

                        $("#paid_hours_cell_" + x).attr("class", "align_center error_row");
                    } else {
                        $("#paid_hours_cell_" + x).attr("class", "align_center");
                    }

                }


            }


            function setPageSize(new_size) {

                <?=$this->index_name?> = 0;
                <?=$this->order_prepend?>pagesize = new_size;
                loadEmps();
            }


            function setAllToValue() {


                //	var value = $('#set_all_to_value').val();

                var obj = null;
//				for(var x=0;(obj=getEl('paid_time_'+x)) != null;x++){
//
//					obj.value = value;
//
//				}

                //value = parseFloat(value);

                //var min = value * 60;

                var hours = parseInt($('#paid_hour_setall').val());//Math.floor(min / 60);
                var minutes = parseInt($('#paid_min_setall').val());//parseInt(min % 60);

                if (minutes < 10) {
                    minutes = '0' + minutes;
                }

                for (var x = 0; (obj = getEl('paid_hour_' + x)) != null; x++) {

                    obj.value = hours;

                }

                for (var x = 0; (obj = getEl('paid_min_' + x)) != null; x++) {

                    obj.value = minutes;

                }

            }


            function exportResultsCSV(total_mode) {

                var url = getEmpsURL(true, total_mode);

                window.open(url);
            }

            function toggleDateMode(way) {

                if (way == 'daterange') {
                    // SHOW EXTRA DATE FIELD
                    $('#date2_span').show();
                } else {
                    // HIDE IT
                    $('#date2_span').hide();
                }

            }


            function setActivityHoursTotals() {

                var total = 0;
                var obj = null;
                for (var x = 0; (obj = getEl('activity_hours_' + x)) != null; x++) {

                    total += parseFloat(obj.value);

                }

                var html = "Page Total - Detected OLD(Activity): " + (Math.round(total * 100) / 100) + '<br />';


                total = 0;
                obj = null;
                for (var x = 0; (obj = getEl('new_activity_hours_' + x)) != null; x++) {

                    total += parseFloat(obj.value);

                }


                html += "Page Total - Detected Activity: " + (Math.round(total * 100) / 100) + '<br />';

                $('#spn_total_activity').html(html);

            }

            function setPaidHoursTotals() {

                var total = 0;

                /**        DISABLING THE 'TECHNICALLY CORRECT' way of adding up the hours and minutes, for a proper total.
                 Management decided they preferred the rounded number 2/21/2017

                 var obj = null;
                 var obj2 = null;

                 for(var x=0;(obj = getEl('paid_hour_'+x)) != null;x++){
					obj2 = getEl('paid_min_'+x);

					total += parseInt(obj2.value);

					total += parseInt(obj.value) * 60;

					//total += parseFloat(obj.value);

				}
                 ***/


                var obj = null;

                for (var x = 0; (obj = getEl('paid_ghetto_time_' + x)) != null; x++) {

                    //alert($('#paid_ghetto_time_'+x).html());

                    total += parseFloat($('#paid_ghetto_time_' + x).html());
                }
                $('#spn_total_paid').html("Page Total - Paid: " + (Math.round(total * 100) / 100)); //*100)/100)
            }
        </script>
        <div id="dialog-modal-edit_emp" title="Editing Record"></div>
        <!-- ****START**** THIS AREA REPLACES THE OLD TABLES WITH THE NEW ONEUI INTERFACE BASED ON BOOTSTRAP -->
        <div class="block">
            <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>" onsubmit="loadEmps();return false">
                <input type="hidden" name="searching_emp">
                <div class="block-header bg-primary-light">
                    <h4 class="block-title">Employee Hours</h4>
                    <!--<button type="button" value="Search" title="Toggle Search" class="btn btn-sm btn-primary" onclick="toggleSaleSearch();">Toggle Search</button>-->
                    <? if (checkAccess('employee_hours_edit')) { ?>
                        <button class="btn btn-sm btn-primary" type="button" title="Add Employee Hours" onclick="displayEditEmpDialog(0)">Add Hours</button>
                        <?
                    } ?>
                    <div id="emps_prev_td" class="page_system_prev"></div>
                    <div id="emps_page_td" class="page_system_page"></div>
                    <div id="emps_next_td" class="page_system_next"></div>
                    <select title="Rows Per Page" class="custom-select-sm" name="<?= $this->order_prepend ?>pagesize" id="<?= $this->order_prepend ?>pagesizeDD" onchange="setPageSize(this.value)">
                        <option value="20">20</option>
                        <option value="50"<?=($this->pagesize == 50)?' SELECTED ':''?>>50</option>
                        <option value="100"<?=($this->pagesize == 100)?' SELECTED ':''?>>100</option>
                        <option value="500"<?=($this->pagesize == 500)?' SELECTED ':''?>>500</option>
                    </select>
                    <div class="d-inline-block ml-2">
                        <button class="btn btn-sm btn-dark" title="Total Found">
                            <i class="si si-list"></i>
                            <span class="badge badge-light badge-pill"><div id="total_count_div"></div></span>
                        </button>
                    </div>
                </div>
                <div class="bg-info-light" id="emp_search_table">
                    <div class="form-group form-row">
                        <div class="col-4 input-group input-group-sm">
                            <input type="hidden" name="searching_emps"/>
                            <input type="text" class="form-control" placeholder="Agent ID.." name="s_agent_id" value="<?= htmlentities($_REQUEST['s_agent_id']) ?>"/>
                        </div>
                        <div class="col-4">
                            <?
                            if (($_SESSION['user']['priv'] >= 5) || ($_SESSION['user']['allow_all_offices'] == 'yes')) {
                                echo makeOfficeDD("s_office_id", $_REQUEST['s_office_id'], 'form-control custom-select-sm', $this->index_name . " = 0;loadEmps();", "[Select Office]");
                            } else {
                                ?>
                                <select name="s_office_id" onchange="<?= $this->index_name ?> = 0;loadEmps()">
                                    <option value="">[Select Office]</option>
                                    <?
                                    foreach ($_SESSION['assigned_offices'] as $ofc) {
                                        echo '<option value="' . $ofc . '"';
                                        if ($_REQUEST['s_office_id'] == $ofc) echo ' SELECTED ';
                                        echo '>Office ' . $ofc . '</option>';
                                    }
                                    ?>
                                </select>
                                <?
                            }
                            ?>
                        </div>
                        <div class="col-4 input-group input-group-sm">
                            <?= makeViciUserGroupDD("s_user_group", $_REQUEST['s_user_group'], 'form-control custom-select-sm', $this->index_name . " = 0;loadEmps()", 5, "[Select Group(s)]"); ?>
                        </div>
                    </div>
                    <div class="form-group form-row">
                        <div class="col-4 input-group input-group-sm">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="s_show_problems" onclick="loadEmps();" />
                                <label class="form-check-label" for="s_show_problems">Only Problems</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="s_main_users" onclick="loadEmps();" />
                                <label class="form-check-label" for="s_main_users">Only Main Users</label>
                            </div>
                        </div>
                        <div class="col-4 input-group input-group-sm">
                            <select class="custom-select-sm" title="Select Date Mode" name="s_date_mode" id="date_mode" onchange="toggleDateMode(this.value);">
                                <option value="date">Date Mode</option>
                                <option value="daterange"<?= ($_REQUEST['s_date_mode'] == 'daterange') ? ' SELECTED ' : '' ?>>Date Range Mode</option>
                                <option value="any"<?= ($_REQUEST['s_date_mode'] == 'any') ? ' SELECTED ' : '' ?>>ANY</option>
                            </select>
                            <span id="date1_span">
                            <?= makeTimebar("stime_", 1, null, false, time()); ?>
                                <span id="time1_span" class="nod">
                                    <?= makeTimebar("stime_", 2, null, false, (time() - 3600)); ?>
                                </span>
                            </span>
                            <span id="date2_span" class="nod">
                                <?= makeTimebar("etime_", 1, null, false, time()); ?>
                                <span id="time2_span" class="nod">
                                    <?= makeTimebar("etime_", 2, null, false, time()); ?>
                                </span>
                            </span>
                            <span id="nodate_span" class="nod">ANY/ALL DATES</span>
                        </div>
                        <div class="col-4 input-group input-group-sm">
                            <button type="submit" value="Search" title="Search Employee Hours" class="btn btn-sm btn-primary" name="the_Search_button" onclick="<?= $this->index_name ?> = 0;loadEmps();return false;">Search</button>
                            <button type="button" value="Reset" title="Reset Search Criteria" class="btn btn-sm btn-primary" onclick="resetEmpForm();resetPageSystem('<?= $this->index_name ?>');loadEmps();return false;">Reset</button>
                        </div>
                    </div>
                </div>
                <div class="block-content block-content-full">
                    <table class="table table-sm table-striped" id="emp_table">
                        <tr>
                            <th class="row2 text-center"><?= $this->getOrderLink('time_started') ?>Date</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('username') ?>Agent</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('office') ?>Office</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('call_group') ?>Group</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('seconds_INCALL') ?>Detected (OLD)</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('seconds_INCALL') ?>Detected</a><a href="#" onclick="alert('The hours that the system detected activity for. (not perfect/accurate)\n\nNote: 6.5 hrs means 6 hours and 30 minutes.');return false;">&nbsp;(?)</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('seconds_READY') ?>Breakdown</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('paid_time') ?>Paid</a><a href="#" onclick="alert('Note: 6.5 hrs means 6 hours and 30 minutes.');return false;">&nbsp;(?)</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('notes') ?>Notes</a></th>
                        </tr>
                    </table>
                </div>
            </form>
            <div class="input-group input-group-sm text-center">
                <button type="button" class="btn btn-sm btn-success" title="Export Results to CSV" name="export_csv" onclick="exportResultsCSV()">Export Results CSV</button>
                <button type="button" class="btn btn-sm btn-success" title="Export TOTALS to CSV" name="export_totals" onclick="exportResultsCSV(1)">Export Totals CSV</button>
                <button type="button" class="btn btn-sm btn-success" title="Export Clean TOTALS to CSV" name="export_clean_totals" onclick="exportResultsCSV(2)">Export Clean Totals CSV</button>
            </div>
            <? if (!checkAccess('employee_hours_edit')) {
                ?>
                <form method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>" id="set_hours_form" onsubmit="return processListSubmit(this);">
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="submitting_activity_changes">
                        <input type="hidden" name="activity_ids" id="activity_ids"/>
                        <input type="hidden" name="activity_hours" id="activity_hours"/>
                        <input type="hidden" name="activity_notes" id="activity_notes"/>
                        <div id="setallspan"></div>
                        <button type="button" class="btn btn-sm btn-danger" title="Set All (On-Screen)" onclick="setAllToValue();">Set All (On-Screen)</button>
                        <button type="button" class="btn btn-sm btn-danger" title="Save Changes" onclick="$('#set_hours_form').submit();" name="save_button">Save Changes</button>
                    </div>
                </form>
                <?
            } ?>
        </div>
        <!-- ****END**** THIS AREA REPLACES THE OLD TABLES WITH THE NEW ONEUI INTERFACE BASED ON BOOTSTRAP -->
        <script>
            $(function () {
                $('#setallspan').html(
                    makeNumberDD('paid_hour_setall', 0, 0, 24, 1, false, '', false) + "h&nbsp;" + makeNumberDD('paid_min_setall', 0, 0, 59, 1, true, '', false) + 'm'
                );
                $("#dialog-modal-edit_emp").dialog({
                    autoOpen: false,
                    width: 'auto',
                    height: 'auto',
                    modal: false,
                    draggable: true,
                    position: {my: 'center', at: 'center'},
                    resizable: false
                });
            });
            loadEmps();
        </script>
        <?
    }

    function getOrderLink($field)
    {
        $var = '<a href="#" onclick="setOrder(\'' . addslashes($this->order_prepend) . '\',\'' . addslashes($field) . '\',';
        $var .= "((" . $this->order_prepend . "orderdir == 'DESC')?'ASC':'DESC')";
        $var .= ");loadEmps();return false;\">";
        return $var;
    }
}
