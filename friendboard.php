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

$fields = ['_id', 'facebookID', 'netWorth', 'netWorth_2', 'netWorth_pow', 'displayName'];

echo "microtime: ".microtime()."\r\n";
$document = $collection->findOne([ 'facebookID' => $facebookID]);
echo "microtime: ".microtime()."\r\n";

if (!is_object($document)) {
    return array("code" => 141, "error" => "User not found");
}

$result['currentUser'] = bson_document_to_array($document, $fields);

$friends = array();
$filter_friends = array($facebookID);
$url = "https://graph.facebook.com/v2.7/$facebookID/friends?access_token={$config['facebook_token']}&limit=50";

get_facebook_friends:
echo "microtime: ".microtime()."\r\n";
$result_facebook = file_get_contents($url);
echo "microtime: ".microtime()."\r\n";
$json_facebook = json_decode($result_facebook);

foreach ($json_facebook->data as $v) {
    $friends[$v->id] = $v->name;
    $filter_friends[] = $v->id;
}

if (isset($json_facebook->paging->next)) {
    $url = $json_facebook->paging->next;
    goto get_facebook_friends;
}

$filter = array('netWorth_2' => array('$gt' => 0), 'facebookID' => array('$in' => $filter_friends));
$sort = array('netWorth_pow' => -1, 'netWorth_2' => -1, 'facebookID' => -1); // desc(-1), asc(1)
$options = array('sort' => $sort, 'limit' => (int) $limit);

$documents = $collection->find($filter, $options);

//$result['topPlayer'] = bson_documents_to_array($documents, $fields);

$url2 = "https://graph.facebook.com/?ids=" . $facebookID . "&access_token=" . $config['facebook_token'];
echo "microtime: ".microtime()."\r\n";
$result_facebook2 = file_get_contents($url2);
echo "microtime: ".microtime()."\r\n";
$json_facebook2 = json_decode($result_facebook2);

$friends[$facebookID] = $json_facebook2->$facebookID->name;

$result['currentUser']['name'] = $json_facebook2->$facebookID->name;
$result['currentUser']['rank'] = 0;
$result['filter_friends'] = $filter_friends;

$i = 1;
foreach ($result['topPlayer'] as $k=>$v) {
    $result['topPlayer'][$k]['name'] = $friends[$v['facebookID']];
    $result['topPlayer'][$k]['rank'] = $i;
    $i++;
}

$result['status'] = TRUE;

return $result;