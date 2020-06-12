<h2 class="render-title separate-top"><?php _e('Authorize.Net settings', 'payment_pro'); ?> <span><a href="javascript:void(0);" onclick="$('#dialog-authorize').dialog('open');" ><?php _e('help', 'payment_pro'); ?></a></span> <span style="font-size: 0.5em" ><a href="javascript:void(0);" onclick="$('.authorize').toggle();" ><?php _e('Show options', 'payment_pro'); ?></a></span></h2>
<div class="form-row authorize hide">
    <div class="form-label"><?php _e('Don\'t have an account?', 'payment_pro'); ?></div>
    <div class="form-controls">
        <label>
            <?php printf(__('Create an <a href="%s" target="_blank">Authorize.Net account</a>', 'payment_pro'), 'http://reseller.authorize.net/application/?id=5562279'); ?>
        </label>
    </div>
</div>
<div class="form-row authorize hide">
    <div class="form-label"><?php _e('Enable Authorize.Net', 'payment_pro'); ?></div>
    <div class="form-controls">
        <div class="form-label-checkbox">
            <label>
                <input type="checkbox" <?php echo (osc_get_preference('authorize_enabled', 'payment_pro') ? 'checked="true"' : ''); ?> name="authorize_enabled" value="1" />
                <?php _e('Enable Authorize.Net as a method of payment', 'payment_pro'); ?>
            </label>
        </div>
    </div>
</div>
<div class="form-row authorize hide">
    <div class="form-label"><?php _e('Authorize.Net API login ID', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="authorize_api_login_id" value="<?php echo payment_pro_decrypt(osc_get_preference('authorize_api_login_id', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row authorize hide">
    <div class="form-label"><?php _e('Authorize.Net API transaction key', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="authorize_api_tx_key" value="<?php echo payment_pro_decrypt(osc_get_preference('authorize_api_tx_key', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row authorize hide">
    <div class="form-label"><?php _e('Authorize.Net sandbox'); ?></div>
    <div class="form-controls">
        <div class="form-label-checkbox">
            <label>
                <input type="checkbox" <?php echo (osc_get_preference('authorize_sandbox', 'payment_pro') ? 'checked="true"' : ''); ?> name="authorize_sandbox" value="1" />
                <?php _e('Use Authorize.Net sandbox to test everything is right before going live. <b>You need to modify your TEST/LIVE status at <a href="https://sandbox.authorize.net/">Authorize.Net</a> too.</b>', 'payment_pro'); ?>
            </label>
        </div>
    </div>
</div>
