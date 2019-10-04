#!/usr/bin/php
<?php

	require_once("/var/www/html/reports/db.inc.php");


	
	
	$cnt = execSQL("update sales s inner join lead_tracking l ".
			" on l.id = s.lead_tracking_id ".
			" set s.employer = l.employer, s.occupation = l.occupation ".
			" WHERE s.sale_time > unix_timestamp(curdate() - interval 10 day);"
	);
	
	
	echo ($cnt > 0)?"Successfully updated $cnt records.\n":"No records updated";
