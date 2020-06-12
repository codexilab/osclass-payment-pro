<?php

    payment_pro_cart_drop();

/*    if(Params::getParam('cm')!='') {
        $data = Params::getParam('cm');
    } else if(Params::getParam('custom')!='') {
        $data = Params::getParam('custom');
    } else {
        $data = Params::getParam('extra');
    }
    $data = json_decode(base64_decode($data), true);
*/


    // GET TX ID
    $tx = Params::getParam('tx')!=''?Params::getParam('tx'):Params::getParam('txn_id');
    $payment = ModelPaymentPro::newInstance()->getPaymentByCode($tx, 'AUTHORIZE');
    if (isset($payment['pk_i_id'])) {
        osc_add_flash_ok_message(__('Payment processed correctly', 'payment_pro'));
    } else {
        osc_add_flash_info_message(__('We are processing your payment, if we did not finish in a few seconds, please contact us', 'payment_pro'));
    }
    payment_pro_js_redirect_to(osc_route_url('payment-pro-done', array('tx' => $tx)));