<?php

osc_add_route('bank-explain', 'payment/bank-explain/', 'payment/bank/explain/', PAYMENT_PRO_PLUGIN_FOLDER . 'payments/bank/explain.php');
osc_add_route('payment-pro-admin-bank', 'paymentpro/admin/bank', 'paymentpro/admin/bank', PAYMENT_PRO_PLUGIN_FOLDER . 'payments/bank/admin/manage.php');
require_once PAYMENT_PRO_PATH . 'payments/bank/BankPayment.php';

osc_add_hook('ajax_banktransfer', array('BankPayment', 'ajaxCreate'));

function payment_pro_bank_install() {
    osc_set_preference('bank_account', '', 'payment_pro', 'STRING');
    osc_set_preference('bank_enabled', '0', 'payment_pro', 'BOOLEAN');
    osc_set_preference('bank_only_packs', '0', 'payment_pro', 'BOOLEAN');
    osc_set_preference('bank_msg', __('Please make a wire transfer to the account: {BANK_ACCOUNT} with the concept "{CODE}" of a total of {AMOUNT}. We are unable to process your payment if the concept does not containg the code.', 'payment_pro'), 'payment_pro', 'STRING');
}
osc_add_hook('payment_pro_install', 'payment_pro_bank_install');

function payment_pro_bank_conf_save() {
    osc_set_preference('bank_account', Params::getParam("bank_account")!='' ? Params::getParam("bank_account") : '', 'payment_pro', 'STRING');
    osc_set_preference('bank_enabled', Params::getParam("bank_enabled")==1 ? Params::getParam("bank_enabled") : '0', 'payment_pro', 'BOOLEAN');
    osc_set_preference('bank_only_packs', Params::getParam("bank_only_packs")==1 ? Params::getParam("bank_only_packs") : '0', 'payment_pro', 'BOOLEAN');
    osc_set_preference('bank_msg', Params::getParam("bank_msg"), 'payment_pro', 'STRING');


    if(Params::getParam("bank_enabled")==1) {
        payment_pro_register_service('Bank', __FILE__);
    } else {
        payment_pro_unregister_service('Bank');
    }
}
osc_add_hook('payment_pro_conf_save', 'payment_pro_bank_conf_save');

function payment_pro_bank_conf_form() {
    require_once dirname(__FILE__) . '/admin/conf.php';
}
osc_add_hook('payment_pro_conf_form', 'payment_pro_bank_conf_form', 4);

function payment_pro_bank_conf_footer() {
    require_once dirname(__FILE__) . '/admin/footer.php';
}
osc_add_hook('payment_pro_conf_footer', 'payment_pro_bank_conf_footer');


/*********************
 *** ADMIN SECTION ***
 *********************/
function payment_pro_bank_header_tab($route) { ?>
    <li <?php if($route == 'payment-pro-admin-bank'){ echo 'class="active"';} ?>><a href="<?php echo osc_route_admin_url('payment-pro-admin-bank'); ?>"><?php _e('Bank transfers', 'payment_pro'); ?></a></li>
<?php }
osc_add_hook('payment_pro_admin_header_tab', 'payment_pro_bank_header_tab');

function payment_pro_bank_page_header() {
    if(Params::getParam('route')=='payment-pro-admin-bank') {
        osc_remove_hook('admin_page_header', 'customPageHeader');
        osc_add_hook('admin_page_header',  'payments_pro_admin_page_header_bank' );
    }
}
osc_add_hook('admin_header', 'payment_pro_bank_page_header');

function payments_pro_admin_page_header_bank() {
    ?>
    <h1><?php _e('Bank transfers', 'payments_pro'); ?></h1>
    <?php @include(PAYMENT_PRO_PATH . '/admin/header.php');
}

function payment_pro_bank_body_class($array){
    if(Params::getParam('route')=='payment-pro-admin-bank') {
        $array[] = 'market';
    }
    return $array;
}
osc_add_filter('admin_body_class','payment_pro_bank_body_class');


function payment_pro_bank_title() {
    if(Params::getParam('route')=='payment-pro-admin-bank') {
        osc_remove_filter('admin_title', 'customPageTitle');
        osc_add_filter('admin_title', 'payments_pro_admin_title_bank');
    }
}
osc_add_hook('init', 'payment_pro_bank_title');



//osc_add_hook('admin_menu_init', 'payment_pro_admin_menu');

