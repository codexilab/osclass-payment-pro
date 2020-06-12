<h2 class="render-title separate-top"><?php _e('Pagseguro settings', 'payment_pro'); ?> <span><a href="javascript:void(0);" onclick="$('#dialog-pagseguro').dialog('open');" ><?php _e('help', 'payment_pro'); ?></a></span> <span style="font-size: 0.5em" ><a href="javascript:void(0);" onclick="$('.pagseguro').toggle();" ><?php _e('Show options', 'payment_pro'); ?></a></span></h2>
<div class="form-row pagseguro hide">
    <div class="form-label"><?php _e('Enable Pagseguro'); ?></div>
    <div class="form-controls">
        <div class="form-label-checkbox">
            <label>
                <input type="checkbox" <?php echo (osc_get_preference('pagseguro_enabled', 'payment_pro') ? 'checked="true"' : ''); ?> name="pagseguro_enabled" value="1" />
                <?php _e('Enable Pagseguro as a method of payment', 'payment_pro'); ?>
            </label>
        </div>
    </div>
    <?php if(osc_get_preference('currency', 'payment_pro')!="BRL") { ?>
    <span class="help-box">
        <?php _e('<b>Important:</b> Pagseguro only works with "BRL" as currency.', 'payment_pro'); ?>
    </span>
    <?php }; ?>
</div>
<div class="form-row pagseguro hide">
    <div class="form-label"><?php _e('Pagseguro e-mail', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="pagseguro_email" value="<?php echo osc_get_preference('pagseguro_email', 'payment_pro'); ?>" /></div>
</div>
<div class="form-row pagseguro hide">
    <div class="form-label"><?php _e('Token', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="pagseguro_token" value="<?php echo payment_pro_decrypt(osc_get_preference('pagseguro_token', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row pagseguro hide">
    <div class="form-label"><?php _e('AppId', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="pagseguro_appid" value="<?php echo payment_pro_decrypt(osc_get_preference('pagseguro_appid', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row pagseguro hide">
    <div class="form-label"><?php _e('AppKey', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="pagseguro_appkey" value="<?php echo payment_pro_decrypt(osc_get_preference('pagseguro_appkey', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row pagseguro hide">
    <div class="form-label"><?php _e('Token (sandbox)', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="pagseguro_sandbox_token" value="<?php echo payment_pro_decrypt(osc_get_preference('pagseguro_sandbox_token', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row pagseguro hide">
    <div class="form-label"><?php _e('AppId (sandbox)', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="pagseguro_sandbox_appid" value="<?php echo payment_pro_decrypt(osc_get_preference('pagseguro_sandbox_appid', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row pagseguro hide">
    <div class="form-label"><?php _e('AppKey (sandbox)', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="pagseguro_sandbox_appkey" value="<?php echo payment_pro_decrypt(osc_get_preference('pagseguro_sandbox_appkey', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row pagseguro hide">
    <div class="form-label"><?php _e('Pagseguro sandbox'); ?></div>
    <div class="form-controls">
        <div class="form-label-checkbox">
            <label>
                <input type="checkbox" <?php echo (osc_get_preference('pagseguro_sandbox', 'payment_pro') ? 'checked="true"' : ''); ?> name="pagseguro_sandbox" value="1" />
                <?php _e('Use Pagseguro sandbox to test everything is right before going live', 'payment_pro'); ?>
            </label>
        </div>
    </div>
</div>
