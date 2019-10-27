<?	/***************************************************************
	 *	Change Password - Simple GUI module to change a motherfucking password. What did you think it is? Certainly not rocket science. Asshole.
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['change_password'] = new ChangePassword;


class ChangePassword{


	## SET PASSWORD COMPLEXITY REQUIRMENTS
	## THIS IS INTENDED TO BE USED BY OTHER CLASSES

	var $pw_uppercase = true;
	var $pw_lowercase = true;
	var $pw_digits = true;
	var $pw_specialchars = false;
	var $pw_minlength = '8';


	function ChangePassword(){

		$this->handlePOST();

	}


	function handlePOST(){


	}


	function handleFLOW($expiredpw=false){

		$this->makeGUI($expiredpw);

	}


	function makeGUI($expiredpw){


		?><script src="js/md5.js"></script>
		<script>

			// JS FUNCTIONS TO CHECK PASSWORD COMPLEXITY
			function pwCheckComplexity(pw){

				// SET CHARACTER MATCH STRINGS
				var uppercase = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
				var lowercase = "abcdefghijklmnopqrstuvwxyz";
				var digits = "0123456789";
				var specialChars ="!@#$%&*()[]{}?><,.:;~`";

				// SET COMPLEXITY REQUIREMENT FLAGS BASED ON CLASS SETTINGS
				<?=($this->pw_uppercase)?'var ucaseFlag = contains(pw, uppercase);':'var ucaseFlag = false;'?>
				<?=($this->pw_lowercase)?'var lcaseFlag = contains(pw, lowercase);':'var lcaseFlag = false;'?>
				<?=($this->pw_digits)?'var digitsFlag = contains(pw, digits);':'var digitsFlag = false;'?>
				<?=($this->pw_specialchars)?'var specialCharsFlag = contains(pw, specialChars);':'var specialCharsFlag = false;'?>

				// CHECK COMPLEXITY MATCH FLAGS
				if(pw.length>=<?=$this->pw_minlength?><?=($this->pw_uppercase)?' && ucaseFlag ':''?><?=($this->pw_lowercase)?' && lcaseFlag ':''?><?=($this->pw_digits)?' && digitsFlag ':''?><?=($this->pw_specialchars)?' && specialCharsFlag ':''?>)
					
					return true;

				else
					
					return false;


			}

			// RUN PASSWORD THROUGH COMPLEXITY CHECK
			function contains(pw,allowedChars) {

				for (i = 0; i < pw.length; i++) {

						var char = pw.charAt(i);
						if (allowedChars.indexOf(char) >= 0) { return true; }

					}

				return false;

			}

			function submitChangePass(frm){


				if(frm.new_pass.value != frm.conf_pass.value){
					alert('ERROR: Password doesn\'t match confirmation.');
					return false;
				}

				// ONLY CHECK PW COMPLEXITY FOR PRIV 4 OR HIGHER
				if(frm.priv.value >= 4){

					if(!pwCheckComplexity(frm.new_pass.value)){

						alert("Error: Password doesn't meet the complexity requirements, please try again.");
						frm.new_pass.select();
						return false;

					}

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
							<?=($expiredpw)?'window.location=\'index.php\';':''?>
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
			<input type="hidden" id="priv" name="priv" value="<?=$_SESSION['user']['priv']?>">
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
				<?=($expiredpw)?'<input type="button" value="Cancel" onclick="window.location.replace(\'index.php?o\')">':''?>
				<input type="submit" value="Change Password">

			</th>
		</tr>
		</form>
		</table><?
	}


}
