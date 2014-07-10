<?php

error_reporting(E_ERROR);

require_once "../includes/functions.php";
require_once "../includes/IpPhoneApi.php";
require_once "../includes/AxlRisApi.php";
require_once "../includes/AxlClass.php";
require_once "../includes/mySqlDb.php";
require_once "../includes/KLogger.php";

//$_REQUEST['deviceName'] = "SEP0023EBC87F46"; // Put MAC here to test via CLI

$martyAxl = 'sloanma';  //My CUCM AXL Account

if (isset($_REQUEST['deviceName']))
{
    /*
     * Sanitize data
     */
    $phone = clean($_REQUEST['deviceName']);

    /*
     * Instantiate Objects
     */
    $axl = new AxlClass('10.132.10.10','8443','7.0/');
    $risClient = new AxlRisApi('10.132.10.10');
    $klogger = new KLogger("../Logs/CheckWeb/$phone",KLogger::DEBUG);
    $mySql = database::MySqlConnection();


    /*
     * Get the IP of the phone we need to check
     */

    getIp:

    $ip = getDeviceIp($phone,$risClient,$klogger);

    $message = 'There was an error gathering the device IP address';
    if (!$ip)
    {
        $klogger->logInfo($message,$ip);
        $mySql->query("INSERT INTO web_results(device,ip,code,status,last_updated) VALUES ('$phone','','500', '$message',NOW()) ON DUPLICATE KEY UPDATE device = '$phone', ip = '', code = '500', status = '$message', last_updated = NOW() ");
        echo json_encode(array('success' => false,'message' => "$message", 'code' => '500 Server Error'));
        exit;
    }

    if (preg_match('/^AxisFault: Exceeded allowed rate for Reatime information/',$ip[0])) {

        sleep(30);
        goto getIp;

    }

    if (is_array($ip))
    {
        $klogger->logInfo($message,$ip);
        $mySql->query("INSERT INTO web_results(device,ip,code,status,last_updated) VALUES ('$phone','','500', '$message',NOW()) ON DUPLICATE KEY UPDATE device = '$phone', ip = '', code = '500', status = '$message', last_updated = NOW() ");
        echo json_encode(array('success' => false,'message' => "$message", 'code' => '500 Server Error'));
        exit;
    }
    $klogger->logInfo("Got IP for $phone", $ip);

    /*
     * Press keys
     */

    //$ip = '10.132.219.2';
    $res = IpPhoneApi::checkWeb($ip,$klogger);

    if (!preg_match('/Cisco Systems/',$res))
    {
        $mySql->query("INSERT INTO web_results(device,ip,code,status,last_updated) VALUES ('$phone','$ip','500', 'No Response from Phones Web Server or Unsupported Model',NOW()) ON DUPLICATE KEY UPDATE device = '$phone', ip = '$ip', code = '500', status = 'No Response from Phones Web Server or Unsupported Model', last_updated = NOW() ");
        echo json_encode(array('success' => false,'message' => 'There was an error accessing the web interface', 'code' => '500 Server Error'));
        exit;
    }

    $klogger->logInfo("Result", $res);
    $mySql->query("INSERT INTO web_results(device,ip,code,status,last_updated) VALUES ('$phone','$ip','200', 'Web Access Enabled',NOW()) ON DUPLICATE KEY UPDATE device = '$phone', ip = '$ip', code = '200', status = 'Web Access Enabled', last_updated = NOW() ");
    echo json_encode(array('success' => true,'message' => 'Web Access Enabled', 'code' => '200 OK'));

}