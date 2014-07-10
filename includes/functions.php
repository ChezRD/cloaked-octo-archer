<?php

//ini_set('display_errors', 'On');
require_once "mySqlDb.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/includes/AxlRisApi.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/includes/AxlClass.php";
require_once "KLogger.php";

function addAdmin($userName,$firstName,$lastName,$role){

    $pass = genPassword(5);

    $connection = database::MySqlConnection();

    if(!$connection->query("INSERT INTO user (username,firstname,lastname,password,fk_role) "
        . "VALUES (\"$userName\",\"$firstName\",\"$lastName\",\"" .md5($pass) . "\",\"" . $role . "\")"))
    {
        //var_dump($connection->error);
    } else { print "<b class=\"text-success\"> Successfully added user: $userName</b></br>"
        . "Their temporary password is: $pass<br/>"
        . "This can be changed under user options.<hr>"; }
}


function updateAdmin($userName,$oldPass,$newPass,$confirmPass){

    if ($newPass != $confirmPass) {
        print '<b class=\"text-error\">The passwords do not match.</b>'
            . '<a href="userOptions.php?username=' . $userName . '"><span class="glyphicon"></span>Try again</a><hr>';
        exit;
    }
    $connection = database::MySqlConnection();

    if(!$connection->query('UPDATE user SET password = "' .md5($newPass) . '" WHERE username = "' . $userName . '"'))
    {
        var_dump($connection->error);

    } else { print "<b class=\"text-success\"> Successfully updated your password</b></br>";}
}

function genPassword ($length = 8)
{
  // given a string length, returns a random password of that length
  $password = "";
  // define possible characters
  $possible = "0123456789abcdfghjkmnpqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
  $i = 0;
  // add random characters to $password until $length is reached
  while ($i < $length) {
    // pick a random character from the possible ones
    $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
    // we don't want this character if it's already in the password
    if (!strstr($password, $char)) {
      $password .= $char;
      $i++;
    }
  }
  return $password;
}

function clean($str, $encode_ent = false) {
    $str = @trim($str);

    if($encode_ent) {
        $str = htmlentities($str);
    }

    if(version_compare(phpversion(),'4.3.0') >= 0) {
        if(get_magic_quotes_gpc()) {
            $str = stripslashes($str);
        }

        if(@mysql_ping()) {
            $str = mysql_real_escape_string($str);
        } else {
            $str = addslashes($str);
        }
    } else {
        if(!get_magic_quotes_gpc()) {
            $str = addslashes($str);
        }
    }

    return $str;
}

function processCsv($file)
{

    $file['name'] = "csvFiles/" . $file['file']['name'];
    $file['tempName'] = $file['file']['tmp_name'];
    $file['type'] = $file['file']['type'];
    $file['size'] = $file['file']['size'];

    if (validateFile($file)) {

        return  openFile($file['name']);
    }

}

function processCsvCtl($file)
{
    $martyAxl = 'sloanma';  //My CUCM AXL Account to associate device to
    $just_devices = []; // Create array to hold just device names

    $csv = processCsv($file); // Open and process the CSV file

    //Create $dev_array which will be used to query SXML with 'Item' => SEP....
    foreach ($csv as $row)
    {
        if ($row == '') continue;

        $dev_array[]['Item'] = $row[0];

    }

    // Create utility objects
    $axl = new AxlClass('10.132.10.10','8443','7.0/');
    $risClient = new AxlRisApi('10.132.10.10');
    $klogger = new KLogger($_SERVER["DOCUMENT_ROOT"] .  "/Logs/CTL/Bulk",KLogger::DEBUG);

    // Device Array Index
    $i = 0;

    // Iterate $dev_array in chunks of 200, which is the maximum query size for CUCM SXML 7.x
    foreach (array_chunk($dev_array,200,true) as $chunk)
    {
        // Send query to SXML to obtain registration status for
        $ris_query  = getDeviceIpBulk($chunk,$risClient,$klogger);

        foreach ($chunk as $key => $val)
        {
            $ip_results[$i]['DeviceName'] = $val['Item'];

            foreach ($ris_query as $cm_node)
            {
                if (!isset($cm_node->CmDevices[0])) continue;

                $ip_results[$i]['IpAddress'] = searchForIp($cm_node->CmDevices,$ip_results[$i]['DeviceName']);

                $klogger->logInfo('IP Results',$ip_results[$i]['IpAddress']);

                if (filter_var($ip_results[$i]['IpAddress'], FILTER_VALIDATE_IP)) break;

            }

            if (!$ip_results[$i]['IpAddress'])
            {
                $ip_results[$i]['IpAddress'] = "Unregistered";
            } else {
                array_push($just_devices,$ip_results[$i]['DeviceName']);
            }

            $i++;
        }
    }

    $userObj = getEndUser($martyAxl,$axl,$klogger);

    $res = updateUserDevAssocKeep($martyAxl,$just_devices,$userObj,$axl,$klogger);

    return $ip_results;
}

function searchForIp($array,$value)
{
    foreach ($array as $device)
    {
        if ($device->Name == $value && $device->Status == "Registered")
        {
            return $device->IpAddress;
        }
    }
    return false;
}

function openFile($file){

    $csv = array();
    $file_handle = fopen($file,'r');

    $count = 0;
    while (!feof($file_handle) ) {
        $row = fgetcsv($file_handle);
        if ($count == 0) {
            $count++;
            continue;
        }
        $csv[] = $row;
    }
    return $csv;
}

function validateFile($file){

    $allowedExts = array("csv");
    $allowedTypes = array("text/csv","application/csv","application/octet-stream");
    $temp = explode(".", $file['name']);
    $extension = end($temp);

    if (in_array($file["file"]["type"],$allowedTypes) && in_array($extension, $allowedExts))
    {
        if(move_uploaded_file($file['tempName'],$file['name']))
        {
            return TRUE;
        }
    } else {
        return FALSE;
    }
}

/*
 * Provisioning Functions
 */
function errorResponse($upi,$device,$cluster,$message,$code,$pass_fail,$mySql,$process)
{

    $pass_fail->logInfo("FAIL: UPI $upi - MESSAGE: $message - CODE: $code");
    $mySql->query("INSERT INTO " . $process . "_results (upi,device,cluster,code,message,status,last_updated) VALUES ('$upi','$device','$cluster','$code','$message','FAIL',NOW()) ON DUPLICATE KEY UPDATE device = '$device', cluster = '$cluster', code = '$code', message = '$message', status = 'FAIL', last_updated = NOW()");

    echo json_encode(array('success' => FALSE,'message' => $message, 'code' => $code));
    exit;
}

function validateUpi($upi)
{
    if (!(preg_match('/[0-9]{3,11}/',$upi))){
        return FALSE;
    }
    return TRUE;
}

function getEndUser($upi,$axl,$klogger)
{

    $response = $axl->getUser($upi);

    $klogger->logInfo("Request",$axl->_client->__getLastRequest());
    $klogger->logInfo("Response",$axl->_client->__getLastResponse());

    return $response;

}

function getPhone($device,$axl,$klogger)
{

    $response = $axl->getPhone($device);

    $klogger->logInfo("Request",$axl->_client->__getLastRequest());
    $klogger->logInfo("Response",$axl->_client->__getLastResponse());

    if ($response->return->device)
    {
        return $response->return->device;
    }
    return FALSE;
}

function addJabber($upi,$primaryDevice,$primaryDeviceObj,$line1,$axl,$klogger)
{

    $description = $primaryDeviceObj->description;
    $location = $primaryDeviceObj->locationName;
    $devicePool = $primaryDeviceObj->devicePoolName->_;
    $callingSearchSpace = $primaryDeviceObj->callingSearchSpaceName->_;
    $presenceGroup = $primaryDeviceObj->presenceGroupName->_;
    $subscribeCss = $primaryDeviceObj->subscribeCallingSearchSpaceName->_;
    $reroutingCss = "GLB-PRESENCE_SUBSCRIBE-CSS";

    $line1->associatedEndusers->enduser->userId = $upi;

    $response =  $axl->addPhone($upi,'CUPC' . $upi,$primaryDevice,$description,$location,$devicePool,$callingSearchSpace,$presenceGroup,$subscribeCss,$reroutingCss,$line1);

    $klogger->logInfo("Request",$axl->_client->__getLastRequest());
    $klogger->logInfo("Response",$axl->_client->__getLastResponse());

    return $response;
}

function disableVideoHP($device,$xml,$axl,$klogger)
{

    $response = $axl->disableVideo($device,$xml);

    $klogger->logInfo("Request",$axl->_client->__getLastRequest());
    $klogger->logInfo("Response",$axl->_client->__getLastResponse());

    return $response;
}
function updateUserDevAssoc($userId,$device,$axl,$klogger)
{
    $devices[] = $device;

    $devices = array_values($devices);

    $response = $axl->updateUserDevAssoc($userId,$devices);

    $klogger->logInfo("Request",$axl->_client->__getLastRequest());
    $klogger->logInfo("Response",$axl->_client->__getLastResponse());

    return $response;
}
function updateUserDevAssocKeep($userId,$device_list,$userObj,$axl,$klogger)
{
    if (!isset($userObj->return->user->associatedDevices->device))
    {
        $devices = $device_list;

    } elseif (is_array($userObj->return->user->associatedDevices->device)) {

        $devices = array_merge($userObj->return->user->associatedDevices->device,$device_list);

    } else {
        array_push($device_list,$userObj->return->user->associatedDevices->device);
        $devices = $device_list;
    }

    $devices = array_values($devices);

    $klogger->logInfo("Request",$devices);

    $response = $axl->updateUserDevAssoc($userId,$devices);

    $klogger->logInfo("Request",$axl->_client->__getLastRequest());
    $klogger->logInfo("Response",$axl->_client->__getLastResponse());

    return $response;
}
function updatePrimaryExtension($userId,$primaryExtension,$axl,$klogger)
{

    $response =  $axl->updatePrimaryExtension($userId,$primaryExtension);

    $klogger->logInfo("Request",$axl->_client->__getLastRequest());
    $klogger->logInfo("Response",$axl->_client->__getLastResponse());

    return $response;

}
function updateUserLicense($upi,$ups,$upc,$axl,$klogger)
{

    $response =  $axl->updateLicense($upi,$ups,$upc);

    $klogger->logInfo("Request",$axl->_client->__getLastRequest());
    $klogger->logInfo("Response",$axl->_client->__getLastResponse());

    return $response;

}

function updateUserGroup($upi,$axl,$klogger)
{

    foreach (array('Standard CTI Allow Control of Phones supporting Rollover Mode','Standard CTI Allow Control of Phones supporting Connected Xfer and conf','Standard CCM End Users','Standard CTI Enabled') as $i)
    {
        $response = $axl->udpateUserGroups($upi,$i);

        $klogger->logInfo("Request",$axl->_client->__getLastRequest());
        $klogger->logInfo("Response",$axl->_client->__getLastResponse());
        $klogger->logInfo("Setting user role $i for $upi");

        if (is_array($response))
        {
            $return[] = $response;
        }
    }
    if (is_array($return))
    {
        return $return;
    }
    return 'No fails';
}

function updateBfcp($deviceName,$axl,$klogger)
{

    $response = $axl->executeSql('update',"UPDATE device SET enablebfcp = 't' WHERE name = '$deviceName'");

    $klogger->logInfo("Request",$axl->_client->__getLastRequest());
    $klogger->logInfo("Response",$axl->_client->__getLastResponse());

    return $response;
}

function removeCipc($device,$axl,$klogger)
{

    $response = $axl->removeDevice($device);

    $klogger->logInfo("Request",$axl->_client->__getLastRequest());
    $klogger->logInfo("Response",$axl->_client->__getLastResponse());

    return $response;
}

function resetPhone($device,$axl,$klogger)
{

    $response = $axl->resetPhone($device);

    $klogger->logInfo("Request",$axl->_client->__getLastRequest());
    $klogger->logInfo("Response",$axl->_client->__getLastResponse());

    return $response;
}

function getDeviceIp($device,$ris,$klogger)
{
    $response = $ris->getDeviceIp($device);

    $klogger->logInfo("Request",$ris->_client->__getLastRequest());
    $klogger->logInfo("Response",$ris->_client->__getLastResponse());

    return $response;
}

function getDeviceIpBulk($devices,$ris,$klogger)
{
    $response = $ris->getDeviceIpBulk($devices);

    $klogger->logInfo("Request",$ris->_client->__getLastRequest());
    $klogger->logInfo("Response",$ris->_client->__getLastResponse());

    return $response;
}

function checkMAC($primaryDevice)
{

    if (preg_match('/^SEP[0-9a-fA-F]{12}$/', $primaryDevice)){
        return $primaryDevice;

    }

    return '';
}

function setCluster($cluster,$env)
{
    switch(strtoupper($env)) {
        case 'PROD':
            switch (strtoupper($cluster)) {
                case 'WAS':
                    $clusterIps = array('10.178.8.1','127.0.0.1','127.0.0.1');
                    $ports = array('8443','9110','9111');
                    return array($clusterIps,$ports);
                case 'CDG':  //10.138.184.1
                    $clusterIps = array('127.0.0.1','127.0.0.1','10.178.8.1');
                    $ports = array('9110','9111','8443');
                    return array($clusterIps,$ports);
                case 'MAA':  //10.138.200.1
                    $clusterIps = array('127.0.0.1','127.0.0.1','10.178.8.1');
                    $ports = array('9111','9110','8443');
                    return array($clusterIps,$ports);
                default:
                    return false;
            }
            break;
        case 'DEV':
            switch (strtoupper($cluster)) {
                case 'WAS':
                    $clusterIps = array('192.168.158.10','192.168.1.120');
                    $ports = array('8443','8443');
                    return array($clusterIps,$ports);
                    break;
                case 'CDG':
                    $clusterIps = array('192.168.1.120','192.168.158.10');
                    $ports = array('8443','8443');
                    return array($clusterIps,$ports);
                    break;
                default:
                    echo json_encode(array('success' => false,'message' => 'Cluster ID is Invalid'));
            }
        default:
            return false;
    }

}

function updateDescription($device,$description,$axl,$klogger)
{

    $response = $axl->updateDeviceDescription($device,$description);

    $klogger->logInfo("Request",$axl->_client->__getLastRequest());
    $klogger->logInfo("Response",$axl->_client->__getLastResponse());

    return $response;
}

function updateLinePartition($line,$from,$to,$axl,$klogger)
{
    $response = $axl->updateLinePartition($line,$from,$to);

    $klogger->logInfo("Request",$axl->_client->__getLastRequest());
    $klogger->logInfo("Response",$axl->_client->__getLastResponse());
    //$klogger->logInfo("Request Headers",$axl->_client->__getLastRequestHeaders());

    return $response;
}
function clearMySqlTable($table)
{
    $connection = database::MySqlConnection();
    $connection->query("TRUNCATE TABLE $table");
}
function setCtlKeys($model)
{
    switch ($model){

        case "Cisco 7975":
            return  [

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
        case "Cisco 7937": //Fall through
        case "Cisco 9971":
            return [

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
}