#!/usr/bin/php
<?php

    /***
     * 
     * Process Tracker Schedules Checker
     * Written by: Daniel Brummer
     *  
     */
	session_start();

    # DISABLE MOST ERROR REPORTING
    error_reporting(E_ERROR | E_PARSE);

    # MODULE INCLUDES & VARIABLE DECLARATIONS
    $base_dir = "/var/www/html/staging-git/";
    //$base_dir = "/var/www/html/ProjectX-ReportsAndAdmin/";

    require_once($base_dir."db.inc.php");

	include_once($base_dir."dbapi/dbapi.inc.php");

    $logfile = "/var/log/px-process_tracker_schedules_".date('d-M-Y').".log";

    $schedule_base_sql = "SELECT * FROM `".$_SESSION['dbapi']->process_tracker->schedule_table."` WHERE 1";
    

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
    wh_log($logfile,"-------- PROCESS TRACKER SCHEDULES --------");
    
    ### HOURLY SCHEDULE CHECK
    wh_log($logfile,"Running hourly schedule check...");

    # BUILD SQL AND GRAB ENABLED SCHEDULES TO BE CHECKED HOURLY
    $hourly_sql = $schedule_base_sql." AND `enabled` = 'yes' AND `script_frequency` = 'hourly' ";

    # RUN QUERY AND GRAB RESULTS FOR HOURLY SCHEDULES
    $hourly_res = $_SESSION['dbapi']->query($hourly_sql);

    # CHECK IF ROWS RETURNED
    if(($resultscnt=mysqli_num_rows($hourly_res)) > 0){

        # LOOP THROUGH RESULTS AND RUN PROCESS CHECKS
        wh_log($logfile,"Found ".$resultscnt." hourly schedules to check...");
        
        while($hourly_row = mysqli_fetch_array($hourly_res, MYSQLI_ASSOC)){

            # CHECK FOR COMPLETED PROCESSES BASED ON SCHEDULE CRITERIA
            wh_log($logfile,"Checking schedule - process_code: ".$hourly_row['script_process_code']." time_start: ".$hourly_row['time_start']." time_margin: ".$hourly_row['time_margin']);

            # EXTRACT MINUTE FROM TIME START
            $time_start = explode(":",$hourly_row['time_start']);
            $time_start_minute = $time_start[1];

            # BUILD TIMESTAMP RANGE FOR PROCESS TRACKER RUN STATUS MATCH
            # BEGIN WITH START TIME MINUTE AFTER THE HOUR
            $time_start_begin = strtotime(date('H').":".$time_start_minute.":00");

            # SET TIME MARGIN TO 5 MINUTES AS DEFAULT IF NONE IS PROVIDED
            $time_start_margin = ($hourly_row['time_margin']==0)?5:$hourly_row['time_margin'];

            # END WITH START TIME PLUS TIME MARGIN
            $time_start_end = strtotime(date('H').":".($time_start_minute + $time_start_margin).":00");

            # RUN PROCESS CHECK
            if($_SESSION['dbapi']->process_tracker->processCheck($hourly_row['script_process_code'],$time_start_begin,$time_start_end)) {

                # PROCESS CHECK IS TRUE
                wh_log($logfile,"- Schedule - process_code: ".$hourly_row['script_process_code']." found completed process.");
                $_SESSION['dbapi']->process_tracker->updateScheduleStatus($hourly_row['id'],'success',$curtime);
                $hourly_row['last_success'] = $curtime;


            } else {

                # COMPLETED PROCESS NOT FOUND, START FAILED CHECK INFO GATHERING
                # ADD ALL THE INFO FROM THE SCHEDULE WITH ADDITIONAL INFORMATION FOR FAILED CHECK
                wh_log($logfile,"- Schedule - process_code: ".$hourly_row['script_process_code']." found no completed processes - failed check info gathering started.");

                $_SESSION['dbapi']->process_tracker->updateScheduleStatus($hourly_row['id'],'fail',$curtime);
                $hourly_row['last_failed'] = $curtime;
                $failed_checks[] = $hourly_row;


            }
                
        }
		
    } else {
    
        wh_log($logfile,"Found no hourly schedules to check...");
                
    }


    ### DAILY SCHEDULE CHECK
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
        wh_log($logfile,"Found ".$resultscnt." daily schedules to check...");
        
        while($daily_row = mysqli_fetch_array($daily_res, MYSQLI_ASSOC)){

            # CHECK FOR COMPLETED PROCESSES BASED ON SCHEDULE CRITERIA
            wh_log($logfile,"Checking schedule - process_code: ".$daily_row['script_process_code']." time_start: ".$daily_row['time_start']." time_margin: ".$daily_row['time_margin']);

            # BUILD TIMESTAMP RANGE FOR PROCESS TRACKER RUN STATUS MATCH
            # BEGIN WITH START TIME 
            $time_start = strtotime($daily_row['time_start']);

            # END WITH END TIME PLUS TIME MARGIN
            $time_end = strtotime($daily_row['time_end']) + ($daily_row['time_margin'] * 60);

            # RUN PROCESS CHECK
            if($_SESSION['dbapi']->process_tracker->processCheck($daily_row['script_process_code'],$time_start,$time_end)) {

                # PROCESS CHECK IS TRUE
                wh_log($logfile,"- Schedule - process_code: ".$daily_row['script_process_code']." found completed processes.");
                $_SESSION['dbapi']->process_tracker->updateScheduleStatus($daily_row['id'],'success',$curtime);
                $daily_row['last_success'] = $curtime;


            } else {

                # COMPLETED PROCESS NOT FOUND, START FAILED CHECK INFO GATHERING
                # ADD ALL THE INFO FROM THE SCHEDULE WITH ADDITIONAL INFORMATION FOR FAILED CHECK
                wh_log($logfile,"- Schedule - process_code: ".$daily_row['script_process_code']." found no completed processes - failed check info gathering started.");

                $_SESSION['dbapi']->process_tracker->updateScheduleStatus($daily_row['id'],'fail',$curtime);
                $daily_row['last_failed'] = $curtime;
                $failed_checks[] = $daily_row;


            }
    
        }
		
    } else {
    
        wh_log($logfile,"Found no daily schedules to check...");
                
    }

    ### WEEKLY SCHEDULE CHECK
    wh_log($logfile,"Running weekly schedule check...");

    # BUILD SQL AND GET ENABLED SCHEDULES TO BE CHECKED DAILY
    $weekly_sql = $schedule_base_sql." AND `enabled` = 'yes' AND `script_frequency` = 'weekly' ";

    # FIND SCHEDULES WITH PROCESSES THAT SHOULD HAVE COMPLETED IN THE PAST HALF HOUR
    $weekly_sql .= " AND `time_start` BETWEEN SUBTIME(CURRENT_TIME(), 003000) AND CURRENT_TIME() ";

    # DAY OF WEEK CHECK SQL
    $cur_day_of_week = strtolower(date("D",$curtime));
    $weekly_sql .= " AND FIND_IN_SET('".$cur_day_of_week."',time_dayofweek) > 0 ";

    # RUN QUERY AND GET RESULTS FOR DAILY SCHEDULES
    $weekly_res = $_SESSION['dbapi']->query($weekly_sql);

    # CHECK IF ROWS RETURNED
    if(($resultscnt=mysqli_num_rows($weekly_res)) > 0){

        # LOOP THROUGH RESULTS AND RUN PROCESS CHECKS
        wh_log($logfile,"Found ".$resultscnt." weekly schedules to check...");
        
        while($weekly_row = mysqli_fetch_array($weekly_res, MYSQLI_ASSOC)){

            # CHECK FOR COMPLETED PROCESSES BASED ON SCHEDULE CRITERIA
            wh_log($logfile,"Checking schedule - process_code: ".$weekly_row['script_process_code']." time_start: ".$weekly_row['time_start']." time_margin: ".$weekly_row['time_margin']);

            # BUILD TIMESTAMP RANGE FOR PROCESS TRACKER RUN STATUS MATCH
            # BEGIN WITH START TIME 
            $time_start = strtotime($weekly_row['time_start']);

            # END WITH END TIME PLUS TIME MARGIN
            $time_end = strtotime($weekly_row['time_end']) + ($weekly_row['time_margin'] * 60);

            # RUN PROCESS CHECK
            if($_SESSION['dbapi']->process_tracker->processCheck($weekly_row['script_process_code'],$time_start,$time_end)) {

                # PROCESS CHECK IS TRUE
                wh_log($logfile,"- Schedule - process_code: ".$weekly_row['script_process_code']." found completed processes.");
                $_SESSION['dbapi']->process_tracker->updateScheduleStatus($weekly_row['id'],'success',$curtime);
                $weekly_row['last_success'] = $curtime;


            } else {

                # COMPLETED PROCESS NOT FOUND, START FAILED CHECK INFO GATHERING
                # ADD ALL THE INFO FROM THE SCHEDULE WITH ADDITIONAL INFORMATION FOR FAILED CHECK
                wh_log($logfile,"- Schedule - process_code: ".$weekly_row['script_process_code']." found no completed processes - failed check info gathering started.");

                $_SESSION['dbapi']->process_tracker->updateScheduleStatus($weekly_row['id'],'fail',$curtime);
                $weekly_row['last_failed'] = $curtime;
                $failed_checks[] = $weekly_row;


            }
    
        }
		
    } else {
    
        wh_log($logfile,"Found no weekly schedules to check...");
                
    }    

    ### MONTHLY SCHEDULE CHECK
    wh_log($logfile,"Running monthly schedule check...");

    # BUILD SQL AND GET ENABLED SCHEDULES TO BE CHECKED DAILY
    $monthly_sql = $schedule_base_sql." AND `enabled` = 'yes' AND `script_frequency` = 'monthly' ";

    # FIND SCHEDULES WITH PROCESSES THAT SHOULD HAVE COMPLETED IN THE PAST HALF HOUR
    $monthly_sql .= " AND `time_start` BETWEEN SUBTIME(CURRENT_TIME(), 003000) AND CURRENT_TIME() ";

    # DAY OF MONTH CHECK SQL
    $cur_day_of_month = strtolower(date("j",$curtime));
    $monthly_sql .= " AND time_dayofmonth = '".$cur_day_of_month."' ";

    # RUN QUERY AND GET RESULTS FOR DAILY SCHEDULES
    $monthly_res = $_SESSION['dbapi']->query($monthly_sql);

    # CHECK IF ROWS RETURNED
    if(($resultscnt=mysqli_num_rows($monthly_res)) > 0){

        # LOOP THROUGH RESULTS AND RUN PROCESS CHECKS
        wh_log($logfile,"Found ".$resultscnt." monthly schedules to check...");
        
        while($monthly_row = mysqli_fetch_array($monthly_res, MYSQLI_ASSOC)){

            # CHECK FOR COMPLETED PROCESSES BASED ON SCHEDULE CRITERIA
            wh_log($logfile,"Checking schedule - process_code: ".$monthly_row['script_process_code']." time_start: ".$monthly_row['time_start']." time_margin: ".$monthly_row['time_margin']);

            # BUILD TIMESTAMP RANGE FOR PROCESS TRACKER RUN STATUS MATCH
            # BEGIN WITH START TIME 
            $time_start = strtotime($monthly_row['time_start']);

            # END WITH END TIME PLUS TIME MARGIN
            $time_end = strtotime($monthly_row['time_end']) + ($monthly_row['time_margin'] * 60);

            # RUN PROCESS CHECK
            if($_SESSION['dbapi']->process_tracker->processCheck($monthly_row['script_process_code'],$time_start,$time_end)) {

                # PROCESS CHECK IS TRUE
                wh_log($logfile,"- Schedule - process_code: ".$monthly_row['script_process_code']." found completed processes.");
                $_SESSION['dbapi']->process_tracker->updateScheduleStatus($monthly_row['id'],'success',$curtime);
                $monthly_row['last_success'] = $curtime;


            } else {

                # COMPLETED PROCESS NOT FOUND, START FAILED CHECK INFO GATHERING
                # ADD ALL THE INFO FROM THE SCHEDULE WITH ADDITIONAL INFORMATION FOR FAILED CHECK
                wh_log($logfile,"- Schedule - process_code: ".$monthly_row['script_process_code']." found no completed processes - failed check info gathering started.");

                $_SESSION['dbapi']->process_tracker->updateScheduleStatus($monthly_row['id'],'fail',$curtime);
                $monthly_row['last_failed'] = $curtime;
                $failed_checks[] = $monthly_row;


            }
    
        }
		
    } else {
    
        wh_log($logfile,"Found no monthly schedules to check...");
                
    }        

    # FAILED RUN BREAKDOWN AND ALERTING
    wh_log($logfile,"Processing failed process checks and alerting...");

    # LOOP THROUGH FAILED CHECK ARRAY
    foreach($failed_checks as $item) {

        # LOG FAILED PROCESSES AND SEND ALERT
        wh_log($logfile," - Failed Check - Sending alert notification for ".$item['schedule_name']);
        $_SESSION['dbapi']->process_tracker->sendAlert($item);

    }

    # CLEAN-UP AND CONSOLE OUTPUT



