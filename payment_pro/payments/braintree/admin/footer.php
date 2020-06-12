<form id="dialog-braintree" method="get" action="#" class="has-form-actions hide">
    <div class="form-horizontal">
        <div class="form-row">
            <h3><?php _e('Learn more about Braintree', 'payment_pro'); ?></h3>
            <p>
                <?php printf(__('Braintree official website: %s', 'payment'), '<a href="https://www.braintreepayments.com/">https://www.braintreepayments.com/</a>'); ?>.
                <br/>
                <?php printf(__('Getting started: %s', 'payment'), '<a href="https://www.braintreepayments.com/tour/payment-gateway">https://www.braintreepayments.com/tour/payment-gateway</a>'); ?>.
                <br/>
            </p>
        </div>
        <div class="form-actions">
            <div class="wrapper">
                <a class="btn" href="javascript:void(0);" onclick="$('#dialog-braintree').dialog('close');"><?php _e('Cancel'); ?></a>
            </div>
        </div>
    </div>
</form>

<script type="text/javascript" >
    $("#dialog-braintree").dialog({
        autoOpen: false,
        modal: true,
        width: '90%',
        title: '<?php echo osc_esc_js( __('Braintree help', 'payment') ); ?>'
    });
</script>