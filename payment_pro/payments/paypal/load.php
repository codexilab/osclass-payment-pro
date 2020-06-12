<?php

osc_add_route('paypal-notify', 'payment/paypal-notify/(.+)', 'payment/paypal-notify/{extra}', PAYMENT_PRO_PLUGIN_FOLDER . 'payments/paypal/notify_url.php');
osc_add_route('paypal-return', 'payment/paypal-return/(.+)', 'payment/paypal-return/{extra}', PAYMENT_PRO_PLUGIN_FOLDER . 'payments/paypal/return.php');
osc_add_route('paypal-cancel', 'payment/paypal-cancel/(.+)', 'payment/paypal-cancel/{extra}', PAYMENT_PRO_PLUGIN_FOLDER . 'payments/paypal/cancel.php');
osc_add_route('paypal-webhook', 'payment/paypal-webhook', 'payment/paypal-webhook', PAYMENT_PRO_PLUGIN_FOLDER . 'payments/paypal/webhook.php');
//require_once PAYMENT_PRO_PATH . 'payments/paypal/lib/autoload.php';
require_once PAYMENT_PRO_PATH . 'payments/paypal/PaypalPayment.php';

function payment_pro_paypal_install() {
    osc_set_preference('paypal_api_username', payment_pro_crypt(''), 'payment_pro', 'STRING');
    osc_set_preference('paypal_api_password', payment_pro_crypt(''), 'payment_pro', 'STRING');
    osc_set_preference('paypal_api_signature', payment_pro_crypt(''), 'payment_pro', 'STRING');
    osc_set_preference('paypal_email', '', 'payment_pro', 'STRING');
    osc_set_preference('paypal_standard', '1', 'payment_pro', 'BOOLEAN');
    osc_set_preference('paypal_sandbox', '1', 'payment_pro', 'BOOLEAN');
    osc_set_preference('paypal_enabled', '0', 'payment_pro', 'BOOLEAN');
}
osc_add_hook('payment_pro_install', 'payment_pro_paypal_install');

function payment_pro_paypal_conf_save() {
    osc_set_preference('paypal_api_username', payment_pro_crypt(Params::getParam("paypal_api_username")), 'payment_pro', 'STRING');
    osc_set_preference('paypal_api_password', payment_pro_crypt(Params::getParam("paypal_api_password")), 'payment_pro', 'STRING');
    osc_set_preference('paypal_api_signature', payment_pro_crypt(Params::getParam("paypal_api_signature")), 'payment_pro', 'STRING');
    osc_set_preference('paypal_email', Params::getParam("paypal_email"), 'payment_pro', 'STRING');
    osc_set_preference('paypal_standard', '1', 'payment_pro', 'BOOLEAN');
    //osc_set_preference('paypal_standard', Params::getParam("paypal_standard") ? Params::getParam("paypal_standard") : '0', 'payment_pro', 'BOOLEAN');
    osc_set_preference('paypal_sandbox', Params::getParam("paypal_sandbox") ? Params::getParam("paypal_sandbox") : '0', 'payment_pro', 'BOOLEAN');
    osc_set_preference('paypal_enabled', Params::getParam("paypal_enabled") ? Params::getParam("paypal_enabled") : '0', 'payment_pro', 'BOOLEAN');

    if(Params::getParam("paypal_enabled")==1) {
        payment_pro_register_service('Paypal', __FILE__);
    } else {
        payment_pro_unregister_service('Paypal');
    }
}
osc_add_hook('payment_pro_conf_save', 'payment_pro_paypal_conf_save');



function payment_pro_paypal_conf_form() {
    require_once dirname(__FILE__) . '/admin/conf.php';
}
osc_add_hook('payment_pro_conf_form', 'payment_pro_paypal_conf_form', 1);

function payment_pro_paypal_conf_footer() {
    require_once dirname(__FILE__) . '/admin/footer.php';
}
osc_add_hook('payment_pro_conf_footer', 'payment_pro_paypal_conf_footer');


// BLINDLY CREATE A NEW WEBHOOK
function payment_pro_paypal_create_webhook() {

    $apiContext = PaypalPayment::getApiContext();
    $output = \PayPal\Api\Webhook::getAll($apiContext);
    print_r($output->webhooks);

    $webhook = new \PayPal\Api\Webhook();
    $webhook->setUrl(osc_route_url('paypal-webhook'));

    $webhookEventTypes = array();
    $webhookEventTypes[] = new \PayPal\Api\WebhookEventType(
        '{
        "name":"PAYMENT.AUTHORIZATION.CREATED"
    }'
    );
    $webhookEventTypes[] = new \PayPal\Api\WebhookEventType(
        '{
        "name":"PAYMENT.SALE.COMPLETED"
    }'
    );
    $webhook->setEventTypes($webhookEventTypes);

    try {
        $output = $webhook->create($apiContext);
    } catch (Exception $ex) {
        return false;
    }

    return true;

}

