<?php

osc_add_hook('ajax_paymentauthorize', array('AuthorizePayment', 'ajaxPayment'));
require_once PAYMENT_PRO_PATH . 'payments/authorize/AuthorizeNet.php';
require_once PAYMENT_PRO_PATH . 'payments/authorize/AuthorizePayment.php';

function payment_pro_authorize_install() {
    osc_set_preference('authorize_api_login_id', payment_pro_crypt(''), 'payment_pro', 'STRING');
    osc_set_preference('authorize_api_tx_key', payment_pro_crypt(''), 'payment_pro', 'STRING');
    osc_set_preference('authorize_sandbox', '1', 'payment_pro', 'BOOLEAN');
    osc_set_preference('authorize_enabled', '0', 'payment_pro', 'BOOLEAN');
}
osc_add_hook('payment_pro_install', 'payment_pro_authorize_install');

function payment_pro_authorize_conf_save() {
    osc_set_preference('authorize_api_login_id', payment_pro_crypt(Params::getParam("authorize_api_login_id")), 'payment_pro', 'STRING');
    osc_set_preference('authorize_api_tx_key', payment_pro_crypt(Params::getParam("authorize_api_tx_key")), 'payment_pro', 'STRING');
    osc_set_preference('authorize_sandbox', Params::getParam("authorize_sandbox") ? Params::getParam("authorize_sandbox") : '0', 'payment_pro', 'BOOLEAN');
    osc_set_preference('authorize_enabled', Params::getParam("authorize_enabled") ? Params::getParam("authorize_enabled") : '0', 'payment_pro', 'BOOLEAN');

    if(Params::getParam("authorize_enabled")==1) {
        payment_pro_register_service('Authorize', __FILE__);
    } else {
        payment_pro_unregister_service('Authorize');
    }
}
osc_add_hook('payment_pro_conf_save', 'payment_pro_authorize_conf_save');



function payment_pro_authorize_conf_form() {
    require_once dirname(__FILE__) . '/admin/conf.php';
}
osc_add_hook('payment_pro_conf_form', 'payment_pro_authorize_conf_form', 6);

function payment_pro_authorize_conf_footer() {
    require_once dirname(__FILE__) . '/admin/footer.php';
}
osc_add_hook('payment_pro_conf_footer', 'payment_pro_authorize_conf_footer');

osc_add_hook('payment_pro_checkout_footer', array('AuthorizePayment', 'dialogJS'));

if(!defined("AUTHORIZENET_API_LOGIN_ID")) {
    define("AUTHORIZENET_API_LOGIN_ID", payment_pro_decrypt(osc_get_preference('authorize_api_login_id', 'payment_pro')));
}
if(!defined("AUTHORIZENET_TRANSACTION_KEY")) {
    define("AUTHORIZENET_TRANSACTION_KEY", payment_pro_decrypt(osc_get_preference('authorize_api_tx_key', 'payment_pro')));
}
if(!defined("AUTHORIZENET_SANDBOX")) {
    define("AUTHORIZENET_SANDBOX", osc_get_preference('authorize_sandbox', 'payment_pro'));
}
