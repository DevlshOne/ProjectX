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

			function resetPWimages(){
				$('#pw_minlength_span').html('<img src="images/circle-red.gif" width="20" border="0" />');
				$('#pw_letter_span').html('<img src="images/circle-red.gif" width="20" border="0" />');
				$('#pw_digit_span').html('<img src="images/circle-red.gif" width="20" border="0" />');
				$('#pw_symbol_span').html('<img src="images/circle-red.gif" width="20" border="0" />');
			}
		
			// JS FUNCTIONS TO CHECK PASSWORD COMPLEXITY
			function pwCheckComplexity(pw){

				// FIRST, RESET THE IMAGES 
				resetPWimages();
				
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

				
				<?
				
				if($this->pw_uppercase && $this->pw_lowercase){
					?>if(ucaseFlag == true && lcaseFlag == true){
						$('#pw_letter_span').html('<img src="images/circle-green.gif" width="20" border="0" />');
					}<?
					
				}else if($this->pw_uppercase || $this->pw_lowercase){
					?>if(ucaseFlag == true || lcaseFlag == true){
						$('#pw_letter_span').html('<img src="images/circle-green.gif" width="20" border="0" />');
					}<?
					
				}
				?>
				
				
				if(digitsFlag){
					$('#pw_digit_span').html('<img src="images/circle-green.gif" width="20" border="0" />');
				}

				if(specialCharsFlag){
					$('#pw_symbol_span').html('<img src="images/circle-green.gif" width="20" border="0" />');
				}

				if(pw.length >= <?=$this->pw_minlength?>){
					$('#pw_minlength_span').html('<img src="images/circle-green.gif" width="20" border="0" />');
				}
				
				
				// CHECK COMPLEXITY MATCH FLAGS
				if(pw.length >= <?=$this->pw_minlength?><?=($this->pw_uppercase)?' && ucaseFlag ':''?><?=($this->pw_lowercase)?' && lcaseFlag ':''?><?=($this->pw_digits)?' && digitsFlag ':''?><?=($this->pw_specialchars)?' && specialCharsFlag ':''?>){

					
					
					return true;

				}else{
					
					return false;

				}
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

					if(frm.old_pass.value == frm.conf_pass.value){
						alert("ERROR: Unable to use previous password.");
						return false;
					}

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
		<form method="POST" action="<?=stripurl()?>" onsubmit="return submitChangePass(this)"  autocomplete="off" >
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
			<td><input type="password" name="old_pass" id="old_pass"  autocomplete="off" ></td>
		</tr>
		<tr>
			<td colspan="2" style="padding:10px">
			
				<span class="big">New Password requirements:</span><br />
				<ul>
					<li><span id="pw_minlength_span"><img src="images/circle-red.gif" width="20" border="0" /></span>Must be at least <?=$this->pw_minlength?> characters.</li>
				<?	
					if($this->pw_uppercase == true && $this->pw_lowercase == true){
						?><li><span id="pw_letter_span"><img src="images/circle-red.gif" width="20" border="0" /></span>Must contain both UPPER and LOWER case letters.</li><?
						
					}else if($this->pw_uppercase == true || $this->pw_lowercase == true){
						
						?><li><span id="pw_letter_span"><img src="images/circle-red.gif" width="20" border="0" /></span>Must contain <?=($this->pw_uppercase)?"UPPER":"LOWER"?> case letters.</li><?
					}
					
					if($this->pw_digits){
						?><li><span id="pw_digit_span"><img src="images/circle-red.gif" width="20" border="0" /></span>Must contain number(s)</li><?
					}
					
					if($this->pw_specialchars == true){
						
						?><li><span id="pw_symbol_span"><img src="images/circle-red.gif" width="20" border="0" /></span>Must contain symbols(s)</li><?
					}
			
			
				?></ul>
			</td>
		</tr>
		<tr>
			<th align="left">New Password:</th>
			<td><input type="password" name="new_pass" id="new_pass" onkeyup="pwCheckComplexity(this.value)"  autocomplete="off" ></td>
		</tr>
		<tr>
			<th align="left">Confirm Password:</th>
			<td><input type="password" name="conf_pass" id="conf_pass"  autocomplete="off" ></td>
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
