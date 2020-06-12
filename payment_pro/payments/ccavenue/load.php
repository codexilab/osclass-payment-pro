<?php

// routes
osc_add_route('ccavenue-redirect', 'ccavenue/ccavenue-redirect/(.+)', 'ccavenue/ccavenue-redirect/{extra}', PAYMENT_PRO_PLUGIN_FOLDER . 'payments/ccavenue/notify_url.php');
require_once PAYMENT_PRO_PATH . 'payments/ccavenue/CcavenuePayment.php';

function payment_pro_ccavenue_install() {
    osc_set_preference('ccavenue_merchant_id', payment_pro_crypt(''), 'payment_pro', 'STRING');
    osc_set_preference('ccavenue_working_key', payment_pro_crypt(''), 'payment_pro', 'STRING');
    osc_set_preference('ccavenue_access_code', payment_pro_crypt(''), 'payment_pro', 'STRING');
    osc_set_preference('ccavenue_sandbox_working_key', payment_pro_crypt(''), 'payment_pro', 'STRING');
    osc_set_preference('ccavenue_sandbox_access_code', payment_pro_crypt(''), 'payment_pro', 'STRING');
    osc_set_preference('ccavenue_sandbox', 'sandbox', 'payment_pro', 'BOOLEAN');
    osc_set_preference('ccavenue_enabled', '0', 'payment_pro', 'BOOLEAN');
}
osc_add_hook('payment_pro_install', 'payment_pro_ccavenue_install');

function payment_pro_ccavenue_conf_save() {
    osc_set_preference('ccavenue_merchant_id', payment_pro_crypt(Params::getParam("ccavenue_merchant_id")), 'payment_pro', 'STRING');
    osc_set_preference('ccavenue_working_key', payment_pro_crypt(Params::getParam("ccavenue_working_key")), 'payment_pro', 'STRING');
    osc_set_preference('ccavenue_access_code', payment_pro_crypt(Params::getParam("ccavenue_access_code")), 'payment_pro', 'STRING');
    osc_set_preference('ccavenue_sandbox_working_key', payment_pro_crypt(Params::getParam("ccavenue_sandbox_working_key")), 'payment_pro', 'STRING');
    osc_set_preference('ccavenue_sandbox_access_code', payment_pro_crypt(Params::getParam("ccavenue_sandbox_access_code")), 'payment_pro', 'STRING');
    osc_set_preference('ccavenue_sandbox', Params::getParam("ccavenue_sandbox") ? Params::getParam("ccavenue_sandbox") : '0', 'payment_pro', 'BOOLEAN');
    osc_set_preference('ccavenue_enabled', Params::getParam("ccavenue_enabled") ? Params::getParam("ccavenue_enabled") : '0', 'payment_pro', 'BOOLEAN');

    if(Params::getParam("ccavenue_enabled")==1) {
        payment_pro_register_service('Ccavenue', __FILE__);
    } else {
        payment_pro_unregister_service('Ccavenue');
    }
}
osc_add_hook('payment_pro_conf_save', 'payment_pro_ccavenue_conf_save');



function payment_pro_ccavenue_conf_form() {
    require_once dirname(__FILE__) . '/admin/conf.php';
}
osc_add_hook('payment_pro_conf_form', 'payment_pro_ccavenue_conf_form', 5);

function payment_pro_ccavenue_conf_footer() {
    require_once dirname(__FILE__) . '/admin/footer.php';
}
osc_add_hook('payment_pro_conf_footer', 'payment_pro_ccavenue_conf_footer');