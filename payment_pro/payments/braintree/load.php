<?php

osc_add_hook('ajax_braintree', array('BraintreePayment', 'ajaxPayment'));
require_once PAYMENT_PRO_PATH . 'payments/braintree/lib/Braintree.php';
require_once PAYMENT_PRO_PATH . 'payments/braintree/BraintreePayment.php';

function payment_pro_braintree_install() {
    osc_set_preference('braintree_merchant_id', payment_pro_crypt(''), 'payment_pro', 'STRING');
    osc_set_preference('braintree_public_key', payment_pro_crypt(''), 'payment_pro', 'STRING');
    osc_set_preference('braintree_private_key', payment_pro_crypt(''), 'payment_pro', 'STRING');
    osc_set_preference('braintree_encryption_key', payment_pro_crypt(''), 'payment_pro', 'STRING');
    osc_set_preference('braintree_sandbox', 'sandbox', 'payment_pro', 'STRING');
    osc_set_preference('braintree_enabled', '0', 'payment_pro', 'BOOLEAN');
}
osc_add_hook('payment_pro_install', 'payment_pro_braintree_install');

function payment_pro_braintree_conf_save() {
    osc_set_preference('braintree_merchant_id', payment_pro_crypt(Params::getParam("braintree_merchant_id")), 'payment_pro', 'STRING');
    osc_set_preference('braintree_public_key', payment_pro_crypt(Params::getParam("braintree_public_key")), 'payment_pro', 'STRING');
    osc_set_preference('braintree_private_key', payment_pro_crypt(Params::getParam("braintree_private_key")), 'payment_pro', 'STRING');
    osc_set_preference('braintree_encryption_key', payment_pro_crypt(Params::getParam("braintree_encryption_key")), 'payment_pro', 'STRING');
    osc_set_preference('braintree_sandbox', (Params::getParam("braintree_sandbox") == 'sandbox') ? 'sandbox' : 'production', 'payment_pro', 'STRING');
    osc_set_preference('braintree_enabled', Params::getParam("braintree_enabled") ? Params::getParam("braintree_enabled") : '0', 'payment_pro', 'BOOLEAN');

    if(Params::getParam("braintree_enabled")==1) {
        payment_pro_register_service('Braintree', __FILE__);
    } else {
        payment_pro_unregister_service('Braintree');
    }
}
osc_add_hook('payment_pro_conf_save', 'payment_pro_braintree_conf_save');



function payment_pro_braintree_conf_form() {
    require_once dirname(__FILE__) . '/admin/conf.php';
}
osc_add_hook('payment_pro_conf_form', 'payment_pro_braintree_conf_form', 3);

function payment_pro_braintree_conf_footer() {
    require_once dirname(__FILE__) . '/admin/footer.php';
}
osc_add_hook('payment_pro_conf_footer', 'payment_pro_braintree_conf_footer');

osc_add_hook('payment_pro_checkout_footer', array('BraintreePayment', 'dialogJS'));