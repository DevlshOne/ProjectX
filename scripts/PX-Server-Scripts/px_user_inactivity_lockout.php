#!/usr/bin/php
<?php

    #$basedir = "/var/www/dev/html/";
    $basedir = "/var/www/html/reports/";

    include_once($basedir."db.inc.php");
    

    # VARIABLE DECLARATIONS
    $inactivity_days = 90;
    $inactity_time = time() - (86400 * $inactivity_days); //strtotime("-".$inactivity_days." days");
    $minimum_priv = "4"; 
    $users_table = "users";
    $users_enable_field = "enabled";
    $user_last_login_field = "last_login";


    $where = "WHERE priv >= '".$minimum_priv."' AND `".$user_last_login_field."` <= '".intval($inactity_time)."' AND enabled='yes' ";
    
    
    
    $rows = fetchAllAssoc("SELECT username FROM `users` ".$where." ORDER BY `username` ASC");
    
    if(count($rows) > 0){
	    echo date("H:i:s m/d/Y")." - Disabling the following users: ";
    	foreach($rows as $row){
    		echo $row['username']." ";
	    }
    	echo "\n";
    }
    
//    exit;
    
    
    # BUILD SQL TO UPDATE USERS ENABLED FIELD BASED ON INACTIVITY TIME
    $sql = "UPDATE `".$users_table."` SET `".$users_enable_field."`='no', modifiedby_time=UNIX_TIMESTAMP() ".$where;

    //echo $sql;exit;

    $cnt = execSQL($sql);

    if($cnt>0){
    	echo date("H:i:s m/d/Y")." - Updated ".$cnt." user records enabled flag to no.\n";
    }
    