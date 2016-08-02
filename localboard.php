<?php

defined('IS_DEVELOPMENT') OR exit('No direct script access allowed');

require 'mongodb_helper.php';

$facebookID = isset($params[1]) ? $params[1] : "";
$countryCode = isset($params[2]) ? $params[2] : "";
$limit = isset($params[3]) ? $params[3] : 50;

if (trim($facebookID) == "") {
    return array(
        "code" => 141,
        "error" => "parameter facebook id not found"
    );
}

$db = get_mongodb(IS_DEVELOPMENT);
$collection = $db->selectCollection("_User");

$document = $collection->findOne([ 'facebookID' => $facebookID]);

if (!is_object($document)) {
    return array("code" => 141, "error" => "User not found");
}

$fields = ['facebookID', 'netWorth', 'netWorth_2', 'netWorth_pow', 'displayName'];

$result['status'] = TRUE;
$result['currentUser'] = bson_document_to_array($document, $fields);

$key = "BillionaireAPI/leaderboard.php?localboard/".$countryCode;
$array_cache = apcu_fetch($key);
if ($array_cache === FALSE) {
    $filter = array('countryCode' => array('$eq' => $countryCode));
    $sort = array('netWorth_pow' => -1, 'netWorth_2' => -1, 'facebookID' => -1); // desc(-1), asc(1)
    $options = array('sort' => $sort, 'limit' => (int) $limit);

    $documents = $collection->find($filter, $options);

    $array_cache = bson_documents_to_array($documents, $fields);
    apcu_store($key, $array_cache, 180);
}
$result['topPlayer'] = $array_cache;

$netWorth_pow = isset($document->netWorth_pow) ? $document->netWorth_pow : 0;
$netWorth_2 = isset($document->netWorth_2) ? $document->netWorth_2 : 0;
$count1 = $collection->count(array('countryCode' => array('$eq' => $countryCode), 
                                    'netWorth_pow' => array('$gt' => $netWorth_pow)));
$count2 = $collection->count(array('countryCode' => array('$eq' => $countryCode), 
                                    'netWorth_pow' => array('$eq' => $netWorth_pow), 
                                    'netWorth_2' => array('$gt' => $netWorth_2)));
$count3 = $collection->count(array('countryCode' => array('$eq' => $countryCode), 
                                    'netWorth_pow' => array('$eq' => $netWorth_pow), 
                                    'netWorth_2' => array('$eq' => $netWorth_2),
                                    'facebookID' => array('$gte' => $facebookID)));

$i = 1;
$facebook_ids = array($facebookID);
foreach ($result['topPlayer'] as $k => $v) {
//    $result['topPlayer'][$k]['name'] = 'Player '.$i;
    if (trim($v['facebookID']) != "") {
        $facebook_ids[] = $v['facebookID'];
    }
    $result['topPlayer'][$k]['rank'] = $i;
    $i++;
}

$url = "https://graph.facebook.com/?ids=" . implode(",", $facebook_ids) . "&access_token=" . $config['facebook_token'];
$result_facebook = file_get_contents($url);
$json_facebook = json_decode($result_facebook);

$result['currentUser']['name'] = isset($json_facebook->$facebookID->name) ? $json_facebook->$facebookID->name : "N/A";
$result['currentUser']['rank'] = $count1 + $count2 + $count3;

foreach ($result['topPlayer'] as $k => $v) {
    if (trim($v['facebookID']) != "" && isset($json_facebook->$v['facebookID']->name)) {
        $result['topPlayer'][$k]['name'] = $json_facebook->$v['facebookID']->name;
    } else {
        $result['topPlayer'][$k]['name'] = "N/A";
    }
}

return $result;
