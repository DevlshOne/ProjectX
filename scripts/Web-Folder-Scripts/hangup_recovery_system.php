#!/usr/bin/php
<?php
session_start();

global $delimiter;

$basedir = "/var/www/html/dev/";

$push_to_cluster_id = 3; // 3 == TAPS CLUSTER

$push_to_list_id = "200000";


$vici_web_host = "10.101.11.17";

// $delimiter = "/t";
$delimiter = "|";

$finshed_dispo = "RECOVR";


// THIS WAS A REQUEST MADE BY LEE, SO WE ARE NOT IMMEDIATELY CALLING HTEM BACK
$minutes_offset = "30"; // HOW MANY MINUTES TO WAIT BEFORE BEING INCLUDED IN THE CALLBACK

/**
 * ******************
 */

include_once ($basedir . "site_config.php");
include_once ($basedir . "db.inc.php");
include_once ($basedir . "utils/db_utils.php");

// echo "DIR:" . $basedir."\n";
function escapeCSV($input)
{
    global $delimiter;

    return str_replace($delimiter, "_", $input);
}

// "TODAY" TIMEFRAME
// $stime = mktime(0, 0, 0);
// $etime = $stime + 86399;

// SHIFT BACK 30 MINUTES, TO WAIT A LITTLE BIT BEFORE CALLING HTEM BACK (LEE REQUEST)
$etime = time() - ($minutes_offset * 60);

// "-1 HOUR" TIMEFRAME
$stime = $etime - 3600;

echo date("H:i:s m/d/Y") . " - Processing Hangup Recovery ...\n<br/>";

// CONNECT TO PX
connectPXDB();

// LOAD DESTINATION CLUSTER INFO
$cluster = querySQL("SELECT * FROM vici_clusters WHERE `id`='" . intval($push_to_cluster_id) . "' ");

// Standard vicidial lead fields
/**
 * Vendor Lead Code - shows up in the Vendor ID field of the GUI
 * Source Code - internal use only for admins and DBAs
 * List ID - the list number that these leads will show up under
 * Phone Code - the prefix for the phone number - 1 for US, 44 for UK, 61 for AUS, etc
 * Phone Number - must be at least 8 digits long
 * Title - title of the customer - Mr. Ms. Mrs, etc...
 * First Name
 * Middle Initial
 * Last Name
 * Address Line 1
 * Address Line 2
 * Address Line 3
 * City
 * State - limited to 2 characters
 * Province
 * Postal Code
 * Country
 * Gender
 * Date of Birth
 * Alternate Phone Number
 * Email Address
 * Security Phrase
 * Comments
 * Rank
 * Owner
 */
$field_list = array(
    'vendor_lead_code',
    'source_id',
    'list_id',
    'phone_code',
    'phone_number',
    'title',
    'first_name',
    'middle_initial',
    'last_name',
    'address1',
    'address2',
    'address3',
    'city',
    'state',
    'postal_code',
    'country_code',
    'gender',
    'date_of_birth',
    'alt_phone',
    'email',
    'security_phrase',
    'comments',
    'rank',
    'owner'
);
// Custom field list - Keeping separate for now for my sanity or whats left of it
$custom_field_list = array(
    'sale_amount',
    'agent_id',
    'verifier',
    'campaign',
    'date_sold',
    'time_sold',
    'contact',
    'c_list_id',
    'department',
    'o_lead_id',
    'o_vici_cluster',
    'o_agent_id',
    'o_verifier',
    'o_department',
    'occupation',
    'employer'
);



// PULL THE LEADS THAT ARE CLASSIFIED HANGUP ("hangup" or "NOVERI" dispo)
// Changed query to out put matching vicidial data and only what we want.
// 04-30-2018 changed 'id' to px_id and passing to vici to store with hangups for tracking
$myquery = "
SELECT
    id as 'vendor_lead_code',
    campaign AS 'campaign',
    RIGHT(TRIM(phone_num), 10) AS 'phone_number',
    first_name AS 'first_name',
    middle_initial AS 'middle_initial',
    last_name AS 'last_name',
    address1 AS 'address1',
    city AS 'city',
    state AS 'state',
    zip_code AS 'postal_code',
    comments AS 'comments',
    DATE_FORMAT(FROM_UNIXTIME(time), '%m-%d-%Y') AS 'date_sold',
    DATE_FORMAT(FROM_UNIXTIME(time), '%r') AS 'time_sold',
    amount AS 'sale_amount',
    occupation AS 'occupation',
    employer AS 'employer',
    'HUVER' AS 'verifier',
    '--A--user--B--' as 'agent_id',
    office AS 'o_department',
    campaign_code AS 'c_list_id',
    vici_cluster_id AS 'o_vici_cluster',
    lead_id AS 'o_lead_id',
    agent_username AS 'o_agent_id',
    verifier_username AS 'o_verifier_id'
FROM
    lead_tracking
WHERE
    `dispo` IN ('hangup' , 'NOVERI')
        AND `time` BETWEEN '$stime' AND '$etime'
        #AND `time` BETWEEN UNIX_TIMESTAMP(now() - INTERVAL 24 HOUR) AND UNIX_TIMESTAMP(now())

        #?? Stopping looping perhaps? This could cause problems
        AND `list_id` != '" . mysqli_real_escape_string($_SESSION['db'],$push_to_list_id) . "'
        #Remove records that are missing critical data if any of these fields are 0 ignore the record
        AND 0 NOT IN (lead_id , vici_cluster_id, campaign_id)
        #Using group to remove duplicated records in the dispo log
        GROUP BY vici_cluster_id , lead_id;";


#echo $myquery;
#exit;


$res = query($myquery);

$rowcnt = mysqli_num_rows($res);
echo "Found $rowcnt records to process.\n<br/>";

if ($rowcnt <= 0) {
    die("No records to process.\n");
}

// WRITE THEM TO A TEMP FILE
$tmpfname = tempnam(sys_get_temp_dir(), 'HangupRecovery'); // good

$fh = fopen($tmpfname, "w");

$completed_id_stack = array();
$line = "";
//
while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
    // Get the column headers if we don't allready have them
    if (! isset($header_fields))
        $header_fields = array_keys($row);

    // Changed in query to match vici
    // FYI in custom mode order is not important. See the curl comments below for more info

    $line = "";

    foreach ($row as $key => $val) {
        $line .= escapeCSV($val) . $delimiter;
    }

    $line .= "\n";

    // echo "Writing line: " . $line . "\n";

    fwrite($fh, $line);

    $completed_id_stack[] = $row['vendor_lead_code'];
}

// CLOSE THE FILE WHEN FINISHED WRITING THE LEADS
fclose($fh);

echo "Filename: ".$tmpfname."\n";



// BUILD THE POST ARRAY
$post = array(
    'leadfile_name' => $tmpfname,
    'DB' => "",
    // Overriding list_id is REQUIRED for loading custom fields data so unfortunately one list at a time.
    'list_id_override' => $push_to_list_id,
    'phone_code_override' => "1",

    // 'list_id_override' => "in_file",
    // 'phone_code_override' => "in_file",

    // Tell API we will be sending data in a custom format
    'file_layout' => "custom",

    // Tell API we need to dedupe.. This may need changed to DUPCAMP.
    // We will likely need to DEDUPE all status by vici 'campaign' and purge to 7 days or less unless callback
    // 'dupcheck' => "DUPLIST",
    'dupcheck' => 'DUPCAMP',

    // 'dedupe_statuses[]' => "NEW",

    'usacan_check' => "NONE",
    'postalgmt' => "POSTAL",
    'OK_to_process' => "OK TO PROCESS",
    // Cause they dont fucking set it right (I know what your thinking... Its the only value that works.. I tried)
    'lead_file' => '/tmp/vicidial_temp_file.txt'
//    'leadfile' => '@' . realpath($tmpfname)
);


if (function_exists('curl_file_create')) { // php 5.5+
	$post['leadfile'] = curl_file_create(realpath($tmpfname));
} else { //
	$post['leadfile'] = '@' . realpath($tmpfname);
}





// Tell API how we are sending the data. -1 means not used
// Set column order for export
$csv_post_data = array();
// Pass the header and index positions from mysql to the api, appending '_field' to each Column
foreach ($header_fields as $field_index => $field_name)
    $post[$field_name . '_field'] = (string) $field_index;

// Unused fields must be set to -1 or the default csv postion for the field will be used making a mess of things.
foreach (array_merge($field_list, $custom_field_list) as $field_name)
    if (! isset($post[$field_name . '_field']))
        $post[$field_name . '_field'] = "-1";

//echo json_encode($post);
//echo realpath($tmpfname);
// echo file_get_contents(realpath($tmpfname));
// print_r($post);
// exit();

// $fields_string = http_build_query($post);

//$url = "http://10.101.15.51/dev2/test.php";
$url = 'http://'.$vici_web_host.'/vicidial/admin_listloader_fourth_gen.php';
// $url = 'http://' . $cluster['web_ip'] . '/vicidial/admin_listloader_fourth_gen.php';

// print_r($cluster);

echo "Preparing to CURL POST TO: " . $url . "\n";

//print_r($post);exit;

// PUSH THE LEADS TO VICI VIA CURL/API
$process = curl_init($url);
curl_setopt($process, CURLOPT_USERPWD, $cluster['web_api_user'] . ":" . $cluster['web_api_pass']);
curl_setopt($process, CURLOPT_TIMEOUT, 45);
curl_setopt($process, CURLOPT_POST, 1);
curl_setopt($process, CURLOPT_POSTFIELDS, $post); // $fields_string);
curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
$return = curl_exec($process);

if ($errno = curl_errno($process)) {
    echo "cURL error ({$errno}):\n";
}

curl_close($process);
echo "CURL Results: ".$return."\n";

if (count($completed_id_stack) > 0 && $errno == 0) {
    // $sql = "UPDATE `lead_tracking` SET `dispo`='".mysql_real_escape_string($finshed_dispo)."' WHERE `id` IN(";

    $sql = "UPDATE `lead_tracking` SET `list_id`='" . mysqli_real_escape_string($_SESSION['db'],$push_to_list_id) . "' WHERE `id` IN(";

    $x = 0;
    foreach ($completed_id_stack as $sid) {
        $sql .= ($x ++ > 0) ? "," : '';

        $sql .= $sid;
    }

    if ($x > 0) {

        $sql .= ")";

        echo "Updating `lead_tracking` to mark leads as recycled\n";

        echo $sql."\n";
	//echo "Skipping query for testing!!!!!!!!!!!\n";
        execSQL($sql);
    }
}


