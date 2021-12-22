<?php

//createBooking
$app->post('/createBooking', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());

    verifyRequiredParams(array('bk_total', 'bk_name', 'bk_email', 'bk_phone', 'bk_address'),$r->booking);

    $db = new DbHandler();

    $bk_total = $db->purify($r->booking->bk_total);
    $bk_name = $db->purify($r->booking->bk_name);
    $bk_email = $db->purify($r->booking->bk_email);
    $bk_phone = $db->purify($r->booking->bk_phone);
    $bk_address = $db->purify($r->booking->bk_address);
    $bk_company = isset($r->booking->bk_company) ? $db->purify($r->booking->bk_company) : NULL;
    $bk_time_submitted = date("Y-m-d H:i:s");
    $bk_status = 'PENDING';
    $bk_payment_status = 'PENDING';
    $items = $r->items;
    // var_dump($items); die;

    $bk_id = $db->insertToTable(
        [ $bk_total, $bk_status, $bk_name, $bk_email, $bk_phone, $bk_address, $bk_company, $bk_time_submitted, $bk_payment_status ], /*values - array*/
        [ 'bk_total', 'bk_status', 'bk_name', 'bk_email', 'bk_phone', 'bk_address', 'bk_company', 'bk_time_submitted', 'bk_payment_status' ],
        "booking" /*table name - string*/
    );

    if($bk_id) {
        // insert items
        $bi_ids = [];
        foreach ($items as $i => $item) {
            $bi_ids[] = $db->insertToTable(
                [ $bk_id, $db->purify($item->bi_vehicle_id), $db->purify($item->bi_num_vehicle), $db->purify($item->bi_num_days), $db->purify($item->bi_total), $db->purify($item->bi_discount_applied), $db->purify($item->bi_discount_percent) ], //values - array
                [ 'bi_booking_id', 'bi_vehicle_id', 'bi_num_vehicle', 'bi_num_days', 'bi_total', 'bi_discount_applied', 'bi_discount_percent' ], //columns - array
                "booking_item" //table name - string
            );
        }
    };

    if($bk_id && count($bi_ids) > 0) { /*created successfully*/
        // log user action
        $response['bk_id'] = $bk_id;
        $response['status'] = "success";
        $response["message"] = "Booking created successfully!";
        echoResponse(200, $response);
    } else { /* failed*/
        $response['status'] = "error";
        $response["message"] = "Something went wrong while trying to create the booking, please try again later or contact Support";
        echoResponse(201, $response);
    }
});

//createBookingItem
$app->post('/createBookingItem', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());

    verifyRequiredParams(array('bi_vehicle_id','bi_num_vehicle', 'bi_num_days', 'bi_discount_applied', 'bi_discount_percent'),$r->booking_item);

    $db = new DbHandler();

    $bi_booking_id =  isset($r->booking_item->bi_booking_id) ? $db->purify($r->booking_item->bi_booking_id) : '';
    $bi_vehicle_id =  isset($r->booking_item->bi_vehicle_id) ? $db->purify($r->booking_item->bi_booking_id) : '';
    $bi_num_vehicle = $db->purify($r->booking_item->bi_num_vehicle);
    $bi_num_days = $db->purify($r->booking_item->bi_num_days);
    $bi_discount_applied = $db->purify($r->booking_item->bi_discount_applied);
    $bi_discount_percent = $db->purify($r->booking_item->bi_discount_percent);

    $bi_query = $db->insertToTable([$bi_vehicle_id, $bi_booking_id, $bi_num_vehicle, $bi_num_days, $bi_discount_applied, $bi_discount_percent], /*values - array*/
            ['bi_vehicle_id', 'bi_booking_id', 'bi_num_vehicle', 'bi_num_days', 'bi_discount_applied', 'bi_discount_percent'], /*column names - array*/
            "booking_item" /*table name - string*/
        );

    if($bi_query) { /*created successfully*/
            // log user action
            $response['bi_query'] = $bi_query;
            $response['status'] = "success";
            $response["message"] = "Booking Item created successfully!";
            echoResponse(200, $response);
        } else { /* failed*/
            $response['status'] = "error";
            $response["message"] = "Something went wrong while trying to create the booking item, please try again later or contact Support";
            echoResponse(201, $response);
        }
});

//getVehicleList
$app->get('/getVehicleList', function() use ($app) {
    $response = [];
	$db = new DbHandler();

	$query = "SELECT * FROM vehicle";

    $vehicles = $db->getRecordset($query);

    if($vehicles) {
    	$response['vehicles'] = $vehicles;
    	$response['status'] = "success";
        $response["message"] =  count($vehicles) . " Vehicle(s) found!";
        echoResponse(200, $response);
    } else {
    	$response['status'] = "error";
        $response["message"] = "No Vehicle found!";
        echoResponse(201, $response);
    }
});


//getDiscountList
$app->get('/getDiscountList', function() use ($app) {
    $response = [];
    $db = new DbHandler();

    $query = "SELECT * FROM discount";

    $discounts = $db->getRecordset($query);

    if($discounts) {
        $response['discounts'] = $discounts;
        $response['status'] = "success";
        $response["message"] =  count($discounts) . " Discount(s) found!";
        echoResponse(200, $response);
    } else {
        $response['status'] = "error";
        $response["message"] = "No discount found!";
        echoResponse(201, $response);
    }
});

//deleteBookingItem
$app->get('/deleteBookingItem', function() use ($app) {
    $response = [];
	$db = new DbHandler();

	$bk_id = $db->purify($app->request->get('id'));

    $bk_delete = $db->deleteFromTable("booking_item", "bi_id", $bi_id);

    if($bk_delete) {
    	$response['status'] = "success";
        $response["message"] =  "Item" .$bi_booking_id. " deleted successfully!";
        echoResponse(200, $response);
    } else {
    	$response['status'] = "error";
        $response["message"] = "Item" .$bi_booking_id. " delete FAILED!";
        echoResponse(201, $response);
    }
});

//deleteBookingItem
$app->get('/getBooking', function() use ($app) {
    $response = [];
    $db = new DbHandler();

    $bk_id = $db->purify($app->request->get('id'));

    $booking = $db->getOneRecord("SELECT * FROM booking WHERE bk_id = '$bk_id'");

    if($booking) {
        $response['booking'] = $booking;
        $response['status'] = "success";
        $response["message"] =  "Booking found";
        echoResponse(200, $response);
    } else {
        $response['status'] = "error";
        $response["message"] = "Booking NOT found!";
        echoResponse(201, $response);
    }
});
