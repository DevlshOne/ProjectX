<?php


    ##
    ## play_voice_file.php - Takes file path and outputs back to audio player
    ##

    session_start();

    # Get wavfile and output with proper encoding and headers
    $wavfile = trim($_REQUEST['file']);

    $mime_type=mime_content_type($wavfile);

    //send the wav to client via headers
    if(file_exists($wavfile)){
        $handle = fopen($wavfile, "rb");

        header('Content-Description: File Transfer');
        header("Content-Transfer-Encoding: binary"); 
        header('Content-Type: '.$mime_type);
        header('Content-length: ' . filesize($wavfile));
        header('Content-Disposition: attachment;filename="' . $file.'"');

        while (!feof($handle)) {
            echo fread($handle, 4096);
            flush();
        }
        fclose($handle);
    }else{
        header("HTTP/1.0 404 Not Found");
    }




?>