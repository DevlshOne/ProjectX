#!/usr/bin/php
<?php

    #$basedir = "/var/www/dev/html/";
    $basedir = "/var/www/html/reports/";

    include_once($basedir."db.inc.php");
    

    # VARIABLE DECLARATIONS
    $inactivity_days = "90";
    $inactity_time = strtotime("-".$inactivity_days." days");
    $minimum_priv = "4"; 
    $users_table = "users";
    $users_enable_field = "enabled";
    $user_last_login_field = "last_login";


    # BUILD SQL TO UPDATE USERS ENABLED FIELD BASED ON INACTIVITY TIME
    $sql = "UPDATE `".$users_table."` SET `".$users_enable_field."`='no' WHERE priv >= '".$minimum_priv."' AND ".$user_last_login_field." <= '".intval($inactity_time)."';";

    $cnt = execSQL($sql);

    if($cnt>0){
        echo "Updated ".$cnt." user records enabled flag to no.\n";
    }
    