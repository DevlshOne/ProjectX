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

    $schedule_base_sql = "SELECT * FROM `".$_SESSION['dbapi']->process_tracker->schedule_table."` WHERE 1";
    $process_base_sql = "SELECT * FROM `".$_SESSION['dbapi']->process_tracker->table."` WHERE 1";

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
    
    ### HOURLY SCHEDULE CHECK
    // # RUN EVERYTIME SINCE CHECKER IS EXPECTED TO RUN EVERY HALF HOUR
    // wh_log($logfile,"Running hourly schedule check...");

    // # BUILD SQL AND GRAB ENABLED SCHEDULES TO BE CHECKED HOURLY
    // $hourly_sql = $schedule_base_sql." AND `enabled` = 'yes' AND `script_frequency` = 'hourly' ";

    // #$hourly_sql .= " AND `time_start` BETWEEN SUBTIME(CURRENT_TIME(), 003000) AND CURRENT_TIME() "; <-GOOD FOR DAILY CHECK MAYBE

    // # RUN QUERY AND GRAB RESULTS FOR HOURLY SCHEDULES
    // $hourly_res = $_SESSION['dbapi']->query($hourly_sql);

    // # CHECK IF ROWS RETURNED
    // if(($resultscnt=mysqli_num_rows($hourly_res)) > 0){

    //     # LOOP THROUGH RESULTS AND RUN PROCESS CHECKS
    //     wh_log($logfile,"Found ".$resultscnt." hourly schedules...");
        
    //     while($hourly_row = mysqli_fetch_array($hourly_res, MYSQLI_ASSOC)){

    //         # CHECK FOR COMPLETED PROCESSES BASED ON SCHEDULE CRITERIA
    //         wh_log($logfile,"Checking schedule - process_code: ".$hourly_row['script_process_code']." time_start: ".$hourly_row['time_start']." time_margin: ".$hourly_row['time_margin']);

    //         # EXTRACT MINUTE FROM TIME START
    //         $time_start = explode(":",$hourly_row['time_start']);
    //         $time_start_minute = $time_start[1];

    //         # BUILD TIMESTAMP RANGE FOR PROCESS TRACKER RUN STATUS MATCH
    //         # BEGIN WITH START TIME MINUTE AFTER THE HOUR
    //         $time_start_begin = strtotime(date('H').":".$time_start_minute.":00");

    //         # SET TIME MARGIN TO 5 MINUTES AS DEFAULT IF NONE IS PROVIDED
    //         $time_start_margin = ($hourly_row['time_margin']==0)?5:$hourly_row['time_margin'];

    //         # END WITH START TIME PLUS TIME MARGIN
    //         $time_start_end = strtotime(date('H').":".($time_start_minute + $time_start_margin).":00");
            
    //         # BUILD SQL FOR COMPLETED PROCESS CHECK
    //         $process_check_sql = $process_base_sql." AND `process_code` = '".$hourly_row['script_process_code']."' AND `time_started` >= '".$time_start_begin."' AND `time_ended` <= '".$time_start_end."' AND `result` = 'completed' ";

    //         # RUN QUERY AGAINST PROCESS TRACKER TABLE AND MATCH WITH SCHEDULE INFO
    //         $hourly_check_res = $_SESSION['dbapi']->query($process_check_sql);

    //         # IF COMPLETED PROCESS FOUND DO NOTHING BUT UPDATE STATUS
    //         if(($processcnt=mysqli_num_rows($hourly_check_res)) > 0){

    //             wh_log($logfile,"- Schedule - process_code: ".$hourly_row['script_process_code']." found ".$processcnt." completed processes.");
    //             $_SESSION['dbapi']->process_tracker->updateScheduleStatus($hourly_row['id'],'success',$curtime);

    //         } else {

    //             # COMPLETED PROCESS NOT FOUND, START FAILED CHECK INFO GATHERING
    //             # ADD ALL THE INFO FROM THE SCHEDULE WITH ADDITIONAL INFORMATION FOR FAILED CHECK
    //             wh_log($logfile,"- Schedule - process_code: ".$hourly_row['script_process_code']." found 0 completed processes - failed check info gathering started.");

    //             $_SESSION['dbapi']->process_tracker->updateScheduleStatus($hourly_row['id'],'fail',$curtime);
    //             $hourly_row['last_failed'] = $curtime;
    //             $failed_checks[] = $hourly_row;


    //         }


    
    //     }
		
    // } else {
    
    //     wh_log($logfile,"Found 0 hourly check schedules...");
                
    // }


    # RUN DAILY CHECK
    wh_log($logfile,"Running daily schedule check...");

    # BUILD SQL AND GET ENABLED SCHEDULES TO BE CHECKED DAILY
    $daily_sql = $schedule_base_sql." AND `enabled` = 'yes' AND `script_frequency` = 'daily' ";

    # FIND SCHEDULES WITH PROCESSES THAT SHOULD HAVE COMPLETED IN THE PAST HALF HOUR
    $daily_sql .= " AND `time_start` BETWEEN SUBTIME(CURRENT_TIME(), 003000) AND CURRENT_TIME() ";

    # RUN QUERY AND GET RESULTS FOR DAILY SCHEDULES
    $daily_res = $_SESSION['dbapi']->query($daily_sql);

    # CHECK IF ROWS RETURNED
    if(($resultscnt=mysqli_num_rows($daily_res)) > 0){

        # LOOP THROUGH RESULTS AND RUN PROCESS CHECKS
        wh_log($logfile,"Found ".$resultscnt." daily schedules...");
        
        while($daily_row = mysqli_fetch_array($daily_res, MYSQLI_ASSOC)){

            # CHECK FOR COMPLETED PROCESSES BASED ON SCHEDULE CRITERIA
            wh_log($logfile,"Checking schedule - process_code: ".$daily_row['script_process_code']." time_start: ".$daily_row['time_start']." time_margin: ".$daily_row['time_margin']);

            # BUILD TIMESTAMP RANGE FOR PROCESS TRACKER RUN STATUS MATCH
            # BEGIN WITH START TIME 
            $time_start = strtotime($daily_row['time_start']);

            # END WITH END TIME PLUS TIME MARGIN
            $time_end = strtotime($daily_row['time_end']) + ($daily_row['time_margin'] * 60);
            
            # BUILD SQL FOR COMPLETED PROCESS CHECK
            $process_check_sql = $process_base_sql." AND `process_code` = '".$daily_row['script_process_code']."' AND `time_started` >= '".$time_start."' AND `time_ended` <= '".$time_end."' AND `result` = 'completed' ";

            # RUN QUERY AGAINST PROCESS TRACKER TABLE AND MATCH WITH SCHEDULE INFO
            $daily_check_res = $_SESSION['dbapi']->query($process_check_sql);

            # IF COMPLETED PROCESS FOUND DO NOTHING BUT UPDATE STATUS
            if(($processcnt=mysqli_num_rows($daily_check_res)) > 0){

                wh_log($logfile,"- Schedule - process_code: ".$daily_row['script_process_code']." found ".$processcnt." completed processes.");
                $_SESSION['dbapi']->process_tracker->updateScheduleStatus($daily_row['id'],'success',$curtime);

            } else {

                # COMPLETED PROCESS NOT FOUND, START FAILED CHECK INFO GATHERING
                # ADD ALL THE INFO FROM THE SCHEDULE WITH ADDITIONAL INFORMATION FOR FAILED CHECK
                wh_log($logfile,"- Schedule - process_code: ".$daily_row['script_process_code']." found 0 completed processes - failed check info gathering started.");

                $_SESSION['dbapi']->process_tracker->updateScheduleStatus($daily_row['id'],'fail',$curtime);
                $daily_row['last_failed'] = $curtime;
                $failed_checks[] = $daily_row;


            }


    
        }
		
    } else {
    
        wh_log($logfile,"Found 0 daily schedules...");
                
    }

    # RUN WEEKLY CHECK

    # RUN MONTHLY CHECK

    # FAILED RUN BREAKDOWN AND ALERTING

    print_r($failed_checks);

    # CLEAN-UP AND CONSOLE OUTPUT



