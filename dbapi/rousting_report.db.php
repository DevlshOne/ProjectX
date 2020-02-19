<?php
/**
 * User: brent
 * Date: 1/7/20
 * Time: 11:14 AM
 */

$rr = new RoustingReportAPI();

class RoustingReportAPI
{
    public function __construct()
    {

    }

    public function getDetails($clusterId, $userGroup, $startTime, $endTime)
    {
        $rousterReport = new RousterReport();
        $roustingData = $rousterReport->generateData($clusterId, null, $startTime, $endTime, $userGroup);

        return $roustingData;
    }

}