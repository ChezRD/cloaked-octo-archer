<?php

error_reporting(E_ERROR);

require_once "../includes/functions.php";
require_once "../includes/IpPhoneApi.php";
require_once "../includes/AxlRisApi.php";
require_once "../includes/AxlClass.php";
require_once "../includes/mySqlDb.php";
require_once "../includes/KLogger.php";


//$_REQUEST['deviceName'] = "SEP0023EBC87F46";

if (isset($_REQUEST['deviceName']))
{
    /*
     * Sanitize data
     */
    $phone = clean($_REQUEST['deviceName']);

    /*
     * Instantiate Objects
     */
    $axl = new AxlClass('10.132.10.10','8443');
    $risClient = new AxlRisApi('10.132.10.10');
    $klogger = new KLogger("../Logs/Reset/$phone",KLogger::DEBUG);
    $mySql = database::MySqlConnection();

    /*
     * Reset Phone
     */
    $response = resetPhone($phone,$axl,$klogger);

    if (is_array($response))
    {
        $message = 'There was an error resetting the device';
        $klogger->logInfo($message,$response);
        echo json_encode(array('success' => false,'message' => $message, 'code' => '500 Server Error'));
        $mySql->query("INSERT INTO reset_results(device,code,status,last_updated) VALUES ('phone', '500', '$message',NOW()) ON DUPLICATE KEY UPDATE device = '$phone', code = '500', status = '$message', last_updated = NOW() ");
        exit;

    } else {
        $mySql->query("INSERT INTO reset_results(device,code,status,last_updated) VALUES ('$phone', '200', 'Reset Sent',NOW()) ON DUPLICATE KEY UPDATE device = '$phone', code = '200', status = 'Reset Sent', last_updated = NOW() ");
        $klogger->logInfo("Reset device $phone");
        echo json_encode(array('success' => true,'message' => 'Reset Sent', 'code' => '200 OK'));
    }
}