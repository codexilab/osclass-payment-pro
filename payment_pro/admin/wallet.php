<?php if ( ! defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

    if(Params::getParam('plugin_action')=='done') {
        ob_get_clean();

        if(Params::getParam('userId')!='') {
            $success = ModelPaymentPro::newInstance()->addWallet(Params::getParam('userId'), Params::getParam('amount'));
            $wallet = ModelPaymentPro::newInstance()->getWallet(Params::getParam('userId'));
            $user = User::newInstance()->findByPrimaryKey(Params::getParam('userId'));
            if($success) {
                osc_add_flash_ok_message(sprintf(__('Amount added to the user\'s credit. Current balance: %s (User #%s - %s - %s)', 'payment_pro'), $wallet['formatted_amount'], @$user['pk_i_id'], @$user['s_name'], @$user['s_email']), 'admin');
            } else {
                osc_add_flash_error_message(sprintf(__('Error adding the credit: Current balance: %s (User #%s - %s - %s)', 'payment_pro'), $wallet['formatted_amount'], @$user['pk_i_id'], @$user['s_name'], @$user['s_email']), 'admin');
            }
        } else {
            osc_add_flash_error_message(__('No user selected', 'payment_pro'), 'admin');
        }

        osc_redirect_to(osc_route_admin_url('payment-pro-admin-wallet'));
    }
?>

<div id="general-setting">
    <div id="general-settings" style="float:left; width: 50%;">
        <h2 class="render-title"><?php _e('Add credit to users', 'payment_pro'); ?></h2>
        <ul id="error_list"></ul>
        <form name="payment_pro_form" action="<?php echo osc_admin_base_url(true); ?>" method="post">
            <input type="hidden" name="page" value="plugins" />
            <input type="hidden" name="action" value="renderplugin" />
            <input type="hidden" name="route" value="payment-pro-admin-wallet" />
            <input type="hidden" name="userId" value="" id="userId"/>
            <input type="hidden" name="plugin_action" value="done" />
            <fieldset>
                <div class="form-horizontal">
                    <div class="form-row">
                        <div class="form-label"><?php _e('Start typing to search an user (by email or name)', 'payment_pro'); ?></div>
                        <div class="form-controls"><input type="text" class="xlarge" id="user" name="user" value="" /></div>
                    </div>
                    <div class="form-row">
                        <div class="form-label"><?php _e('Credit', 'payment_pro'); ?></div>
                        <div class="form-controls"><input type="text" class="xlarge" name="amount" value="1" /> <?php echo payment_pro_currency(); ?></div>
                    </div>

                    <div class="clear"></div>
                    <div class="form-actions">
                        <input type="submit" id="save_changes" value="<?php echo osc_esc_html( __('Add credit', 'payment_pro') ); ?>" class="btn btn-submit" />
                    </div>
                </div>
            </fieldset>
        </form>
    </div>

    <div style="float:right; width: 50%;">
        <h2 class="render-title"><?php _e('User info', 'payment_pro'); ?></h2>
        <div class="form-horizontal">
            <div class="form-row">
                <div class="form-label"><?php _e('Name', 'payment_pro'); ?></div>
                <div class="form-controls"><input type="text" class="xlarge" id="user_name" name="user_name" value="" readonly="readonly"/></div>
            </div>
            <div class="form-row">
                <div class="form-label"><?php _e('E-mail', 'payment_pro'); ?></div>
                <div class="form-controls"><input type="text" class="xlarge" id="user_email" name="user_email" value="" readonly="readonly"/></div>
            </div>
            <div class="form-row">
                <div class="form-label"><?php _e('Current balance', 'payment_pro'); ?></div>
                <div class="form-controls"><input type="text" class="xlarge" id="user_balance" name="user_balance" value="" readonly="readonly"/> <?php echo payment_pro_currency(); ?></div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        // users autocomplete
        $('input[name="user"]').attr("autocomplete", "off");
        $('#user').autocomplete({
            source: "<?php echo osc_admin_base_url(true); ?>?page=ajax&action=userajax",
            minLength: 0,
            select: function (event, ui) {
                if (ui.item.id == '') {
                    return false;
                } else {
                    $.getJSON(
                        "<?php echo osc_admin_base_url(true); ?>?page=ajax&action=runhook&hook=payment_pro_userinfo&id=" + ui.item.id,
                        {"s_username": $("#s_username").attr("value")},
                        function(data){
                            if(data.error==0) {
                                $("#user_name").attr("value", data.user.s_name);
                                $("#user_email").attr("value", data.user.s_email);
                                $("#user_balance").attr("value", data.wallet.formatted_amount);
                            } else {
                                $("#user_name").attr("value", "<?php _e("Error getting data", "payment_pro"); ?>");
                                $("#user_email").attr("value", "<?php _e("Error getting data", "payment_pro"); ?>");
                                $("#user_balance").attr("value", "<?php _e("Error getting data", "payment_pro"); ?>");
                            }
                        }
                    );
                }
                $('#userId').val(ui.item.id);
            },
            search: function () {
                $('#userId').val('');
            }
        });

        $('.ui-autocomplete').css('zIndex', 10000);
    });
</script>