#!/usr/bin/php
<?php
        $basedir = "/var/www/ringreport/";

        include_once($basedir."db.inc.php");
        include_once($basedir."util/microtime.php");
        include_once($basedir."classes/ringing_calls.inc.php");


        $_SESSION['ringing_calls']->pullDataFromAllServers();
