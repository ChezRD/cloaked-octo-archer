<?php

error_reporting(E_ERROR);

require_once "../includes/functions.php";
require_once "../includes/IpPhoneApi.php";
require_once "../includes/AxlRisApi.php";
require_once "../includes/AxlClass.php";
require_once "../includes/mySqlDb.php";
require_once "../includes/KLogger.php";


if (isset($_REQUEST['lineNumber']) && isset($_REQUEST['cluster']))
{

    /*
     * Sanitize data
     */
    $line = clean($_REQUEST['lineNumber']);
    $cluster = clean($_REQUEST['cluster']);

    switch($cluster)
    {
        case 'AO':
            $axl = new AxlClass('10.132.10.10','8443','7.0/'); //10.132.10.10
            $from = 'EW-Internal_pt';
            $to = 'MIGRATE_pt';
            break;
        case 'NIPT':
            $axl = new AxlClass('10.179.168.10','8443',''); //10.179.168.10
            $from = 'Test_PT';
            $to = 'All-DN_pt';
            break;
        default:
            echo json_encode(array('success' => false,'message' => "Cluster: $cluster unknown", 'code' => '404 NOT FOUND'));
            exit;
    }

    /*
     * Instantiate Objects
     */
    $klogger = new KLogger("../Logs/Switcher/$line",KLogger::DEBUG);
    $mySql = database::MySqlConnection();

    /*
     * Send message to switch the line's partition
     */

    $response = updateLinePartition($line,$from,$to,$axl,$klogger);

    if (is_array($response))
    {
        $message = "There was an error updating the line's partition";

        $klogger->logInfo($message,$response);
        echo json_encode(array('success' => false,'message' => "$message", 'code' => '500 Server Error'));
        //$mySql->query("INSERT INTO ctl_results(device,ip,code,status,last_updated) VALUES ('$phone','','500', '$message',NOW()) ON DUPLICATE KEY UPDATE device = '$phone', ip = '', code = '500', status = '$message', last_updated = NOW() ");
        exit;

    } else { $klogger->logInfo("Updated line partition successfully for line $line on cluster $cluster"); }

    echo json_encode(array('success' => true,'message' => 'Partition Updated', 'code' => '200 OK'));

}