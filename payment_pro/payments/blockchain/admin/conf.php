<h2 class="render-title separate-top"><?php _e('Blockchain settings', 'payment_pro'); ?> <span><a href="javascript:void(0);" onclick="$('#dialog-blockchain').dialog('open');" ><?php _e('help', 'payment_pro'); ?></a></span> <span style="font-size: 0.5em" ><a href="javascript:void(0);" onclick="$('.blockchain').toggle();" ><?php _e('Show options', 'payment_pro'); ?></a></span></h2>
<div class="form-row blockchain hide">
    <div class="form-label"><?php _e('Enable Blockchain'); ?></div>
    <div class="form-controls">
        <div class="form-label-checkbox">
            <label>
                <input type="checkbox" <?php echo (osc_get_preference('blockchain_enabled', 'payment_pro') ? 'checked="true"' : ''); ?> name="blockchain_enabled" value="1" />
                <?php _e('Enable Blockchain as a method of payment', 'payment_pro'); ?>
            </label>
        </div>
    </div>
</div>
<div class="form-row blockchain hide">
    <div class="form-label"><?php _e('Blockchain API KEY', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="blockchain_apikey" value="<?php echo payment_pro_decrypt(osc_get_preference('blockchain_apikey', 'payment_pro')); ?>" /></div>
</div>
<div class="form-row blockchain hide">
    <div class="form-label"><?php _e('Bitcoin XPUB address', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="blockchain_xpub" value="<?php echo osc_get_preference('blockchain_xpub', 'payment_pro'); ?>" /></div>
</div>
<div class="form-row blockchain hide">
    <div class="form-label"><?php _e('Bitcoin confirmations (default = 6)', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xsmall" name="blockchain_confirmations" value="<?php echo osc_get_preference('blockchain_confirmations', 'payment_pro'); ?>" /></div>
</div>
