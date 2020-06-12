<form id="dialog-bank" method="get" action="#" class="has-form-actions hide">
    <div class="form-horizontal">
        <div class="form-row">
            <h3><?php _e('How to use local bank or wire transfer', 'payment_pro'); ?></h3>
            <p><?php _e('The plugin assign a short code each time an user want to pay for something and instructs the user to make a payment to your bank account with the given code as a concept. <b>You are required</b> to manually check your bank account (daily, hourly or as frequent as you want to) for payments made with these special codes. Later on the admin panel you could select the payments and mark them as paid.', 'payment_pro'); ?></p>
            <p><?php _e('We suggest to only allow buying credit packs with bank account, as bank transfer are not for microtransactions.', 'payment_pro'); ?></p>
        </div>

        <div class="form-row">
            <h3><?php _e('Important', 'payment_pro'); ?></h3>
            <p><?php _e('Do not forget to include these special keywords in your message or important information will be missing:', 'payment_pro'); ?></p>
            <p>
                <?php _e('<b>{CODE}:</b> the unique code that the user needs to make the payment', 'payment_pro'); ?>
                <br/>
                <?php _e('<b>{AMOUNT}:</b> total amount that has to be paid', 'payment_pro'); ?>
                <br/>
                <?php _e('<b>{BANK_ACCOUNT}:</b> your bank account', 'payment_pro'); ?>
            </p>
        </div>

        <div class="form-row">
            <h3><?php _e('Notes', 'payment_pro'); ?></h3>
            <p><?php _e('Osclass nor this plugin has access to your bank account, that is the reason you need to manually check for payments and mark them as paid.', 'payment_pro'); ?></p>
        </div>
        <div class="form-actions">
            <div class="wrapper">
                <a class="btn" href="javascript:void(0);" onclick="$('#dialog-bank').dialog('close');"><?php _e('Cancel'); ?></a>
            </div>
        </div>
    </div>
</form>

<script type="text/javascript" >
    $("#dialog-bank").dialog({
        autoOpen: false,
        modal: true,
        width: '90%',
        title: '<?php echo osc_esc_js( __('Bank help', 'payment_pro') ); ?>'
    });
</script>