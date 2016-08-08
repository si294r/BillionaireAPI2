<?php

defined('IS_DEVELOPMENT') OR exit('No direct script access allowed');

require 'mongodb_helper.php';

$facebookID = isset($params[1]) ? $params[1] : "";

$limit = isset($params[2]) ? $params[2] : 50;
$overwrite_top_player_cache = isset($params[3]) ? $params[3] : 0;

if (trim($facebookID) == "" && $overwrite_top_player_cache == 0) {
    return array(
        "code" => 141,
        "error" => "parameter facebook id not found"
    );
}

$db = get_mongodb(IS_DEVELOPMENT, $overwrite_top_player_cache == 0);
$collection = $db->selectCollection("_User");

$fields = ['_id', 'facebookID', 'netWorth', 'netWorth_2', 'netWorth_pow', 'displayName'];

if ($overwrite_top_player_cache == 0) {
    $document = $collection->findOne([ 'facebookID' => $facebookID]);

    if (!is_object($document)) {
        return array("code" => 141, "error" => "User not found");
    }

    $result['currentUser'] = bson_document_to_array($document, $fields);
}

$update_top_player_cache = 0;
$key = "BillionaireAPI/leaderboard.php?globalboard";
$array_cache = apcu_fetch($key);
if ($array_cache === FALSE || $overwrite_top_player_cache == 1) {
    $filter = array();
    $sort = array('netWorth_pow' => -1, 'netWorth_2' => -1, 'facebookID' => -1); // desc(-1), asc(1)
    $options = array('sort' => $sort, 'limit' => (int) $limit);

    $documents = $collection->find($filter, $options);

    $array_cache = bson_documents_to_array($documents, $fields);
    $update_top_player_cache = 1;
}
$result['topPlayer'] = $array_cache;

if ($overwrite_top_player_cache == 0) {
    $netWorth_pow = isset($document->netWorth_pow) ? $document->netWorth_pow : 0;
    $netWorth_2 = isset($document->netWorth_2) ? $document->netWorth_2 : 0;
    $count1 = $collection->count(array(//'facebookID' => array('$exists' => true),
        
        'netWorth_pow' => array('$gt' => $netWorth_pow)));
    $count2 = $collection->count(array(//'facebookID' => array('$exists' => true),
        
        'netWorth_pow' => array('$eq' => $netWorth_pow),
        'netWorth_2' => array('$gt' => $netWorth_2)));
    $count3 = $collection->count(array( '$and' =>
            array(
                //array('facebookID' => array('$exists' => true)),
        
                array('netWorth_pow' => array('$eq' => $netWorth_pow)),
                array('netWorth_2' => array('$eq' => $netWorth_2)),
                array('facebookID' => array('$gte' => $facebookID))
                )
            ));

    $facebook_ids = array($facebookID);
} else {
    $count1 = $count2 = $count3 = 0;

    $facebook_ids = array();
}

if ($update_top_player_cache == 1) {
    $i = 1;
    foreach ($result['topPlayer'] as $k => $v) {
        if (!isset($v['facebookID'])) {
            $result['topPlayer'][$k]['facebookID'] = "0"; 
        } elseif (trim($v['facebookID']) != "") {
            $facebook_ids[] = $v['facebookID'];
        }
        $result['topPlayer'][$k]['rank'] = $i;
        $i++;
    }
}

$url = "https://graph.facebook.com/?ids=" . implode(",", $facebook_ids) . "&access_token=" . $config['facebook_token'];
$result_facebook = file_get_contents($url);
$json_facebook = json_decode($result_facebook);

if ($overwrite_top_player_cache == 0) {
    $result['currentUser']['name'] = isset($json_facebook->$facebookID->name) ? $json_facebook->$facebookID->name : "N/A";
    $result['currentUser']['rank'] = $count1 + $count2 + $count3;
}

if ($update_top_player_cache == 1) {
    foreach ($result['topPlayer'] as $k => $v) {
        if (trim($v['facebookID']) != "" && isset($json_facebook->$v['facebookID']->name)) {
            $result['topPlayer'][$k]['name'] = $json_facebook->$v['facebookID']->name;
        } else {
            $result['topPlayer'][$k]['name'] = "N/A";
        }
    }

    apcu_store($key, $result['topPlayer'], 240);
}

$result['status'] = TRUE;

return $result;
