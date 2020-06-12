<?php
$reference = Params::getParam('txid');

payment_pro_cart_drop();
$extra = ModelPaymentPro::newInstance()->pendingExtra($reference, 'PAGSEGURO');
if(isset($extra['fk_i_invoice_id'])) {
    $payment = ModelPaymentPro::newInstance()->getPaymentByCode($tx, 'PAGSEGURO');
    if (isset($payment['pk_i_id'])) {
        osc_add_flash_ok_message(__('Payment processed correctly', 'payment_pro'));
    } else {
        osc_add_flash_info_message(__('We are processing your payment, if we did not finish in a few seconds, please contact us', 'payment_pro'));
    }
    $invoice = ModelPaymentPro::newInstance()->invoiceById($extra['fk_i_invoice_id']);
    if(isset($invoice['s_code'])) {
        $reference = $invoice['s_code'];
    }
} else {
    osc_add_flash_info_message(__('We are processing your payment, if we did not finish in a few seconds, please contact us', 'payment_pro'));
}

payment_pro_js_redirect_to(osc_route_url('payment-pro-done', array('tx' => $reference)));
