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
			let idx = 0;
		<?
			$res = query("SELECT * FROM vici_clusters WHERE `status`='enabled' ".
				
				//" AND `cluster_type` != 'verifier' ".
				
				" ORDER BY `name` ASC",1);
	
	

			/**
			 * CONNECT TO CLUSTERS AND PUSH NULL-CAMPAIGN DNC AND THE PER-CAMPAIGN DNC
			 */
			
			$clusters = array();
			while($row = mysqli_fetch_array($res)){
		
				$clusters[$row['id']] = $row;
		
			}
			foreach($clusters as $cluster_id => $vicidb){
			
				?>cluster_array[idx++] = {'tag':'<?=addslashes($vicidb['callerid_tag'])?>', 'name':'<?=addslashes($vicidb['name'])?>'};
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

					let totalrecordscount = 0;

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

                            var html = '<table border="0" width="780" >' +
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



                    
                    $('#cluster_lookup_results_div').html('<img src="images/ajax-loader.gif" border="0" />Loading');
                    
                    for(let z=0;z < cluster_array.length;z++){
                        
						// VICI API POST (BRENTS TOOL)
						 $.ajax({
	                        type: "POST",
	                        cache: false,
	                        url: '<?=$this->vici_lookup_api?>?phone_number='+phone_num+'&cluster='+cluster_array['tag'],

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
	                        	alert(msg);
	
								// PARSE THE JSON
								var obj = JSON.parse(msg);
								
								// POPULATE THE TABLES
								
	
	
	                            var color = 0;
	                            var clss = '';
	                            let tmpobj = ;
	                            for (var x = 0; x < obj['vici_logs'].length; x++) {
	
	                                clss = 'row' + (color++ % 2);

	                                tmpobj = obj['vici_logs'][x];
	
	                                html += '<tr>';
	
	                                html += '<td style="padding:3px" class="' + clss + '" align="center">' + cluster_array['name'] + '</td>';
	                                html += '<td style="padding:3px" class="' + clss + '" >' + dataarr[x].getAttribute('full_name') + '</td>';
	                                html += '<td style="padding:3px" class="' + clss + '" align="right">$' + dataarr[x].getAttribute('transaction_amount') + '</td>';
	                                html += '<td style="padding:3px" class="' + clss + '" align="center">' + dataarr[x].getAttribute('project') + '</td>';
	                                html += '<td style="padding:3px" class="' + clss + '" align="center">' + dataarr[x].getAttribute('processor_id') + '</td>';
	
	                                html += '<td style="padding:3px" class="' + clss + '" align="center">' + dataarr[x].getAttribute('transaction_id') + '</td>';
	
	
	                                html += '</tr>';
	
	                            }
	
	                            totalrecordscount += totalcount;
	
	                            html += '</tbody></table>';
	
	                            $('#cluster_lookup_results_div').append(html);
	
	                            applyUniformity();
	                        	
	
	                        }
						 }); // END AJAX TO BRENTS TOOL


						 
                    }// END FOREACH CLUSTER








                    
                }


                return false;
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
                    <br />
                    <input type="checkbox" name="search_areas[]" value="dripp" />Search DRIPP<br />
                    <input type="checkbox" name="search_areas[]" value="vici" />Search All Clusters<br />
                    <input type="checkbox" name="search_areas[]" value="px" />Search PX/LMT<br />
                    <input type="checkbox" name="search_areas[]" value="listtool" />Search List Tool

                </form>
                <div id="dripp_lookup_results_div"></div>
                <div id="cluster_lookup_results_div">
                
               		<table border="0" width="780" id="tbl_vici_results_logs" >
                    <tr><th colspan="10" height="30" align="left" class="pad_left ui-widget-header">Vicidial Lookup Results - Logs</th></tr>
					<thead><tr>
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
					</thead><tbody></tbody></table>
					
					<table border="0" width="780" id="tbl_vici_results_lists" >
                    <tr><th colspan="10" height="30" align="left" class="pad_left ui-widget-header">Vicidial Lookup Results - Lists</th></tr>
					<thead><tr>
						<th class="row2">Cluster</th>
						<th class="row2" align="left">Lead ID</th>
						<th class="row2" align="left">Campaign ID</th>
						<th class="row2" align="left">Call Date</th>
						<th class="row2" align="left">User</th>
						<th class="row2" align="left">List ID</th>
						<th class="row2" align="left">Duration</th>
						<th class="row2" align="left">Alt Dial</th>
					</tr>
					</thead><tbody></tbody></table>
                
                
                </div>
                <div id="px_lookup_results_div"></div>
                <div id="listtool_lookup_results_div"></div>
                <div id="current_time_span" class="small text-right">Server Time: <?= date("g:ia m/d/Y T") ?></div>
            </div>
        </div>
        <script>
            applyUniformity();
        </script><?

    }
}
