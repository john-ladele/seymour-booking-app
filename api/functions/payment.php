<?php

$app->get('/getPaymentList', function() use ($app) {
    $response = [];
	$db = new DbHandler();

    $fromDate = $db->purify($app->request->get('fromDate'));
    $toDate = $db->purify($app->request->get('toDate'));

    $paymentList = "SELECT *,DATE_FORMAT ( bk_time_initiated, '%Y-%m-%d') AS bk_date_convert, DATE_FORMAT ( bk_time_initiated, '%H:%i') AS bk_time_convert FROM payment LEFT JOIN customer ON bk_customer_id=cust_id";

    if(!empty($fromDate)&&!empty($toDate)){
        $paymentList .= " WHERE DATE_FORMAT(bk_time_initiated, '%Y-%m-%d') BETWEEN '$fromDate' AND '$toDate' BOOKING BY bk_time_initiated DESC";
    }else{
        $paymentList .= " BOOKING BY bk_time_initiated DESC";
    }

    $payments = $db->getRecordset($paymentList);

    if($payments) {
    	$response['payments'] = $payments;
    	$response['status'] = "success";
        $response["message"] =  count($payments) . " payment(s) found!";
        echoResponse(200, $response);
    } else {
    	$response['status'] = "error";
        $response["message"] = "No payment found!";
        echoResponse(201, $response);
    }
});

$app->post('/simulateCustomerPayment', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());

    verifyRequiredParams(array('bk_total'),$r->payment);

    $db = new DbHandler();
    $ss = new SessionHandlr();

    $bk_total = $db->purify($r->payment->bk_total);
    $session = $ss->getSession('call2fix_customer');
    $bk_customer_id = $session['call2fix_customer']['cust_id'];

    //create new payment
    $bk_id = $db->insertToTable(
        ['DEPOSIT', $bk_customer_id, $bk_total, 'SUCCESSFUL', date("Y-m-d H:i:s")], /*values - array*/
        ['bk_type', 'bk_customer_id', 'bk_total', 'bk_payment_status', 'bk_time_initiated'], /*column names - array*/
        "payment" /*table name - string*/
    );

    if($bk_id) {
        // update customer
        $table = "customer";
        $columns = [ 'cust_security_deposit'=>$bk_total, 'cust_verified'=>'1' ];
        $where = [ 'cust_id'=>$bk_customer_id ];
        $customer_update = $db->updateInTable($table, $columns, $where);

        if($customer_update >= 0) {
            $response['bk_id'] = $bk_id;
            $response['status'] = "success";
            $response["message"] = "Payment created successfully!";
            echoResponse(200, $response);
        } else {
            $response['status'] = "error";
            $response["message"] = "Something went wrong while trying to update your security deposit, please try later or contact Support.";
            echoResponse(201, $response);
        }
    } else { /* failed*/
        $response['status'] = "error";
        $response["message"] = "Something went wrong while trying to simulate your payment, please try again later or contact Support";
        echoResponse(201, $response);
    }
});

$app->post('/processOnlinePayment', function() use ($app) {

    $response = array();

    $r = json_decode($app->request->getBody());
    // echoResponse(200, $r);
    verifyRequiredParams(['bk_id', 'bk_payment_ref'],$r->booking); //booking
    // verifyRequiredParams(['txRef'],$r->response->tx); //rave response

    $db = new DbHandler();

    $response['booking_received'] = $booking = $r->booking;
    $response['$rave_response'] = $rave_response = $r->response;

    $bk_id = $db->purify($booking->bk_id);
    $bk_total = $db->purify($booking->bk_total);
    $bk_payment_ref = $db->purify($booking->bk_payment_ref);

    // for booking, save ref and set status to processing
    $table = "booking";
    $columns = ['bk_payment_status'=>'PROCESSING', 'bk_payment_ref'=>$bk_payment_ref ];
    $where = ['bk_id'=>$bk_id];
    $update_booking = $db->updateInTable($table, $columns, $where);

    // first compose the booking array to be returned as part of response
    $response['booking']['bk_id'] = $bk_id;
    $response['booking']['bk_total'] = $bk_total;
    $response['booking']['bk_payment_ref'] = $bk_payment_ref;
    // now compose rave parameters
    $rave_flw_ref = $db->purify($rave_response->tx->flwRef);
    $response['rave_currency'] = $rave_currency = $db->purify($rave_response->tx->currency);
    $rave_resp_code = $db->purify($rave_response->data->responsecode);
    $rave_resp_msg = $db->purify($rave_response->data->responsemessage);
    $response['rave_amount'] = $rave_amount = $db->purify($rave_response->tx->amount);
    $rave_time = $db->purify($rave_response->tx->createdAt);
    $rave_auth_type = $db->purify($rave_response->tx->paymentType);
    $rave_device_fingerprint = $db->purify($rave_response->tx->device_fingerprint);

    $cols = [ 'rave_booking_id', 'rave_tranx_ref', 'rave_flw_ref', 'rave_currency', 'rave_resp_code', 'rave_resp_msg', 'rave_amount', 'rave_time', 'rave_auth_type', 'rave_device_fingerprint' ];
    $vals = [ $bk_id, $bk_payment_ref, $rave_flw_ref, $rave_currency, $rave_resp_code, $rave_resp_msg, $rave_amount, $rave_time, $rave_auth_type, $rave_device_fingerprint ];
    $table = "rave_transaction";
    $rave_id = $db->insertToTable($vals, $cols, $table);

    if(!$rave_id) {
        // transaction insertion failed, return error
        $response['status'] = "error";
        $response["message"] = "Something went wrong while trying to register your transaction. Please contact Support to investigate this issue!";
        echoResponse(201, $response);
    } else {
        // success
        $response['booking']['rave_resp_code'] = $rave_resp_code;
        $response['booking']['rave_resp_msg'] = $rave_resp_msg;
        // was payment successful from client-side?
        if($rave_resp_code != '00' && $rave_resp_code != '0') {
            // payment failed from client-side, no need to verify - just return
            $response['status'] = "error";
            $response["message"] = "Payment Failed.";
            echoResponse(201, $response);
        } else {
            // payment success
            // success - attempt verification with rave, is verification successful?
            $query = array(
                "SECKEY" => RAVE_LIVE_SECRET,
                "flw_ref" => $rave_flw_ref,
                "txref" => $bk_payment_ref,
                "normalize" => "1"
            );

            $data_string = json_encode($query);

            $live_url = "https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify";
            $test_url = "https://ravesandboxapi.flutterwave.com/flwv3-pug/getpaidx/api/v2/verify";

            $ch = curl_init($live_url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

            $curl_response = curl_exec($ch);

            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($curl_response, 0, $header_size);
            $body = substr($curl_response, $header_size);

            curl_close($ch);

            $response['$resp'] = $resp = json_decode($curl_response, true);
            // var_dump($resp); die;

            $response['$chargeResponse'] = $chargeResponse = $resp['data']['chargecode'];;
            $response['$chargeAmount'] = $chargeAmount = $resp['data']['amount'];
            $response['$chargeCurrency'] = $chargeCurrency = $resp['data']['currency'];

            if (($chargeResponse == "00" || $chargeResponse == "0") && ($chargeAmount == $rave_amount)  && ($chargeCurrency == $rave_currency)) {
                //Give Value and return to Success page
                $response['payment_status'] = "success";
                // yes - update booking status, transaction status and create subs

                // update booking status to COMPLETED
                $table = "booking";
                $columns = ['bk_status'=>'COMPLETED', 'bk_payment_status'=>'SUCCESSFUL'];
                $where = ['bk_id'=>$bk_id];
                $response['update_booking'] = $db->updateInTable($table, $columns, $where);

                // update rave transaction VERIFIED
                $table = "rave_transaction";
                $columns = ['rave_verified'=>'1'];
                $where = ['rave_id'=>$rave_id];
                $response['update_rave'] = $db->updateInTable($table, $columns, $where);

                // get booking items
                $items = $db->getRecordset("SELECT * FROM booking_item
                    LEFT JOIN vehicle ON bi_vehicle_id = veh_id
                    WHERE bi_booking_id = '$bk_id' ");
                $item_rows = "";
                foreach ($items as $i => $item) {
                    $item_rows .=
                    "<tr>
                        <td>".($i+1)."</td>
                        <td>$item[veh_name]</td>
                        <td>$item[bi_num_vehicle]</td>
                        <td>$item[bi_num_days]</td>
                        <td>".number_format($item['bi_total'],2)."</td>
                    </tr>";
                }

                // notify customer
                $swiftmailer = new mySwiftMailer();
                $subject = "Your SUCCESSFUL Booking on seymouraviation.com";
                $body = "<p>Hello $booking->bk_name,</p>
                <p>Your booking on seymouraviation.com was <strong>SUCCESSFUL</strong>.</p>
                <p>Transaction Details:</p>
                <table width='500px' cellpadding='5' cellspacing='0' border='1'>
                    <tr>
                        <td>Payment Ref</td>
                        <td>$bk_payment_ref</td>
                    </tr>
                    <tr>
                        <td>Booking ID</td>
                        <td>$bk_id</td>
                    </tr>
                    <tr>
                        <td>Amount</td>
                        <td>".number_format($bk_total,2)."</td>
                    </tr>
                    <tr>
                        <td>Date</td>
                        <td>".date("d M Y", strtotime($booking->bk_time_submitted))."</td>
                    </tr>
                    <tr>
                        <td>Response Message</td>
                        <td>$rave_resp_msg</td>
                    </tr>
                    <tr>
                        <td>Customer</td>
                        <td>$booking->bk_name</td>
                    </tr>
                </table>
                <p>Your Booking Details:</p>
                <table width='80%' cellpadding='5' cellspacing='0' border='1'>
                    <tr>
                        <th>S/N</th>
                        <th>Vehicle Type</th>
                        <th>No of Vehicles</th>
                        <th>No of Days</th>
                        <th>Amount(₦)</th>
                    </tr>
                    $item_rows
                    <tr>
                        <td colspan='4'><strong>TOTAL</strong></td>
                        <td><strong>".number_format($bk_total,2)."</strong></td>
                    </tr>
                </table>
                <p>We will contact you shortly.</p>
                <p><br><strong>Seymour Aviation</strong></p>";
                $swiftmailer->sendmail(FROM_EMAIL, LONGNAME, [$booking->bk_email], $subject, $body);

                // notify admin
                $subject = "SUCCESSFUL Booking on seymouraviation.com";
                $body = "<p>Hello,</p>
                <p>A booking on seymouraviation.com was <strong>SUCCESSFUL</strong>.</p>
                <p>Transaction Details:</p>
                <table width='500px' cellpadding='5' cellspacing='0' border='1'>
                    <tr>
                        <td>Payment Ref</td>
                        <td>$bk_payment_ref</td>
                    </tr>
                    <tr>
                        <td>Booking ID</td>
                        <td>$bk_id</td>
                    </tr>
                    <tr>
                        <td>Amount</td>
                        <td>".number_format($bk_total,2)."</td>
                    </tr>
                    <tr>
                        <td>Date</td>
                        <td>".date("d M Y", strtotime($booking->bk_time_submitted))."</td>
                    </tr>
                    <tr>
                        <td>Response Message</td>
                        <td>$rave_resp_msg</td>
                    </tr>
                    <tr>
                        <td>Customer</td>
                        <td>$booking->bk_name</td>
                    </tr>
                    <tr>
                        <td>Email</td>
                        <td>$booking->bk_email</td>
                    </tr>
                    <tr>
                        <td>Phone</td>
                        <td>$booking->bk_phone</td>
                    </tr>
                    <tr>
                        <td>Address</td>
                        <td>$booking->bk_address</td>
                    </tr>
                    <tr>
                        <td>Company</td>
                        <td>$booking->bk_company</td>
                    </tr>
                </table>
                <p>Booking Details:</p>
                <table width='80%' cellpadding='5' cellspacing='0' border='1'>
                    <tr>
                        <th>S/N</th>
                        <th>Vehicle Type</th>
                        <th>No of Vehicles</th>
                        <th>No of Days</th>
                        <th>Amount(₦)</th>
                    </tr>
                    $item_rows
                    <tr>
                        <td colspan='4'><strong>TOTAL</strong></td>
                        <td><strong>".number_format($bk_total,2)."</strong></td>
                    </tr>
                </table>
                <p>This customer will be awaiting correspondence.</p>
                <p><br><strong>Seymour Aviation</strong></p>";
                $swiftmailer->sendmail(FROM_EMAIL, LONGNAME, [ADMIN_EMAIL], $subject, $body);

                $response['status'] = "success";
                echoResponse(200, $response);
            } else {
                $response['payment_status'] = "failure";
                //failure notifications
                // notify customer
                $swiftmailer = new mySwiftMailer();
                $subject = "Your FAILED Booking on seymouraviation.com";
                $body = "<p>Hello $booking[bk_name],</p>
                <p>Your booking on seymouraviation.com <strong>FAILED</strong>.</p>
                <p>Transaction Details:</p>
                <table width='500px' cellpadding='5' cellspacing='0' border='1'>
                    <tr>
                        <td>Payment Ref</td>
                        <td>$bk_payment_ref</td>
                    </tr>
                    <tr>
                        <td>Booking ID</td>
                        <td>$bk_id</td>
                    </tr>
                    <tr>
                        <td>Amount</td>
                        <td>".number_format($bk_total,2)."</td>
                    </tr>
                    <tr>
                        <td>Date</td>
                        <td>".date("d M Y", strtotime($booking->bk_time_submitted))."</td>
                    </tr>
                    <tr>
                        <td>Response Message</td>
                        <td>$rave_resp_msg</td>
                    </tr>
                    <tr>
                        <td>Customer</td>
                        <td>$booking->bk_name</td>
                    </tr>
                </table>
                <p>Regards,</p>
                <p><br><strong>Seymour Aviation</strong></p>";
                $swiftmailer->sendmail(FROM_EMAIL, LONGNAME, [$booking->bk_email], $subject, $body);

                // notify admin
                $subject = "FAILED Booking on seymouraviation.com";
                $body = "<p>Hello,</p>
                <p>A booking on seymouraviation.com <strong>FAILED</strong>.</p>
                <p>Transaction Details:</p>
                <table width='500px' cellpadding='5' cellspacing='0' border='1'>
                    <tr>
                        <td>Payment Ref</td>
                        <td>$bk_payment_ref</td>
                    </tr>
                    <tr>
                        <td>Booking ID</td>
                        <td>$bk_id</td>
                    </tr>
                    <tr>
                        <td>Amount</td>
                        <td>".number_format($bk_total,2)."</td>
                    </tr>
                    <tr>
                        <td>Date</td>
                        <td>".date("d M Y", strtotime($booking->bk_time_submitted))."</td>
                    </tr>
                    <tr>
                        <td>Response Message</td>
                        <td>$rave_resp_msg</td>
                    </tr>
                    <tr>
                        <td>Customer</td>
                        <td>$booking->bk_name</td>
                    </tr>
                    <tr>
                        <td>Email</td>
                        <td>$booking->bk_email</td>
                    </tr>
                    <tr>
                        <td>Phone</td>
                        <td>$booking->bk_phone</td>
                    </tr>
                    <tr>
                        <td>Address</td>
                        <td>$booking->bk_address</td>
                    </tr>
                    <tr>
                        <td>Company</td>
                        <td>$booking->bk_company</td>
                    </tr>
                </table>
                <p>Booking Details:</p>
                <table width='80%' cellpadding='5' cellspacing='0' border='1'>
                    <tr>
                        <th>S/N</th>
                        <th>Vehicle Type</th>
                        <th>No of Vehicles</th>
                        <th>No of Days</th>
                        <th>Amount(₦)</th>
                    </tr>
                    $item_rows
                    <tr>
                        <td colspan='4'><strong>TOTAL</strong></td>
                        <td><strong>".number_format($bk_total,2)."</strong></td>
                    </tr>
                </table>
                <p>Regards.</p>
                <p><br><strong>Seymour Aviation</strong></p>";
                $swiftmailer->sendmail(FROM_EMAIL, LONGNAME, [ADMIN_EMAIL], $subject, $body);
                // no - update status of payment
                $response['status'] = "error";
                $response["message"] = "Sorry, we couldn't verify this payment. Please contact Support.";
                echoResponse(201, $response);
            }
        }
    }

});