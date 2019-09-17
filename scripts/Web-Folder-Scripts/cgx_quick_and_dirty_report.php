<?php
	$basedir = "/var/www/html/dev/";

	include_once($basedir."db.inc.php");
//	include_once($basedir."utils/microtime.php");
//	include_once($basedir."utils/format_phone.php");
	include_once($basedir."utils/db_utils.php");



	$stime = mktime(0,0,0);
	$etime = mktime(23,59,59);


	//echo "Starting ".date("m/d/Y",$stime)."...\n";


	// CONNECT PX DB
	connectPXDB();



	list($salecnt) = queryROW("SELECT COUNT(*) FROM `sales` WHERE `office`='20' AND `sale_time` BETWEEN '$stime' AND '$etime' ");

	list($callcnt) = queryROW("SELECT COUNT(*) FROM `lead_tracking` WHERE `office`='20' AND `time` BETWEEN '$stime' AND '$etime' ");

	list($in_pain_cnt) = queryROW("SELECT COUNT(*) FROM `lead_tracking` WHERE `office`='20' AND `time` BETWEEN '$stime' AND '$etime' AND `in_pain`='yes' ");


	list($diabetes_cnt) = queryROW("SELECT COUNT(*) FROM `lead_data` ".
							" INNER JOIN `lead_tracking` ON `lead_tracking`.id=`lead_data`.lead_tracking_id ".
							" WHERE `lead_tracking`.`office`='20' AND `lead_tracking`.`time` BETWEEN '$stime' AND '$etime' ".

							" AND `lead_data`.xml_data LIKE '%diabetes=\"Diabetes%' ");

	$sql = "SELECT COUNT(*) FROM `lead_data` ".
							" INNER JOIN `lead_tracking` ON `lead_tracking`.id=`lead_data`.lead_tracking_id ".
							" WHERE `lead_tracking`.`office`='20' AND `lead_tracking`.`time` BETWEEN '$stime' AND '$etime' ".

							" AND `lead_data`.xml_data LIKE '%needs_life_insurance=\"true\"%' ";
//echo $sql;
	list($lifeins_cnt) = queryROW($sql);


	echo "Office: 20 <br />\n";
	echo "Date: ".date("m/d/Y", $stime)."<br /><br />\n\n";

	echo "Total Calls: ".number_format($callcnt)."<br />\n";
	echo "Good Deals:  ".number_format($salecnt)."<br />\n";
	echo "IN PAIN leads: ".number_format($in_pain_cnt)."<br />\n";
	echo "DIABETES leads: ".number_format($diabetes_cnt)."<br />\n";
	echo "LIFE INS leads: ".number_format($lifeins_cnt)."<br />\n";


//	$res = query("SELECT * FROM `sales` WHERE office='20' AND sale_time BETWEEN '$stime' AND '$etime' ",1);
//	$sales = array();
//
//	$salecnt=0;
//	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
//
//		if(!is_array($sales[$row['verifier_username']])){
//			$sales[$row['verifier_username']] = array();
//		}
//
//		$sales[$row['verifier_username']][] = $row;
//		$salecnt++;
//	}
//
//
//
//	$res = query("SELECT * FROM `lead_tracking` WHERE office='20' AND `time` BETWEEN '$stime' AND '$etime' ", 1);


