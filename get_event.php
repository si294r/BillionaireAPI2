<?php

require '/var/www/vendor/autoload.php';

$start_time = microtime(true);

$query_string = isset($_SERVER["QUERY_STRING"]) ? $_SERVER["QUERY_STRING"] : "";
$params = explode("/", $query_string);

$device     = isset($params[0]) ? $params[0] : "";
$version    = isset($params[1]) ? $params[1] : "";
$debug      = isset($params[2]) ? $params[2] : "";

//$device = isset($_GET['device']) ? $_GET['device'] : "";
//$version = isset($_GET['version']) ? $_GET['version'] : "";
//$debug = isset($_GET['debug']) ? $_GET['debug'] : "0";

$config['username'] = 'billionaire';
$config['password'] = '4legrium';
$config['hostname'] = 'mongodb_server';
$config['database'] = 'billionaire_prod';
$config['database_dev'] = 'billionaire_dev';

//$db_host = 'localhost';
//$db_user = 'root';
//$db_pass = 'password';
//$db_name = 'Billionaire_prod';
//$tbl_event = 'event';

$json['current_time'] = gmdate('Y-m-d H:i:s');
$json['device'] = $device;
$json['version'] = $version;
$json['exception'] = "";

//if ($debug == "1") {
//    $tbl_event = str_replace("_prod", "_dev", $db_name) . "." . $tbl_event;
//}

try {
    
    // get mongodb object database
    
    $is_development = $debug == "1";
    $database = $is_development == true 
            ? $config['database_dev']
            : $config['database'];
    
    $connection_string = "mongodb://"
            . $config['username'] . ":"
            . $config['password'] . "@"
            . $config['hostname'] . "/"
            . $database;

    $client = new MongoDB\Client($connection_string); // create object client 
    $db = $client->$database; // select database

    $document = $db->event->findOne(['device' => $device, 'version' => $version, 'status' => 'Active']);

    // convert BSON Object Document to Array PHP
    
    $array = NULL;

    if (is_object($document)) {
        foreach ($document as $k => $v) {
            if (is_object($v)) {
                $array[$k] = (string) $v;
            } else {
                $array[$k] = $v;
            }
        }
        
        $json['event_time']['start'] = $array['start_date'];
        $json['event_time']['end'] = $array['end_date'];
    }
    
//    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

//    $query = "select start_date, end_date from " . $tbl_event . " "
//            . "where `device` = ? and `version` = ? and `status`='active' ";

//    if ($stmt = $mysqli->prepare($query)) {
//
//        $stmt->bind_param("ss", $device, $version);
//        $stmt->execute();
//        $stmt->bind_result($start_date, $end_date);
//
//        if ($stmt->fetch()) {
//            $json['event_time']['start'] = $start_date;
//            $json['event_time']['end'] = $end_date;
//        }
//
//        $stmt->close();
//    }
} catch (Exception $ex) {
    $json['exception'] = $ex->getMessage();
} finally {
//    $mysqli->close();
}

$end_time = microtime(true);

$json['execute_time'] = number_format($end_time - $start_time, 5);
$json['memory_usage'] = memory_get_usage(true);

echo json_encode($json);


