#!/usr/bin/php
<?php

	//$cmd = "normalize-audio -n "; // NO CHANGE MODE - VIEW ONLY
	$cmd = "normalize-audio "; // ACTUALLY CHANGE FILES

foreach($argv as $idx=>$arg){
	if($idx == 0)continue;

	//print_r($arg);
	$cmd .= " ".escapeshellarg($arg)." ";



}

echo `$cmd`."\n";
