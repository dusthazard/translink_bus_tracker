<?php

// get the json data from the request

if(!$data = json_decode(file_get_contents('php://input'), true)) {
  header('HTTP/1.1 400 Bad Request');
  die();
}

$origin=isset($_SERVER['HTTP_ORIGIN'])?$_SERVER['HTTP_ORIGIN']:$_SERVER['HTTP_HOST'];
header('Access-Control-Allow-Origin: '.HOST_NAME);	
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, *");

require '../vendor/autoload.php';
require '../auth.php';
require 'classes/api.php';

// get the current conditions

$api = new api;

$current_conditions = $api->getData();

// get the routes requested

$result = [];

foreach($data['routes'] as $route) {
  
  if(isset($current_conditions['data'][$route]))
    $result = array_merge($result,$current_conditions['data'][$route]);

}

// return the result

echo json_encode(array(
  'timestamp' => $current_conditions['timestamp'],
  'data' => $result
));