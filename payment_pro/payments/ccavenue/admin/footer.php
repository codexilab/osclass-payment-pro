<form id="dialog-ccavenue" method="get" action="#" class="has-form-actions hide">
    <div class="form-horizontal">
        <div class="form-row">
            <h3><?php _e('Learn more about Ccavenue', 'payment_pro'); ?></h3>
            <p><?php _e('To successfully integrate you need to follow the below steps:', 'payment_pro'); ?></p>
            <p><?php _e('Go to the <b>Settings and Options</b> link in the top menu and click on the <b>Generate Working Key</b> link.', 'payment_pro'); ?></p>
            <ul>
                <li>
                    <?php _e('Please login to your CCAvenue account', 'paymet_pro'); ?>
                </li>
                <li>
                    <?php _e('Go to <b>Settings & Options</b>', 'paymet_pro'); ?>
                </li>
                <li>
                    <?php _e('Click the <b>Get Working Key</b>', 'paymet_pro'); ?>
                </li>
                <li>
                    <?php echo sprintf(__('In Osclass admin panel, go to <b><a href="%s">Payment options</a></b> and set the <b>Working key</b>', 'paymet_pro'), osc_route_admin_url('payment-pro-admin-conf') ); ?>
                </li>
            </ul>

            <p><?php _e('Merchant id', 'payment_pro'); ?>:  <?php _e('This ID is generated for you at the time of activation of your site on Ccavenue. You can get your <b>Ccavenue Merchant Id</b> at <b>Generate Key</b> of <b>Settings & Options</b> section.', 'payment_pro'); ?>
            </p>

        </div>
        <div class="form-actions">
            <div class="wrapper">
                <a class="btn" href="javascript:void(0);" onclick="$('#dialog-ccavenue').dialog('close');"><?php _e('Cancel'); ?></a>
            </div>
        </div>
    </div>
</form>

<script type="text/javascript" >
    $("#dialog-ccavenue").dialog({
        autoOpen: false,
        modal: true,
        width: '90%',
        title: '<?php echo osc_esc_js( __('Ccavenue help', 'payment') ); ?>'
    });
</script>


