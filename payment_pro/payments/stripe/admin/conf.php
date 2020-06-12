<h2 class="render-title separate-top"><?php _e('Stripe settings', 'payment_pro'); ?> <span><a href="javascript:void(0);" onclick="$('#dialog-stripe').dialog('open');" ><?php _e('help', 'payment_pro'); ?></a></span> <span style="font-size: 0.5em" ><a href="javascript:void(0);" onclick="$('.stripe').toggle();" ><?php _e('Show options', 'payment_pro'); ?></a></span></h2>
<div class="form-row stripe hide">
    <div class="form-label"><?php _e('Enable Stripe'); ?></div>
    <div class="form-controls">
        <div class="form-label-checkbox">
            <label>
                <input type="checkbox" <?php echo (osc_get_preference('stripe_enabled', 'payment_pro') ? 'checked="true"' : ''); ?> name="stripe_enabled" value="1" />
                <?php _e('Enable Stripe as a method of payment', 'payment_pro'); ?>
            </label>
        </div>
    </div>
</div>
<div class="form-row stripe hide">
    <div class="form-label"><?php _e('Enable Sandbox'); ?></div>
    <div class="form-controls">
        <div class="form-label-checkbox">
            <label>
                <input type="checkbox" <?php echo (osc_get_preference('stripe_sandbox', 'payment_pro') ? 'checked="true"' : ''); ?> name="stripe_sandbox" value="1" />
                <?php _e('Enable sandbox for development testing', 'payment_pro'); ?>
            </label>
        </div>
    </div>
</div>
<div class="form-row stripe hide">
    <div class="form-label"><?php _e('Accept Bitcoins'); ?></div>
    <div class="form-controls">
        <div class="form-label-checkbox">
            <label>
                <input type="checkbox" <?php echo (osc_get_preference('stripe_bitcoin', 'payment_pro') ? 'checked="true"' : ''); ?> name="stripe_bitcoin" value="1" />
                <?php _e('To process live Bitcoin payments, you need to <a href="https://dashboard.stripe.com/account/payments/settings">enable the live Bitcoin API on your account</a>', 'payment_pro'); ?>
            </label>
        </div>
    </div>
</div>
<div class="form-row stripe hide">
    <div class="form-label"><?php _e('Stripe secret key', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="stripe_secret_key" value="<?php echo payment_pro_decrypt(osc_get_preference('stripe_secret_key', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row stripe hide">
    <div class="form-label"><?php _e('Stripe public key', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="stripe_public_key" value="<?php echo payment_pro_decrypt(osc_get_preference('stripe_public_key', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row stripe hide">
    <div class="form-label"><?php _e('Stripe secret key (test)', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="stripe_secret_key_test" value="<?php echo payment_pro_decrypt(osc_get_preference('stripe_secret_key_test', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row stripe hide">
    <div class="form-label"><?php _e('Stripe public key (test)', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="stripe_public_key_test" value="<?php echo payment_pro_decrypt(osc_get_preference('stripe_public_key_test', 'payment_pro')); ?>" /></div>
</div>