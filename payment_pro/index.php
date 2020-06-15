<?php
/*
 * Copyright 2015-2017 Osclass
 *
 * You shall not distribute this plugin and any its files (except third-party libraries) to third parties.
 * Rental, leasing, sale and any other form of distribution are not allowed and are strictly forbidden.
 */


/*
Plugin Name: Osclass Payments Pro
Plugin URI: https://github.com/codexilab/osclass-payment_pro
Description: Complete and professional payments system for all your needs
Version: 3.4.0-1b
Author: Osclass
Author URI: http://www.osclass.org/
Short Name: osclass_payment_pro
Plugin update URI: osclass_payment_pro
*/

define('PAYMENT_PRO_PATH', PLUGINS_PATH . 'payment_pro/');
define('PAYMENT_PRO_URL', osc_base_url() . 'oc-content/plugins/payment_pro/');
define('PAYMENT_PRO_PLUGIN_FOLDER', 'payment_pro/');

@include_once PAYMENT_PRO_PATH . 'config.php';
if(!defined('PAYMENT_PRO_CRYPT_KEY')) {
    define('PAYMENT_PRO_CRYPT_KEY', 'randompasswordchangethis');
}

class PackageAssignment {
    public static function check() {
        if (function_exists('check_package_assignment')) {
            return check_package_assignment();
        }
        return false;
    }
}

// PAYMENT STATUS
define('PAYMENT_PRO_FAILED', 0);
define('PAYMENT_PRO_COMPLETED', 1);
define('PAYMENT_PRO_PENDING', 2);
define('PAYMENT_PRO_ALREADY_PAID', 3);
define('PAYMENT_PRO_WRONG_AMOUNT_TOTAL', 4);
define('PAYMENT_PRO_WRONG_AMOUNT_ITEM', 5);
define('PAYMENT_PRO_DISABLED', 6);
define('PAYMENT_PRO_ENABLED', 7);
define('PAYMENT_PRO_CREATED', 8);
define('PAYMENT_PRO_CANCELED', 9);

// load necessary functions
require_once PAYMENT_PRO_PATH . 'functions.php';
require_once PAYMENT_PRO_PATH . 'products.php';
require_once PAYMENT_PRO_PATH . 'ModelPaymentPro.php';
require_once PAYMENT_PRO_PATH . 'payments/Payment.php';
// Load different methods of payments
$services = json_decode(osc_get_preference('services', 'payment_pro'), true);
if(is_array($services)) {
    foreach ($services as $service => $file) {
        @include_once $file;
    }
    View::newInstance()->_exportVariableToView('_payment_pro_services', $services);
}
unset($services);

function payment_pro_install() {
    ModelPaymentPro::newInstance()->install();
}

function payment_pro_uninstall() {
    ModelPaymentPro::newInstance()->uninstall();
}

function payment_pro_admin_menu() {
    osc_add_admin_menu_page('Osclass Payments Pro', osc_route_admin_url('payment-pro-admin-log'), 'plugin_payment_pro', 'administrator');
    osc_add_admin_submenu_page('plugin_payment_pro', __('Payment options', 'payment_pro'), osc_route_admin_url('payment-pro-admin-conf'), 'payment_pro_settings', 'administrator');
    osc_add_admin_submenu_page('plugin_payment_pro', __('Categories fees', 'payment_pro'), osc_route_admin_url('payment-pro-admin-prices'), 'payment_pro_prices', 'administrator');
    osc_add_admin_submenu_page('plugin_payment_pro', __('Add credit to users', 'payment_pro'), osc_route_admin_url('payment-pro-admin-wallet'), 'payment_pro_wallet', 'administrator');
    osc_add_admin_submenu_page('plugin_payment_pro', __('Manage credit packs', 'payment_pro'), osc_route_admin_url('payment-pro-admin-packs'), 'payment_pro_packs', 'administrator');
    osc_add_admin_submenu_page('plugin_payment_pro', __('History of payments', 'payment_pro'), osc_route_admin_url('payment-pro-admin-log'), 'payment_pro_log', 'administrator');
    //osc_add_admin_submenu_page('plugin_payment_pro', __('Subscriptions', 'payment_pro'), osc_route_admin_url('payment-pro-admin-subs'), 'payment_pro_subs', 'administrator');
    osc_run_hook('payment_pro_admin_menu');
}

function payment_pro_style_admin_menu() {
    ?>
    <style>
        #plugin_payment_pro .ico {
            background-image: url('<?php echo osc_base_url(); ?>oc-content/plugins/payment_pro/img/split.png') !important;
            background-position:0px -48px;
        }
        #plugin_payment_pro .ico:hover,
        .current #plugin_payment_pro .ico{
            background-position:0px -0px !important;
        }
        body.compact #plugin_payment_pro .ico{
            background-position:-48px -48px;
        }
        body.compact #plugin_payment_pro .ico:hover,
        body.compact .current #plugin_payment_pro .ico{
            background-position:-48px 0px !important;
        }
    </style>
    <?php
}
osc_add_hook('admin_footer', 'payment_pro_style_admin_menu');

function payment_pro_publish($item, $do_edit = false) {
    $item = Item::newInstance()->findByPrimaryKey($item['pk_i_id']);
    if(!$do_edit && PackageAssignment::check() == false) {
        ModelPaymentPro::newInstance()->createItem($item['pk_i_id'], 0, null, null, $item['b_enabled']);
    } elseif (!$do_edit && PackageAssignment::check() == true) {
        ModelPaymentPro::newInstance()->createItem($item['pk_i_id'], 1, null, null, $item['b_enabled']);
    }
    $checkout = false;
    $category_fee = 0;
    $premium_fee = 0;
    // Need to pay to publish ?
    if(osc_get_preference('pay_per_post', 'payment_pro')==1 && PackageAssignment::check() == false) {
        $is_paid = false;
        if($do_edit) {
            $is_paid = ModelPaymentPro::newInstance()->publishFeeIsPaid($item['pk_i_id']);
        }
        if(!$is_paid) {
            $category_fee = ModelPaymentPro::newInstance()->getPublishPrice($item['fk_i_category_id']);
            if (isset($category_fee['price']) && $category_fee['price'] > 0) {
                // Catch and re-set FlashMessages
                osc_resend_flash_messages();
                $mItems = new ItemActions(false);
                $mItems->disable($item['pk_i_id']);
                if($item['b_enabled']==1) {
                    ModelPaymentPro::newInstance()->enableItem($item['pk_i_id']);
                    payment_pro_cart_add(
                        'PUB' . $item['fk_i_category_id'] . '-' . $item['pk_i_id'],
                        sprintf(__('Publish fee for item %s', 'payment_pro'), $item['pk_i_id']),
                        $category_fee['price'],
                        1,
                        osc_get_preference('default_tax', 'payment_pro')/*$category_fee['tax']*/
                    );
                    $checkout = true;
                }
            } else {
                // PRICE IS ZERO
                if(!$do_edit && PackageAssignment::check() == true) {
                    ModelPaymentPro::newInstance()->payPostItem($item['pk_i_id']);
                    //ModelPaymentPro::newInstance()->createItem($item['pk_i_id'], 1, null, null, $item['b_enabled']);
                }
            }
        }
    } else {
        if(!$do_edit && PackageAssignment::check() == true) {
            ModelPaymentPro::newInstance()->payPostItem($item['pk_i_id']);
        }
    }
    if(osc_get_preference('allow_premium', 'payment_pro')==1) {
        $premium_fee = ModelPaymentPro::newInstance()->getPremiumPrice($item['fk_i_category_id']);
        if(isset($premium_fee['price']) && $premium_fee['price']>0) {
            if(Params::getParam('payment_pro_make_premium')==1) {
                if($item['b_enabled']==1) {
                    payment_pro_cart_add(
                        'PRM' . $item['fk_i_category_id'] . '-' . $item['pk_i_id'],
                        sprintf(__('Premium fee for item %s', 'payment_pro'), $item['pk_i_id']),
                        $premium_fee['price'],
                        1,
                        osc_get_preference('default_tax', 'payment_pro')/*$premium_fee['tax']*/
                    );
                    $checkout = true;
                }
            }
        }
    }
    if(osc_get_preference('allow_highlight', 'payment_pro')==1) {
        $highlight_fee = ModelPaymentPro::newInstance()->getHighlightPrice($item['fk_i_category_id']);
        if(isset($highlight_fee['price']) && $highlight_fee['price']>0) {
            if(Params::getParam('payment_pro_make_highlight')==1) {
                if($item['b_enabled']==1) {
                    payment_pro_cart_add(
                        'HLT' . $item['fk_i_category_id'] . '-' . $item['pk_i_id'],
                        sprintf(__('Highlight enhancement for listing %d', 'payment_pro'), $item['pk_i_id']),
                        $highlight_fee['price'],
                        1,
                        osc_get_preference('default_tax', 'payment_pro')/*$highlight_fee['tax']*/
                    );
                    $checkout = true;
                }
            }
        }
    }

    $checkout = osc_apply_filter("payment_pro_before_checkout", $checkout, $item);
    $products = payment_pro_cart_get();
    if($checkout && !OC_ADMIN && !empty($products)) {
        ModelPaymentPro::newInstance()->addQueue(date('Y-m-d H:i:s', time()+1800), $item['pk_i_id'], $category_fee!=0, $premium_fee!=0);
        osc_redirect_to(osc_route_url('payment-pro-checkout', array('itemId' => $item['pk_i_id'])));
    }
}

function payment_pro_edited_item($item) {
    payment_pro_publish($item, true);
}

function payment_pro_user_menu() {
    echo '<li class="opt_payment" ><a href="'.osc_route_url('payment-pro-user-menu').'" >'.__("Listings payment status", 'payment_pro').'</a></li>' ;
    if(osc_get_preference('allow_wallet', 'payment_pro')==1) {
        echo '<li class="opt_payment_pro_pack" ><a href="'.osc_route_url('payment-pro-user-packs').'" >'.__("Buy credit for payments", 'payment_pro').'</a></li>' ;
    }
}

function payment_pro_cron() {
    ModelPaymentPro::newInstance()->purgeExpired();
    ModelPaymentPro::newInstance()->purgePending();
    ModelPaymentPro::newInstance()->purgeSubscriptions();

    $date = date('Y-m-d H:i:s');
    $emails = ModelPaymentPro::newInstance()->getQueue($date);
    foreach($emails as $email) {
        payment_pro_send_email($email);
    }
    ModelPaymentPro::newInstance()->purgeQueue($date);
}

function payment_pro_premium_off($id) {
    ModelPaymentPro::newInstance()->premiumOff($id);
}

function payment_pro_before_edit($item) {
    if((osc_get_preference('pay_per_post', 'payment_pro') == '1' && PackageAssignment::check() == false && ModelPaymentPro::newInstance()->publishFeeIsPaid($item['pk_i_id']))|| (osc_get_preference('allow_premium','payment') == '1' && ModelPaymentPro::newInstance()->premiumFeeIsPaid($item['pk_i_id']))) {
        $cat[0] = Category::newInstance()->findByPrimaryKey($item['fk_i_category_id']);
        View::newInstance()->_exportVariableToView('categories', $cat);
    }
}

function payment_pro_show_item($item) {
    if(osc_get_preference("pay_per_post", 'payment_pro')=="1" && PackageAssignment::check() == false && !ModelPaymentPro::newInstance()->publishFeeIsPaid($item['pk_i_id']) ) {
        if( osc_is_admin_user_logged_in() ) {
            osc_get_flash_message('pubMessages', true);
            osc_add_flash_warning_message( __('The listing hasn\'t been paid', 'payment_pro') );
        } else if(osc_is_web_user_logged_in() && osc_logged_user_id()==$item['fk_i_user_id']) {
            osc_get_flash_message('pubMessages', true);
            osc_add_flash_warning_message( sprintf(__('To make this listing available to others, you need to pay a publish fee. <a href="%s">Continue and make the ad public</a>', 'payment_pro'), osc_route_url('payment-pro-user-menu') ));
        } else {
            ob_get_clean();
            Rewrite::newInstance()->set_location('error');
            header('HTTP/1.1 400 Bad Request');
            osc_current_web_theme_path('404.php');
            exit;
        }
    };
};

function payment_pro_item_delete($itemId) {
    ModelPaymentPro::newInstance()->deleteItem($itemId);
}

function payment_pro_configure_link() {
    osc_redirect_to(osc_route_admin_url('payment-pro-admin-conf'));
}

function payment_pro_form($category_id = null, $item_id = null) {
    $payment_pro_premium_fee = ModelPaymentPro::newInstance()->getPremiumPrice($category_id);
    $payment_pro_publish_fee = ModelPaymentPro::newInstance()->getPublishPrice($category_id);
    $payment_pro_highlight_fee = ModelPaymentPro::newInstance()->getHighlightPrice($category_id);
    if($item_id==null) { // POST
        if((isset($payment_pro_publish_fee['price']) && $payment_pro_publish_fee['price']>0)
            || (isset($payment_pro_premium_fee['price']) && $payment_pro_premium_fee['price']>0)
            || (isset($payment_pro_highlight_fee['price']) && $payment_pro_highlight_fee['price']>0)) {
            $payment_pro_prm = false;
            $payment_pro_hlt = false;
            require 'user/item_edit.php';
        }
    } else {
        $item = Item::newInstance()->findByPrimaryKey($item_id);
        $payment_pro_prm = $item['b_premium']==1;
        $payment_pro_hlt = ModelPaymentPro::newInstance()->isHighlighted($item_id);
        if(!$payment_pro_prm || !$payment_pro_hlt) {
            require 'user/item_edit.php';
        }
    }
}

function payment_pro_enable_item($id) {
    ModelPaymentPro::newInstance()->enableItem($id);
}

function payment_pro_disable_item($id) {
    ModelPaymentPro::newInstance()->disableItem($id);
}


function payment_pro_load_styles() {
    if(!OC_ADMIN) {
        $payment_pro_theme_css = osc_current_web_theme();
        if(file_exists(PAYMENT_PRO_PATH . "styles/" . $payment_pro_theme_css . ".css")) {
            osc_enqueue_style('payment-pro-style-' . $payment_pro_theme_css, PAYMENT_PRO_URL . "styles/" . $payment_pro_theme_css . ".css");
        }
    }
}

function payment_pro_admin_page_header() {

    switch (Params::getParam('route')) {
        case 'payment-pro-admin-conf':

            osc_enqueue_script('colorpicker');
            osc_enqueue_style('colorpicker', osc_assets_url('js/colorpicker/css/colorpicker.css'));
            osc_enqueue_style('payment-pro-admin', PAYMENT_PRO_URL . "/assets/admin.css");

            osc_remove_hook('admin_page_header', 'customPageHeader');
            osc_add_hook('admin_page_header',  function() { echo '<h1>' . __('Payments settings', 'payments_pro') . '</h1>'; @include(dirname(__FILE__) . '/admin/header.php'); } );
            break;
        case 'payment-pro-admin-prices':
            osc_remove_hook('admin_page_header', 'customPageHeader');
            osc_add_hook('admin_page_header',  function() { echo '<h1>' . __('Categories fees', 'payments_pro') . '</h1>'; @include(dirname(__FILE__) . '/admin/header.php'); } );
            break;
        case 'payment-pro-admin-log':
            osc_remove_hook('admin_page_header', 'customPageHeader');
            osc_add_hook('admin_page_header',  function() { echo '<h1>' . __('History of payments', 'payments_pro') . '</h1>'; @include(dirname(__FILE__) . '/admin/header.php'); } );
            break;
        case 'payment-pro-admin-subs':
            osc_remove_hook('admin_page_header', 'customPageHeader');
            osc_add_hook('admin_page_header',  function() { echo '<h1>' . __('Subscriptions', 'payments_pro') . '</h1>'; @include(dirname(__FILE__) . '/admin/header.php'); } );
            break;
        case 'payment-pro-admin-wallet':
            osc_remove_hook('admin_page_header', 'customPageHeader');
            osc_add_hook('admin_page_header',  function() { echo '<h1>' . __('Add credit to users', 'payments_pro') . '</h1>'; @include(dirname(__FILE__) . '/admin/header.php'); } );
            break;
        case 'payment-pro-admin-packs':
            osc_remove_hook('admin_page_header', 'customPageHeader');
            osc_add_hook('admin_page_header',  function() { echo '<h1>' . __('Manage your credit packs', 'payments_pro') . '</h1>'; @include(dirname(__FILE__) . '/admin/header.php'); } );
            break;
        default:
            break;
    }
}

function payment_pro_body_class($array){
    switch (Params::getParam('route')) {
        case 'payment-pro-admin-conf':
        case 'payment-pro-admin-prices':
        case 'payment-pro-admin-log':
        case 'payment-pro-admin-subs':
        case 'payment-pro-admin-wallet':
        case 'payment-pro-admin-packs':
            $array[] = 'market';
            break;
        default:
            break;
    }
    return $array;
}
osc_add_filter('admin_body_class','payment_pro_body_class');

function payment_pro_admin_title() {
    switch (Params::getParam('route')) {
        case 'payment-pro-admin-conf':
            osc_remove_filter('admin_title', 'customPageTitle');
            osc_add_filter('admin_title',  function($string){ return __('Settings, Osclass payments pro ', 'payment_pro').$string;} );
            break;
        case 'payment-pro-admin-prices':
            osc_remove_filter('admin_title', 'customPageTitle');
            osc_add_filter('admin_title',  function($string){ return __('Categories fees, Osclass payments pro ', 'payment_pro').$string;} );
            break;
        case 'payment-pro-admin-log':
            osc_remove_filter('admin_title', 'customPageTitle');
            osc_add_filter('admin_title',  function($string){ return __('History of payments, Osclass payments pro ', 'payment_pro').$string;} );
            break;
        case 'payment-pro-admin-subs':
            osc_remove_filter('admin_title', 'customPageTitle');
            osc_add_filter('admin_title',  function($string){ return __('Subscriptions, Osclass payments pro ', 'payment_pro').$string;} );
            break;
        case 'payment-pro-admin-wallet':
            osc_remove_filter('admin_title', 'customPageTitle');
            osc_add_filter('admin_title',  function($string){ return __('Add credit to users, Osclass payments pro ', 'payment_pro').$string;} );
            break;
        case 'payment-pro-admin-packs':
            osc_remove_filter('admin_title', 'customPageTitle');
            osc_add_filter('admin_title',  function($string){ return __('Manage your credit packs, Osclass payments pro ', 'payment_pro').$string;} );
            break;
        default:
            break;
    }
}

function payment_pro_item_row($row, $aRow) {
    if(osc_get_preference('pay_per_post', 'payment_pro')==1) {
        $enabled = ModelPaymentPro::newInstance()->isEnabled($aRow['pk_i_id']) ? __('Active') : __('Blocked');
        $row['status'] = str_replace(__('Blocked'), $enabled, str_replace(__('Active'), $enabled, $row['status']));
        if (ModelPaymentPro::newInstance()->publishFeeIsPaid($aRow['pk_i_id'])) {
            $row['status'] .= ' ' . __('Paid', 'payment_pro');
        } else {
            $row['status'] .= ' ' . __('No-paid', 'payment_pro');
        };
    }
    return $row;
}

function payment_pro_item_row_class($class, $item) {
    if(osc_get_preference('pay_per_post', 'payment_pro')==1) {
        $enabled = ModelPaymentPro::newInstance()->isEnabled($item['pk_i_id']);
        if($item['b_enabled']!=$enabled) {
            foreach ($class as $k => $v) {
                if ($v == 'status-blocked') {
                    $class[$k] = 'status-active';
                } else if($v == 'status-active') {
                    $class[$k] = 'status-blocked';
                }
            }
        }
        if (ModelPaymentPro::newInstance()->publishFeeIsPaid($item['pk_i_id'])) {
            $class[] = 'payment-pro-paid';
        } else {
            $class[] = 'payment-pro-nopaid';
        };
    }
    return $class;
}

function payment_pro_item_actions($actions, $aRow) {
    if(osc_get_preference('pay_per_post', 'payment_pro')==1) {
        foreach ($actions as $k => $v) {
            if (strpos($v, 'value=DISABLE') !== false || strpos($v, 'value=ENABLE') !== false) {
                if (ModelPaymentPro::newInstance()->isEnabled($aRow['pk_i_id'])) {
                    $actions[$k] = '<a href="' . osc_route_admin_url('payment-pro-admin-pay', array('pay' => 2, 'id' => $aRow['pk_i_id'])) . '" >' . __('Block', 'payment_pro') . '</a>';
                } else {
                    $actions[$k] = '<a href="' . osc_route_admin_url('payment-pro-admin-pay', array('pay' => 3, 'id' => $aRow['pk_i_id'])) . '" >' . __('Unblock', 'payment_pro') . '</a>';
                }
                break;
            }
        }
    }

    if(osc_get_preference('allow_top', 'payment_pro')==1) {
        array_unshift($actions, '<a href="' . osc_route_admin_url('payment-pro-admin-pay', array('top' => 1, 'pay' => 1, 'id' => $aRow['pk_i_id'])) . '" >' . __('Move to top', 'payment_pro') . '</a>');
    }

    if(osc_get_preference('allow_highlight', 'payment_pro')==1) {
        if (payment_pro_is_highlighted($aRow['pk_i_id'])) {
            array_unshift($actions, '<a href="' . osc_route_admin_url('payment-pro-admin-pay', array('highlight' => 1, 'pay' => 0, 'id' => $aRow['pk_i_id'])) . '" >' . __('Unpay highlight fee', 'payment_pro') . '</a>');
        } else {
            array_unshift($actions, '<a href="' . osc_route_admin_url('payment-pro-admin-pay', array('highlight' => 1, 'pay' => 1, 'id' => $aRow['pk_i_id'])) . '" >' . __('Pay highlight fee', 'payment_pro') . '</a>');
        }
    }

    if(osc_get_preference('pay_per_post', 'payment_pro')==1) {
        if (ModelPaymentPro::newInstance()->publishFeeIsPaid($aRow['pk_i_id'])) {
            array_unshift($actions, '<a href="' . osc_route_admin_url('payment-pro-admin-pay', array('pay' => 0, 'id' => $aRow['pk_i_id'])) . '" >' . __('Unpay publish fee', 'payment_pro') . '</a>');
        } else {
            array_unshift($actions, '<a href="' . osc_route_admin_url('payment-pro-admin-pay', array('pay' => 1, 'id' => $aRow['pk_i_id'])) . '" >' . __('Pay publish fee', 'payment_pro') . '</a>');
        }
    }

    return $actions;
}


function payments_pro_admin_zero($str) {
    if(substr(Params::getParam('route'), 0, 18)=='payment-pro-admin-' ||
        (OC_ADMIN && Params::getParam('page')=='users')) {
        $symbol = osc_item_currency_symbol();
        $price = 0;
        $currencyFormat = osc_locale_currency_format();
        $currencyFormat = str_replace('{NUMBER}', number_format($price, osc_locale_num_dec(), osc_locale_dec_point(), osc_locale_thousands_sep()), $currencyFormat);
        $currencyFormat = str_replace('{CURRENCY}', $symbol, $currencyFormat);
        return osc_apply_filter('item_price', $currencyFormat);
    }
    return $str;
}
osc_add_hook('item_price_zero', 'payments_pro_admin_zero');

function payments_pro_userinfo() {
    $user = User::newInstance()->findByPrimaryKey(Params::getParam('id'));
    if(isset($user['pk_i_id'])) {
        $wallet = ModelPaymentPro::newInstance()->getWallet($user['pk_i_id']);
        echo json_encode(array('user' => $user, 'wallet' => $wallet, 'error' => 0));
    } else {
        echo json_encode(array('user' => array(), 'error' => 1));
    }
    die;
}
osc_add_hook('ajax_admin_payment_pro_userinfo', 'payments_pro_userinfo');

function payment_pro_user_column($table) {
    if (osc_get_preference('allow_wallet', 'payment_pro')){
        $table->addColumn('payment_pro_credit', __('Credit'));
    }
}
osc_add_hook("admin_users_table", 'payment_pro_user_column');

function payment_pro_user_row($row, $aRow) {
    if (osc_get_preference('allow_wallet', 'payment_pro')){
        $wallet = ModelPaymentPro::newInstance()->getWallet($aRow['pk_i_id']);
        $row['payment_pro_credit'] = osc_format_price(isset($wallet['i_amount'])?$wallet['i_amount']:0, osc_get_preference('currency', 'payment_pro'));
    }
    return $row;
}
osc_add_hook('users_processing_row', 'payment_pro_user_row');

function payment_pro_style() {
    echo PHP_EOL . '<style>.payment-pro-highlighted { background-color:#' . osc_get_preference('highlight_color', 'payment_pro') . ' !important; }</style>' . PHP_EOL;

}
osc_add_hook('header', 'payment_pro_style');

function payment_pro_item_detail($item) {
    if(osc_is_web_user_logged_in() && isset($item['pk_i_id']) && ModelPaymentPro::newInstance()->isEnabled($item['pk_i_id']) && isset($item['fk_i_user_id']) && $item['fk_i_user_id']==osc_logged_user_id()) {

        $options = payment_pro_menu_options($item);

        if(!empty($options)) {
            echo '<p class="options payments-options">' . join("<span>&nbsp;&nbsp;&nbsp;&nbsp;</span>", $options) . '</p>';
            ?>
            <script type="text/javascript">
                function addProduct(prd, id) {
                    $("#" + prd + "_" + id).attr('disabled', true);
                    $.ajax({
                        type: "POST",
                        url: '<?php echo osc_ajax_plugin_url(PAYMENT_PRO_PLUGIN_FOLDER . 'ajax.php'); ?>&' + prd + '=' + id,
                        data: {a : 'b'},
                        dataType: 'json',
                        success: function(data){
                            if(data.error==0) {
                                window.location = '<?php echo osc_route_url('payment-pro-checkout'); ?>';
                            } else {
                                $("#" + prd + "_" + id).attr('disabled', false);
                                var flash = $("#flash_js");
                                var message = $('<div>').addClass('flashmessage').addClass('flashmessage-error').attr('id', 'flashmessage').html(data.msg);
                                flash.html(message);
                                $("#flashmessage").slideDown('slow').delay(3000).slideUp('slow');
                                $("html, body").animate({ scrollTop: 0 }, "slow");
                            }
                        }
                    });
                }
            </script>
            <?php
        }

    }
}
osc_add_hook('item_detail', 'payment_pro_item_detail');

/**
 * ADD ROUTES (VERSION 3.2+)
 */
osc_add_route('payment-pro-admin-pay', 'paymentpro/admin/pay/([0-1]+)/(.+)', 'paymentpro/admin/pay/{pay}/{id}', PAYMENT_PRO_PLUGIN_FOLDER . 'admin/pay.php');
osc_add_route('payment-pro-admin-conf', 'paymentpro/admin/conf', 'paymentpro/admin/conf', PAYMENT_PRO_PLUGIN_FOLDER . 'admin/conf.php');
osc_add_route('payment-pro-admin-prices', 'paymentpro/admin/prices', 'paymentpro/admin/prices', PAYMENT_PRO_PLUGIN_FOLDER . 'admin/conf_prices.php');
osc_add_route('payment-pro-admin-log', 'paymentpro/admin/log', 'paymentpro/admin/log', PAYMENT_PRO_PLUGIN_FOLDER . 'admin/log.php');
osc_add_route('payment-pro-admin-subs', 'paymentpro/admin/subs', 'paymentpro/admin/subs', PAYMENT_PRO_PLUGIN_FOLDER . 'admin/subs.php');
osc_add_route('payment-pro-admin-wallet', 'paymentpro/admin/wallet', 'paymentpro/admin/wallet', PAYMENT_PRO_PLUGIN_FOLDER . 'admin/wallet.php');
osc_add_route('payment-pro-admin-packs', 'paymentpro/admin/packs', 'paymentpro/admin/packs', PAYMENT_PRO_PLUGIN_FOLDER . 'admin/packs.php');
osc_add_route('payment-pro-checkout', 'paymentpro/checkout', 'paymentpro/checkout', PAYMENT_PRO_PLUGIN_FOLDER . 'user/checkout.php', false, 'custom', 'custom', __('Checkout', 'payment_pro'));
osc_add_route('payment-pro-cart-delete', 'paymentpro/cart/delete/(.+)', 'paymentpro/cart/delete/{id}', PAYMENT_PRO_PLUGIN_FOLDER . 'user/cart-delete.php');
osc_add_route('payment-pro-addcart', 'paymentpro/cart/add/(.+)', 'paymentpro/cart/add/{item}', PAYMENT_PRO_PLUGIN_FOLDER . 'user/cart-add.php');
osc_add_route('payment-pro-done', 'paymentpro/done/(.*)', 'paymentpro/done/{tx}', PAYMENT_PRO_PLUGIN_FOLDER . 'user/done.php', false, 'custom', 'custom', __('Checkout', 'payment_pro'));
osc_add_route('payment-pro-pay-from-wallet', 'paymentpro/wallet/pay/(.*)', 'paymentpro/wallet/pay/{code}', PAYMENT_PRO_PLUGIN_FOLDER . 'user/payfromwallet.php', false, 'custom', 'custom', __('Checkout', 'payment_pro'));
osc_add_route('payment-pro-ajax', 'paymentpro/ajax', 'paymentpro/ajax', PAYMENT_PRO_PLUGIN_FOLDER . 'ajax.php');
osc_add_route('payment-pro-user-menu', 'paymentpro/menu', 'paymentpro/menu', PAYMENT_PRO_PLUGIN_FOLDER . 'user/menu.php', true, 'custom', 'custom', __('Payment status', 'payment_pro'));
osc_add_route('payment-pro-user-packs', 'paymentpro/packs', 'paymentpro/packs', PAYMENT_PRO_PLUGIN_FOLDER . 'user/packs.php', true, 'custom', 'custom', __('Buy credit packs', 'payment_pro'));

function payment_pro_update_version()
{
    ModelPaymentPro::newInstance()->versionUpdate();
}


/**
 * ADD HOOKS
 */
osc_register_plugin(PAYMENT_PRO_PATH."index.php", 'payment_pro_install');
osc_add_hook(PAYMENT_PRO_PATH."index.php_configure", 'payment_pro_configure_link');
osc_add_hook(PAYMENT_PRO_PATH."index.php_uninstall", 'payment_pro_uninstall');
osc_add_hook(PAYMENT_PRO_PATH."index.php_enable", 'payment_pro_update_version');

osc_add_hook('admin_menu_init', 'payment_pro_admin_menu');

osc_add_hook('init', 'payment_pro_admin_title');
osc_add_hook('init', 'payment_pro_load_styles');
osc_add_hook('admin_header', 'payment_pro_admin_page_header');
osc_add_hook('posted_item', 'payment_pro_publish', 8);
osc_add_hook('edited_item', 'payment_pro_edited_item', 8);
osc_add_hook('user_menu', 'payment_pro_user_menu');
osc_add_hook('cron_hourly', 'payment_pro_cron');
osc_add_hook('item_premium_off', 'payment_pro_premium_off');
osc_add_hook('before_item_edit', 'payment_pro_before_edit');
osc_add_hook('show_item', 'payment_pro_show_item');
osc_add_hook('delete_item', 'payment_pro_item_delete');

osc_add_hook('enable_item', 'payment_pro_enable_item');
osc_add_hook('disable_item', 'payment_pro_disable_item');

osc_add_hook('item_form', 'payment_pro_form');
osc_add_hook('item_edit', 'payment_pro_form');

osc_add_filter('items_processing_row', 'payment_pro_item_row', 8);
osc_add_filter('datatable_listing_class', 'payment_pro_item_row_class', 8);
osc_add_filter('more_actions_manage_items', 'payment_pro_item_actions', 3);
