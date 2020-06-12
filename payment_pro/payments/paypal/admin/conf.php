<h2 class="render-title separate-top"><?php _e('Paypal settings', 'payment_pro'); ?> <span><a href="javascript:void(0);" onclick="$('#dialog-paypal').dialog('open');" ><?php _e('help', 'payment_pro'); ?></a></span> <span style="font-size: 0.5em" ><a href="javascript:void(0);" onclick="$('.paypal').toggle();" ><?php _e('Show options', 'payment_pro'); ?></a></span></h2>
<div class="form-row paypal hide">
    <div class="form-label"><?php _e('Enable Paypal'); ?></div>
    <div class="form-controls">
        <div class="form-label-checkbox">
            <label>
                <input type="checkbox" <?php echo (osc_get_preference('paypal_enabled', 'payment_pro') ? 'checked="true"' : ''); ?> name="paypal_enabled" value="1" />
                <?php _e('Enable Paypal as a method of payment', 'payment_pro'); ?>
            </label>
        </div>
    </div>
</div>
<div class="form-row paypal hide">
    <div class="form-label"><?php _e('Paypal API username', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="paypal_api_username" value="<?php echo payment_pro_decrypt(osc_get_preference('paypal_api_username', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row paypal hide">
    <div class="form-label"><?php _e('Paypal API password', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="paypal_api_password" value="<?php echo payment_pro_decrypt(osc_get_preference('paypal_api_password', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row paypal hide">
    <div class="form-label"><?php _e('Paypal API signature', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="paypal_api_signature" value="<?php echo payment_pro_decrypt(osc_get_preference('paypal_api_signature', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row paypal hide">
    <div class="form-label"><?php _e('Paypal email', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="paypal_email" value="<?php echo osc_get_preference('paypal_email', 'payment_pro'); ?>" /></div>
</div>
<?php /* <div class="form-row paypal hide">
    <div class="form-label"><?php _e('Standard payments'); ?></div>
    <div class="form-controls">
        <div class="form-label-checkbox">
            <label>
                <input type="checkbox" <?php echo (osc_get_preference('paypal_standard', 'payment_pro') ? 'checked="true"' : ''); ?> name="paypal_standard" value="1" />
                <?php _e('Use "Standard payments" (not recommended), only if your country/currency or account type is not supported by Express Checkout.', 'payment_pro'); ?>
            </label>
        </div>
    </div>
</div> */ ?>
<div class="form-row paypal hide">
    <div class="form-label"><?php _e('Paypal sandbox'); ?></div>
    <div class="form-controls">
        <div class="form-label-checkbox">
            <label>
                <input type="checkbox" <?php echo (osc_get_preference('paypal_sandbox', 'payment_pro') ? 'checked="true"' : ''); ?> name="paypal_sandbox" value="1" />
                <?php _e('Use Paypal sandbox to test everything is right before going live', 'payment_pro'); ?>
            </label>
        </div>
    </div>
</div>
