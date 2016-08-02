<?php

defined('IS_DEVELOPMENT') OR exit('No direct script access allowed');

require 'mongodb_helper.php';

$facebookID = isset($params[1]) ? $params[1] : "";
$limit = isset($params[2]) ? $params[2] : 50;
        
if (trim($facebookID) == "") {
    return array(
        "code" => 141,
        "error" => "parameter facebook id not found"
    );
}

$db = get_mongodb(IS_DEVELOPMENT);
$collection = $db->selectCollection("_User");

$document = $collection->findOne([ 'facebookID' => $facebookID ], 
        ['facebookID' => true, 'netWorth' => false, 'netWorth_2' => false, 'netWorth_pow' => true, 'displayName' => true]);

if (!is_object($document)) {
    return array("code" => 141, "error" => "User not found");
}

$filter = array();
$sort = array('netWorth_pow' => -1, 'netWorth_2' => -1, 'facebookID' => -1); // desc(-1), asc(1)
$options = array('sort' => $sort, 'limit' => (int) $limit);

$documents = $collection->find($filter, $options);

$result['status'] = TRUE;
$result['currentUser'] = bson_document_to_array($document);
$result['topPlayer'] = bson_documents_to_array($documents);

//$score = isset($document->score) ? $document->score : 0;
$count1 = 0; //$collection->count(array('score' => array('$gt' => $score)));
$count2 = 0; //$collection->count(array('score' => array('$eq' => $score), 'facebook_id' => array('$gte' => $facebook_id)));

$i = 1;
$facebook_ids = array($facebookID);
foreach ($result['topPlayer'] as $k=>$v) {
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
$result['currentUser']['rank'] = $count1 + $count2;

foreach ($result['topPlayer'] as $k=>$v) {
    if (trim($v['facebookID']) != "" && isset($json_facebook->$v['facebookID']->name)) {
        $result['topPlayer'][$k]['name'] = $json_facebook->$v['facebookID']->name;
    } else {
        $result['topPlayer'][$k]['name'] = "N/A";
    }
}

//echo json_encode($result);

return $result;
