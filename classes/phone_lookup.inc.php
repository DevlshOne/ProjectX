<?	/***************************************************************
	 *	Names - Handles list/search/import names
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['phone_lookup'] = new PhoneLookupTool;


class PhoneLookupTool{

	var $lookup_api = "https://dripp.advancedtci.com/dripp/tools/phone_lookup_api.php";


	function PhoneLookupTool(){


		## REQURES DB CONNECTION!



		$this->handlePOST();
	}


	function handlePOST(){

		// THIS SHIT IS MOTHERFUCKIGN AJAXED TO THE TEETH
		// SEE api/names.api.php FOR POST HANDLING!
		// <3 <3 -Jon

	}

	function handleFLOW(){
		# Handle flow, based on query string

		if(!checkAccess('phone_lookup')){


			accessDenied("Phone Lookup");

			return;

		}else{


			$this->makeSearchForm();


		}

	}




	function makeSearchForm(){

		?><script>

			function validatePhoneField(name,value,frm){

				//alert(name+","+value);


				switch(name){
				default:

					// ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
					return true;
					break;

				case 'phone_num':


					if(!value)return false;

					if(value.length < 10 || value.length > 10)return false;

					return true;


					break;

				}
				return true;
			}
			function lookup_phone(frm){

				// call function to lookup the phone number

				var params = getFormValues(frm,'validatePhoneField');

				// FORM VALIDATION FAILED!
				// param[0] == field name
				// param[1] == field value
				if(typeof params == "object"){

					switch(params[0]){
					default:

						alert("Error submitting form. Check your values");

						break;

					case 'phone_num':

						alert("Please enter a proper 10 digit phone number to lookup.");
						eval('try{frm.'+params[0]+'.select();}catch(e){}');
						break;

					}

				// SUCCESS - POST AJAX TO SERVER
				}else{

//alert('<?=$this->lookup_api?>');

					$.ajax({
						type: "POST",
						cache: false,
						url: '<?=$this->lookup_api?>',
						data: params,
						error: function(jqXHR, exception){

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
						success: function(msg){

							//alert(msg);

							var xmldoc = getXMLDoc(msg);

							var dataarr = xmldoc.getElementsByTagName('Record');
							var totalcount = dataarr.length;

							if(totalcount <= 0){

								$('#lookup_results_div').html("No results found");
								return;

							}

							var html = '<table border="0" width="780" >'+
										'<tr><th colspan="6" height="30" align="left" class="pad_left ui-widget-header">DRIPP Lookup Results</th></tr>';

							html += '<tr>'+

									'<th class="row2">Time</th>'+
									'<th class="row2" align="left">Customer</th>'+
									'<th class="row2" align="right">Amount</th>'+

									'<th class="row2" align="center">Project</th>'+
									'<th class="row2" align="center">Processor</th>'+
									'<th class="row2" align="center">Transaction ID</th>'+
								'</tr>';



							var color = 0;
							var clss='';
							for(var x=0;x < dataarr.length;x++){

								clss = 'row'+(color++%2);

								html += '<tr>';

								html += '<td style="padding:3px" class="'+clss+'" align="center">'+dataarr[x].getAttribute('transaction_date')+'</td>';
								html += '<td style="padding:3px" class="'+clss+'" >'+dataarr[x].getAttribute('full_name')+'</td>';
								html += '<td style="padding:3px" class="'+clss+'" align="right">$'+dataarr[x].getAttribute('transaction_amount')+'</td>';
								html += '<td style="padding:3px" class="'+clss+'" align="center">'+dataarr[x].getAttribute('project')+'</td>';
								html += '<td style="padding:3px" class="'+clss+'" align="center">'+dataarr[x].getAttribute('processor_id')+'</td>';

								html += '<td style="padding:3px" class="'+clss+'" align="center">'+dataarr[x].getAttribute('transaction_id')+'</td>';


								html += '</tr>';

							}


							html += '</table>';

							$('#lookup_results_div').html(html);

							applyUniformity();
						}

					});
				}


				return false;
			}

		</script>
		<form method="POST" action="<?=stripurl('')?>" onsubmit="return lookup_phone(this)">
			<input type="hidden" name="lookingup_phone" value="1" />
			<input type="hidden" name="mode" value="lookup" />


				<table border="0" width="250" height="100">
				<tr>
					<th colspan="2" class="pad_left ui-widget-header" height="30">
						Lookup DRIPP Sale
					</th>
				</tr>
				<tr>
					<th align="left">PHONE #:</th>
					<td><input type="text" size="12" name="phone_num" onkeyup="this.value=this.value.replace(/[^0-9]/g,'')" /></td>
				</tr>
				<tr>
					<td colspan="2" align="center"><input type="submit" value="Lookup"></td>
				</tr>
				</table>

		</form>

		<div id="lookup_results_div"></div>

		<script>
			applyUniformity();
		</script><?

	}




}
