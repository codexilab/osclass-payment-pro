<form id="dialog-paypal" method="get" action="#" class="has-form-actions hide">
    <div class="form-horizontal">
        <div class="form-row">
            <?php /* <h3><?php _e('API or Standard Payments?', 'payment_pro'); ?></h3>
            <p>
                <?php _e('API payments give you more control over the payment process, it\'s required for digital goods & micropayments (Note: Not all countries are allowed to have digital goods & micropayments processes). On the other side standard payments are simple, less customizable but works everywhere.', 'payment_pro'); ?>.
                <br/>
                <?php _e('Micropayments offers a reduction on the fee to pay Paypal for orders under 4$ (or equivalent), around 5cents + 5% while standard payments have a fee around 30cents + 5%. Due the nature of OSClass is recommended to use micropayments, but we\'re aware that they\'re not available worldwide. Please check with Paypal the avalaibility of the service in your area.', 'payment_pro'); ?>.
                <br/>
            </p> */ ?>
            <h3><?php _e('Setting up your Paypal account for payments', 'payment_pro'); ?></h3>
            <p>
                <?php _e('You need Paypal API credentials', 'payment_pro'); ?>.
                <br/>
                <?php _e('(Optional) Tell Paypal where is your IPN file', 'payment_pro'); ?>
            </p>
            <h3><?php _e('How to obtain API credentials', 'payment_pro'); ?></h3>
            <p>
                <?php _e('In order to use the Paypal plugin you will need Paypal API credentials, you could obtain them for free following these steps', 'payment_pro'); ?>:
                <br/>
                <?php _e('Verify your account status. Go to your PayPal Profile under My Settings and verify that your Account Type is Premier or Business, or upgrade your account', 'payment_pro'); ?>
                <br/>
                <?php _e('Verify your API settings. Click on My Selling Tools. Click Selling Online and verify your API access. Click Update to view or set up your API signature and credentials', 'payment_pro'); ?>
            <h3><?php _e('Setting up your IPN (optional, but highly recommended)', 'payment_pro'); ?></h3>
            <p>
                <?php _e('Click Profile on the My Account tab', 'payment_pro'); ?>.
                <br/>
                <?php _e('Click Instant Payment Notification Preferences in the Selling Preferences column', 'payment_pro'); ?>.
                <br/>
                <?php printf(__("Click Choose IPN Settings to specify your listener’s URL and activate the listener (the URL is %s)", 'payment_pro'), osc_route_url('paypal-notify')); ?>.
            </p>
            </p>
        </div>
        <div class="form-actions">
            <div class="wrapper">
                <a class="btn" href="javascript:void(0);" onclick="$('#dialog-paypal').dialog('close');"><?php _e('Cancel'); ?></a>
            </div>
        </div>
    </div>
</form>
<script type="text/javascript" >
    $(document).ready(function(){
        $("#dialog-paypal").dialog({
            autoOpen: false,
            modal: true,
            width: '90%',
            title: '<?php echo osc_esc_js( __('Paypal help', 'payment_pro') ); ?>'
        });
    });
</script>