#!/usr/bin/php
<?php

/*
 * update_voice_file_descriptions.php
 * 
 * description: takes in a CSV to update projectx.voices_files.descriptions based on campaign and voice ids
 * usage: ./update_voice_file_descriptions.php voice_id csv_filename.csv
 *  
*/

    # CHECK IF ANY ARGUMENTS ARE MISSING
    if(!isset($argv[1]) or !isset($argv[2])){

        die("Invalid arguments. Usage: ./update_voice_file_descriptions.php voice_id csv_filename.csv\n");

    } else {

        # VARIABLE DECLARATIONS
        #$base_dir = "/var/www/html/dev/";
        $base_dir = "/var/www/html/ProjectX-ReportsAndAdmin/";

        $voices_files_table = "voices_files";

        $csv_voice_id = $argv[1];

        $csv_filename = file($argv[2]);

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

            # BUILD SQL TO CHECK IF VOICES FILES RECORD EXISTS
            $get_voice_file_id_sql = "SELECT id FROM `".$voices_files_table."` WHERE voice_id='".$csv_voice_id."' AND file LIKE '%".$voice_file_base_path.$line_filenum."%';";

            # GRAB ID FROM RESULT
            $get_voice_file_id = queryROW($get_voice_file_id_sql);

            # CHECK IF WE FOUND A RECORD
            if(isset($get_voice_file_id[0])){

                if($line_description==''){

                    echo "Voice ID:".$csv_voice_id." Voices Files ID:".$get_voice_file_id[0]." record found but CSV line description was blank.\n";

                } else {

                    # BUILD SQL FOR UPDATING VOICES FILES DESCRIPTION BASED ON FOUND RECORD ID
                    $update_voices_files_description_sql = "UPDATE voices_files SET description='".mysqli_real_escape_string($_SESSION['db'],$line_description)."' WHERE id='".$get_voice_file_id[0]."';";
                    
                    # EXEC SQL AND GET NUMBER OF UPDATED ROWS
                    $update_voices_files_description = execSQL($update_voices_files_description_sql);

                    # UPDATES ROWS = 1, UNCHANGED ROWS = 0
                    if($update_voices_files_description>0){
                        
                        echo "Voice ID:".$csv_voice_id." Voices Files ID:".$get_voice_file_id[0]." description updated.\n";

                        $j++;
                    
                    } else {

                        echo "Voice ID:".$csv_voice_id." Voices Files ID:".$get_voice_file_id[0]." description unchanged.\n";

                        $k++;

                    }
                
                }

            } else {

                # ARRAY HAS NO DATA, VOICES FILES ID NOT FOUND BASED ON INPUTS                
                echo "Result not found for Voice ID:".$csv_voice_id." and CSV file num:".$line_filenum."\n";

                $l++;

            }

        }
    
        # OUTPUT CSV ROW COUNT AND STATS
        echo "CSV Row Count ".$i." - ".$j." descriptions updated, ".$k." unchanged and ".$l." not matched.\n";

        exit(0);

    }



?>