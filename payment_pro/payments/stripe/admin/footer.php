<form id="dialog-stripe" method="get" action="#" class="has-form-actions hide">
    <div class="form-horizontal">
        <div class="form-row">
            <h3><?php _e('Learn more about Stripe', 'payment_pro'); ?></h3>
            <p>
                <?php printf(__('Stripe official website: %s', 'payment_pro'), '<a href="https://stripe.com/">https://stripe.com/</a>'); ?>.
                <br/>
                <?php printf(__('Getting started: %s', 'payment_pro'), '<a href="https://stripe.com/docs">https://stripe.com/docs</a>'); ?>.
                <br/>
            </p>
        </div>
        <div class="form-actions">
            <div class="wrapper">
                <a class="btn" href="javascript:void(0);" onclick="$('#dialog-stripe').dialog('close');"><?php _e('Cancel'); ?></a>
            </div>
        </div>
    </div>
</form>

<script type="text/javascript" >
    $(document).ready(function(){
        $("#dialog-stripe").dialog({
            autoOpen: false,
            modal: true,
            width: '90%',
            title: '<?php echo osc_esc_js( __('Stripe help', 'payment_pro') ); ?>'
        });
    });
</script>