<?php

require '/var/www/vendor/autoload.php';

$start_time = microtime(true);

$query_string = isset($_SERVER["QUERY_STRING"]) ? $_SERVER["QUERY_STRING"] : "";
$params = explode("/", $query_string);

$device = isset($params[0]) ? $params[0] : "";
$version = isset($params[1]) ? $params[1] : "";
$debug = isset($params[2]) ? $params[2] : "";

//$device = isset($_GET['device']) ? $_GET['device'] : "";
//$version = isset($_GET['version']) ? $_GET['version'] : "";
//$debug = isset($_GET['debug']) ? $_GET['debug'] : "0";

/* Example file content '/var/www/mongodb.php'

  $config['username'] = '***********';
  $config['password'] = '***********';
  $config['hostname'] = 'api.alegrium.com';
  $config['database'] = 'billionaire_prod';
  $config['database_dev'] = 'billionaire_dev';
  $config['options'] = array('ssl' => true);

 */
include '/var/www/mongodb.php';

$json['current_time'] = gmdate('Y-m-d H:i:s');
$json['device'] = $device;
$json['version'] = $version;
$json['exception'] = "";

try {

    $key = "BillionaireAPI/get_event.php?".$query_string;
    $array = apcu_fetch($key);
    if ($array === FALSE) {
        // get mongodb object database

        $is_development = $debug == "1";
        $database = $is_development == true ? $config['database_dev'] : $config['database'];

        $connection_string = "mongodb://"
                . $config['username'] . ":"
                . $config['password'] . "@"
                . $config['hostname'] . "/"
                . $database;

        $client = new MongoDB\Client($connection_string, $config['options']); // create object client 
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
        }
        apcu_store($key, $array, 180);
    }

    if (is_array($array)) {
        $json['event_time']['start'] = $array['start_date'];
        $json['event_time']['end'] = $array['end_date'];

        if ($is_development == true) {
            $json['server_time'] = $array['server_time'];
            $json['update_time'] = $array['update_time'];
            try {
                $json['current_time'] = gmdate('Y-m-d H:i:s', (time() - strtotime($array['update_time'])) + strtotime($array['server_time']));
            } catch (Exception $ex) {
                $json['current_time'] = gmdate('Y-m-d H:i:s');
            }
        }
    }
} catch (Exception $ex) {
    $json['exception'] = $ex->getMessage();
} finally {
    
}

$end_time = microtime(true);

$json['execute_time'] = number_format($end_time - $start_time, 5);
$json['memory_usage'] = memory_get_usage(true);

header('Content-Type: application/json');

echo json_encode($json);

