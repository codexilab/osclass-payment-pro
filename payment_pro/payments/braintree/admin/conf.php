<h2 class="render-title separate-top"><?php _e('Braintree settings', 'payment_pro'); ?> <span><a href="javascript:void(0);" onclick="$('#dialog-braintree').dialog('open');" ><?php _e('help', 'payment_pro'); ?></a></span> <span style="font-size: 0.5em" ><a href="javascript:void(0);" onclick="$('.braintree').toggle();" ><?php _e('Show options', 'payment_pro'); ?></a></span></h2>
<div class="form-row braintree hide">
    <div class="form-label"><?php _e('Enable Braintree'); ?></div>
    <div class="form-controls">
        <div class="form-label-checkbox">
            <label>
                <input type="checkbox" <?php echo (osc_get_preference('braintree_enabled', 'payment_pro') ? 'checked="true"' : ''); ?> name="braintree_enabled" value="1" />
                <?php _e('Enable Braintree as a method of payment', 'payment_pro'); ?>
            </label>
        </div>
    </div>
</div>
<div class="form-row braintree hide">
    <div class="form-label"><?php _e('Enable Sandbox'); ?></div>
    <div class="form-controls">
        <div class="form-label-checkbox">
            <label>
                <input type="checkbox" <?php echo (osc_get_preference('braintree_sandbox', 'payment_pro') == 'sandbox' ? 'checked="true"' : ''); ?> name="braintree_sandbox" value="sandbox" />
                <?php _e('Enable sandbox for development testing', 'payment_pro'); ?>
            </label>
        </div>
    </div>
</div>
<div class="form-row braintree hide">
    <div class="form-label"><?php _e('Braintree merchant id', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="braintree_merchant_id" value="<?php echo payment_pro_decrypt(osc_get_preference('braintree_merchant_id', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row braintree hide">
    <div class="form-label"><?php _e('Braintree public key', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="braintree_public_key" value="<?php echo payment_pro_decrypt(osc_get_preference('braintree_public_key', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row braintree hide">
    <div class="form-label"><?php _e('Braintree private key', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="braintree_private_key" value="<?php echo payment_pro_decrypt(osc_get_preference('braintree_private_key', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row braintree hide">
    <div class="form-label"><?php _e('Braintree encryption key', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="braintree_encryption_key" value="<?php echo payment_pro_decrypt(osc_get_preference('braintree_encryption_key',
            'payment_pro')); ?>" /></div>
</div>