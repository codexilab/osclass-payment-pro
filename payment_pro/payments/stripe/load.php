<?php

require_once PAYMENT_PRO_PATH . 'payments/stripe/StripePayment.php';
osc_add_hook('ajax_stripe', array('StripePayment', 'ajaxPayment'));

osc_add_route('stripe-webhook', 'payment/stripe-webhook', 'payment/stripe-webhook', PAYMENT_PRO_PLUGIN_FOLDER . 'payments/stripe/webhook.php');

function payment_pro_stripe_install() {
    osc_set_preference('stripe_secret_key', payment_pro_crypt(''), 'payment_pro', 'STRING');
    osc_set_preference('stripe_public_key', payment_pro_crypt(''), 'payment_pro', 'STRING');
    osc_set_preference('stripe_secret_key_test', payment_pro_crypt(''), 'payment_pro', 'STRING');
    osc_set_preference('stripe_public_key_test', payment_pro_crypt(''), 'payment_pro', 'STRING');
    osc_set_preference('stripe_sandbox', 'sandbox', 'payment_pro', 'STRING');
    osc_set_preference('stripe_enabled', '0', 'payment_pro', 'BOOLEAN');
    osc_set_preference('stripe_bitcoin', '0', 'payment_pro', 'BOOLEAN');
}
osc_add_hook('payment_pro_install', 'payment_pro_stripe_install');

function payment_pro_stripe_conf_save() {
    osc_set_preference('stripe_secret_key', payment_pro_crypt(Params::getParam("stripe_secret_key")), 'payment_pro', 'STRING');
    osc_set_preference('stripe_public_key', payment_pro_crypt(Params::getParam("stripe_public_key")), 'payment_pro', 'STRING');
    osc_set_preference('stripe_secret_key_test', payment_pro_crypt(Params::getParam("stripe_secret_key_test")), 'payment_pro', 'STRING');
    osc_set_preference('stripe_public_key_test', payment_pro_crypt(Params::getParam("stripe_public_key_test")), 'payment_pro', 'STRING');
    osc_set_preference('stripe_sandbox', Params::getParam("stripe_sandbox") ? Params::getParam("stripe_sandbox") : '0', 'payment_pro', 'BOOLEAN');
    osc_set_preference('stripe_enabled', Params::getParam("stripe_enabled") ? Params::getParam("stripe_enabled") : '0', 'payment_pro', 'BOOLEAN');
    osc_set_preference('stripe_bitcoin', Params::getParam("stripe_bitcoin") ? Params::getParam("stripe_bitcoin") : '0', 'payment_pro', 'BOOLEAN');

    if(Params::getParam("stripe_enabled")==1) {
        payment_pro_register_service('Stripe', __FILE__);
    } else {
        payment_pro_unregister_service('Stripe');
    }
}
osc_add_hook('payment_pro_conf_save', 'payment_pro_stripe_conf_save');



function payment_pro_stripe_conf_form() {
    require_once dirname(__FILE__) . '/admin/conf.php';
}
osc_add_hook('payment_pro_conf_form', 'payment_pro_stripe_conf_form', 2);

function payment_pro_stripe_conf_footer() {
    require_once dirname(__FILE__) . '/admin/footer.php';
}
osc_add_hook('payment_pro_conf_footer', 'payment_pro_stripe_conf_footer');


function payment_pro_stripe_load_lib() {
    if(Params::getParam('page')=='custom' && Params::getParam('route')=='payment-pro-checkout') {
        osc_register_script('payment-pro-stripe', 'https://checkout.stripe.com/v2/checkout.js', array('jquery'));
        osc_enqueue_script('payment-pro-stripe');
    }
}
osc_add_hook('init', 'payment_pro_stripe_load_lib');

osc_add_hook('payment_pro_checkout_footer', array('StripePayment', 'dialogJS'));