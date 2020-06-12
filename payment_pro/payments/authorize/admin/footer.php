<form id="dialog-authorize" method="get" action="#" class="has-form-actions hide">
    <div class="form-horizontal">
        <div class="form-row">
            <p><?php printf(__('You need an <a href="%s" target="_blank">Authorize.Net account</a>. Once created, access to your panel and copy the API login ID and API transaction key here.', 'payment_pro'), 'http://reseller.authorize.net/application/?id=5562279'); ?></p>
        </div>
        <div class="form-actions">
            <div class="wrapper">
                <a class="btn" href="javascript:void(0);" onclick="$('#dialog-authorize').dialog('close');"><?php _e('Cancel'); ?></a>
            </div>
        </div>
    </div>
</form>
<script type="text/javascript" >
    $(document).ready(function(){
        $("#dialog-authorize").dialog({
            autoOpen: false,
            modal: true,
            width: '90%',
            title: '<?php echo osc_esc_js( __('Authorize.Net help', 'payment_pro') ); ?>'
        });
    });
</script>