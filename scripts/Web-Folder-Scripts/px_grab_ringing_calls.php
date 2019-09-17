#!/usr/bin/php
<?php
        $basedir = "/var/www/dev/";

        include_once($basedir."db.inc.php");
        include_once($basedir."utils/microtime.php");
        include_once($basedir."classes/ringing_calls.inc.php");


        $_SESSION['ringing_calls']->pullDataFromAllServers();
