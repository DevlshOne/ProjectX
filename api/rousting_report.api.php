<?php
/**
 * Class API_Report_Rousting
 * @author Brent Hansen <bhansen@advancedtci.com>
 * @version 0.1
 * @copyright 2018
 */
class API_Rousting_Report
{
    function handleAPI(){

        if (!($clusterId = $_REQUEST['cluster_id'])) { die('Field `cluster_id` is required'); }
        if (!($userGroup = $_REQUEST['user_group'])) { die('Field `user_group` is required'); }
        $startTime = ($_REQUEST['start_time'])?: strtotime(date('Y-m-d 00:00:00'));
        $endTime = ($_REQUEST['end_time'])?: strtotime(date('Y-m-d 23:59:59'));



        if($_SESSION['user']['priv'] < 5){
            $_SESSION['api']->errorOut('Access denied to non admins.');

            return;
        }

        switch($_REQUEST['action']) {
            case 'hourly':
                $result = $_SESSION['dbapi']->rousting_report->getRoustingTotals($clusterId, $userGroup, $startTime, $endTime);
                break;
            case 'detail':
            default:
                $details = $_SESSION['dbapi']->rousting_report->getDetails($clusterId, $userGroup, $startTime, $endTime);
                $result = $this->createDetailReport($details);
        }

        ## OUTPUT FORMAT TOGGLE
        switch($_SESSION['api']->mode){
            case 'xml':
                header('Content-Type: text/xml');
                $xml = new \SimpleXMLElement('<root/>');
                $this->array_to_xml($result, $xml);
                $out = $xml->asXML();

                // remove xml declaration because functions.api.php#outputFileHeader is adding it :(
                $out = preg_replace('~<\?xml version="1\.0"\?>~', '', $out);

                break;
            case 'csv':

                break;
            ## GENERATE JSON
            case 'json':
            default:
                $out = json_encode($result);
                break;
        }


        ## OUTPUT DATA!
        echo $out;
    }

    public function array_to_xml($dataArray, &$xml)
    {
        foreach ($dataArray as $key => $value) {
            $key = (string) preg_replace('~/~','-', $key); // remove / (slash) in dates (or anywhere)
            $key = is_numeric($key[0]) ? "row$key" : $key;  // xml does not allow fieldname to start w/ digit
            if (is_array($value)) {
                $subnode = $xml->addChild("$key");
                $this->array_to_xml($value, $subnode);
            } else {
                $key = is_numeric($key) ? "item$key" : $key;
                $xml->addChild("$key", "$value");
            }
        }
    }

    public function createDetailReport($results)
    {
        $report = [];

        foreach($results as $row) {
            $activity_time = array_sum($row['agent']['total_activity_date_time_array']);
            $act_total_time = array_sum($row['agent']['total_activity_date_daily_array']);

            $report[] = [
                'agent' => $row['username'],
                'num_calls' => $row['call_cnt'],
                'answered_total' => $row['ans_cnt'],
                'answered_percent' => $row['ans_percent'],
                'calls_per_hour' => $row['worked_calls_hr'],
                'contact_percent' => ($row['call_cnt'] <= 0)?0:number_format( round( (($row['contact_cnt']) / ($row['call_cnt'])) * 100, 2), 2),
                'paid_cc_num' => $row['paid_sale_cnt'],
                'paid_per_hour' => ($row['paid_time'] <= 0)?0:($row['paid_sale_total'] / ($row['paid_time']/60)),
                'worked_per_hour' => ($act_total_time <= 0)?0:($row['paid_sale_total'] / ($activity_time/3600)),
                'paid_cc_amount' => $row['paid_sale_total'],
                'activity' => $this->secondsToTime($activity_time),
                'in_call' => $this->secondsToTime($row['agent']['seconds_INCALL']),
                'time' => $this->secondsToTime($act_total_time),
                'paid_time' => $this->secondsToTime($row['paid_time']),
                'pause' => $this->secondsToTime($row['t_pause']),
                'talk_average' => $this->secondsToTime(($row['call_cnt'] <= 0)?0:round($row['t_talk'] / $row['call_cnt'])),
                'dead' => $this->secondsToTime($row['t_dead']),
                'bump_amount' => $row['agent']['bump_amount'],
                'bump_percent' => $row['agent']['bump_percent'],
                'bump_total' => $row['agent']['bump_count'],
                'pos_bump_amount' => $row['agent']['pos_bump_amount'],
                'pos_bump_percent' => $row['agent']['pos_bump_percent']
            ];
        }

        return $report;
    }

    private function secondsToTime($seconds) {
        $seconds = round($seconds);
        return (new \DateTime('@0'))->diff(new \DateTime("@$seconds"))->format('%H:%I:%S');
    }


}