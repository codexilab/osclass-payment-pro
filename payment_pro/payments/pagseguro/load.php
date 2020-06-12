<?php

osc_add_route('pagseguro-return', 'payment/pagseguro/return', 'payment/pagseguro/return', PAYMENT_PRO_PLUGIN_FOLDER . 'payments/pagseguro/return.php');
osc_add_route('pagseguro-notify', 'payment/pagseguro/notify', 'payment/pagseguro/notify', PAYMENT_PRO_PLUGIN_FOLDER . 'payments/pagseguro/notify.php');
require_once PAYMENT_PRO_PATH . 'payments/pagseguro/PagSeguroConfigWrapper.php';
require_once PAYMENT_PRO_PATH . 'payments/pagseguro/PagSeguroLibrary/PagSeguroLibrary.php';
require_once PAYMENT_PRO_PATH . 'payments/pagseguro/PagseguroPayment.php';


function payment_pro_pagseguro_install() {
    osc_set_preference('pagseguro_enabled', '0', 'payment_pro', 'BOOLEAN');
    osc_set_preference('pagseguro_sandbox', '0', 'payment_pro', 'BOOLEAN');

    osc_set_preference('pagseguro_email', '', 'payment_pro', 'STRING');
    osc_set_preference('pagseguro_token', payment_pro_crypt(Params::getParam('pagseguro_')), 'payment_pro', 'STRING');
    osc_set_preference('pagseguro_appid', payment_pro_crypt(Params::getParam('pagseguro_')), 'payment_pro', 'STRING');
    osc_set_preference('pagseguro_appkey', payment_pro_crypt(Params::getParam('pagseguro_')), 'payment_pro', 'STRING');
    osc_set_preference('pagseguro_sandbox_token', payment_pro_crypt(Params::getParam('pagseguro_')), 'payment_pro', 'STRING');
    osc_set_preference('pagseguro_sandbox_appid', payment_pro_crypt(Params::getParam('pagseguro_')), 'payment_pro', 'STRING');
    osc_set_preference('pagseguro_sandbox_appkey', payment_pro_crypt(Params::getParam('pagseguro_')), 'payment_pro', 'STRING');
}
osc_add_hook('payment_pro_install', 'payment_pro_pagseguro_install');

function payment_pro_pagseguro_conf_save() {
    osc_set_preference('pagseguro_enabled', Params::getParam("pagseguro_enabled")==1 ? Params::getParam("pagseguro_enabled") : '0', 'payment_pro', 'BOOLEAN');
    osc_set_preference('pagseguro_sandbox', Params::getParam("pagseguro_sandbox")==1 ? Params::getParam("pagseguro_sandbox") : '0', 'payment_pro', 'BOOLEAN');

    osc_set_preference('pagseguro_email', trim(Params::getParam('pagseguro_email')), 'payment_pro', 'STRING');
    osc_set_preference('pagseguro_token', payment_pro_crypt(trim(Params::getParam('pagseguro_token'))), 'payment_pro', 'STRING');
    osc_set_preference('pagseguro_appid', payment_pro_crypt(trim(Params::getParam('pagseguro_appid'))), 'payment_pro', 'STRING');
    osc_set_preference('pagseguro_appkey', payment_pro_crypt(trim(Params::getParam('pagseguro_appkey'))), 'payment_pro', 'STRING');
    osc_set_preference('pagseguro_sandbox_token', payment_pro_crypt(trim(Params::getParam('pagseguro_sandbox_token'))), 'payment_pro', 'STRING');
    osc_set_preference('pagseguro_sandbox_appid', payment_pro_crypt(trim(Params::getParam('pagseguro_sandbox_appid'))), 'payment_pro', 'STRING');
    osc_set_preference('pagseguro_sandbox_appkey', payment_pro_crypt(trim(Params::getParam('pagseguro_sandbox_appkey'))), 'payment_pro', 'STRING');

    if(Params::getParam("pagseguro_enabled")==1) {
        payment_pro_register_service('Pagseguro', __FILE__);
    } else {
        payment_pro_unregister_service('Pagseguro');
    }
}
osc_add_hook('payment_pro_conf_save', 'payment_pro_pagseguro_conf_save');


function payment_pro_pagseguro_conf_form() {
    require_once dirname(__FILE__) . '/admin/conf.php';
}
osc_add_hook('payment_pro_conf_form', 'payment_pro_pagseguro_conf_form', 4);

function payment_pro_pagseguro_conf_footer() {
    require_once dirname(__FILE__) . '/admin/footer.php';
}
osc_add_hook('payment_pro_conf_footer', 'payment_pro_pagseguro_conf_footer');

function payment_pro_pagseguro_load_lib() {
    if(Params::getParam('page')=='custom' && Params::getParam('route')=='payment-pro-checkout') {
        if(osc_get_preference('pagseguro_sandbox', 'payment_pro')==1) {
            osc_register_script('pagseguro', PAYMENT_PRO_URL . 'payments/pagseguro/assets/sandbox.pagseguro.lightbox.js', array('jquery'));
        } else {
            osc_register_script('pagseguro', PAYMENT_PRO_URL . 'payments/pagseguro/assets/pagseguro.lightbox.js', array('jquery'));
        }
        osc_enqueue_script('pagseguro');
    }
}
osc_add_hook('init', 'payment_pro_pagseguro_load_lib');



