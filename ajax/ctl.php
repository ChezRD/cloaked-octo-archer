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
    $klogger = new KLogger("../Logs/CTL/$phone",KLogger::DEBUG);
    $mySql = database::MySqlConnection();

    /*
     * Associate phone to the AXL App user to control
     */
    $response = updateUserDevAssoc($martyAxl,$phone,$axl,$klogger);

    if (is_array($response))
    {
        $klogger->logInfo('There was an error updating the user/device association',$response);
        echo json_encode(array('success' => false,'message' => 'There was an error updating the user/device association', 'code' => '500 Server Error'));
        $mySql->query("INSERT INTO ctl_results(device,ip,code,status,last_updated) VALUES ('$phone','','500', 'There was an error updating the user/device association',NOW()) ON DUPLICATE KEY UPDATE device = '$phone', ip = '', code = '500', status = 'There was an error updating the user/device association', last_updated = NOW() ");
        exit;

    } else { $klogger->logInfo("Updated end user/device association for user '$martyAxl' and device $phone"); }

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
        $klogger->logInfo("Updated end user/device association for user '$martyAxl' and device $phone");

        $model = $response->model;
    }

    /*
      * Get the IP of the phone we're going to dial
      */

    getIp:

    $ip = getDeviceIp($phone,$risClient,$klogger);

    if (preg_match('/^AxisFault: Exceeded allowed rate for Reatime information/',$ip[0])) {

        sleep(30);
        goto getIp;

    }

    if (is_array($ip))
    {
        $klogger->logInfo('There was an error gathering the device IP address',$ip);
        echo json_encode(array('success' => false,'message' => 'There was an error gathering the device IP address', 'code' => '500 Server Error'));
        $mySql->query("INSERT INTO ctl_results(device,ip,code,status,last_updated) VALUES ('$phone','','500', 'There was an error gathering the device IP address',NOW()) ON DUPLICATE KEY UPDATE device = '$phone', ip = '', code = '500', status = 'There was an error gathering the device IP address', last_updated = NOW() ");
        exit;
    }
    $klogger->logInfo("Got IP for $phone", $ip);

    /*
     * Set keys to delete CTL
     */

    switch ($model){

        case "Cisco 7975":
            $uri = [

                'Init:Settings',
                'Key:Settings',
                'Key:KeyPad4',
                'Key:KeyPad5',
                'Key:KeyPad1',
                'Key:Soft5',
                'Key:KeyPadStar',
                'Key:KeyPadStar',
                'Key:KeyPadPound',
                'Key:Soft5',
                'Init:Services'
            ];
            break;

        case "Cisco 8961": //Fall through
        case "Cisco 9951": //Fall through
        case "Cisco 9971":
            $uri = [

                'Key:NavBack',
                'Key:NavBack',
                'Key:NavBack',
                'Key:NavBack',
                'Key:NavBack',
                'Key:Applications',
                'Key:KeyPad4',
                'Key:KeyPad4',
                'Key:KeyPad4',
                'Key:Soft3',
            ];
            break;
    }


    /*
     * Press keys
     */
    foreach ($uri as $k)
    {

        $res = IpPhoneApi::keyPress($ip,$k,$klogger);
        $klogger->logInfo("Result", $res);

        $k == "Key:KeyPadPound" ? sleep(3) : sleep(1);

    }

    $mySql->query("INSERT INTO ctl_results(device,ip,code,status,last_updated) VALUES ('$phone','$ip','200', 'CTL Process Sent',NOW()) ON DUPLICATE KEY UPDATE device = '$phone', ip = '$ip', code = '200', status = 'CTL Process Sent', last_updated = NOW() ");
    echo json_encode(array('success' => true,'message' => 'CTL Process Sent', 'code' => '200 OK'));

}