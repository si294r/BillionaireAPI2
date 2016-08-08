<?php

defined('IS_DEVELOPMENT') OR exit('No direct script access allowed');

require '/var/www/vendor/autoload.php';
require '/var/www/mongodb_billionaire.php';

// get mongodb object database
function get_mongodb($is_development = false, $use_balancer = true) {

    global $config;

    if ($use_balancer) {
        $value = apcu_fetch("BillionaireAPI_Balancer_DB");
        if ($value === FALSE) {
            $balancer = 1;
        } else {
            $balancer = $value + 1;
            if ($balancer > count($config['hostname_balancer'])) {
                $balancer = 1;
            }
        }
        apcu_store("BillionaireAPI_Balancer_DB", $balancer);
    }

    $database = $is_development == true ? $config['database_dev'] : $config['database'];
    $hostname = $use_balancer == true ? $config['hostname_balancer'][$balancer - 1] : $config['hostname'];

    $connection_string = "mongodb://"
            . $config['username'] . ":"
            . $config['password'] . "@"
//            . $config['hostname'] . "/"
            . $hostname . "/"
            . $database;

    $client = new MongoDB\Client($connection_string, $config['options']); // create object client 

    return $client->$database; // select database
}

// get mongodb document object id
function bson_oid($id) {
    return new MongoDB\BSON\ObjectID($id);
}

// convert BSON Object Document to Array PHP
function bson_document_to_array($document, $fields = []) {
    $array = NULL;

    if (is_object($document)) {
        foreach ($document as $k => $v) {
            if (count($fields) > 0 && !in_array($k, $fields)) {
                continue;
            }
            if (is_object($v) && "MongoDB\Model\BSONDocument" == get_class($v)) {
                $array[$k] = bson_document_to_array($v);
            } elseif (is_object($v)) {
                $array[$k] = (string) $v;
            } else {
                $array[$k] = $v;
            }
        }
    }
    return $array;
}

// convert BSON Object Documents to Array PHP
function bson_documents_to_array($documents, $fields = []) {
    $array = array();

    if (is_object($documents)) {
        foreach ($documents as $document) {
            $array[] = bson_document_to_array($document, $fields);
        }
    }
    return $array;
}
