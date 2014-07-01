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

    /*
     * Instantiate Objects
     */
    $axl = new AxlClass('10.132.10.10','8443','7.0/');
    $klogger = new KLogger("../Logs/CTL/$phone",KLogger::DEBUG);
    $mySql = database::MySqlConnection();

    /*
     * Get User device association
     */
    $userObj = getEndUser($martyAxl,$axl,$klogger);

    /*
     * Update device association
     */

    $res = updateUserDevAssocKeep($martyAxl,$phone,$userObj,$axl,$klogger);

    echo json_encode(array('success' => true,'message' => "Got data for $_REQUEST[deviceName] & $_REQUEST[ipAddress]", 'code' => '200 OK'));

}