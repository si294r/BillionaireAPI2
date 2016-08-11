<?php

$start_time = microtime(true);

define('IS_DEVELOPMENT', true);
define('BILLIONAIRE_TOKEN', '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ');

function show_error($response_code, $status_code, $message) {
    http_response_code($response_code);
    header('Content-Type: application/json');
    echo json_encode(array('status_code' => $status_code, 'message' => $message, 'server' => $_SERVER["REMOTE_ADDR"]));
    die;
}

if (function_exists("getallheaders")) {
    $headers = getallheaders();
} else {
    $headers['Billionaire-Token'] = $_SERVER["HTTP_BILLIONAIRE_TOKEN"];
}
if (!isset($headers['Billionaire-Token']) || $headers['Billionaire-Token'] != BILLIONAIRE_TOKEN) {
    show_error(401, "401 Unauthorized", "Invalid Billionaire Token");
}

$query_string = isset($_SERVER["QUERY_STRING"]) ? $_SERVER["QUERY_STRING"] : "";
$params = explode("/", $query_string);

$service = isset($params[0]) ? $params[0] : "";

header('Content-Type: application/json');

switch ($service) {
    case 'globalboard' :
    case 'localboard' :
    case 'friendboard' :
        if ($_SERVER["REQUEST_METHOD"] != 'GET') {
            show_error(405, "405 Method Not Allowed", "Invalid Method");
        }
        try {
            $service_result = include($service.'.php');
        } catch (Exception $ex) {
            show_error(500, "500 Internal Server Error", $ex->getMessage());
        }
        break;
    default :
        show_error(503, "503 Service Unavailable", "Invalid Service");
}

$end_time = microtime(true);

$service_result['execution_time'] = number_format($end_time - $start_time, 5);
$service_result['memory_usage'] = memory_get_usage(true);

echo json_encode($service_result);
