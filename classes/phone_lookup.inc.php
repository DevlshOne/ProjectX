<? /***************************************************************
 *    Phone Lookup Tool
 *    Written By: Jonathan Will
 ***************************************************************/

$_SESSION['phone_lookup'] = new PhoneLookupTool;


class PhoneLookupTool
{

    var $lookup_api = "https://dripp.advancedtci.com/dripp/tools/phone_lookup_api.php";


    var $vici_lookup_api = "http://atst.advancedtci.com/phone_lookup/server_query"; //?phone_number=7025551212&cluster=cold-1

    function PhoneLookupTool()
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

        if (!checkAccess('phone_lookup')) {


            accessDenied("Phone Lookup");

            return;

        } else {


            $this->makeSearchForm();


        }

    }


    function makeSearchForm()
    {

        ?>
        <script>


            var cluster_array = new Array();
            var tmpclusteridx = 0;
            <?
                $res = query("SELECT * FROM vici_clusters WHERE `status`='enabled' " .

                    //" AND `cluster_type` != 'verifier' ".

                    " ORDER BY `name` ASC", 1);



                /**
                 * CONNECT TO CLUSTERS AND PUSH NULL-CAMPAIGN DNC AND THE PER-CAMPAIGN DNC
                 */

                $clusters = array();
                while ($row = mysqli_fetch_array($res)) {

                    $clusters[$row['id']] = $row;

                }
                foreach($clusters as $cluster_id => $vicidb){

                ?>cluster_array[tmpclusteridx++] = {'tag': '<?=addslashes($vicidb['callerid_tag'])?>', 'name': '<?=addslashes($vicidb['name'])?>'};
            <?


            }

            ?>




            function validatePhoneField(name, value, frm) {

                //alert(name+","+value);


                switch (name) {
                    default:

                        // ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
                        return true;
                        break;

                    case 'phone_num':


                        if (!value) return false;

                        if (value.length < 10 || value.length > 10) return false;

                        return true;


                        break;

                }
                return true;
            }

            function lookup_phone(frm) {

                // call function to lookup the phone number

                var params = getFormValues(frm, 'validatePhoneField');

                // FORM VALIDATION FAILED!
                // param[0] == field name
                // param[1] == field value
                if (typeof params == "object") {

                    switch (params[0]) {
                        default:

                            alert("Error submitting form. Check your values");

                            break;

                        case 'phone_num':

                            alert("Please enter a proper 10 digit phone number to lookup.");
                            eval('try{frm.' + params[0] + '.select();}catch(e){}');
                            break;

                    }

                    // SUCCESS - POST AJAX TO SERVER
                } else {

//alert('<?=$this->lookup_api?>');

                    var phone_num = frm.phone_num.value;

                    let totalrecordscount = 0;


                    var total_clusters_to_process = cluster_array.length;
                    var total_clusters_processed = 0;

                    var cluster_total_count_arr = new Array();

                    cluster_total_count_arr['logs'] = new Array();
                    cluster_total_count_arr['lists'] = new Array();
                    cluster_total_count_arr['did'] = new Array();
                    cluster_total_count_arr['diallog'] = new Array();


                    if ($('#search_area_dripp').is(":checked")) {


                        $('#dripp_lookup_results_div').html('<img src="images/ajax-loader.gif" border="0" />DRIPP Results Loading');

                        $('#dripp_lookup_results_div').show();

                        // DRIPP API POST
                        $.ajax({
                            type: "POST",
                            cache: false,
                            url: '<?=$this->lookup_api?>',
                            data: params,
                            error: function (jqXHR, exception) {

                                var msg = '';
                                if (jqXHR.status === 0) {
                                    msg = 'Not connect.\n Verify Network.';
                                } else if (jqXHR.status == 404) {
                                    msg = 'Requested page not found. [404]';
                                } else if (jqXHR.status == 500) {
                                    msg = 'Internal Server Error [500].';
                                } else if (exception === 'parsererror') {
                                    msg = 'Requested JSON parse failed.';
                                } else if (exception === 'timeout') {
                                    msg = 'Time out error.';
                                } else if (exception === 'abort') {
                                    msg = 'Ajax request aborted.';
                                } else {
                                    msg = 'Uncaught Error.\n' + jqXHR.responseText;
                                }


                                //							alert("Error looking up phone. Please contact an admin.\n"+msg);

                            },
                            success: function (msg) {

                                //alert(msg);

                                var xmldoc = getXMLDoc(msg);

                                var dataarr = xmldoc.getElementsByTagName('Record');
                                var totalcount = dataarr.length;

                                if (totalcount <= 0) {

                                    $('#dripp_lookup_results_div').html("No results found");
                                    return;

                                }

                                var html = '<table border="0" width="950" >' +
                                    '<tr><th colspan="6" height="30" align="left" class="pad_left ui-widget-header">DRIPP Lookup Results</th></tr>';

                                html += '<tr>' +

                                    '<th class="row2">Time</th>' +
                                    '<th class="row2" align="left">Customer</th>' +
                                    '<th class="row2" align="right">Amount</th>' +

                                    '<th class="row2" align="center">Project</th>' +
                                    '<th class="row2" align="center">Processor</th>' +
                                    '<th class="row2" align="center">Transaction ID</th>' +
                                    '</tr>';


                                var color = 0;
                                var clss = '';
                                for (var x = 0; x < dataarr.length; x++) {

                                    clss = 'row' + (color++ % 2);

                                    html += '<tr>';

                                    html += '<td style="padding:3px" class="' + clss + '" align="center">' + dataarr[x].getAttribute('transaction_date') + '</td>';
                                    html += '<td style="padding:3px" class="' + clss + '" >' + dataarr[x].getAttribute('full_name') + '</td>';
                                    html += '<td style="padding:3px" class="' + clss + '" align="right">$' + dataarr[x].getAttribute('transaction_amount') + '</td>';
                                    html += '<td style="padding:3px" class="' + clss + '" align="center">' + dataarr[x].getAttribute('project') + '</td>';
                                    html += '<td style="padding:3px" class="' + clss + '" align="center">' + dataarr[x].getAttribute('processor_id') + '</td>';

                                    html += '<td style="padding:3px" class="' + clss + '" align="center">' + dataarr[x].getAttribute('transaction_id') + '</td>';


                                    html += '</tr>';

                                }

                                totalrecordscount += totalcount;

                                html += '</table>';

                                $('#dripp_lookup_results_div').html(html);

                                applyUniformity();
                            }

                        });  // END AJAX TO DRIPP


                        // IF DRIPP NOT CHECKED, HIDE IT
                    } else {

                        $('#dripp_lookup_results_div').hide();

                    }


                    if ($('#search_area_clusters').is(":checked")) {


                        $('#cluster_lookup_results_div').show();

                        $('#tbl_vici_results_logs > tbody').html('');
                        $('#tbl_vici_results_lists > tbody').html('');
                        $('#tbl_vici_results_did > tbody').html('');
                        $('#tbl_vici_results_dialog > tbody').html('');

                        $('#area_loading_flag_logs').html('<img src="images/ajax-loader.gif" height="25" border="0" />');
                        $('#area_loading_flag_lists').html('<img src="images/ajax-loader.gif" height="25" border="0" />');
                        $('#area_loading_flag_did').html('<img src="images/ajax-loader.gif" height="25" border="0" />');
                        $('#area_loading_flag_diallog').html('<img src="images/ajax-loader.gif" height="25" border="0" />');


                        ////// $('#cluster_lookup_results_div').html('<img src="images/ajax-loader.gif" border="0" />Loading');
                        total_clusters_processed = 0;

                        for (let z = 0; z < cluster_array.length; z++) {


                            // VICI API POST (BRENTS TOOL)
                            $.ajax({
                                type: "POST",
                                cache: false,
                                url: '<?=$this->vici_lookup_api?>?phone_number=' + phone_num + '&cluster=' + cluster_array[z]['tag'],

                                error: function (jqXHR, exception) {

                                    var msg = '';
                                    if (jqXHR.status === 0) {
                                        msg = 'Not connect.\n Verify Network.';
                                    } else if (jqXHR.status == 404) {
                                        msg = 'Requested page not found. [404]';
                                    } else if (jqXHR.status == 500) {
                                        msg = 'Internal Server Error [500].';
                                    } else if (exception === 'parsererror') {
                                        msg = 'Requested JSON parse failed.';
                                    } else if (exception === 'timeout') {
                                        msg = 'Time out error.';
                                    } else if (exception === 'abort') {
                                        msg = 'Ajax request aborted.';
                                    } else {
                                        msg = 'Uncaught Error.\n' + jqXHR.responseText;
                                    }


                                    //							alert("Error looking up phone. Please contact an admin.\n"+msg);

                                },
                                success: function (msg) {
                                    /**
                                     * {"server":"cold-1","vici_logs":[{"location":"Vici Archive","url":"10.101.1.9","archive":"yes","lead_id":"1","campaign_id":"TESTCALL","call_date":"2019-11-21 14:09:46","status":"B","user":"JPW","list_id":"999","length_in_sec":"20","alt_dial":"MANUAL"},{"location":"Vici Archive","url":"10.101.1.9","archive":"yes","lead_id":"1","campaign_id":"TESTCALL","call_date":"2019-11-21 14:44:44","status":"INCALL","user":"JPW","list_id":"999","length_in_sec":"31","alt_dial":"MANUAL"},{"location":"Vici Archive","url":"10.101.1.9","archive":"yes","lead_id":"1","campaign_id":"TESTPX","call_date":"2019-11-21 16:11:18","status":"B","user":"JPW","list_id":"999","length_in_sec":"24","alt_dial":"MANUAL"},{"location":"Vici Archive","url":"10.101.1.9","archive":"yes","lead_id":"122777425","campaign_id":"Closers","call_date":"2019-12-04 04:45:59","status":"NI","user":"JPW","list_id":"99999999","length_in_sec":"31","alt_dial":"MAIN"}],"vici_lists":[],"did_logs":[],"dial_logs":[]}
                                     **/
                                        //alert(msg);

                                        // PARSE THE JSON
                                    var obj = JSON.parse(msg);

                                    // POPULATE THE TABLES


                                    var html = '';
                                    var color = 0;
                                    var clss = '';
                                    let tmpobj = null;

                                    for (var x = 0; x < obj['vici_logs'].length; x++) {

                                        clss = 'row' + (color++ % 2);

                                        tmpobj = obj['vici_logs'][x];
                                        /**
                                         <th class="row2">Cluster</th>
                                         <th class="row2" align="left">Location</th>
                                         <th class="row2" align="center">Status</th>
                                         <th class="row2" align="left">Lead ID</th>
                                         <th class="row2" align="left">Campaign ID</th>
                                         <th class="row2" align="left">Call Date</th>
                                         <th class="row2" align="left">User</th>
                                         <th class="row2" align="left">List ID</th>
                                         <th class="row2" align="left">Duration</th>
                                         <th class="row2" align="left">Alt Dial</th>
                                         <th class="row2" align="left">&nbsp;</th>**/


                                        html += '<tr>';

                                        html += '<td style="padding:3px" class="' + clss + '" align="center">' + cluster_array[z]['name'] + '</td>';
                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['location'] + '</td>';

                                        html += '<td style="padding:3px" class="' + clss + '" align="center" >' + tmpobj['status'] + '</td>';
                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['lead_id'] + '</td>';
                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['campaign_id'] + '</td>';
                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['call_date'] + '</td>';
                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['user'] + '</td>';
                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['list_id'] + '</td>';
                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['length_in_sec'] + '</td>';
                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['alt_dial'] + '</td>';
                                        html += '<td style="padding:3px" class="' + clss + '" ><a href="#">[LINK]</a></td>';


                                        html += '</tr>';

                                    }

                                    totalrecordscount += obj['vici_logs'].length;


                                    cluster_total_count_arr['logs'][z] = obj['vici_logs'].length;


                                    $('#tbl_vici_results_logs > tbody:last-child').after(html);

                                    applyUniformity();


                                    /******/

                                    html = '';
                                    color = 0;
                                    clss = '';
                                    tmpobj = null;

                                    for (var x = 0; x < obj['vici_lists'].length; x++) {

                                        clss = 'row' + (color++ % 2);

                                        tmpobj = obj['vici_lists'][x];

                                        /**<th class="row2">Cluster</th>
                                         <th class="row2" align="left">Lead ID</th>
                                         <th class="row2" align="left">Entry Date</th>
                                         <th class="row2" align="left">Modify Date</th>
                                         <th class="row2" align="left">Status</th>
                                         <th class="row2" align="left">User</th>
                                         <th class="row2" align="left">Vendor Lead Code</th>
                                         <th class="row2" align="left">Source ID</th>
                                         <th class="row2" align="left">List ID</th>
                                         <th class="row2" align="left">Phone Code</th>
                                         <th class="row2" align="left">First Name</th>
                                         <th class="row2" align="left">Last Name</th>
                                         <th class="row2" align="left">&nbsp;</th>**/


                                        html += '<tr>';

                                        html += '<td style="padding:3px" class="' + clss + '" align="center">' + cluster_array[z]['name'] + '</td>';
                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['lead_id'] + '</td>';

                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['entry_date'] + '</td>';
                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['modify_date'] + '</td>';


                                        html += '<td style="padding:3px" class="' + clss + '" align="center" >' + tmpobj['status'] + '</td>';

                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['user'] + '</td>';
                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['vendor_lead_code'] + '</td>';
                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['source_id'] + '</td>';
                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['list_id'] + '</td>';

                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['phone_code'] + '</td>';
                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['first_name'] + '</td>';
                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['last_name'] + '</td>';

                                        html += '<td style="padding:3px" class="' + clss + '" ><a href="#">[LINK]</a></td>';


                                        html += '</tr>';

                                    }

                                    totalrecordscount += obj['vici_lists'].length;

                                    cluster_total_count_arr['lists'][z] = obj['vici_lists'].length;

                                    $('#tbl_vici_results_lists > tbody:last-child').after(html);

                                    applyUniformity();


                                    /******/

                                    html = '';
                                    color = 0;
                                    clss = '';
                                    tmpobj = null;

                                    for (var x = 0; x < obj['did_logs'].length; x++) {

                                        clss = 'row' + (color++ % 2);

                                        tmpobj = obj['did_logs'][x];

                                        /**<th class="row2">Cluster</th>
                                         <th class="row2" align="left">Extension</th>
                                         <th class="row2" align="left">DID ID</th>
                                         <th class="row2" align="left">Call Date</th>**/

                                        html += '<tr>';
                                        html += '<td style="padding:3px" class="' + clss + '" align="center">' + cluster_array[z]['name'] + '</td>';
                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['extension'] + '</td>';
                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['did_id'] + '</td>';
                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['call_date'] + '</td>';
                                        html += '</tr>';

                                    }

                                    totalrecordscount += obj['did_logs'].length;

                                    cluster_total_count_arr['did'][z] = obj['did_logs'].length;


                                    $('#tbl_vici_results_did > tbody:last-child').after(html);

                                    applyUniformity();


                                    /******/

                                    html = '';
                                    color = 0;
                                    clss = '';
                                    tmpobj = null;

                                    for (var x = 0; x < obj['dial_logs'].length; x++) {

                                        clss = 'row' + (color++ % 2);

                                        tmpobj = obj['dial_logs'][x];

                                        /**<th class="row2">Cluster</th>
                                         <th class="row2" align="left">Number Called</th>
                                         <th class="row2" align="left">Call ID</th>
                                         <th class="row2" align="left">Call Date</th>**/

                                        html += '<tr>';
                                        html += '<td style="padding:3px" class="' + clss + '" align="center">' + cluster_array[z]['name'] + '</td>';
                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['num_dialed'] + '</td>';
                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['obcid'] + '</td>';
                                        html += '<td style="padding:3px" class="' + clss + '" >' + tmpobj['call_date'] + '</td>';
                                        html += '</tr>';

                                    }

                                    totalrecordscount += obj['dial_logs'].length;

                                    cluster_total_count_arr['diallog'][z] = obj['dial_logs'].length;

                                    $('#tbl_vici_results_diallogs > tbody:last-child').after(html);

                                    applyUniformity();


                                    total_clusters_processed++;


                                    if (total_clusters_processed >= total_clusters_to_process) {

                                        $("#area_loading_flag_logs").html('<img src="images/circle-green.gif" height="25" border="0" />');
                                        $("#area_loading_flag_lists").html('<img src="images/circle-green.gif" height="25" border="0" />');
                                        $("#area_loading_flag_did").html('<img src="images/circle-green.gif" height="25" border="0" />');
                                        $("#area_loading_flag_diallog").html('<img src="images/circle-green.gif" height="25" border="0" />');


                                        if (cluster_total_count_arr['logs'][z] <= 0) {
                                            $('#tbl_vici_results_logs > tbody:last-child').after('<tr><td colspan="11" align="center">No results found here.</td></tr>');
                                        }

                                        if (cluster_total_count_arr['lists'][z] <= 0) {
                                            $('#tbl_vici_results_lists > tbody:last-child').after('<tr><td colspan="13" align="center">No results found here.</td></tr>');
                                        }


                                        if (cluster_total_count_arr['did'][z] <= 0) {
                                            $('#tbl_vici_results_did > tbody:last-child').after('<tr><td colspan="4" align="center">No results found here.</td></tr>');
                                        }


                                        if (cluster_total_count_arr['diallog'][z] <= 0) {
                                            $('#tbl_vici_results_diallogs > tbody:last-child').after('<tr><td colspan="4" align="center">No results found here.</td></tr>');
                                        }


                                    } else {
                                        $("#area_loading_flag_logs").html('<img src="images/ajax-loader.gif" height="25" border="0" />' + total_clusters_processed + "&nbsp;/&nbsp;" + total_clusters_to_process);
                                        $("#area_loading_flag_lists").html('<img src="images/ajax-loader.gif" height="25" border="0" />' + total_clusters_processed + "&nbsp;/&nbsp;" + total_clusters_to_process);
                                        $("#area_loading_flag_did").html('<img src="images/ajax-loader.gif" height="25" border="0" />' + total_clusters_processed + "&nbsp;/&nbsp;" + total_clusters_to_process);
                                        $("#area_loading_flag_diallog").html('<img src="images/ajax-loader.gif" height="25" border="0" />' + total_clusters_processed + "&nbsp;/&nbsp;" + total_clusters_to_process);

                                    }

                                }


                            }); // END AJAX TO BRENTS TOOL


                        }// END FOREACH CLUSTER


                    } else { // END IF (SEARCH CLUSTERS CHECKED

                        $('#cluster_lookup_results_div').hide();

                    }


                }


                return false;
            }
            function toggleSearchChecks(checkStatus) {
                $('#search_boxes').find(':checkbox').each(function() {
                    $(this).prop('checked', checkStatus);
                });
            }
        </script>
        <div class="block">
            <div class="block-header bg-primary-light">
                <h4 class="block-title">Phone Lookup Tool</h4>
                <div class="d-inline-block ml-2">
                    <button class="btn btn-sm btn-dark" title="Total Found">
                        <i class="si si-list"></i>
                        <span class="badge badge-light badge-pill"><div id="total_count_div"></div></span>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <form class="d-none d-sm-inline-block" method="POST" action="<?= stripurl('') ?>" onsubmit="return lookup_phone(this);">
                    <input type="hidden" name="lookingup_phone"/>
                    <input type="hidden" name="mode" value="lookup"/>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control form-control-alt" name="phone_num" placeholder="Phone.." pattern="\d{10}" onkeyup="this.value=this.value.replace(/[^0-9]/g,'')"/>
                        <button type="submit" title="Search" class="btn btn-sm btn-primary">Search..</button>
                    </div>
                    <div class="input-group input-group-sm" id="search_boxes">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" onclick="toggleSearchChecks(this.checked);"/><div class="text-black text-uppercase">Search All</div>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="search_area_dripp" name="search_areas[]" value="dripp"/>Search Dripp
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="search_area_clusters" name="search_areas[]" value="vici"/>Search All Clusters
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="search_area_px" name="search_areas[]" value="px"/>Search PX/LMT
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="search_area_listtool" name="search_areas[]" value="listtool"/>Search List Tool
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="search_area_transfers" name="search_areas[]" value="transfers"/>Search Transfers
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="search_area_sales" name="search_areas[]" value="sales"/>Search Sales
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="search_area_dnc" name="search_areas[]" value="dnc"/>Search DNC
                        </div>
                    </div>
                </form>
                <div id="dripp_lookup_results_div" class="nod"></div>
                <br/>
                <div id="cluster_lookup_results_div" class="nod">
                    <table border="0" width="950" id="tbl_vici_results_logs">
                        <thead>
                        <tr>
                            <th colspan="10" height="30" align="left" class="pad_left ui-widget-header">Vicidial Lookup Results - Logs</th>
                            <td nowrap align="right" class="ui-widget-header"><span id="area_loading_flag_logs"><img src="images/ajax-loader.gif" height="25" border="0"/></span></td>
                        </tr>
                        <tr>
                            <th class="row2">Cluster</th>
                            <th class="row2" align="left">Location</th>
                            <th class="row2" align="center">Status</th>
                            <th class="row2" align="left">Lead ID</th>
                            <th class="row2" align="left">Campaign ID</th>
                            <th class="row2" align="left">Call Date</th>
                            <th class="row2" align="left">User</th>
                            <th class="row2" align="left">List ID</th>
                            <th class="row2" align="left">Duration</th>
                            <th class="row2" align="left">Alt Dial</th>
                            <th class="row2" align="left">&nbsp;</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>

                    <br/>

                    <table border="0" width="950" id="tbl_vici_results_lists">
                        <thead>
                        <tr>
                            <th colspan="12" height="30" align="left" class="pad_left ui-widget-header">Vicidial Lookup Results - Lists</th>
                            <td nowrap align="right" class="ui-widget-header"><span id="area_loading_flag_lists"><img src="images/ajax-loader.gif" height="25" border="0"/></span></td>
                        </tr>
                        <tr>
                            <th class="row2">Cluster</th>
                            <th class="row2" align="left">Lead ID</th>
                            <th class="row2" align="left">Entry Date</th>
                            <th class="row2" align="left">Modify Date</th>
                            <th class="row2" align="left">Status</th>
                            <th class="row2" align="left">User</th>
                            <th class="row2" align="left">Vendor Lead Code</th>
                            <th class="row2" align="left">Source ID</th>
                            <th class="row2" align="left">List ID</th>
                            <th class="row2" align="left">Phone Code</th>
                            <th class="row2" align="left">First Name</th>
                            <th class="row2" align="left">Last Name</th>
                            <th class="row2" align="left">&nbsp;</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>


                    <br/>


                    <table border="0" width="950" id="tbl_vici_results_did">
                        <thead>
                        <tr>
                            <th colspan="3" height="30" align="left" class="pad_left ui-widget-header">Vicidial Lookup Results - DID Logs</th>
                            <td nowrap align="right" class="ui-widget-header"><span id="area_loading_flag_did"><img src="images/ajax-loader.gif" height="25" border="0"/></span></td>
                        </tr>
                        <tr>
                            <th class="row2">Cluster</th>
                            <th class="row2" align="left">Extension</th>
                            <th class="row2" align="left">DID ID</th>
                            <th class="row2" align="left">Call Date</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>


                    <br/>


                    <table border="0" width="950" id="tbl_vici_results_diallogs">
                        <thead>
                        <tr>
                            <th colspan="3" height="30" align="left" class="pad_left ui-widget-header">Vicidial Lookup Results - Dial Logs</th>
                            <td nowrap align="right" class="ui-widget-header"><span id="area_loading_flag_diallog"><img src="images/ajax-loader.gif" height="25" border="0"/></span></td>
                        </tr>
                        <tr>
                            <th class="row2">Cluster</th>
                            <th class="row2" align="left">Number Called</th>
                            <th class="row2" align="left">Call ID</th>
                            <th class="row2" align="left">Call Date</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>


                </div>
                <div id="px_lookup_results_div" class="nod"></div>
                <div id="listtool_lookup_results_div" class="nod"></div>
                <div id="current_time_span" class="small text-right">Server Time: <?= date("g:ia m/d/Y T") ?></div>
            </div>
        </div>
        <script>
            applyUniformity();
        </script><?

    }
}
