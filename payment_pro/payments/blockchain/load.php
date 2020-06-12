<?php

osc_add_route('blockchain-notify-long', 'payment/blockchain-notify/(.+)', 'payment/blockchain-notify/{extra}', PAYMENT_PRO_PLUGIN_FOLDER . 'payments/blockchain/callback.php');
osc_add_route('blockchain-notify', 'btc/(.+)', 'btc/{extra}', PAYMENT_PRO_PLUGIN_FOLDER . 'payments/blockchain/callback.php');
osc_add_route('blockchain-create', 'payment/blockchain-create/(.+)', 'payment/blockchain-create/{data}', PAYMENT_PRO_PLUGIN_FOLDER . 'payments/blockchain/create.php');
require_once PAYMENT_PRO_PATH . 'payments/blockchain/BlockchainPayment.php';

osc_add_hook('ajax_blockchain', array('BlockchainPayment', 'ajaxCreate'));


function payment_pro_blockchain_install() {
    osc_set_preference('blockchain_apikey', payment_pro_crypt(''), 'payment_pro', 'STRING');
    osc_set_preference('blockchain_xpub', '', 'payment_pro', 'STRING');
    osc_set_preference('blockchain_confirmations', '6', 'payment_pro', 'INTEGER');
    osc_set_preference('blockchain_enabled', '0', 'payment_pro', 'BOOLEAN');
    osc_set_preference('blockchain_xrate', '1', 'payment_pro', 'STRING');
}
osc_add_hook('payment_pro_install', 'payment_pro_blockchain_install');

function payment_pro_blockchain_conf_save() {

    osc_set_preference('blockchain_apikey', Params::getParam("blockchain_apikey") ? payment_pro_crypt(Params::getParam("blockchain_apikey")) : '', 'payment_pro', 'STRING');
    osc_set_preference('blockchain_xpub', Params::getParam("blockchain_xpub") ? Params::getParam("blockchain_xpub") : '', 'payment_pro', 'STRING');
    osc_set_preference('blockchain_confirmations', is_numeric(Params::getParam("blockchain_confirmations"))? Params::getParam("blockchain_confirmations") : '6', 'payment_pro', 'INTEGER');
    osc_set_preference('blockchain_enabled', Params::getParam("blockchain_enabled") ? Params::getParam("blockchain_enabled") : '0', 'payment_pro', 'BOOLEAN');

    if(Params::getParam("blockchain_enabled")==1) {
        payment_pro_register_service('Blockchain', __FILE__);
    } else {
        payment_pro_unregister_service('Blockchain');
    }
}
osc_add_hook('payment_pro_conf_save', 'payment_pro_blockchain_conf_save');



function payment_pro_blockchain_conf_form() {
    require_once dirname(__FILE__) . '/admin/conf.php';
}
osc_add_hook('payment_pro_conf_form', 'payment_pro_blockchain_conf_form', 4);

function payment_pro_blockchain_conf_footer() {
    require_once dirname(__FILE__) . '/admin/footer.php';
}
osc_add_hook('payment_pro_conf_footer', 'payment_pro_blockchain_conf_footer');


function payment_pro_blockchain_load_lib() {
    if(Params::getParam('page')=='custom' && Params::getParam('route')=='payment-pro-checkout') {
        //osc_register_script('blockchainv2', 'https://blockchain.info/Resources/js/pay-now-button.js', array('jquery'));
        osc_register_script('blockchainv2', PAYMENT_PRO_URL . 'payments/blockchain/pay-now-button.js', array('jquery'));
        osc_enqueue_script('blockchainv2');
    }
}
osc_add_hook('init', 'payment_pro_blockchain_load_lib');

function payment_pro_check_items_blockchain($items, $total, $rate = 1, $error = 0.15) {
    $subtotal = 0;
    foreach($items as $item) {
        $item['amount'] = $item['amount']/1000000;
        if(isset($item['amount_total'])) {
            $subtotal += $item['amount_total']/1000000;
        } else {
            $subtotal += $item['amount']*$item['quantity']*((100+$item['tax'])/100);
        }
        $str = substr($item['id'], 0, 3);
        if($str=='PUB') {
            $cat = explode("-", $item['id']);
            $price = ModelPaymentPro::newInstance()->getPublishPrice(substr($cat[0], 3));
            if($item['quantity']!=1 || $price['price']!=$item['amount']) {
                return PAYMENT_PRO_WRONG_AMOUNT_ITEM;
            }
        } if($str=='PRM') {
            $cat = explode("-", $item['id']);
            $price = ModelPaymentPro::newInstance()->getPremiumPrice(substr($cat[0], 3));
            if($item['quantity']!=1 || $price['price']!=$item['amount']) {
                return PAYMENT_PRO_WRONG_AMOUNT_ITEM;
            }
        } else {
            $correct_price = osc_apply_filter('payment_pro_price_' . strtolower($str), true, $item);
            if(!$correct_price) {
                return PAYMENT_PRO_WRONG_AMOUNT_ITEM;
            }
        }
    }
    if(abs(($subtotal*$rate)-$total)>($total*$error)) {
        return PAYMENT_PRO_WRONG_AMOUNT_TOTAL;
    }
    return PAYMENT_PRO_COMPLETED;
}


