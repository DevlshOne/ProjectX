#!/usr/bin/php
<?
/**
 * Simple launch script to move new version into place and increment the version number
 */

	$old_version_number = "1.409";
	$new_version_number = "1.410";



/** THE REST SHOULD BE FINE, UNLESS THINGS DRASTICALLY CHANGE **/


	$new_version_folder = "/var/www/download/dev/";
	$launch_to_folder = "/var/www/download/";

	$filename_linux = "ProjectX-Client-linux.jar";
	$filename_windows="ProjectX-Client-WINDOWS.jar";



	include_once("/var/www/site_config.php");
	include_once($_SESSION['site_config']['basedir']."/db.inc.php");


// LINUX VERSION
	$old_file = $launch_to_folder.'/'.$filename_linux;
	$backup_file = $launch_to_folder.'/'.$filename_linux.".v".$old_version_number."-".date("m-d-y_H-i-s");
	$cmd = "cp $old_file $backup_file";

	echo $cmd."\n";
	echo `$cmd`;

	$dev_file = $new_version_folder.'/'.$filename_linux;
	
	$cmd = "cp $dev_file $old_file";

	echo $cmd."\n";
	echo `$cmd`;


// WINDOWS VERSION
        $old_file = $launch_to_folder.'/'.$filename_windows;
        $backup_file = $launch_to_folder.'/'.$filename_windows.".v".$old_version_number."-".date("m-d-y_H-i-s");
        $cmd = "cp $old_file $backup_file";

        echo $cmd."\n";
	echo `$cmd`;


        $dev_file = $new_version_folder.'/'.$filename_windows;

        $cmd = "cp $dev_file $old_file";

        echo $cmd."\n";
	echo `$cmd`;




	$sql = "UPDATE servers SET version='".addslashes($new_version_number)."' WHERE 1";


	echo $sql."\n";

	execSQL($sql);


echo "annnd boom goes the dynamite (Done).\n";
