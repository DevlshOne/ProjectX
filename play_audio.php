<?
	session_start();

	$basedir = "";

    include_once($basedir."db.inc.php");
    include_once($basedir."utils/microtime.php");
    include_once($basedir."utils/format_phone.php");
    include_once($basedir."classes/ringing_calls.inc.php");

	$call_id = intval($_REQUEST['call_id']);


	$_SESSION['ringing_calls']->connectPXDB();


	$row = querySQL("SELECT * FROM ringing_calls WHERE id='$call_id'");


	if(!$row){

		die("ERROR: Call not found");

	}



?>
<script>

	function markRecord(status){

		var params = "call_id=<?=$call_id?>&status="+status;

		$.ajax({
			type: "POST",
			cache: false,
			url: 'ajax.php?mode=mark_record',
			data: params,
			error: function(){
				alert("Error saving data. Please contact an admin/IT.");
			},
			success: function(msg){


				//alert(msg);

				parent.loadRings();

				parent.closeAudio();

			}


		});

	}

</script>

<table border="0" width="100%">
<tr>
	<th align="left">Lead ID</th>
	<td><?=$row['lead_id']?></td>
</tr>
<tr>
	<th align="left">Phone Number</th>
	<td><?=format_phone($row['phone_number'])?></td>
</tr>
<tr>
	<th align="left">Time</th>
	<td><?=date("g:ia m/d/Y", $row['time'])?></td>
</tr>
<tr>
	<th align="left">Status</th>
	<td><?= $row['status']?></td>
</tr>
<tr>
	<th align="left">Carrier</th>
	<td><?=$_SESSION['ringing_calls']->carriers[$row['carrier_prefix']]?></td>
</tr>
<tr>
	<th align="left">Recording</th>
	<td><a href="<?= $row['location']?>" target="_blank"><u>Download</u></a></td>
</tr>
<tr>
	<td colspan="2" align="center" height="50">

		<audio id="audio_obj" autoplay controls>
			<source src="<?=$row['location']?>" type="audio/mpeg" />
			Your browser does not support the audio element.
		</audio>

	</td>
</tr>
<tr>
	<td colspan="2">

		<table border="0" width="100%">
		<tr>
			<td align="center"><input type="button" value="Ringing Call" onclick="markRecord('ringing')"></td>
			<td align="center"><input type="button" value="Dead Air" onclick="markRecord('deadair')"></td>
			<td align="center"><input type="button" value="Recording" onclick="markRecord('recording')"></td>
			<td align="center"><input type="button" value="Call Okay" onclick="markRecord('okay')" title="We got 99 problems, but this call ain't one."></td>
		</tr>
		</table>

	</td>
</tr>
</table>
<script>
	parent.applyUniformity();
</script><?


