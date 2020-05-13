<?	/***************************************************************
	 *	Ringing call reporting system - Pulls RING calls from vici, pulls carrier info from opensips, manage/reporting/emailing
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['ringing_calls'] = new RingingCalls;


class RingingCalls{


	var $recording_relay = "http://10.100.0.65/recording_grabber.php";


	var $attach_recordings = true;


	// CARRIERS USING THEIR DIAL PREFIX AS THE ARRAY INDEX
	var $carriers = array(

				2=>"Powernet",
				5=>"IPLink",

				55=>"CCICOM TDM",
				6=>"CCICOM2",
				7=>"Alcazar networks",
				8=>"Xcast",
				9=>"CCICOM1",
				22=>"Stratics",
				23=>"Stratics2",
				24=>"TouchTone",
				25=>"Xcast2",
				998=>"OpenSips 2",
				999=>"OpenSips 1"


			);

	// EMAIL ADDRESSES OF CARRIER SUPPORT (again using carrier prefix, to index them)
	var $carriers_email = array(
				2=>"noc@powernetco.com,pthurnau@powernetco.com,jwarren@powernetco.com", // POWERNET
				5=>"noc@iplinkltd.com,pmonette@iplinkltd.com", // IPFREELY - IPLink

				55=>"Trouble@ccicom.com",

				6=>"Trouble@ccicom.com", // CCICOM2
				7=>"support@alcazarnetworks.com", // Alcazar networks
				8=>"support@xcastlabs.com", // XCAST
				9=>"Trouble@ccicom.com", // CCICOM1
				24=>"TouchTone@touchtone.net",
				998=>"",
				999=>""// OPENSIPS
			);

	var $opensips_carriers;


	// ADDITIONAL EMAIL TO INCLUDE IN THE EMAIL
	var $cc_email = "support@advancedtci.com";//"jon@revenantlabs.net";


	var $purge_duration = 30; ## HOW MANY DAYS TO KEEP THE DATA FOR

	## Page  Configuration
	var $pagesize	= 20;	## Adjusts how many items will appear on each page
	var $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages


	var $table	= 'ringing_calls';			## Classes main table to operate on
	var $orderby	= 'id';		## Default Order field
	var $orderdir	= 'ASC';	## Default order direction


	## Page  Configuration
	var $frm_name = 'ringnextfrm';
	var $index_name = 'ring_list';
	var $order_prepend = 'ring_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING




	function RingingCalls(){


		## REQURES DB CONNECTION!
		include_once($_SESSION['site_config']['basedir']."/utils/db_utils.php");


		$this->handlePOST();
	}


	function handlePOST(){

		// THIS SHIT IS MOTHERFUCKIGN AJAXED TO THE TEETH
		// SEE api/ringing_calls.api.php FOR POST HANDLING!
		// <3 <3 -Jon

	}

	function handleFLOW(){
		# Handle flow, based on query string


//		if(isset($_REQUEST['add_name'])){
//
//			$this->makeV($_REQUEST['add_name']);
//
//		}else{


		if(!checkAccess('ringing_calls')){


			accessDenied("Ringing Calls");

			return;

		}else{



			$this->listEntrys();

		}


//		}

	}

	function makeCarrierDD($name, $selected, $css, $onchange){

		$out = '<select name="'.$name.'" id="'.$name.'" ';

		$out .= ($css)?' class="'.$css.'" ':'';
		$out .= ($onchange)?' onchange="'.$onchange.'" ':'';
		$out .= '>';

		$out .= '<option value="">[All]</option>';

		foreach($this->carriers as $prefix=>$name){
			$out .= '<option value="'.$prefix.'" ';
			$out .= ($selected == $prefix)?' SELECTED ':'';
			$out .= '>'.htmlentities($name).'</option>';
		}

		$out .= '</select>';

		return $out;
	}

	function makeClusterDD($name, $selected, $css, $onchange){

		$out = '<select name="'.$name.'" id="'.$name.'" ';

		$out .= ($css)?' class="'.$css.'" ':'';
		$out .= ($onchange)?' onchange="'.$onchange.'" ':'';
		$out .= '>';


		foreach($_SESSION['site_config']['db'] as $dbidx=>$db){

			$out .= '<option value="'.$db['cluster_id'].'" ';
			$out .= ($selected == $db['cluster_id'])?' SELECTED ':'';
			$out .= '>'.htmlentities($db['name']).'</option>';
		}

		$out .= '<option value="">[All]</option>';


		$out .= '</select>';

		return $out;
	}

	function makeDateDD($name, $selected, $css, $onchange){

		$out = '<select name="'.$name.'" id="'.$name.'" ';

		$out .= ($css)?' class="'.$css.'" ':'';
		$out .= ($onchange)?' onchange="'.$onchange.'" ':'';
		$out .= '>';

		$out .= '<option value="">[Today]</option>';

		$res = $_SESSION['dbapi']->query("SELECT DISTINCT( DATE(FROM_UNIXTIME(`time`)) ) FROM ringing_calls ");

		while($row = mysqli_fetch_row($res)){

			$out .= '<option value="'.$row[0].'" ';
			$out .= ($selected == $row[0])?' SELECTED ':'';
			$out .= '>'.htmlentities($row[0]).'</option>';


		}



		$out .= '</select>';

		return $out;

	}

	function makeStatusDD($name, $selected, $css, $onchange){


		$out = '<select name="'.$name.'" id="'.$name.'" ';

		$out .= ($css)?' class="'.$css.'" ':'';
		$out .= ($onchange)?' onchange="'.$onchange.'" ':'';
		$out .= '>';

		$out .= '<option value="review">New Problem Calls</option>';
		$out .= '<option value="ringing" '.(($selected == 'ringing')?' SELECTED ':'').'>Ringing Calls</option>';
		$out .= '<option value="deadair" '.(($selected == 'deadair')?' SELECTED ':'').'>Dead Air</option>';
		$out .= '<option value="recording" '.(($selected == 'recording')?' SELECTED ':'').'>Recordings</option>';
		$out .= '<option value="okay" '.(($selected == 'okay')?' SELECTED ':'').'>Okay Calls</option>';


		$out .= '<option value="-1">[All]</option>';


		$out .= '</select>';

		return $out;
	}


	function loadOpenSipCarriers(){

		//$this->connectOpenSIPsDB();


		$res = query("SELECT * FROM load_balancer ");

		$this->opensips_carriers = array();

		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			$this->opensips_carriers[] = $row;

		}


	}

	function lookupOpenSIPsCarrier($ip_address){

		foreach($this->opensips_carriers as $idx=>$carrier){

			list($sip,$tmpip) = preg_split("/:/", $carrier['dst_uri']);

			if($tmpip == $ip_address) return $idx;
		}

		return -1;
	}


	function sendDailyEmail($date = null){

		// INCLUDE PEAR FUNCTIONS
		include_once 'Mail.php';
		include_once 'Mail/mime.php' ;

		if($date == null){
			$date = date("m/d/Y");
		}



		echo date("H:i:s m/d/Y")." - Starting to generate/send daily FAS reports.\n";

		foreach($this->carriers as $prefix=>$name){

			list($textdata, $file_array) = $this->generateTextReport($date, $prefix);

			if($textdata == null){

				echo date("H:i:s m/d/Y")." - No calls found for '$name'\n";
				continue;

			}

			// CALLS HAVE BEEN FOUND FOR TODAY


			$subject = "FAS Call Report ($name) - ".$date;

			$headers   = array(
							"From"		=> "ATC IT <support@advancedtci.com>",
							"Subject"	=> $subject,
							"X-Mailer"	=> "ATC FAS Reporting System",
							"Reply-To"	=> "ATC IT <support@advancedtci.com>"
						);



			// IF THE CARRIER DOESNT HAVE AN EMAIL BUT CC EMAIL IS SENT,
			// WE WILL SEND TO THEM DIRECTLY INSTEAD
			if($this->carriers_email[$prefix] && $this->cc_email){
				$headers['Cc'] = $this->cc_email." <".$this->cc_email.">";
			}

			$mime = new Mail_mime(array('eol' => "\n"));

			$mime->setTXTBody($textdata);

			$cleanup_array = array();
			foreach($file_array as $arr){

				// BREAK ARRAY DOWN
				list($url, $phone, $time) = $arr;


				$filedata = $this->curl_get_file($url);

				// BUILD THE TMP FILE PATH
				$tmpfile = sys_get_temp_dir()."/".format_phone_hyphen($phone)."_".date("g-ia_m-d-Y",$time)."_".uniqid().".mp3";

				// WRITE THE FILE
				file_put_contents($tmpfile, $filedata);

				// STORE THE TMP FILE FOR LATER
				$cleanup_array[] = $tmpfile;


				$mime->addAttachment($tmpfile, "audio/mpeg");

			}



			$mail_body = $mime->get();
			$mail_header=$mime->headers($headers);

			$mail =& Mail::factory('mail');

			if($this->carriers_email[$prefix]){



				//if(!mail($this->carriers_email[$prefix], $subject, $textdata, implode("\r\n", $headers))){


				if($mail->send($this->carriers_email[$prefix], $mail_header, $mail_body) != TRUE){

					echo date("H:i:s m/d/Y")." - ERROR: Mail::send() call failed sending to ".$this->carriers_email[$prefix];

				}else{
					echo date("H:i:s m/d/Y")." - Successfully emailed carrier '$name' daily FAS.\n";
				}

			}else if($this->cc_email){


				if($mail->send($this->cc_email, $mail_header, $mail_body) != TRUE){

					echo date("H:i:s m/d/Y")." - ERROR: mail() call failed sending to ".$this->cc_email;

				}else{

					echo date("H:i:s m/d/Y")." - Successfully emailed ".$this->cc_email." daily FAS.\n";

				}

//				if(!mail($this->cc_email, $subject, $textdata, implode("\r\n", $headers))){
//
//					echo date("H:i:s m/d/Y")." - ERROR: mail() call failed sending to ".$this->carriers_email[$prefix];
//
//				}else{
//					echo date("H:i:s m/d/Y")." - Successfully emailed ".$this->cc_email." daily FAS.\n";
//				}

			}


			// DELETE TEMP FILES
			foreach($cleanup_array as $tmpfile){

				echo date("H:i:s m/d/Y")." - Deleting tmp file: $tmpfile\n";

				// DELETE
				unlink($tmpfile);

			}



		}// END FOREACH (carrier)






		echo date("H:i:s m/d/Y")." - FAS Daily email finished.\n";
	}


	function generateTextReport($date, $carrier_prefix, $mode = 'normal'){

		$stime = strtotime($date);
		$etime = $stime + 86400;


		$res = query("SELECT * FROM ringing_calls ".

										// TIMEFRAME - ONE DAY
										" WHERE time BETWEEN '$stime' AND '$etime' ".

										// CARRIER PREFIX, SO WE CAN SORT THE REPORTS PER CARRIER
										" AND carrier_prefix='$carrier_prefix' ".

										// STATUS IS A PROBLEM TYPE (not okay or awaiting review)
										" AND `status` != 'okay' AND `status` != 'review' ".

										"ORDER BY `status` ASC, `time` ASC");

		if(!$res || mysqli_num_rows($res) <= 0){
			return null;
		}

		switch($mode){
		case 'normal':
		default:
			$output = "FAS Calls - Report for '".$this->carriers[$carrier_prefix]."' on ".date("m/d/Y",$stime)."\n";
			$output.= "Total Calls: ".mysqli_num_rows($res)."\n\n";
			break;
		case 'tsv':

			$output = "Phone#\tStatus\tDate\n";
			break;

		case 'csv':
			$output = "Phone#,Status,Date\n";
			break;
		}

		//$output.= "Phone #\tType\tTime\n";


		$file_array = array();
		$fptr = 0;

		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){


			switch($mode){
			case 'normal':
			default:

				if($row['status'] == 'recording' && $this->attach_recordings){

					$file_array[$fptr++] = array($row['location'], $row['phone_number'], $row['time']);

				}


				$output .= format_phone($row['phone_number'])."\t".$row['status']."\t".date("g:ia m/d/Y T", $row['time'])."\n";
				break;
			case 'tsv':

				$output .= format_phone($row['phone_number'])."\t".$row['status']."\t".date("g:ia m/d/Y T", $row['time'])."\n";
				break;

			case 'csv':
				$output .= format_phone($row['phone_number']).",".$row['status'].",".date("g:ia m/d/Y T", $row['time'])."\n";
				break;
			}



		} // END WHILE LOOP


		switch($mode){
		case 'normal':

			$output .= "\nNote: 'deadair' means No RTP Audio\n".
						(($this->attach_recordings)?"Note: Attachments are for the 'recording' calls.\n":"");

			break;
		}

		return array($output, $file_array);
	}



	function curl_get_file($url){

		// create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, $url);

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // $output contains the output string
        $output = curl_exec($ch);

        // close curl resource to free up system resources
        curl_close($ch);

		return $output;
	}

	function curl_write_temp_file($url, $file_prefix = "RecordignCall-"){

		$data = $this->curl_get_file($url);

		$temp_file = tempnam(sys_get_temp_dir(), $file_prefix);

		file_put_contents($temp_file, $data);

		return $temp_file;
	}


	function listEntrys(){


		?><script>

			var ring_delmsg = 'Are you sure you want to delete this call record?';

			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";


			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;


			var RingsTableFormat = [
				['[play_button]','align_center'],
				['lead_id','align_center'],
				['[phone:phone_number]','align_center'],
				['[time:time]','align_center'],
				['[carrier:carrier_prefix]','align_center'],
				['status','align_center'],
				['[link:location:location]','align_left'],


			];


			// DUMP CARRIER PREFIXES TO JAVASCRIPT
			var carrier_prefixes = [
			<?
				foreach($this->carriers as $prefix=>$name){

					echo '[\''.$prefix.'\',\''.$name.'\'],';

				}

			?>
			];






			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getRingsURL(){

				var frm = getEl('<?=$this->frm_name?>');

				return 'api/api.php'+
								"?get=ringing_calls&"+
								"mode=xml&"+

								's_lead_id='+escape(frm.s_lead_id.value)+"&"+
								's_phone='+escape(frm.s_phone.value)+"&"+
								's_carrier='+escape(frm.s_carrier.value)+"&"+
								's_cluster_id='+escape(frm.s_cluster_id.value)+"&"+

								's_status='+escape(frm.s_status.value)+"&"+
								's_date='+escape(frm.s_date.value)+"&"+

								"index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
								"orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
			}


			var rings_loading_flag = false;
			var page_load_start;

			/**
			* Load the name data - make the ajax call, callback to the parse function
			*/
			function loadRings(){

				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = rings_loading_flag');


				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){

					//console.log("NAMES ALREADY LOADING (BYPASSED) \n");
					return;
				}else{

					eval('rings_loading_flag = true');
				}

				page_load_start = new Date();


				$('#total_count_div').html('<img src="images/ajax-loader.gif" border="0">');



				loadAjaxData(getRingsURL(),'parseRings');

			}


			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parseRings(xmldoc){

				<?=$this->order_prepend?>totalcount = parseXMLData('ring',RingsTableFormat,xmldoc);



				// ACTIVATE PAGE SYSTEM!
				if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){


					makePageSystem('rings',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadRings()'
								);

				}else{

					hidePageSystem('rings');

				}

				var enddate = new Date();

				var loadtime = enddate - page_load_start;

				$('#page_load_time').html("Load and render time: "+loadtime+"ms");


				eval('rings_loading_flag = false');
			}


			function handleRingListClick(id){

				playAudio(id);

			}



			function closeAudio(){


//				var coloridx = $("tr[record_id="+call_id+"]").attr('color_index');
//				$("tr[record_id="+call_id+"] > td").attr('class','row'+coloridx);


				$('#media_player').dialog("close");

				$('#media_player').children().filter("audio").each(function(){
				    this.pause(); // can't hurt
				    delete(this); // @sparkey reports that this did the trick!
				    $(this).remove(); // not sure if this works after null assignment
				});
				$('#media_player').empty();

				$('#media_player').unbind("dialogclose");
			}

			function resetImages(){
				$("#ring_table img").each(function(){
					 //alert($(this).attr("src"));

					 $(this).attr("src", "images/play_button_small.png");
				});
			}

			function markPlayButton(call_id){
				$("#ring_table tr[record_id="+call_id+"] img").attr("src","images/play_button_small_selected.png");
			}

			function playAudio(call_id){


				$('#media_player').dialog("open");

				$('#media_player').children().filter("audio").each(function(){
				    this.pause(); // can't hurt
				    delete(this); // @sparkey reports that this did the trick!
				    $(this).remove(); // not sure if this works after null assignment
				});
				$('#media_player').empty();

				$('#media_player').load("play_audio.php?call_id="+call_id);

				// RESET OTHERS
				resetImages();
				// CHANGE IMAGE
				markPlayButton(call_id);



				// REMOVE AND READD TEH CLOSE BINDING, TO STOP THE AUDIO
				$('#media_player').unbind("dialogclose");
				$('#media_player').bind('dialogclose', function(event) {

					$('#media_player').children().filter("audio").each(function(){
				    	this.pause();
				    	delete(this);
				    	$(this).remove();

					});

					$('#media_player').empty();

					//alert("pausing");
				});


			}

			function resetRingForm(frm){


				frm.s_cluster_id.selectedIndex = 0;
				frm.s_status.selectedIndex = 0;
				frm.s_date.selectedIndex = 0;
				frm.s_lead_id.value = '';
				frm.s_phone.value = '';
				frm.s_carrier.value = '';

				loadRings();

			}


			var ringsrchtog = false;

			function toggleRingSearch(){
				ringsrchtog = !ringsrchtog;
				ieDisplay('ring_search_table', ringsrchtog);
			}

		</script>
		<div id="media_player" title="Playing Call Recording"></div>
        <form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadRings();return false">
			<input type="hidden" name="searching_ring">
		<table border="0" width="100%" class="lb" cellspacing="0">
		<tr>
			<td height="40" class="pad_left ui-widget-header">

				<table class="tightTable">
				<tr>
					<td class="pct75">
                        <h4>Ringing Calls</h4>
                        <button type="button" title="Add" value="Add" onclick="displayAddNameDialog(0)">Add</button>
                        <button type="button" title="Search" value="Search" onclick="toggleNameSearch()">Search</button>
					</td>
					<td width="150" align="center">PAGE SIZE: <select name="<?=$this->order_prepend?>pagesizeDD" id="<?=$this->order_prepend?>pagesizeDD" onchange="<?=$this->index_name?>=0; loadRings();return false">
						<option value="20">20</option>
						<option value="50">50</option>
						<option value="100">100</option>
						<option value="500">500</option>
					</select>
                    </td>
					<td align="right"><?
						/** PAGE SYSTEM CELLS -- INJECTED INTO, BY JAVASCRIPT AFTER AJAX CALL **/?>
						<table border="0" cellpadding="0" cellspacing="0" class="page_system_container">
						<tr>
							<td id="rings_prev_td" class="page_system_prev"></td>
							<td id="rings_page_td" class="page_system_page"></td>
							<td id="rings_next_td" class="page_system_next"></td>
						</tr>
						</table>

					</td>
				</tr>
				</table>

			</td>

		</tr>

		<tr>
			<td colspan="2"><table border="0" width="700" id="ring_search_table">
			<tr>
				<td rowspan="2" width="70" align="center" style="border-right:1px solid #000">


					<div id="total_count_div"></div>

				</td>
				<th class="row2">Cluster</th>
				<th class="row2">Status</th>
				<th class="row2">Lead ID</th>
				<th class="row2">Phone</th>
				<th class="row2">Carrier</th>

				<th class="row2">Date</th>

				<td><input type="submit" value="Search" name="the_Search_button"></td>
			</tr>
			<tr>
				<td align="center">
					<?=$this->makeClusterDD('s_cluster_id', $_REQUEST['s_cluster_id'], '', "loadRings();");?>
				</td>
				<td align="center"><?=$this->makeStatusDD('s_status', $_REQUEST['s_status'], '', "loadRings();")?></td>
				<td align="center"><input type="text" name="s_lead_id" size="5" value="<?=htmlentities($_REQUEST['s_lead_id'])?>"></td>
				<td align="center"><input type="text" name="s_phone" size="10" onkeyup="this.value=this.value.replace(/[^0-9]/g,'')" value="<?=htmlentities($_REQUEST['s_phone'])?>"></td>
				<td align="center">
					<?=$this->makeCarrierDD('s_carrier', $_REQUEST['s_carrier'], '', "");?>
				</td>

				<td align="center">
					<?=$this->makeDateDD('s_date', $_REQUEST['s_date'], '', "loadRings();")?>
				</td>

				<td><input type="button" value="Reset" onclick="resetRingForm(this.form);resetPageSystem('<?=$this->index_name?>');loadRings();"></td>
			</tr>
			</table></td>
		</tr></form>
		<tr>
			<td colspan="2"><table border="0" width="950" id="ring_table">
			<tr>
				<th class="row2">&nbsp;</th>
				<th class="row2"><?=$this->getOrderLink('lead_id')?>Lead ID</a></th>
				<th class="row2"><?=$this->getOrderLink('phone_number')?>Phone Number</a></th>
				<th class="row2"><?=$this->getOrderLink('time')?>Time</a></th>
				<th class="row2"><?=$this->getOrderLink('carrier_prefix')?>Carrier</a></th>
				<th class="row2"><?=$this->getOrderLink('status')?>Status</a></th>
				<th class="row2"><?=$this->getOrderLink('location')?>Recording</a></th>

			</tr><?

			// MAGICAL FUCKING AJAX FAIRIES WILL POPULATE THIS SECTION

			?></table></td>
		</tr></table>

		<script>


			 $(function() {

				 //$( "#tabs" ).tabs();

				 $("#media_player").dialog({
					autoOpen: false,
					width: 500,
					height: 260,
					modal: false,
					draggable:true,
					resizable: false
				});
			 });


			loadRings();



		</script><?

	}




	function pullDataFromAllServers(){

		echo date("H:i:s m/d/Y")." - Pulling ringing calls from all vici clusters (".count($_SESSION['site_config']['db']).")\n";

		$curtime = microtime_float();

		$totalcnt = 0;

		foreach($_SESSION['site_config']['db'] as $idx=>$db){

			echo date("H:i:s m/d/Y")." - Pulling ring calls from: ".$db['name']."\n";

			$cnt = $this->pullDataFromServer($idx);

			echo date("H:i:s m/d/Y")." - ".$cnt." added from: ".$db['name']."\n";

			$totalcnt += $cnt;

		}

		$endtime = microtime_float();

		//echo "<br />\nExecution took: ".($endtime - $curtime)."ms";

		echo date("H:i:s m/d/Y")." - Done, ".$totalcnt." added. Execution took: ".($endtime - $curtime)."ms\n";
	}


	function pullDataFromServer($dbidx){





		if(!connectViciDB($dbidx)){

			echo $_SESSION['site_config']['db'][$dbidx]['sqlhost'].": Error connecting to ". $_SESSION['site_config']['db'][$dbidx]['sqlhost']."\n";
			return false;

		}

		$sql = "SELECT * FROM vicidial_agent_log WHERE status LIKE 'ring' AND date(event_time) = curdate() ";

		$res = query($sql);

		if(!$res || mysqli_num_rows($res) <= 0){


			//echo $_SESSION['site_config']['db'][$dbidx]['sqlhost'].": No records returned\n";

			return 0;


		}

		// ARRAY TO HOLD ALL THE DATA
		$rowarr = array();

		$x=0;
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			// GRAB RECORDING INFO
			list($location, $filename, $startepoch) = queryROW("SELECT location,filename,start_epoch FROM recording_log WHERE vicidial_id='".$row['uniqueid']."'");

			// IGNORE THE WAV FILES, AS THEY ARE STILL PROCESSING/TURNING INTO MP3's
			if(strpos($location, ".wav") > -1){
				$location = "";
			}


			// THE +/- 60 SECONDS, WAS TO ENSURE WE ACCOUNT FOR ANOMOLIES WHERE THE TIMING WASNT EXACT
			$s = $startepoch - 60;
			$e = $startepoch + 60;

			// WE CANT USE THE "uniqueid" HERE, BECAUSE IN SOME OF THE RECORDS, IT POINTS TO A 'SIP/' CHANNEL
			// AND WE APPARENTLY NEED THE 'LOCAL/' RECORD FOR CARRIER IDENT.
			list($channel) = queryROW("SELECT channel FROM vicidial_carrier_log ".
										" WHERE UNIX_TIMESTAMP(call_date) BETWEEN '$s' AND '$e' ".
										" AND lead_id='".$row['lead_id']."' ".
										" AND channel NOT LIKE 'SIP%' ");



			$tmparr = preg_split("/_/", $filename, 2);

			$phone = trim($tmparr[1]);

			// FALL BACK INCASE SOMEONE IN THE FUTURE FUCKS UP THE RECORDING FILENAME FORMAT
			if(!$phone || strlen($phone) < 10){

				// LAST RESORT: GRAB PHONE NUMBER FROM THE vicidial_list TABLE
				list($phone) = queryROW("SELECT phone_number FROM vicidial_list WHERE lead_id='".$row['lead_id']."'");
			}



			$tmparr = preg_split("/\//", $channel, 2);
			$tmparr = preg_split("/@/", $tmparr[1], 2);

			$carrier = substr($tmparr[0], 0, strlen($tmparr[0]) - 11 );



//			$tmparr = preg_split("/\//", $channel);
//
//			$carrier = substr($tmparr[1], 0, $this->prefix_digits );

			// EXTRACT THE CARRIER PREFIX FROM CHANNEL
			//$carrier = $channel[6];

//			if(!$this->carriers[$carrier]){
//
//				echo "CHANNEL: ".$channel."\n";
//				print_r($row);
//
//				//echo "CHANNEL: ".$channel."\n";
//			}



			// RATHER THAN DO THIS HERE, DO IT OUTSIDE THE LOOP, SO YOU DONT HAVE TO DISCONNECT AND RECONNECT OVER AND OVER
//			if($carrier == 999){
//
//				// LOOK UP CARRIER IN OPENSIPS INSTEAD
//
//				$sdate = date("Y-m-d H:i:s", $row['event_time'] - 30);
//				$edate = date("Y-m-d H:i:s", $row['event_time'] + 30);
//
//				$sql = "SELECT * FROM opensips.acc ".
//						" WHERE `to_phone` LIKE '%$phone' ".
//						" AND `method` = 'INVITE' ".
//						" AND `time` BETWEEN '$sdate' AND '$edate' ".
//						" AND `to_ip` IS NOT NULL";
//
//			}else{



				// ADD DATA TO THE ARRAY
				$rowarr[$x] = array(
								'lead_id'=>$row['lead_id'],
								'location'=>$location,
								'carrier_prefix'=>$carrier,
								'phone_number'=>$phone,
								'uniqueid'=>$row['uniqueid'],
								'time'=>strtotime($row['event_time'])
							);

				//print_r($rowarr[$x]);

				$x++;

//			}

		} // END WHILE LOOP

//print_r($rowarr);exit;


	/*	echo "Starting OpenSips PostProcessing, ".count($rowarr)." records to process...\n";

		// DISCONNECT FROM VICI DB
		//mysqli_close($db);


		// PROCESS OPEN SIPS RECORDS
		$this->connectOpenSIPsDB();

		// $this->opensips_carriers should be populated
		$this->loadOpenSipCarriers();



		foreach($rowarr as $idx=>$row){

			/// ONLY DO THIS FOR OPEN SIPS CALLS
			if($row['carrier_prefix'] != 999){
				continue;
			}


			$phone = $row['phone_number'];

			// LOOK UP CALL IN OPEN SIPS DB
			$sdate = date("Y-m-d H:i:s", $row['time'] - 30);
			$edate = date("Y-m-d H:i:s", $row['time'] + 30);

			$sql = "SELECT * FROM opensips.acc ".
					" WHERE `to_phone` LIKE '%$phone' ".
					" AND `method` = 'INVITE' ".
					" AND `time` BETWEEN '$sdate' AND '$edate' ".
					" AND `to_ip` IS NOT NULL";

			$call = querySQL($sql);


			if($call){

				// FIND THE CARRIER
				$cidx = $this->lookupOpenSIPsCarrier($call['to_ip']);

				// ADJUST THE DIAL PREFIX TO MATCH
				$rowarr[$idx]['orig_carrier_prefix'] = $row['carrier_prefix'];
				$rowarr[$idx]['carrier_prefix'] = $this->opensips_carriers[$cidx]['legacy_prefix'];

				echo date("g:i:sa m/d/Y")." - Found ".$phone." - remapping to carrier #".$rowarr[$idx]['carrier_prefix']." - ".$this->carriers[$rowarr[$idx]['carrier_prefix']]."\n";

			}else{

				// ERROR
				echo date("g:i:sa m/d/Y")." - ERROR: Call not found in OpenSips Database for ".$phone." between '$sdate' AND '$edate' \n";

			}

		}



		echo "OpenSips Processing complete. Adding to PX db.\n";

**/


		mysqli_close($_SESSION['db']);


		// CONNECT TO PX DATABASE
		connectPXDB();






		// CRAM THE DATA INTO THE NEW FOUND CONNECTION

		// SAVE THE CLUSTER ID, FOR EASIER ACCESS
		$cluster_id = intval($_SESSION['site_config']['db'][$dbidx]['cluster_id']);


		$sql = 	"INSERT IGNORE INTO `ringing_calls`(time, cluster_id, lead_id, uniqueid, phone_number, carrier_prefix, orig_carrier_prefix, location) VALUES ";

		$x=0;
		foreach($rowarr as $row){

			// SKIP RECORDS WHERE RECORDING LOCATION HASNT BEEN PROCESSED YET
			if(!trim($row['location'])){
				continue;
			}


			if($x > 0)$sql .= ",";

			$sql .= "('".$row['time']."', '$cluster_id', '".$row['lead_id']."', '".$row['uniqueid']."', '".$row['phone_number']."','".$row['carrier_prefix']."','".((isset($row['orig_carrier_prefix']))?$row['orig_carrier_prefix']:$row['carrier_prefix'])."','".mysqli_real_escape_string($_SESSION['db'],$row['location'])."')";

//			execSQL(
//				"INSERT IGNORE INTO `ringing_calls`(time, cluster_id, lead_id, uniqueid, phone_number, carrier_prefix, location) VALUES ".
//				"('".strtotime($row['time'])."', '$cluster_id', '".$row['lead_id']."', '".$row['uniqueid']."', '".$row['phone_number']."','".$row['carrier_prefix']."','".mysqli_real_escape_string($_SESSION['db'],$row['location'])."')"
//			);


			$x++;
		}


		//echo $sql."\n";
		return execSQL($sql);



	}


	// ADDED AS A SECONDARY PASS, TO REMAP CARRIER PREFIX
	function processOpenSipsRecords(){


		/**
		 * LOAD OPENSIPS DB's FROM PX DB INSTEAD OF SITECONFIG
		 */

// CONNECT TO PX DATABASE
		connectPXDB();

		$opensips_arr = array();
		$res = query("SELECT * FROM opensips_servers WHERE enabled='yes' ", 1);
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			$opensips_arr[] = $row;

		}



		$stime = mktime(0,0,0);
		$etime = $stime + 86399;


		foreach($opensips_arr as $opensips){

		//foreach($_SESSION['site_config']['opensipsdb'] as $dbidx=>$opensips){

			// CONNECT TO PX DATABASE
			connectPXDB();

			$res = query("SELECT * FROM ringing_calls WHERE carrier_prefix='".$opensips['prefix']."' AND `time` BETWEEN '$stime' AND '$etime' ");
			$rowarr = array();
			while($row = mysqli_fetch_array($res)){

				$rowarr[] = $row;

			}

			echo "Starting OpenSips PostProcessing(".$opensips['prefix']."), ".count($rowarr)." records to process...\n";

			// DISCONNECT FROM PX DB
			//mysqli_close($db);

			if(count($rowarr) <= 0){

				echo "No records to process for this OpenSips server, Skipping\n";
				continue;

			}

print_r($opensips);

		// PROCESS OPEN SIPS RECORDS

			// CONNECT TO OPENSHITS DB

			$db = mysqli_connect(
				$opensips['ip_address'],
				$opensips['db_user'],
				$opensips['db_pass'],
				$opensips['db_name']
			);

			if(!$db){

				echo $opensips['ip_address'].": Error connecting to ". $opensips['ip_address']."\n";
				return false;

			}


			// DB CONNECTED AT THIS POINT
//			// SELECT THE DATABASE
//			if(!mysql_select_db($opensips['db_name'])){
//
//				echo  $opensips['ip_address'].": Error - Cannot select db.\n";
//
//				return false;
//			}


			// SAVE DB TO SESSION, SO THE db.inc.php FUNCTIONS WORK
			$_SESSION['db'] = $db;


//			if(!connectOpenSipsDB($dbidx)){
//
//
//				echo "ERROR CONNECTING TO OPENSIPS DB! Skipping\n";
//				continue;
//
//			}
			//$this->connectOpenSIPsDB();

			// $this->opensips_carriers should be populated
			$this->loadOpenSipCarriers();



			foreach($rowarr as $idx=>$row){

				$phone = $row['phone_number'];

				// LOOK UP CALL IN OPEN SIPS DB
				$sdate = date("Y-m-d H:i:s", $row['time'] - 30);
				$edate = date("Y-m-d H:i:s", $row['time'] + 30);

				$sql = "SELECT * FROM opensips.acc ".
						" WHERE `to_phone`='1$phone' ".
						" AND `method` = 'INVITE' ".
						" AND `time` BETWEEN '$sdate' AND '$edate' ".
						" AND `to_ip` IS NOT NULL";

	//echo $sql;

				$call = querySQL($sql);


				if($call){

					// FIND THE CARRIER
					$cidx = $this->lookupOpenSIPsCarrier($call['to_ip']);

					// ADJUST THE DIAL PREFIX TO MATCH
					$rowarr[$idx]['orig_carrier_prefix'] = $row['carrier_prefix'];
					$rowarr[$idx]['carrier_prefix'] = $this->opensips_carriers[$cidx]['legacy_prefix'];

					echo date("g:i:sa m/d/Y")." - Found ".$phone." - remapping to carrier #".$rowarr[$idx]['carrier_prefix']." - ".$this->carriers[$rowarr[$idx]['carrier_prefix']]."\n";

				}else{

					// ERROR
					echo date("g:i:sa m/d/Y")." - ERROR: Call not found in OpenSips Database for ".$phone." between '$sdate' AND '$edate' \n";

				}

			}


			// RECONNECT TO PX
			connectPXDB();

			foreach($rowarr as $row){

				// DIDNT GET UPDATED FOR WHATEVER REASON (acc TABLE GOT NUKED)
				if($row['carrier_prefix'] == $opensips['prefix']){
					//skip
					continue;
				}

				execSQL("UPDATE ringing_calls SET ".
						" carrier_prefix='".mysqli_real_escape_string($_SESSION['db'],$row['carrier_prefix'])."', ".
						" orig_carrier_prefix='".mysqli_real_escape_string($_SESSION['db'],$row['orig_carrier_prefix'])."' ".
						" WHERE id='".$row['id']."' ");

			}


		} // END FOREACH!



		echo "OpenSips Processing complete.\n";



	}


	function purgeOldRecords(){


		## ANYTHING OLDER THAN (NOW - PURGE DURATION DAYS)
		$purgetime = time() - ($this->purge_duration * 86400);

		echo date("g:ia m/d/Y")." - Purging all ringing calls older than ".date("g:ia m/d/Y", $purgetime)."\n";

		$cnt = execSQL("DELETE FROM ringing_calls WHERE time < '$purgetime'");

		echo date("g:ia m/d/Y")." - Done, Purged $cnt records.\n";

	}






	function getOrderLink($field){

		$var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';

		$var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";

		$var.= ");loadRings();return false;\">";

		return $var;
	}
}
