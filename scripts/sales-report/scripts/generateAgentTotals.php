#!/usr/bin/php
<?

	$agent_total_dir = "/srv/www/htdocs/vicidialmsg/totals/";

	$verbose = true;

	// NEED THIS DATA ACCESSABLE IN FUNCTIONS
	global $combine_users,$hours, $offices, $records, $groups, $sale_totals, $counts;


	function getBaseDir(){$out = dirname($_SERVER["SCRIPT_NAME"]); if($out[strlen($out)-1] != '/')$out .= '/'; return $out; }

	$basedir = "/srv/www/htdocs/sales/";//getBaseDir();



	require_once($basedir."site_config.php");
	require_once($_SESSION['site_config']['basedir']."/db.inc.php");
	require_once($_SESSION['site_config']['basedir']."/utils/report_utils.php");
	require_once($_SESSION['site_config']['basedir']."/utils/stripurl.php");


	$sales_file = $_SESSION['site_config']['xml_dir']."sales.xml";
	$hours_file = $_SESSION['site_config']['xml_dir']."vicihours.xml";

	// REPORT ON ALL OFFICES
	$showing_offices = null;

	// REPORT ON INDIVIDUAL USERS
	$combine_users = false;

/**
 * Hours parsing
 */
	parseHoursData($hours_file);
/**
 * SALES PARSING
 */
	parseSalesData($sales_file);


	$sales_totals = getUserTotals($records);



	// SEND DATA TO THE DATABASE





	$group_totals = array();
	$group_ranks = array();

	// WRITE THE AGENTS TOTAL FILES
	foreach($sales_totals as $obj){
		$filename = $agent_total_dir.$obj->user;

		$filedata = "$".number_format($obj->amount);

		if(file_put_contents($filename,$filedata)  === FALSE){
			echo "Error writing to: $filename\n";
		}else{
			if($verbose)echo "Wrote $filename: ".$filedata."\n";
		}

		if(!array_key_exists($obj->group, $group_totals)){
			$group_totals[$obj->group] = 0;
			$group_ranks[$obj->group] = array();
		}

		$group_totals[$obj->group] += $obj->amount;
		$group_ranks[$obj->group][$obj->user] = $obj->amount;

	}

	// WRITE THE CAMPAIGN/GROUP TOTAL
	foreach($groups as $group){

		$filename = $agent_total_dir.$group;
		$filedata = "$".number_format($group_totals[$group]);

		if(file_put_contents($filename,$filedata)  === FALSE){
			echo "Error writing to: $filename\n";
		}else{
			if($verbose)echo "Wrote $filename: ".$filedata."\n";
		}





		// WRITE THE RANKING FILES
		$filename = $agent_total_dir.$group."rank.html";
		$filedata = '<table border="0">';
		$x=1;

		arsort($group_ranks[$group]);

		foreach($group_ranks[$group] as $user=>$amount){

			$filedata .= "<tr>".
						'<th align="left">'.$x.":</th>".
						"<td>".$user."</td>".
						'<td align="right">$'.number_format($amount).'</td>'.
						'</tr>';

			$x++;
		}
		$filedata .= '</table>';
		if(file_put_contents($filename,$filedata)  === FALSE){
			echo "Error writing to: $filename\n";
		}else{
			if($verbose)echo "Wrote $filename: ".$filedata."\n";
		}


	}



