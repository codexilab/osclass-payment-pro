<?php

    $sandbox = false;
    $email_admin = false;
    if(osc_get_preference('paypal_sandbox', 'payment_pro')==1) {
        $sandbox = true;
        $email_admin = true;
    }

    /* * ****************************
     * STANDARD PAYPAL NOTIFY URL *
     *    NOT MODIFY BELOW CODE   *
     * **************************** */
    // Read the post from PayPal and add 'cmd'
    $header = '';
    $req    = 'cmd=_notify-validate';
    if (function_exists('get_magic_quotes_gpc')) {
        $get_magic_quotes_exists = true;
    } else {
        $get_magic_quotes_exists = false;
    }

    foreach ($_POST as $key => $value) {
        // Handle escape characters, which depends on setting of magic quotes
        if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
            $value = urlencode(stripslashes($value));
        } else {
            $value = urlencode($value);
        }
        if($key!='extra') {
            $req .= "&$key=$value";
        }
    }

    // Post back to PayPal to validate
    if(!$sandbox) {
        $curl = curl_init('https://www.paypal.com/cgi-bin/webscr');
    } else {
        $curl = curl_init('https://www.sandbox.paypal.com/cgi-bin/webscr');
    }

    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $req);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $res = curl_exec($curl);

    if (strcmp($res, 'VERIFIED') == 0) {

        error_log('VERIFIED');

        if(substr($_POST['txn_type'], 0, 6) == 'subscr') { // subscr_payment
            PaypalPayment::processSubcriptionPayment();
        } else {
            PaypalPayment::processStandardPayment();
        }

        if($email_admin) {
            $emailtext = '';
            foreach ($_REQUEST as $key => $value) {
                $emailtext .= $key . ' = ' . $value . '\n\n';
            }
            mail(osc_contact_email() , 'OSCLASS PAYPAL DEBUG', $emailtext . '\n\n ---------------- \n\n' . $req);
        }
    } else if (strcmp($res, 'INVALID') == 0) {
        // INVALID: Do nothing
    }
