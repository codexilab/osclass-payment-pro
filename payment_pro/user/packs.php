<?php

    $wallet = ModelPaymentPro::newInstance()->getWallet(osc_logged_user_id());
    if(isset($wallet['i_amount']) && $wallet['i_amount']>0) {
        $credit_msg = sprintf(__('Your current credit is %s', 'payment_pro'), osc_format_price($wallet['i_amount'], payment_pro_currency()));
    } else {
        $credit_msg = __('Your wallet is empty. Buy some credits.', 'payment_pro');
    }

    $packs = ModelPaymentPro::newInstance()->getPacks();

?>

<h2><?php echo $credit_msg; ?></h2>
<?php if(count($packs)>0) {
    foreach($packs as $pack) { ?>
        <div class="payments-item">
            <h4><?php printf(__('Credit pack #%s', 'payment_pro'), str_pad($pack['pk_i_id'], 3, "0",STR_PAD_LEFT)); ?></h4>
            <h5><?php echo $pack['s_name']; ?></h5>
            <div><label><?php printf(__("Price: %s", 'payment_pro'), osc_format_price($pack['i_amount_cost'], osc_get_preference('currency', 'payment_pro'))); ?></label></div>
            <div><label><?php printf(__("Amount rewarded: %s", 'payment_pro'), osc_format_price($pack['i_amount'], payment_pro_currency())); ?></label></div>
            <ul class="payments-ul wallet-ul">
                <?php echo '<button class="no-uniform wallet-btn" id="wlt_' . $pack['pk_i_id'] . '" onclick="javascript:addPack(' . $pack['pk_i_id'] . ');">' . __('Buy this pack', 'payment_pro') . '</button>'; ?>
            </ul>
        </div>
        <div style="clear:both;"></div>

    <?php } ?>
    <script type="text/javascript">
            function addPack(id) {
                $("#wlt_" + id).attr('disabled', true);
                $.ajax({
                    type: "POST",
                    url: '<?php echo osc_ajax_plugin_url(PAYMENT_PRO_PLUGIN_FOLDER . 'ajax.php'); ?>&wlt=' + id,
                    dataType: 'json',
                    success: function(data){
                    if(data.error==0) {
                        window.location = '<?php echo osc_route_url('payment-pro-checkout'); ?>';
                        } else {
                        $("#wlt_" + id).attr('disabled', false);
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
<?php } else {
    echo '<div>' . __('There are no packs available to buy', 'payment_pro') . '</div>';
}?>
