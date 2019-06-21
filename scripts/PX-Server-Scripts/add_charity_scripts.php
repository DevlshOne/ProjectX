#!/usr/bin/php
<?

	// INCLUDE DATABASE FILE
	include("/var/www/html/dev/db.inc.php");

	function out($str){	echo date("H:i:s m-d-Y")." - ".$str."\n"; }
/***/

	// VARIABLES

	$screen_num = 2;

	$voice_id = 15;
	$campaign_id=4;


	out("Add Charity scripts\nBy: Jonathan Will\n");



	if(count($argv) < 2){
		die("Error: Missing arguments.\n".
			"ARGS: ".$argv[0]." (VICI CAMPAIGN ID) (*CAMPAIGN ID) (*VOICE ID) \n".
			"Example: ".$argv[0]."NPTAC 4 15\n".
			"* means optional\n\n");
		//die("Error: Please provide the vici campaign ID\nExample: BCSFC NPTAC DVSC\n");

	}


	$dat = array();
	$dat['screen_num'] = $screen_num;
	$dat['variables'] = 'campaign='.$argv[1];
	$dat['campaign_id']=($argv[2])?intval($argv[2]):$campaign_id;
	$dat['voice_id'] = ($argv[3])?intval($argv[3]):$voice_id;



	$x = 71;


	$dat['name'] = "Charity Name";
	$dat['keys'] = $x++;
	aadd($dat,'scripts');

	$dat['name'] = "Charity Phone #";
	$dat['keys'] = $x++;
	aadd($dat,'scripts');

	$dat['name'] = "Charity location/ HQ";
	$dat['keys'] = $x++;
	aadd($dat,'scripts');

	$dat['name'] = "Charity President";
	$dat['keys'] = $x++;
	aadd($dat,'scripts');

	$dat['name'] = "Charity Website";
	$dat['keys'] = $x++;
	aadd($dat,'scripts');

	$dat['name'] = "Charity %";
	$dat['keys'] = $x++;
	aadd($dat,'scripts');

	$dat['name'] = "Tax Deductible";
	$dat['keys'] = $x++;
	aadd($dat,'scripts');

	$dat['name'] = "Charity Description";
	$dat['keys'] = $x++;
	aadd($dat,'scripts');


	out("Done.");




