<?php

$status = CcavenuePayment::processPayment();

// GET TX ID
$tx = Params::getParam('ccavenue_order_id');
$status_msg = Params::getParam('ccavenue_status_message');
if($status==PAYMENT_PRO_COMPLETED) {
    osc_add_flash_ok_message(__('Payment processed correctly', 'payment_pro'));
} else if($status==PAYMENT_PRO_PENDING) {
    osc_add_flash_info_message(__('We are processing your payment, if we did not finish in a few seconds, please contact us', 'payment_pro'));
} else {
    osc_add_flash_error_message(sprintf(__('Something failed (%s). Please write down this transaction ID and contact us: %s', 'payment_pro'), $status_msg, $tx));
}
payment_pro_js_redirect_to(osc_route_url('payment-pro-done', array('tx' => $tx)));