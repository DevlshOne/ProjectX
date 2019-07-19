#!/usr/bin/php
<?php

/*
 * update_script_descriptions.php
 * 
 * description: takes in a CSV to update projectx.scripts.descriptions based on campaign and voice ids
 * usage: ./update_script_descriptions.php campaign_id voice_id csv_filename.csv
 *  
*/

    # CHECK IF ANY ARGUMENTS ARE MISSING
    if(!isset($argv[1]) or !isset($argv[2]) or !isset($argv[3])){

        die("Invalid arguments. Usage: ./update_script_descriptions.php campaign_id voice_id csv_filename.csv\n");

    } else {

        # VARIABLE DECLARATIONS
        $base_dir = "/var/www/html/dev/";
        #$base_dir = "/home/dbrummer/homesvr01-shared/pxdev1/dev/";

        $csv_campaign_id = $argv[1];

        $csv_voice_id = $argv[2];

        $csv_filename = file($argv[3]);

        $voice_file_base_path = "/playback/voice-".$csv_voice_id."/";

        $csv_data = [];

        $i = 0;
        
        $j = 0;

        $k = 0;

        $l = 0;

        # MODULE INCLUDES
        include_once($base_dir."db.inc.php");

        # LOOP THROUGH EACH CSV LINE
        foreach ($csv_filename as $csv_line){

            # SKIP FIRST CSV (HEADER) ROW
            if ($i++ == 0) continue;        

            # ASSOCIATE LINE VARIABLES WITH CSV ROW DATA
            list($line_keystroke,$line_filenum,$line_label,$line_description) = str_getcsv($csv_line);      

            # BUILD SQL FOR GETTING VOICES_FILES.SCRIPT_ID FROM VOICE ID AND CSV FILENAME PREFIX
            $get_script_id_sql = "SELECT voices_files.script_id FROM voices_files WHERE voice_id='".$csv_voice_id."' AND file LIKE '%".$voice_file_base_path.$line_filenum."%';";

            # GRAB ARRAY WITH RESULT
            $get_script_id = queryROW($get_script_id_sql);

            # CHECK IF ARRAY HAS DATA
            if(isset($get_script_id[0])){

                # BUILD SQL FOR UPDATING DESCRIPTION BASED ON GATHERED SCRIPT_ID AND CSV DESCIPTION
                $update_script_description_sql = "UPDATE scripts SET description='".mysqli_real_escape_string($_SESSION['db'],$line_description)."' WHERE id='".$get_script_id[0]."' AND campaign_id='".$csv_campaign_id."' AND voice_id='".$csv_voice_id."';";
                
                # EXEC SQL AND GET NUMBER OF UPDATED ROWS
                $update_script_description = execSQL($update_script_description_sql);

                # UPDATES ROWS = 1, UNCHANGED ROWS = 0
                if($update_script_description>0){
                    
                    echo "Campaign ID:".$csv_campaign_id." Voice ID:".$csv_voice_id." Script ID:".$get_script_id[0]." description updated.\n";

                    $j++;
                
                } else {

                    echo "Campaign ID:".$csv_campaign_id." Voice ID:".$csv_voice_id." Script ID:".$get_script_id[0]." description unchanged.\n";

                    $k++;

                }

            } else {

                # ARRAY HAS NO DATA, SCRIPT_ID NOT FOUND BASED ON INPUTS                
                echo "Result not found for Campaign ID:".$csv_campaign_id." Voice ID:".$csv_voice_id." and CSV file num:".$line_filenum."\n";

                $l++;

            }

        }
    
        # OUTPUT CSV ROW COUNT AND STATS
        echo "CSV Row Count ".$i." - ".$j." descriptions updated, ".$k." not changed and ".$l." not matched.\n";

        exit(0);

    }



?>