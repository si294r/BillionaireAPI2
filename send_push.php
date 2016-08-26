<?php

defined('IS_DEVELOPMENT') OR exit('No direct script access allowed');

function getAnnotation($annotationIndex) {
    $annotation = [
        //0
        "M",
        "B",
        "T",
        "QD",
        "QT",
        "SX",
        "ST",
        "OT",
        "NL",
        // Decillion 9
        "DL",
        "UL",
        "DDL",
        "TDL",
        "QTL",
        "QDL",
        "SDL",
        "STL",
        "OTL",
        "NDL",
        // Vigintillion 19
        "VG",
        "UVG",
        "DVG",
        "TVG",
        "QAVG",
        "QIVG",
        "SXVG",
        "SPVG",
        "OCVG",
        "NOVG",
        // Trigintillion 29
        "TG",
        "UTG",
        "DTG",
        "TTG",
        "QATG",
        "QITG",
        "SXTG",
        "SPTG",
        "OCTG",
        "NOTG",
        // Quadragintillion 39
        "QAG",
        "UQIG",
        "DQAG",
        "TQAG",
        "QAQAG",
        "QIQAG",
        "SXQAG",
        "SPQAG",
        "OCQAG",
        "NOQAG",
        // Quinquagintillion 49
        "QIG",
        "UQIG",
        "DQIG",
        "TQIG",
        "QAQIG",
        "QIQIG",
        "SXQIG",
        "SPQIG",
        "OCQIG",
        "NOQIG",
        // Sexagintillion 59
        "SXG",
        "USXG",
        "DSXG",
        "TSXG",
        "QASXG",
        "QISXG",
        "SXSXG",
        "SPSXG",
        "OCSXG",
        "NOSXG",
        // Septuagintillion 69
        "SPG",
        "USPG",
        "DSPG",
        "TSPG",
        "QASPG",
        "QISPG",
        "SXSPG",
        "SPSPG",
        "OCSPG",
        "NOSPG",
        // Octogintillion
        "OCG",
        "UOCG",
        "DOCG",
        "TOCG",
        "QAOCG",
        "QIOCG",
        "SXOCG",
        "SPOCG",
        "OCOCG",
        "NOOCG",
        // Nonagintillion
        "NOG",
        "UNOG",
        "DNOG",
        "TNOG",
        "QANOG",
        "QINOG",
        "SXNOG",
        "SPNOG",
        "OCNOG",
        "NONOG",
        // Centillion
        "C",
        "UC"
    ];

    return $annotation[$annotationIndex];
}

require 'mongodb_helper.php';

$json = json_decode($input);

$userFacebookID = isset($json->facebookID) ? $json->facebookID : "";
$facebookFriendsID = isset($json->facebookFriendsID) ? $json->facebookFriendsID : [];
$networthValue = isset($json->networthValue) ? $json->networthValue : 0;

$db = get_mongodb(IS_DEVELOPMENT);
$User = $db->selectCollection("_User");

$document = $User->findOne([ 'facebookID' => $userFacebookID]);

$user_result = bson_document_to_array($document);

$userNameArr = explode(" ", $user_result['displayName']);
$userName = $userNameArr[0] . " " . substr($userNameArr[1], 0, 1) . ".";

$networthPow = $user_result['netWorth_pow'];
$networth = floor($user_result['netWorth_2']);
$networthResult = $networth; //  * Math.pow(10, networthPow);
$networthString = strval($networthResult);
$annotation = 0;
$length = strlen($networthString);
$frontIndex = $length;
$annotationStr = "";

if ($networthPow > 0) {
    $length = $networthPow + 15; // 74
    $frontIndex = $length % 3; // 2
    $frontIndex = ($frontIndex == 0) ? 3 : $frontIndex;
    $annotation = floor((($length - $frontIndex) - 3) / 3) - 1;
    $annotationStr = getAnnotation($annotation);
} else if ($length > 6 && $networthPow == 0) {
    $frontIndex = $length % 3;
    $frontIndex = ($frontIndex == 0) ? 3 : $frontIndex;
    $annotation = floor((($length - $frontIndex) - 3) / 3) - 1;
    $annotationStr = getAnnotation($annotation);
}

$networthResult = substr($networthString, 0, $frontIndex);
$nextDigit = 0;

for ($i = 0; $i < 3; $i++) {
    $currentDigit = $frontIndex + $i;
    $currentDigitString = substr($networthString, $currentDigit, $currentDigit + 1);
    if (intval($currentDigitString) > 0) {
        $nextDigit = i + 1;
    }
}
if ($nextDigit > 0) {
    $networthResult .= "," . substr($networthString, $frontIndex, $frontIndex + $nextDigit);
}
$networthResult .= $annotationStr;

$message = "Boss, " . $userName . " just reached $" . $networthValue . ". Manage your business to get ahead!";

$Installation = $db->selectCollection("_Installation");
$document2 = $Installation->findOne([ 'facebookID' => $facebookFriendsID[count($facebookFriendsID) - 1]]);

$install_result = bson_document_to_array($document2);

$device_token = isset($install_result['deviceToken']) ? $install_result['deviceToken'] : "";

$headers = array(
    "Push-Token: ".PUSH_TOKEN,
    "Content-Type: application/json"
);

$body_message['apps_name'] = "billionaire_prod";
$body_message['device_token'] = $device_token;
$body_message['message'] = $message;
echo json_encode($body_message);        
$http = curl_init();

curl_setopt_array($http, array(
    CURLOPT_URL => "http://api.alegrium.com/PushAPI/",
    CURLOPT_PORT => 80,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_POST => TRUE,
    CURLOPT_POSTFIELDS => json_encode($body_message),
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HEADER => 1
));

$result = curl_exec($http);
curl_close($http);

$json_result = json_decode($result);
return $json_result;