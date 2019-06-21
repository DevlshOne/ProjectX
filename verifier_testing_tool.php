<?

	include_once("site_config.php");
	include_once("db.inc.php");
	include_once("utils/jsfunc.php");
	include_once("utils/DropDowns.php");

	$testing_phone = "100".substr("".time(), 0, 7);

//	$testing_phone ="7021112222";

	if(isset($_POST['verifier_testing_lead_poster'])){

		$px_ip = trim($_REQUEST['px_server_ip']);
		$username = trim($_REQUEST['username']);
		$extension = trim($_REQUEST['extension']);
		$campaign = trim($_REQUEST['campaign']);


		list($testingid) = queryROW("SELECT id FROM lead_tracking WHERE phone_num='".addslashes($testing_phone)."'");


		$dat = array();
		$dat['phone_num'] = $testing_phone;
		$dat['extension'] = $extension;
		$dat['campaign'] = $campaign;
		$dat['campaign_code'] = $campaign;


//		$dat['lead_id'] = time();

		$dat['first_name'] = "VerifierTestTool";
		$dat['last_name'] = "Testing";


		// ADD NEW FAKE RECORD
		if(!$testingid){

			$testingid = aadd($dat, 'lead_tracking');

//		// BE A HIPPY AND RECYCLE IT
		}else{

			aedit($testingid, $dat, 'lead_tracking');

		}


		## CURL HIT THE FAKE URL
		$url = "http://".$px_ip.":2288/VerifierPass?lead_id=".urlencode($testingid)."&campaign=".urlencode($campaign)."&phone_num=".urlencode($testing_phone)."&user=".urlencode($username)."&channel=".urlencode($extension)."&comments=TESTING%20YAY&recording_id=110101010&verifier_call_id=VTHEREISNTONE&entry_list_id=0";


		$ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	    $data = curl_exec($ch);
	    $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $response_error = curl_error($ch);

	    ## CLOSE CURL SESSION
	    curl_close($ch);

		if($response_code != 200){

			jsAlert("ERROR: ".$response_code." - ".$response_error);

		}else{
			jsAlert("Successfully Sent!");
		}

	}



?><html>
<head>
	<title>Project X - Management Tools and Reports</title>


		<link rel="stylesheet" href="css/reset.css"> <!-- CSS reset -->

<link href='https://fonts.googleapis.com/css?family=Open+Sans:300,400,700' rel='stylesheet' type='text/css'>

		<link rel="stylesheet" type="text/css" href="css/style.css" />
		<link rel="stylesheet" href="css/navstyle.css"> <!-- Resource style -->
		<link rel="stylesheet" type="text/css" href="css/cupertino/jquery-ui-1.10.3.custom.min.css" />

		<link rel="stylesheet" href="themes/default/css/uniform.default.css" media="screen" />

			<script src="js/jquery-1.10.2.min.js"></script>
			<script src="js/jquery-ui-1.10.3.custom.min.js"></script>
			<script src="js/jquery.uniform.min.js"></script>

<script>
	function applyUniformity(){
		$("input:submit, button, input:button").button();
		$("input:text, input:password, input:reset, input:checkbox, input:radio, input:file").uniform();
	}
</script>


</head>
<body>


<form method="POST" action="<?=$_SERVER['REQUEST_URI']?>">

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
			<td>

				<?
					echo makeServerIPDD('px_server_ip', (($_REQUEST['px_server_ip'])?$_REQUEST['px_server_ip']:"10.101.15.66"), 0);
				?>
				<?/*<input type="text" name="px_server_ip" value="<?=($_REQUEST['px_server_ip'])?$_REQUEST['px_server_ip']:"10.10.0.66"?>" />*/?>

			</td>
		</tr>
		<tr>
			<th>Username:</th>
			<td><input type="text" size="10" name="username" value="<?=($_REQUEST['username'])?$_REQUEST['username']:""?>"></td>
		</tr>
		<tr>
			<th>Extension:</th>
			<td><input type="text" size="10" name="extension" value="<?=($_REQUEST['extension'])?$_REQUEST['extension']:""?>"></td>
		</tr>
		<tr>
			<th>Campaign CODE:</th>
			<td><input type="text" size="6" name="campaign" value="<?=($_REQUEST['campaign'])?$_REQUEST['campaign']:""?>"></td>
		</tr>

		<tr>
			<td colspan="2">

				<input type="submit" value="Generate and Send Fake lead" title="Goddamn it!">

			</td>
		</tr>
		</table>
	</td>
</tr>
</table>
</form>
<script>
	applyUniformity();
</script>
</body>
