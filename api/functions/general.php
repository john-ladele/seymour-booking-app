<?php

$app->get('/test', function() use ($app) {
    $response = array();

    $db = new DbHandler();

    //test if this api is working. Return some params
    $response['param_submitted'] = $app->request->get('id');
    $response['owner'] = "John O";
    $response['time'] = date('Y-m-d H:i:s');
    $response['status'] = "success";
    $response["message"] = "API Working perfectly!";
    echoResponse(200, $response);
});

$app->post('/testPost', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());

    $response['data'] = $r->data;
    $response["message"] = "API Post Working perfectly!";
    echoResponse(200, $response);
});

$app->get('/testPassGen', function() use ($app) {
    $response = array();

    $db = new DbHandler();
    $pg = new PasswordGenerator();

    //test if this api is working. Return some params
    $response['param_submitted'] = $app->request->get('id');
    $response['owner'] = "John O";
    $response['time'] = date('Y-m-d H:i:s');
    $response['randomPassword'] = $pg->randomPassword(99);
    $response['randomNumericPassword'] = $pg->randomNumericPassword(6);

    $response['status'] = "success";
    $response["message"] = "API Working perfectly!";
    echoResponse(200, $response);
});

$app->get('/testObjPurify', function() use ($app) {

    $db = new DbHandler();

    class MyClass
	{
	    public $var1 = 'value 1';
	    public $var2 = 'value 2';
	    public $var3 = 'value 3';
	}

    $mc = new MyClass();
    $mc = $db->purifyObj($mc);
    var_dump($mc); die;

    //test if this api is working. Return some params
    $response['param_submitted'] = $app->request->get('id');
    $response['owner'] = "John O";
    $response['time'] = date('Y-m-d H:i:s');
    $response['status'] = "success";
    $response["message"] = "API Working perfectly!";
    echoResponse(200, $response);
});

$app->get('/testMailer', function() use ($app) {
    $response = array();

    $db = new DbHandler();
    $email = $db->purify($app->request->get('email'));

    if($email) {
        // Send Test Email
        $swiftmailer = new mySwiftMailer();
        $subject = "Test Email from ".SHORTNAME;
        $body = "<p>Hello,</p>
    <p>
    <p>If you can read this, Email Delivery is working on ".SHORTNAME."</p>
    <p>Thank you for using ".SHORTNAME." App.</p>
    <p>NOTE: please DO NOT REPLY to this email.</p>
    <p><br><strong>".SHORTNAME." App</strong></p>";
        $swiftmailer->sendmail(FROM_EMAIL, SHORTNAME, [$email], $subject, $body);

        $response['status'] = "success";
        $response["message"] = "Mail sent!";
        echoResponse(200, $response);
    } else {
        $response['status'] = "error";
        $response["message"] = "No email provided!";
        echoResponse(201, $response);
    }
});
