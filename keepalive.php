<?php

session_start();


// THATS IT, JUST ENOUGH TO POKE THE SESSION!

if($_SESSION['user']['id']){
	
	include_once("dbapi/dbapi.inc.php");
	
	$_SESSION['dbapi']->users->updateLastActionTime();
	
}