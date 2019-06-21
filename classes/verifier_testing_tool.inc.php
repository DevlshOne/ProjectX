<?php
/* Verifier Testing Tool
 * Written by: Jonathan P Will
 * Created on May 17, 2016
 *
 */


 class VerifierTestingTool{






	function handleFLOW(){

		$this->makeGUI();

	}


	function makeGUI(){


		?><script>

		function checkToolForm(frm){

			if(!frm.px_server_ip.value)return recheck("Please enter a valid PX Server IP", frm.px_server_ip);

			if(!frm.username.value)return recheck("Please enter the username you are testing with.", frm.username);

			if(!frm.extension.value)return recheck("Please enter the extension you are testing with.", frm.extension);

			if(!frm.campaign.value)return recheck("Please enter the CaSeSensAtIvE Campaign Code you are testing with, such as BCRSF.", frm.campaign);


			// BUILD PARAM LIST
			var params = getFormValues(frm)


			// AJAX SUBMIT IT
			$.ajax({
				type: "POST",
				cache: false,
				url: 'api/api.php?get=verifier_testing_tool&mode=xml&action=test',
				data: params,
				error: function(){
					alert("Error submitting form. Please contact an admin.");
				},
				success: function(msg){

					alert("Successfully sent!");

				}


			});

			return false;
		}

		</script>
		<form method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="return checkToolForm(this)">

			<input type="hidden" name="verifier_testing_lead_poster">
		<table border="0" width="100%" height="100%">
		<tr>
			<td align="center">
				<table border="0" align="center">
				<tr>
					<td colspan="2" height="30" class="pad_left ui-widget-header">VERIFIER 7x KEY TEST TOOL</td>
				</tr>
				<tr>
					<th>PX Server:</th>
					<td><input type="text" name="px_server_ip" value="<?=($_REQUEST['px_server_ip'])?$_REQUEST['px_server_ip']:"10.100.0.69"?>" /></td>
				</tr>
				<tr>
					<th>Username:</th>
					<td><input type="text" size="10" name="username" value="<?=($_REQUEST['username'])?$_REQUEST['username']:""?>"></td>
				</tr>
				<tr>
					<th>Extension:</th>
					<td><input type="text" size="10" name="extension" onkeyup="this.value = this.value.replace(/[^0-9]/g,'')" value="<?=($_REQUEST['extension'])?$_REQUEST['extension']:""?>"></td>
				</tr>
				<tr>
					<th>Campaign CODE:</th>
					<td><input type="text" size="6" name="campaign" value="<?=($_REQUEST['campaign'])?$_REQUEST['campaign']:""?>"></td>
				</tr>

				<tr>
					<td colspan="2">

						<input type="submit" value="Generate and Send Fake lead">

					</td>
				</tr>
				</table>
			</td>
		</tr>
		</table>
		</form>

		<script>
			applyUniformity();
		</script><?

	}


 }




