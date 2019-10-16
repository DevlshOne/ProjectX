#!/usr/bin/php
<?php

	$base_dir = "/var/www/html/reports/";

	require_once($base_dir."db.inc.php");

	include_once($base_dir."dbapi/dbapi.inc.php");
	
	global $process_name;
	
	$process_name = "email_manager_review";
	
	$procid = $_SESSION['dbapi']->process_tracker->logStartProcess($process_name, 'started', implode(" ", $argv));
	
	$process_logs = '';
	
	

	$verbose = false;

	$subject = "Manager Sale/Reviews";

	//$email_to = "jon@revenantlabs.net";
	$email_to = "manager-review@tpfeinc.com";
	//$email_to = "phreak42x@gmail.com";

	$from_email = "support@advancedtci.com";



	$clusters = array();
	$res = query("SELECT * FROM vici_clusters ORDER BY id ASC", 1);
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
		$clusters[$row['id']] = $row;
	}





	// GET ALL UNSENT REVIEW SALES
	$res = query("SELECT * FROM lead_tracking WHERE dispo='REVIEW' AND email_sent='no' ORDER BY vici_cluster_id ASC", 1);
	$reviewsales = array();
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		$reviewsales[] = $row;


	}

// 	$boundary = md5(uniqid(time()));
	$extra_sendmail_parms = " -f$from_email ";

	$headers = "From: $from_email \n";
	$headers .= "Return-Path: $from_email \n";
	$headers .= "Date: ".date("r")."\n";
// 	$headers .= 'Content-Type: multipart/alternative; boundary="' . $boundary . '"' . "\n\n";
// 	$headers .= "Email requires HTML. Please enable HTML email viewing. \n";

// 	$headers .= '--' . $boundary . "\n";


// 	$headers .= 'Content-Type: text/plain; charset=ISO-8859-1' ."\n";
// 	$headers .= 'Content-Transfer-Encoding: 8bit'. "\n\n";




 	$html = "Manager Sale/Reviews\n\n";

 	$x=0;
 	
 	foreach($reviewsales as $sale){
 		
 		if($sale['lead_id'] <= 0) continue;
 		
			
				
		$html .= $clusters[$sale['vici_cluster_id']]['name']."   Lead ID#".$sale['lead_id']." Phone:".$sale['phone_num']." Verifier:".$sale['verifier_username']."\n";
		
		$x++;
	}


	$html .= "\n";


// 	$headers .= '--' . $boundary . "\n";

// 	$headers .= 'Content-Type: text/HTML; charset=ISO-8859-1' ."\n";
// 	$headers .= 'Content-Transfer-Encoding: 8bit'. "\n\n";
/**
	ob_start();
	ob_clean();

	?><html>
	<body>
		<table border="0" width="500px">
		<tr>
			<th>
				<font style="font-size:18px">
					Manager Sale/Reviews
				</font><br />
			</th>
		</tr>
		<tr>
			<td><table border="0" width="100%">
			<tr>
				<th>Cluster</th>
				<th>Lead ID</th>
				<th>Verifier</th>
			</tr><?

			$x = 0;
			foreach($reviewsales as $sale){

				if($sale['lead_id'] <= 0) continue;

				?><tr>
					<td align="center"><?=$clusters[$sale['vici_cluster_id']]['name']?></td>
					<td align="center"><?=$sale['lead_id']?></td>
					<td align="center"><?=$sale['verifier_username']?></td>
				</tr><?
				$x++;
			}

			?></table></td>
		</tr>
		</table>
	</body>
	</html><?

	$html = ob_get_contents()."\n";
	ob_end_clean();
**/

// 	$headers .= '--' . $boundary . "--\n";

	$emailcnt=0;

	if($x > 0){


		## SEND MAIL TO MANAGERS
		if(mail($email_to,$subject, $html,$headers,$extra_sendmail_parms)){

			$emailcnt++;
			
			if($verbose)echo date()." Successfully sent\n";


			foreach($reviewsales as $sale){

				execSQL("UPDATE lead_tracking SET email_sent='yes' WHERE id='".$sale['id']."'");

			}


		}else{

			echo date()." ERROR SENDING EMAIL\n";
		}

	}else{
		if($verbose)echo date("H:i:s m/d/Y")." Skipped, no records.\n";
	}
	
	$str = "Send $emailcnt Emails, for $x records.\n";
	
	$process_logs .= $str;
	echo $str;
	
	
	$_SESSION['dbapi']->process_tracker->logFinishProcess($procid, "completed", $process_logs);
	


