<?php

//createRegion
$app->post('/createRegion', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());

    verifyRequiredParams(array('reg_name', 'reg_country', 'reg_manager_id'),$r->region);

    $db = new DbHandler();
    $ss = new SessionHandlr();

    $reg_name = $db->purify($r->region->reg_name);
    $reg_country = $db->purify($r->region->reg_country);
    $reg_manager_id = $db->purify($r->region->reg_manager_id);
    $session = $ss->getSession('call2fix_user');
    $user_created_by = $session['call2fix_user']['user_fullname'];

    $reg_check  = $db->getOneRecord("SELECT reg_id FROM region WHERE reg_name = '$reg_name' AND reg_country = '$reg_country'");

    //checking if region already exists with same customer, number and street
    if($reg_check)
    {
        //already used
        $response['status'] = "error";
        $response["message"] = "Region with provided Name and Country already Exists for this Customer! Please use a different set of values";
        echoResponse(201, $response);
    } else {
        //create new
        $reg_id = $db->insertToTable(
            [$reg_name, $reg_country, $reg_manager_id], /*values - array*/
            ['reg_name', 'reg_country', 'reg_manager_id'], /*column names - array*/
            "region" /*table name - string*/
        );

        if($reg_id) { /*created successfully*/
            // log user action
            $lg = new Logger();
            $lg->logAction("call2fix_user", $user_created_by . " created a region");
            $response['reg_id'] = $reg_id;
            $response['status'] = "success";
            $response["message"] = "Region created successfully!";
            echoResponse(200, $response);
        } else { /* failed*/
            $response['status'] = "error";
            $response["message"] = "Something went wrong while trying to create the region, please try again later or contact Support";
            echoResponse(201, $response);
        }
    }

});

//editRegion
$app->post('/editRegion', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());

    verifyRequiredParams(array('reg_name', 'reg_country', 'reg_manager_id'),$r->region);

    $db = new DbHandler();
    $ss = new SessionHandlr();

    $reg_id = $db->purify($r->region->reg_id);
    $reg_name = $db->purify($r->region->reg_name);
    $reg_country = $db->purify($r->region->reg_country);
    $reg_manager_id = $db->purify($r->region->reg_manager_id);
    $session = $ss->getSession('call2fix_user');
    $user_created_by = $session['call2fix_user']['user_fullname'];

    $reg_check  = $db->getOneRecord("SELECT reg_id FROM region WHERE reg_name = '$reg_name' AND reg_country = '$reg_country' AND reg_id<>$reg_id");

    //checking if name is already used by another
    if($reg_check)
    {
        //already used
        $response['status'] = "error";
        $response["message"] = "Region with provided Name and Country already Exists for this Customer! Please use a different set of values";
        echoResponse(201, $response);
    } else {
        //update
        $update_region = $db->updateInTable(
        	"region", /*table*/
        	[ 'reg_name'=>$reg_name, 'reg_country' => $reg_country, 'reg_manager_id' => $reg_manager_id ], /*columns*/
        	[ 'reg_id'=>$reg_id ] /*where clause*/
        );

        if($update_region >= 0) { /*edited successfully*/
            // log user action
            $lg = new Logger();
            $lg->logAction("call2fix_user", $user_created_by . " editted a region");
            $response['status'] = "success";
            $response["message"] = "Region updated successfully!";
            echoResponse(200, $response);
        } else { /*failed */
            $response['status'] = "error";
            $response["message"] = "Something went wrong while trying to update the region, please try again later or contact Support";
            echoResponse(201, $response);
        }
    }

});

//getRegion
$app->get('/getRegion', function() use ($app) {
    $response = [];
	$db = new DbHandler();
    $reg_id = $db->purify($app->request->get('id'));

    $region = $db->getOneRecord("SELECT * FROM region WHERE reg_id='$reg_id' "); //LEFT JOIN customer ON ppty_customer_id = cust_id LEFT JOIN site ON ppty_site_id = site_id 

    if($region) {
    	$response['region'] = $region;
    	$response['status'] = "success";
        $response["message"] = "Region found!";
        echoResponse(200, $response);
    } else {
    	$response['status'] = "error";
        $response["message"] = "No region found!";
        echoResponse(201, $response);
    }
});

//getRegionList
$app->get('/getRegionList', function() use ($app) {
    $response = [];
	$db = new DbHandler();

	$query = "SELECT * FROM region LEFT JOIN user ON reg_manager_id=user_id WHERE 1=1  ORDER BY reg_name";

    $regions = $db->getRecordset($query);

    if($regions) {
    	$response['regions'] = $regions;
    	$response['status'] = "success";
        $response["message"] =  count($regions) . " Region(s) found!";
        echoResponse(200, $response);
    } else {
    	$response['status'] = "error";
        $response["message"] = "No region found!";
        echoResponse(201, $response);
    }
});

//deleteRegion
$app->get('/deleteRegion', function() use ($app) {
    $response = [];
	$db = new DbHandler();
    $ss = new SessionHandlr();

	$reg_id = $db->purify($app->request->get('id'));
    $session = $ss->getSession('call2fix_user');
    $user_created_by = $session['call2fix_user']['user_fullname'];

    $reg_delete = $db->deleteFromTable("region", "reg_id", $reg_id);

    if($reg_delete) {
        // log user action
        $lg = new Logger();
        $lg->logAction("call2fix_user", $user_created_by . " deleted a region");
    	$response['status'] = "success";
        $response["message"] =  "Region deleted successfully!";
        echoResponse(200, $response);
    } else {
    	$response['status'] = "error";
        $response["message"] = "Region delete FAILED!";
        echoResponse(201, $response);
    }
});

