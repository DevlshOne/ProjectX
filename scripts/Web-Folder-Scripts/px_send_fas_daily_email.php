#!/usr/bin/php
<?php
        $basedir = "/var/www/ringreport/dev/";

        include_once($basedir."db.inc.php");
        include_once($basedir."utils/microtime.php");
        include_once($basedir."utils/format_phone.php");
        include_once($basedir."classes/ringing_calls.inc.php");


		$_SESSION['ringing_calls']->connectPXDB();

        $_SESSION['ringing_calls']->sendDailyEmail();
        
        
