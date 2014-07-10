<?php

error_reporting(E_ERROR);

require_once "../includes/functions.php";
require_once "../includes/IpPhoneApi.php";
require_once "../includes/AxlRisApi.php";
require_once "../includes/AxlClass.php";
require_once "../includes/mySqlDb.php";
require_once "../includes/KLogger.php";

//$_REQUEST['deviceName'] = "SEP0026CB3B9218"; // Put MAC here to test via CLI

$martyAxl = 'sloanma';  //My CUCM AXL Account

if (isset($_REQUEST['deviceName']) && isset($_REQUEST['ipAddress']))
{
    /*
     * Sanitize data
     */

    $phone = clean($_REQUEST['deviceName']);
    $ip = clean($_REQUEST['ipAddress']);

    if ($ip == "Unregistered")
    {
        echo json_encode(array('success' => false,'message' => "This device is not registered", 'code' => '404 Not Found'));
        exit;
    }

    /*
     * Instantiate Objects
     */
    $axl = new AxlClass('10.132.10.10','8443','7.0/');
    $klogger = new KLogger("../Logs/CTL/$phone",KLogger::DEBUG);
    $mySql = database::MySqlConnection();

    /*
     * Get Device Model
     */
    $response = getPhone($phone,$axl,$klogger);

    if (is_array($response))
    {
        $klogger->logInfo('There was an error getting the device model',$response);
        echo json_encode(array('success' => false,'message' => 'There was an error getting the device model', 'code' => '500 Server Error'));
        $mySql->query("INSERT INTO ctl_results(device,ip,code,status,last_updated) VALUES ('$phone','','500', 'There was an error getting the device model',NOW()) ON DUPLICATE KEY UPDATE device = '$phone', ip = '', code = '500', status = 'There was an error getting the device model', last_updated = NOW() ");
        exit;

    } else {
        $model = $response->model;
        $klogger->logInfo("Obtained device model $model for $phone");
    }

    /*
     * Get array of keys presses to delete the CTL, based on the device model
     */
    $uri = setCtlKeys($model);

    /*
     * Press keys
     */
    foreach ($uri as $k)
    {
        $res = IpPhoneApi::keyPress($ip,$k,$klogger);
        $klogger->logInfo("Result", $res);

        $k == "Key:KeyPadPound" ? sleep(2) : usleep(500000);
    }

    $mySql->query("INSERT INTO ctl_results(device,ip,code,status,last_updated) VALUES ('$phone','$ip','200', 'CTL Process Sent',NOW()) ON DUPLICATE KEY UPDATE device = '$phone', ip = '$ip', code = '200', status = 'CTL Process Sent', last_updated = NOW() ");
    echo json_encode(array('success' => true,'message' => 'CTL Process Sent', 'code' => '200 OK'));

}