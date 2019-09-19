#!/usr/bin/php
<?

	$basedir = "/var/www/dev/";

	include_once($basedir."db.inc.php");
	include_once($basedir."utils/db_utils.php");

	connectPXDB();

	// GET ALL XFER's WHOS DISPO TIME IS OLDER THAN 5 MINUTES, AND SITLL HAS NO VERIFICATION INFO

	echo date("g:i:sa m/d/Y")." - Started.\n";

	$total_cnt = execSQL("UPDATE lead_tracking SET dispo='NOVERI' ".
						" WHERE dispo='XFER' ".							// RECORD IS MARKED AS XFER
						" AND agent_dispo_time != 0 ".					// MAKE SURE IT HAS AN AGENT_DISPO TIME (SANITY)
						" AND agent_dispo_time < '".(time() - 300)."' ".// ALL RECORDS OLDER THAN 5 MINUTES
						" AND verifier_lead_id = 0 ".					// WHOS RECORDS LACK A VERIFICATION LEAD ID, MEANING VERIFICATION NEVER GOT IT(HANGUP)
						" AND verifier_id = 0"							// ALSO LACKING VERIFIER USER ID
					);


	echo "\n".date("g:i:sa m/d/Y")." - Done. Total ".number_format($total_cnt)."\n";

