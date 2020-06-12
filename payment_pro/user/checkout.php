<?php
require_once PAYMENT_PRO_PATH . 'CheckoutDataTable.php';

$products = payment_pro_cart_get();
$extra = osc_apply_filter('payment_pro_checkout_extra', array('user' => osc_logged_user_id(), 'email' => osc_logged_user_email()));;

$checkoutDataTable = new CheckoutDataTable();
$checkoutDataTable->table($products);
$aData = $checkoutDataTable->getData();

$aRawRows = $checkoutDataTable->rawRows();
$columns = $aData['aColumns'];
$rows = $aData['aRows'];

if(osc_get_preference('allow_wallet', 'payment_pro')==1 && osc_get_preference('use_tokens', 'payment_pro')==1 ) {
    $tokens = true;
} else {
    $tokens = false;
}

if (osc_is_web_user_logged_in() && Session::newInstance()->_get('subscription') != '1' && osc_get_preference('allow_wallet', 'payment_pro')==1) {
    $showWalletButton = true;
} else {
    $showWalletButton = false;
}

if($showWalletButton && !empty($aRawRows)) {
    foreach($aRawRows as $r) {
        if(substr($r['id'] . "   ", 0, 3)=="WLT") {
            $showWalletButton = false;
            break;
        }
    }
}

if ($showWalletButton) {
    $wTotal = 0;
    foreach($products as $p) {
        $wTotal += $p['quantity']*$p['amount']*((100+$p['tax'])/100)*1000000;
    }
    $wallet = ModelPaymentPro::newInstance()->getWallet(osc_logged_user_id());

    $data = array(
        'random' => mt_rand(),
        'items' => $products,
        'date' => date('Y-m-d H:i:s')
    );

    $wallet_code = payment_pro_crypt(json_encode($data));
    $wallet_short_code = substr(sha1($wallet_code), 0, 8);
    Session::newInstance()->_set('payment_pro_wallet_' . $wallet_short_code, $wallet_code);
}


?>
<style type="text/css">
    .payments-ul {
        list-style-type:none;
    }
    .payments-ul li
    {
        display: inline-block;
    }
    .payments-preview {
        float:left;
        width: 40%;
    }
    .payments-options {
        float:left;
        width: 60%;
    }
    table.table {
        width: 100%;
        max-width: 100%;
        border: 1px solid #dddddd;
    }
    table.table tr th,
    table.table tr td{
        vertical-align: top;
        border-top: 1px solid #ddd;
        padding:8px;
    }
    table.table tr:nth-child(odd) {
        background-color: #ffffff;
    }
    table.table tr:nth-child(even) {
        background-color: #f5f5f5;
    }
    #bank_info {
        cursor: pointer;
        min-height: 20px;
        margin-bottom: 20px;
        background-color: #f5f5f5;
        border: 1px solid #e3e3e3;
        -webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,.05);
        box-shadow: inset 0 1px 1px rgba(0,0,0,.05);
        margin: 1em;
        padding: 24px;
        border-radius: 6px;
    }
</style>
<div class="payments-pro-wrapper">
    <h1><?php _e('Checkout page', 'payment_pro'); ?></h1>
    <div class="table-contains-actions">
        <table class="table" cellpadding="0" cellspacing="0">
            <thead align="left">
                <tr>
                    <?php
                    foreach ($columns as $k => $v) {
                        echo '<th class="col-' . $k . '">' . $v . '</th>';
                    };
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rows) > 0) { ?>
                        <?php foreach ($rows as $key => $row) { ?>
                        <tr>
                            <?php foreach ($row as $k => $v) { ?>
                                <td class="col-<?php echo $k; ?>"><?php echo $v; ?></td>
                        <?php }; ?>
                        </tr>
                    <?php }; ?>
<?php } else { ?>
                    <tr>
                        <td colspan="6" class="text-center">
                            <p><?php _e('Your cart is empty', 'payment_pro'); ?></p>
                        </td>
                    </tr>
<?php } ?>
            </tbody>
        </table>
        <div id="table-row-actions">
            <p style="font-style: italic"><?php _e('Continue and pay with:', 'payment_pro'); ?></p>
            <?php if(!$tokens || !$showWalletButton) { ?>
                <ul class="payments-ul">
                    <?php if (Session::newInstance()->_get('subscription') == '1') {
                        payment_pro_recurring_buttons($products, $extra);
                    } else {
                        payment_pro_buttons($products, $extra);
                    } ?>
                </ul>
            <?php }; ?>
            <?php if ($showWalletButton) { ?>
                <ul class="payments-ul">
                    <?php if(isset($wallet['i_amount']) && $wallet['i_amount']>=$wTotal) {
                        echo '<li style="cursor:pointer;cursor:hand" class="payment stripe-btn" onclick="javascript:window.location=\'' . osc_route_url('payment-pro-pay-from-wallet', array('code' => $wallet_short_code)) . '\'" ><img src="'.PAYMENT_PRO_URL . 'img/pay_with_wallet.png" ></li>';
                        echo '<br /><span>';
                        printf(__('You have %s in your account, after the purchase you will have %s', 'payment_pro'), payment_pro_format_price($wallet['i_amount'], payment_pro_currency()), payment_pro_format_price(($wallet['i_amount']-$wTotal), payment_pro_currency()));
                        echo '</span>';
                    } else {
                        _e('Insufficient funds in your wallet', 'payment_pro');
                    } ?>
                </ul>
            <?php } ?>
        </div>
    </div>
</div>

<?php osc_run_hook('payment_pro_checkout_footer'); ?>