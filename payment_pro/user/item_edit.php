<?php
$pay_opt = array();

if(!$payment_pro_prm && isset($payment_pro_premium_fee['price']) && $payment_pro_premium_fee['price']>0) {
$opt = '<div class="payments-box">' .
        '<div class="payments-box-header"> ' . __('Make this ad premium', 'payment_pro') . '</div>' .
        '<div class="payments-box-content">' .
            '<ul>' .
                '<li><img src=" ' . PAYMENT_PRO_URL . 'img/tick_16.png"/>&nbsp;' . __('Make your ad to stand out at the home and search pages', 'payment_pro') . '</li>' .
                '<li><img src=" ' . PAYMENT_PRO_URL . 'img/tick_16.png"/>&nbsp;' . sprintf(__('Premium days %s', 'payment_pro'), osc_get_preference('premium_days', 'payment_pro')) . '</li>' .
            '</ul>' .
        '</div>' .
        '<div class="payments-box-footer">' .
            '<label><input type="checkbox" name="payment_pro_make_premium" id="payment_pro_make_premium" value="1" /> ' . sprintf(__('Make this ad premium (+%s)', 'payment_pro'), osc_format_price($payment_pro_premium_fee['price']*1000000, payment_pro_currency())) . '</label>' .
        '</div>' .
    '</div>';
    $pay_opt["PRM"] = $opt;
}

if(!$payment_pro_hlt && isset($payment_pro_highlight_fee['price']) && $payment_pro_highlight_fee['price']>0) {
$opt = '<div class="payments-box">' .
        '<div class="payments-box-header">' . __('Highlight this ad', 'payment_pro') . '</div>' .
        '<div class="payments-box-content">' .
            '<ul>' .
                '<li><img src="' . PAYMENT_PRO_URL . 'img/tick_16.png"/>&nbsp;' . __('Attract more visitors with a different background color for your ad', 'payment_pro') . '</li>' .
                '<li><img src="' . PAYMENT_PRO_URL . 'img/tick_16.png"/>&nbsp;' . sprintf(__('Highlight days %s', 'payment_pro'), osc_get_preference('highlight_days', 'payment_pro')) . '</li>' .
            '</ul>' .
        '</div>' .
        '<div class="payments-box-footer">' .
            '<label><input type="checkbox" name="payment_pro_make_highlight" id="payment_pro_make_highlight" value="1" /> ' . sprintf(__('Highlight this ad (+%s)', 'payment_pro'), osc_format_price($payment_pro_highlight_fee['price']*1000000, payment_pro_currency())) . '</label>' .
        '</div>' .
    '</div>';
    $pay_opt["HLT"] = $opt;
}


if(isset($payment_pro_publish_fee['price']) && $payment_pro_publish_fee['price']>0) {
$opt = '<div class="payments-box">' .
        '<div class="payments-box-header">' . __('Publish fee', 'payment_pro') . '</div>' .
        '<div class="payments-box-footer">' .
            '<label><input type="checkbox" name="dummy_check" id="dummy_check" value="1" checked="checked" disabled="disabled" /> ' . sprintf(__('Publishing this ad costs %s', 'payment_pro'), osc_format_price($payment_pro_publish_fee['price']*1000000, payment_pro_currency())) . '</label>' .
        '</div>' .
    '</div>' .
    '</div>';
    $pay_opt["PUB"] = $opt;
};

$nopts = count($pay_opt);
if($nopts>0) {
?>

<style>
    .payments-box {
        width: <?php echo $nopts==3?30:45; ?>%;
        margin-right: 1%;
        border: 1px #cccccc solid;
        float:left; 
        margin-bottom: 1em;
    }

    .payments-box > .payments-box-header,
    .payments-box > .payments-box-content,
    .payments-box > .payments-box-footer{
        padding:0px 10px;
    }
    .payments-box > .payments-box-header {
        height: auto;
        line-height: 1.5em;
        background-color: bisque;
        font-weight: 500;
        text-align: center;
        padding: 5px 5px;
    }
    .payments-box > .payments-box-content > ul {
        padding:0px;
        margin: 1em 0px;
    }
    .payments-box > .payments-box-content > ul > li {
        list-style: none;
        line-height: 2em;
    }
    .payments-box > .payments-box-footer {
        padding: 10px 10px;
        border-top: 1px #cccccc solid;
    }
    .payments-box > .payments-box-footer > label {
        float: none;
        display:block;
        width: 100%;
        text-align: left;
        font-size: 1em;
        padding: 0px;
    }
    .payments-box > .payments-box-footer > label > input[type="checkbox"]  {
        width: auto;
        height: auto;
        vertical-align: middle;
        margin:0px;
        margin-top: -2px;
        padding: 0;
        border: none;
        box-shadow: none;
    }
</style>

<h2><?php _e('Publish options', 'payment_pro') ; ?></h2>
<div class="control-group payments-box-wrapper">
<?php
    $pay_opt = osc_apply_filter("payment_pro_show_options", $pay_opt, $category_id, $item_id);
    foreach($pay_opt as $po) {
        echo $po;
    }


?>
</div>
<div style="clear:both"></div>
<?php }; ?>