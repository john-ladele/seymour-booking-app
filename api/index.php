<?php
//die('here');
// error_reporting(0);
// ini_set("display_errors", FALSE);
date_default_timezone_set('Africa/Lagos');
ini_set('log_errors',TRUE);
ini_set('max_execution_time', 3600); //execution time in seconds

error_reporting(E_ALL); ini_set("display_errors", TRUE);
// apc_clear_cache();
// header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
// header("Access-Control-Allow-Headers: Origin, X-Auth-Token, X-Requested-With, Content-Type, Accept, Authorization");

if($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
   header( "HTTP/1.1 200 OK" );
   exit();
}

require_once 'config.php';

require './libs/Slim/Slim.php';

// include classes
foreach (glob("classes/*.php") as $filename)
{
    require_once $filename;
    // echo $filename. '<br>';
}


\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// include functions
foreach (glob("functions/*.php") as $filename)
{
    require_once $filename;
}

/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields,$request_params) {
    $error = false;
    $error_fields = "";
    foreach ($required_fields as $field) {
        if (!isset($request_params->$field) || strlen(trim($request_params->$field)) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["status"] = "error";
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoResponse(200, $response);
        $app->stop();
    }
}

//send JSON response back to referrer
function echoResponse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}


$app->run();