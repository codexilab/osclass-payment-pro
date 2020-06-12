<h2 class="render-title separate-top"><?php _e('Ccavenue settings', 'payment_pro'); ?> <span><a href="javascript:void(0);" onclick="$('#dialog-ccavenue').dialog('open');" ><?php _e('help', 'payment_pro'); ?></a></span> <span style="font-size: 0.5em" ><a href="javascript:void(0);" onclick="$('.ccavenue').toggle();" ><?php _e('Show options', 'payment_pro'); ?></a></span></h2>
<div class="form-row ccavenue hide">
    <div class="form-label"><?php _e('Enable Ccavenue'); ?></div>
    <div class="form-controls">
        <div class="form-label-checkbox">
            <label>
                <input type="checkbox" <?php echo (osc_get_preference('ccavenue_enabled', 'payment_pro') ? 'checked="true"' : ''); ?> name="ccavenue_enabled" value="1" />
                <?php _e('Enable Ccavenue as a method of payment', 'payment_pro'); ?>
            </label>
        </div>
    </div>
</div> 
<div class="form-row ccavenue hide">
    <div class="form-label"><?php _e('Ccavenue merchant id', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="ccavenue_merchant_id" value="<?php echo payment_pro_decrypt(osc_get_preference('ccavenue_merchant_id', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row ccavenue hide">
    <div class="form-label"><?php _e('Ccavenue access code', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="ccavenue_access_code" value="<?php echo payment_pro_decrypt(osc_get_preference('ccavenue_access_code', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row ccavenue hide">
    <div class="form-label"><?php _e('Ccavenue working key', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="ccavenue_working_key" value="<?php echo payment_pro_decrypt(osc_get_preference('ccavenue_working_key', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row ccavenue hide">
    <div class="form-label"><?php _e('Ccavenue access code (sandbox)', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="ccavenue_sandbox_access_code" value="<?php echo payment_pro_decrypt(osc_get_preference('ccavenue_sandbox_access_code', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row ccavenue hide">
    <div class="form-label"><?php _e('Ccavenue working key (sandbox)', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="ccavenue_sandbox_working_key" value="<?php echo payment_pro_decrypt(osc_get_preference('ccavenue_sandbox_working_key', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row ccavenue hide">
    <div class="form-label"><?php _e('Enable sandbox'); ?></div>
    <div class="form-controls">
        <div class="form-label-checkbox">
            <label>
                <input type="checkbox" <?php echo (osc_get_preference('ccavenue_sandbox', 'payment_pro') ? 'checked="true"' : ''); ?> name="ccavenue_sandbox" value="1" />
                <?php _e('Enable Ccavenue sandbox, to test your payments', 'payment_pro'); ?>
            </label>
        </div>
    </div>
</div>
