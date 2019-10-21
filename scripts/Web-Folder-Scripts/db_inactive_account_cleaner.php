#!/usr/bin/php
<?php
	// DELETE USER ACCOUNTS THAT HAVE BEEN DELETED FROM VICI BUT NOT THE DATABASE
	// AUTHORS MICHAEL SEGRETI & JONATHAN WILL

        $basedir = "/var/www/html/reports/";

        include_once($basedir."db.inc.php");

        connectPXDB();

        $cnt =  execSQL("DELETE FROM `users` WHERE enabled='no'");

        echo date("H:i:s m/d/Y")."- $cnt rows deleted!\n";

