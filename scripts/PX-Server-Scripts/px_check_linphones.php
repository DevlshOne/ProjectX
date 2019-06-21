#!/usr/bin/php
<?php

	//$server = "10.101.1.5";
	$server = "localhost";

	$result = file_get_contents("http://".$server.":2288/Status/?textmode");


	echo $result;



