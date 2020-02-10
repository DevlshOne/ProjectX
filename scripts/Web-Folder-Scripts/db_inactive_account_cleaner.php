#!/usr/bin/php
<?php
	// DELETE USER ACCOUNTS THAT HAVE BEEN DELETED FROM VICI BUT NOT THE DATABASE
	// AUTHORS MICHAEL SEGRETI & JONATHAN WILL

        $basedir = "/var/www/html/reports/";

        include_once($basedir."db.inc.php");

        connectPXDB();

        
        $buffertime = time() - 1209600; // TWO WEEKS
        
        $where = " WHERE enabled='no' AND modifiedby_time <= '$buffertime' ";
        
        $userstr = "";
        $x=0;
        $rowarr = fetchAllAssoc("SELECT username FROM `users` $where ");
        foreach($rowarr as $row){
        	
        	
        	$userstr .= ($x++ > 0)?',':'';
        	
        	$userstr .= $row['username'];
        	
        }
        
        
        $cnt =  execSQL("DELETE FROM `users` $where");

        echo date("H:i:s m/d/Y")." - Deleted users ($userstr): $cnt rows deleted!\n";

