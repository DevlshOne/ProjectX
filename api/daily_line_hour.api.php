<?php
/**
 * Class API_Report_Rousting
 * @author Brent Hansen <bhansen@advancedtci.com>
 * @version 0.1
 * @copyright 2018
 */
class API_Daily_Line_Hour
{
    function handleAPI(){

        try {
            if ($_SESSION['user']['priv'] < 5) {
                throw new \Exception('Access Denied');
            }

            $startUnixTime = (@$_REQUEST['start_time']) ?: strtotime(date('Y-m-d 00:00:00'));
            $endUnixTime = (@$_REQUEST['end_time']) ?: strtotime(date('Y-m-d 23:59:59'));

            switch ($_REQUEST['action']) {
                case 'rouster_group_stats':
                    $details = $_SESSION['dbapi']->daily_line_hour->getRoustingGroupStats($startUnixTime, $endUnixTime);
                    break;
                case 'sales_group_stats':
                    $details = $_SESSION['dbapi']->daily_line_hour->getSalesGroupStats($startUnixTime, $endUnixTime);
                    break;
                case 'verifier_group_stats':
                    $details = $_SESSION['dbpai']->daily_line_hour->getVerifierGroupStats($startUnixTime, $endUnixTime);
                    break;
                default:
                    throw new \Exception('action parameter required');
            }
        } catch (\Exception $e) {
            $details['error'] = true;
            $details['error_message'] = $e->getMessage();
        }

        ## OUTPUT FORMAT TOGGLE
        switch ($_SESSION['api']->mode) {
            case 'xml':
                header('Content-Type: text/xml');
                $xml = new \SimpleXMLElement('<root/>');
                $this->array_to_xml($details, $xml);
                $out = $xml->asXML();

                // remove xml declaration because functions.api.php#outputFileHeader is adding it :(
                $out = preg_replace('~<\?xml version="1\.0"\?>~', '', $out);

                break;
            case 'csv':

                break;
            ## GENERATE JSON
            case 'json':
            default:
                $out = json_encode($details);
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

    private function secondsToTime($seconds) {
        $seconds = round($seconds);
        return (new \DateTime('@0'))->diff(new \DateTime("@$seconds"))->format('%H:%I:%S');
    }


}