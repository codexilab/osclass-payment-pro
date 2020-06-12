<?php

    ob_get_clean();

    if(!osc_is_web_user_logged_in()) {
        osc_add_flash_error_message(__('You need to login to be able to pay with your credits', 'payment_pro'));
        osc_redirect_to(osc_route_url('payment-pro-checkout'));
        die;
    }

    $code = str_replace(" ", "+", Params::getParam('code', true, true));
    if ($code=='' || strlen($code)!=8) {
        osc_add_flash_error_message(__('Payment not valid', 'payment_pro'));
        osc_redirect_to(osc_route_url('payment-pro-checkout'));
        die;
    }

    $wallet_code = Session::newInstance()->_get('payment_pro_wallet_' . $code);
    $data = json_decode(payment_pro_decrypt($wallet_code), true);
    if(!is_array($data) || !isset($data['items'])) {
        osc_add_flash_error_message(__('Payment not valid.', 'payment_pro'));
        osc_redirect_to(osc_route_url('payment-pro-checkout'));
        die;
    }
    $products = $data['items'];

    $wallet = ModelPaymentPro::newInstance()->getWallet(osc_logged_user_id());

    $wTotal = 0;
    foreach($products as $p) {
        if(!isset($p['amount'])) {
            osc_add_flash_error_message(__('There were an error processing your cart, please try again', 'payment_pro'));
            osc_redirect_to(osc_route_url('payment-pro-checkout'));
            die;
        }
        $wTotal += $p['quantity']*$p['amount']*((100+$p['tax'])/100)*1000000;
    }
    $wallet = ModelPaymentPro::newInstance()->getWallet(osc_logged_user_id());

    if(!isset($wallet['i_amount']) || $wallet['i_amount']<$wTotal) {
        osc_add_flash_error_message(__('Insuficient funds', 'payment_pro'));
        osc_redirect_to(osc_route_url('payment-pro-checkout'));
        die;
    }


    if(count($products)==1) {
        $p = current($products);
        $amount = $p['amount']*$p['quantity'];
        $amount_total = $p['amount']*$p['quantity']*((100+$p['tax'])/100);
        $amount_tax = $p['amount']*$p['quantity']*($p['tax']/100);
        $description = $p['description'];
        $product_id = $p['id'];
        $products[$p['id']]['currency'] = osc_get_preference('currency', 'payment_pro');
    } else {
        $amount = 0;
        $amount_tax = 0;
        $amount_total = 0;
        //$ids = array();
        foreach($products as $k => $p) {
            $amount += $p['amount']*$p['quantity'];
            $products[$k]['amount_total'] = ($p['amount']*$p['quantity']*((100+$p['tax'])/100));
            $amount_total += $products[$k]['amount_total'];
            $products[$k]['amount_tax'] = $p['amount']*$p['quantity']*($p['tax']/100);
            $amount_tax += $products[$k]['amount_tax'];
            $products[$k]['currency'] = osc_get_preference('currency', 'payment_pro');
        }
    }

    $exists = ModelPaymentPro::newInstance()->getPaymentByCode(osc_logged_user_id() . "-" . $code, 'WALLET', PAYMENT_PRO_COMPLETED);
    if (isset($exists['pk_i_id'])) {
        payment_pro_cart_drop();
        osc_add_flash_warning_message(__('Warning! This payment was already paid', 'payment_pro'));
        osc_redirect_to(osc_route_url('payment-pro-done', array('tx' => $code)));
        die;
    }

    ModelPaymentPro::newInstance()->addWallet(osc_logged_user_id(), -$wTotal/1000000);

    $invoiceId = ModelPaymentPro::newInstance()->saveInvoice(
        osc_logged_user_id() . "-" . $code,
        $amount,
        $amount_tax,
        $amount_total,
        PAYMENT_PRO_COMPLETED,
        osc_get_preference('currency', 'payment_pro'),
        osc_logged_user_email(),
        osc_logged_user_id(),
        'WALLET',
        $products
    );

    foreach ($products as $item) {
        $tmp = explode("-", $item['id']);
        $item['item_id'] = $tmp[count($tmp) - 1];
        osc_run_hook('payment_pro_item_paid', $item, $data, $invoiceId);
    }


    payment_pro_cart_drop();
    Session::newInstance()->_drop('payment_pro_wallet_' . $code);
    $wallet = ModelPaymentPro::newInstance()->getWallet(osc_logged_user_id());
    $wallet_amount = osc_format_price($wallet['i_amount'], osc_get_preference('currency', 'payment_pro'));

    osc_add_flash_ok_message(sprintf(__('Success! %s was deducted from your account\'s credit. Your current credit available is %s', 'payment_pro'), osc_format_price($wTotal, osc_get_preference('currency', 'payment_pro')), $wallet_amount));
    osc_redirect_to(osc_route_url('payment-pro-done', array('tx' => $code)));
    die;

