<?	/***************************************************************
	 *	Change Password - Simple GUI module to change a motherfucking password. What did you think it is? Certainly not rocket science. Asshole.
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['change_password'] = new ChangePassword;


class ChangePassword{



	function ChangePassword(){


		## REQURES DB CONNECTION!



		$this->handlePOST();
	}







	function handlePOST(){


	}

	function handleFLOW(){

		$this->makeGUI();

	}



	function makeGUI(){


		?><script src="js/md5.js"></script>
		<script>


		function submitChangePass(frm){


			if(frm.new_pass.value != frm.conf_pass.value){
				alert('ERROR: Password doesn\'t match confirmation.');
				return false;
			}



			frm.pass_hash.value = hex_md5(frm.new_pass.value);
			frm.old_pass_hash.value = hex_md5(frm.old_pass.value);

			// BLANK OUT PLAIN TEXT PWS BEFORE SUBMIT
			frm.new_pass.value = '';
			frm.old_pass.value = '';
			frm.conf_pass.value = '';

			var params = getFormValues(frm);

			// AJAX SUBMIT TO SERVER
			$.ajax({
				type: "POST",
				cache: false,
				url: 'api/api.php?get=change_password',
				data: params,
				error: function(){
					alert("Error saving form. Please contact an admin.");
				},
				success: function(msg){

					//alert(msg);
					var result = handleEditXML(msg);
					var res = result['result'];

					if(res <= 0){

						alert(result['message']);

						return;

					}else{
						alert("Successfully changed password.");
					}

				}
			});



			return false;

		}

		</script>
		<form method="POST" action="<?=stripurl()?>" autocomplete="off" onsubmit="return submitChangePass(this)">
			<input type="hidden" name="changing_password">
			<input type="hidden" id="pass_hash" name="pass_hash">
			<input type="hidden" id="old_pass_hash" name="old_pass_hash">
		<table border="0" width="350" align="center">
		<tr>
			<th align="left">User:</th>
			<td><?=$_SESSION['user']['username']?></td>
		</tr>
		<tr>
			<th align="left">Old Password:</th>
			<td><input type="password" name="old_pass" id="old_pass"></td>
		</tr>
		<tr>
			<th align="left">New Password:</th>
			<td><input type="password" name="new_pass" id="new_pass"></td>
		</tr>
		<tr>
			<th align="left">Confirm Password:</th>
			<td><input type="password" name="conf_pass" id="conf_pass"></td>
		</tr>
		<tr>
			<th colspan="2" align="center">

				<input type="submit" value="Change Password">

			</th>
		</tr>
		</form>
		</table><?
	}




}
