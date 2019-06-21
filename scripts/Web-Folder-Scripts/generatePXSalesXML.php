#!/usr/bin/php
<?
	require_once("/var/www/db.inc.php");





	// GET ALL SALES FOR TODAY

	$stime = mktime(0,0,0);
	$etime = mktime(23,59,59);

	$res = query("SELECT * FROM sales ".
				" WHERE `sale_time` BETWEEN '$stime' AND '$etime' ".
				"".
				"");


	$output = "<?xml version=\"1.0\" standalone=\"yes\"?>\n<DocumentElement>\n";

	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		$output .= "<sales>\n";

		$output .= "<agent_id>".$row['agent_username']."</agent_id>\n";
		$output .= "<agent_name>".$row['agent_name']."</agent_name>\n";

		$output .= "<server>".$row['server_ip']."</server>\n";
		$output .= "<sale_amount>".$row['amount']."</sale_amount>\n";
		$output .= "<user_group>".$row['call_group']."</user_group>\n";
		$output .= "<office>".$row['office']."</office>\n";

		$output .= "</sales>\n";
	}

	$output .= "</DocumentElement>\n";


	echo $output;