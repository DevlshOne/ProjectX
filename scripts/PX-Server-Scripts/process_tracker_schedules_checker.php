#!/usr/bin/php
<?php

    /***
     * 
     * Process Tracker Schedules Checker
     * 
     *  
     */

    # DISABLE MOST ERROR REPORTING
    error_reporting(E_ERROR | E_PARSE);

    # MODULE INCLUDES & VARIABLE DECLARATIONS
    #$base_dir = "/var/www/html/reports/";
    $base_dir = "/var/www/html/ProjectX-ReportsAndAdmin/";

    require_once($base_dir."db.inc.php");

	include_once($base_dir."dbapi/dbapi.inc.php");

    $logfile = $base_dir."scripts/PX-Server-Scripts/process_tracker_schedules_".date('d-M-Y').".log";

    $base_sql = "SELECT * FROM `".$_SESSION['dbapi']->process_tracker->schedule_table."` WHERE 1 ";

    $curtime = time();

    $failed_checks = [];    # LETS STORE A FAILED CHECK WHENEVER WE DONT GET A MATCH THEN RUN THROUGH IT AT THE END


    # FUNCTIONS (MOVE TO DB FILE MAYBE?)
    function wh_log($logfile,$log_msg) {
        $log_time = date('Y-m-d h:i:sa');
        file_put_contents($logfile, $log_time." - ".$log_msg."\n", FILE_APPEND);
    }


    ###
    ### MAIN
    ###
    wh_log($logfile,"--------PROCESS TRACKER SCHEDULE CHECKER START--------");
    
    ### RUN HOURLY CHECK
    wh_log($logfile,"Running check for schedules that run hourly.");

    # BUILD SQL AND GRAB ENABLED SCHEDULES TO BE CHECKED HOURLY
    $hourly_sql = $base_sql." AND `enabled` = 'yes' AND `script_frequency` = 'hourly' ";

    # CHECK FOR SCHEDULES WITH START TIME IN THE PAST 30 MINUTES
    $hourly_sql .= " AND `time_start` BETWEEN SUBTIME(CURRENT_TIME(), 003000) AND CURRENT_TIME() ";

    # RUN QUERY AND GRAB RESULTS FOR HOURLY SCHEDULES
    $hourly_res = $_SESSION['dbapi']->query($hourly_sql);

    # CHECK IF ROWS RETURNED
    if(($cnt=mysqli_num_rows($hourly_res)) > 0){

        # LOOP THROUGH RESULTS AND RUN PROCESS CHECKS
        wh_log($logfile,"Found ".$cnt." schedules that match the hourly check criteria.");
        
        while($hourly_row = mysqli_fetch_array($hourly_res, MYSQLI_ASSOC)){

            # CHECK FOR COMPLETED PROCESSES BASED ON SCHEDULE CRITERIA
            wh_log($logfile,"Running check - name: ".$hourly_row['schedule_name']." process_code: ".$hourly_row['script_process_code']." time_start: ".$hourly_row['time_start']." time_margin: ".$hourly_row['time_margin']);
            
            # GET TIME CALCULATIONS

            # RUN QUERY AGAINST PROCESS TRACKER TABLE AND MATCH WITH SCHEDULE INFO

            # FAILED RUN CHECK INFO GATHERING
            #$hourly_row['schedule_name']
            #$hourly_row['script_process_code']
            #$hourly_row['time_start']
            #$hourly_row['time_margin']
            #$hourly_row['notification_email']
    
        }
		
    } else {
    
        wh_log($logfile,"Found 0 schedules that match the hourly check criteria.");
                
    }

    
    







    # RUN DAILY CHECK

    # RUN WEEKLY CHECK

    # RUN MONTHLY CHECK

    # FAILED RUN BREAKDOWN AND ALERTING

    //wh_log($logfile,"HEYOOOOO");

    # CLEAN-UP AND CONSOLE OUTPUT



