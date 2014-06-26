<?php

error_reporting(E_ERROR);

require_once "../includes/functions.php";
require_once "../includes/IpPhoneApi.php";
require_once "../includes/AxlRisApi.php";
require_once "../includes/AxlClass.php";
require_once "../includes/mySqlDb.php";
require_once "../includes/KLogger.php";


//$_REQUEST['deviceName'] = "SEP0023EBC87F46"; // My NIPT 7975

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
    $klogger = new KLogger("../Logs/Dialer/$phone",KLogger::DEBUG);
    $mySql = database::MySqlConnection();


    /*
     * Associate phone to the AXL App user to control
     */
    $response = updateUserDevAssoc($martyAxl,$phone,$axl,$klogger);

    if (is_array($response))
    {
        $klogger->logInfo('There was an error updating the user/device association',$response);
        echo json_encode(array('success' => false,'message' => 'There was an error updating the user/device association', 'code' => '500 Server Error'));
        $mySql->query("INSERT INTO dial_results(device,ip,code,status,last_updated) VALUES ('$phone','','500', 'There was an error updating the user/device association',NOW()) ON DUPLICATE KEY UPDATE device = '$phone', ip = '', code = '500', status = 'There was an error updating the user/device association', last_updated = NOW() ");
        exit;

    } else { $klogger->logInfo("Updated end user/device association for user '$martyAxl' and device $phone"); }


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
        $mySql->query("INSERT INTO dial_results(device,ip,code,status,last_updated) VALUES ('$phone','','500', 'There was an error gathering the device IP address',NOW()) ON DUPLICATE KEY UPDATE device = '$phone', ip = '', code = '500', status = 'There was an error gathering the device IP address', last_updated = NOW() ");
        exit;
    }
    $klogger->logInfo("Got IP for $phone", $ip);


    /*
     * Gather patterns to dial
     *
     * Need to create relational table with 'dial-plan ID'
     * then use in the query 'where dialplanid = n
     */
    $res = $mySql->query('SELECT pattern FROM test_plan');

    if ($res->num_rows)
    {
        while ($row = mysqli_fetch_assoc($res))
        {
            $testPlan[] = $row;
        }
    }


    /*
     * Iterate patterns
     */

    foreach ($testPlan as $pattern)
    {
        $res = IpPhoneApi::dial($ip,$pattern['pattern'],$klogger);
        $klogger->logInfo("Dial Results", $res);

        sleep(5);
        $res = IpPhoneApi::keyPress($ip,"Key:Speaker",$klogger);
        $klogger->logInfo("End Call Results", $res);

        $mySql->query("INSERT INTO dial_results(device,ip,code,status,last_updated) VALUES ('$phone','$ip','200', '$pattern[pattern]', NOW()) ON DUPLICATE KEY UPDATE device = '$phone', ip = '$ip', code = '200', status = '$pattern[pattern]', last_updated = NOW() ");

    }

    //$mySql->query("INSERT INTO dial_results(device,ip,code,status,last_updated) VALUES ('$phone','$ip','200', 'Dial Process Sent', NOW()) ON DUPLICATE KEY UPDATE device = '$phone', ip = '$ip', code = '200', status = 'Dial Process Sent', last_updated = NOW() ");
    echo json_encode(array('success' => true,'message' => 'Dial Process Sent', 'code' => '200 OK'));

}