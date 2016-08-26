<?php

$start_time = microtime(true);

define('IS_DEVELOPMENT', true);
require '/var/www/token.php';

function show_error($response_code, $status_code, $message) {
    http_response_code($response_code);
    header('Content-Type: application/json');
    echo json_encode(array('status_code' => $status_code, 'message' => $message, 'remote_addr' => $_SERVER["REMOTE_ADDR"]));
    die;
}

$pass_token = isset($_SERVER["REMOTE_ADDR"]) && in_array($_SERVER["REMOTE_ADDR"], ['127.0.0.1']);

if (function_exists("getallheaders")) {
    $headers = getallheaders();
} else {
    $headers['Billionaire-Token'] = isset($_SERVER["HTTP_BILLIONAIRE_TOKEN"]) ? $_SERVER["HTTP_BILLIONAIRE_TOKEN"] : "";
}
if (!$pass_token && (!isset($headers['Billionaire-Token']) || $headers['Billionaire-Token'] != BILLIONAIRE_TOKEN)) {
    show_error(401, "401 Unauthorized", "Invalid Billionaire Token");
}

$query_string = isset($_SERVER["QUERY_STRING"]) ? $_SERVER["QUERY_STRING"] : "";
$params = explode("/", $query_string);

$service = isset($params[0]) ? $params[0] : "";

header('Content-Type: application/json');

switch ($service) {
    case 'send_push' :
        if ($_SERVER["REQUEST_METHOD"] != 'POST') {
            show_error(405, "405 Method Not Allowed", "Invalid Method");
        }
        $input = file_get_contents("php://input");
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
