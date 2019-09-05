#!/usr/bin/php
<?php
	global $code_conversion_arr;
	global $email_results_to;
	global $email_html;

	global $stime;
	global $etime;

	$email_html = null;

	$base_dir = "/var/www/html/dev/";
	$tmpdir = "/var/log/nams_export/";



	include_once($base_dir."site_config.php");
	include_once($base_dir."db.inc.php");

	include_once($base_dir."classes/pac_reports.inc.php");


	global $campaign_array;
	global $offices;

	global $campaign_totals;


	// GRAB TODAY TIMEFRAME
	$stime = mktime(0,0,0);
	$etime = mktime(23,59,59);

	$unsent_only = true;


	if($argv[1] && !$argv[2] && ($tmptime = strtotime($argv[1])) > 0){

		$stime = mktime(0,0,0, date("m", $tmptime), date("d", $tmptime), date("Y", $tmptime));
		$etime = mktime(23,59,59, date("m", $stime), date("d", $stime), date("Y", $stime));

		$unsent_only = false;

	}else if($argv[1] && $argv[2] && (($tmpstime = strtotime($argv[1])) > 0) && (($tmpetime = strtotime($argv[2])) > 0)){

		$stime = mktime(0,0,0, date("m", $tmpstime), date("d", $tmpstime), date("Y", $tmpstime));
		$etime = mktime(23,59,59, date("m", $tmpetime), date("d", $tmpetime), date("Y", $tmpetime));

		$unsent_only = false;

	// SEND THE UNSENT ONLY
	}else{

		$stime = 0;
		$etime = 0;

	}

	// tESTING TIME
	//$stime = mktime(0,0,0, 12, 15, 2016);
	//$etime = mktime(23,59,59, 12, 15, 2016);


	function TSVFilter($input){
		//return preg_replace("/\t/", " ", $input, -1);


		return generalFilter($input);
	}


	function generalFilter($input){
		return preg_replace('/[^a-zA-Z0-9.,-=_ $@#^&:;\'"\?]/','', $input, -1);
	}

	function endsWith($haystack, $needle) {return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);}


	function getCSVFilename(){
		global $stime;

		$ttime = (!$stime)?time():$stime;
		return "WEB-SALES_".date("m-d-Y", $ttime).".csv";
	}

	function getHTMLFilename(){
		global $stime;

		$ttime = (!$stime)?time():$stime;
		return "WEB-SALES_".date("m-d-Y", $ttime).".html";
	}

	function getZIPFilename(){
		global $stime;

		$ttime = (!$stime)?time():$stime;
		return "WEB-SALES_".date("m-d-Y", $ttime).".zip";
	}


	function generateCoverSheet($totals_arr){

		global $stime, $etime;
		global $email_html;



		if(count($totals_arr) < 1){
			return null;
		}

		ob_start();
		ob_clean();

		?>
		<table border="0" align="center">
		<tr>
			<th><h1>Summary Report</h1></th>
		</tr>
		<tr>
			<th align="left">Office: WEB SALES</th>
		</tr>
		<tr>
			<th align="left">Filename: <?=getCSVFilename()?></th>
		</tr>
		</table>

		<br />

		<table border="1" align="center">
		<tr>
			<th align="left">Campaign</th>
			<th align="right"># of Deals</th>
			<th align="right">Total</th>
		</tr><?

		$totalcount=0;
		$totalamount=0;
		foreach($totals_arr as $code=>$data){


			?><tr>
				<td><?=$code?></td>
				<td align="right"><?=number_format($data['count'])?></td>
				<td align="right">$<?=number_format($data['amount'])?></td>
			</tr><?

			$totalcount += $data['count'];
			$totalamount += $data['amount'];
		}

		?><tr>
			<th>WEB TOTAL:</th>
			<th align="right"><?=number_format($totalcount)?></th>
			<th align="right">$<?=number_format($totalamount)?></th>
		</tr>
		</table>

		<?

		$html = ob_get_contents();

		ob_end_clean();


		$email_html .= $html."<br /><br />\n";


		$html = "<html><head><title>WEB Campaign Totals - ".date("m/d/Y", $stime)."</title></head><body>".
				$html.
				"</body></html>";

		return $html;


	}

	echo "NAMS WEB SALES Export STARTING - ".date("H:i:s m/d/Y")."\n";



	if($stime && $etime){
		echo " - Setting Timeframe to ".date("H:i:s m/d/Y", $stime)." - ".date("H:i:s m/d/Y", $etime)."\n";
	}else{
		echo " - Generating ".(($unsent_only)?"UNSENT":"ALL")." web sales.\n";
	}


	list($data,$totals_arr) = $_SESSION['pac_reports']->exportNams($stime, $etime, $unsent_only);


	if($data != null && trim($data)){
		$output_filename = getCSVFilename();
		$tsv_filename = $tmpdir.$output_filename;

		// WRITE THE DATA FILE
		$written = file_put_contents($tsv_filename, $data);
		// PISS PANTS IF IT DIDNT WORK RIGHT
		if($written != strlen($data)){
			echo "WEB SALES CSV didn't write enough data!\n";
		}


		// WRITE HTML TO FILE
		$html = generateCoverSheet($totals_arr);
		$html_filename = $tmpdir.getHTMLFilename();

		$written = file_put_contents($html_filename, $html);
		if($written != strlen($html)){
			echo "WEB SALES HTML didn't write enough data!\n";
		}


	// ZIP THEM TOGETHER!

		$output_filename = $tmpdir.getZIPFilename();

		$zip = new ZipArchive();
		$zip->open($output_filename, ZIPARCHIVE::CREATE);


		$zip->addFile($tsv_filename, getCSVFilename());
		$zip->addFile($html_filename, getHTMLFilename());

		$zip->close();



		// DELETE THE OTHER FILES
		unlink($tsv_filename);
		unlink($html_filename);



	}

