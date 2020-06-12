<h2 class="render-title separate-top"><?php _e('Bank transfer settings', 'payment_pro'); ?> <span><a href="javascript:void(0);" onclick="$('#dialog-bank').dialog('open');" ><?php _e('help', 'payment_pro'); ?></a></span> <span style="font-size: 0.5em" ><a href="javascript:void(0);" onclick="$('.bank').toggle();" ><?php _e('Show options', 'payment_pro'); ?></a></span></h2>
<div class="form-row bank hide">
    <p><?php _e('<b>IMPORTANT:</b> Read the help before enabling bank payments', 'payment_pro'); ?></p>
</div>
<div class="form-row bank hide">
    <div class="form-label"><?php _e('Enable Bank'); ?></div>
    <div class="form-controls">
        <div class="form-label-checkbox">
            <label>
                <input type="checkbox" <?php echo (osc_get_preference('bank_enabled', 'payment_pro') ? 'checked="true"' : ''); ?> name="bank_enabled" value="1" />
                <?php _e('Enable bank transfer as a method of payment', 'payment_pro'); ?>
            </label>
        </div>
    </div>
</div>
<div class="form-row bank hide">
    <div class="form-label"><?php _e('Bank Account', 'payment_pro'); ?></div>
    <div class="form-controls"><input type="text" class="xlarge" name="bank_account" value="<?php echo osc_get_preference('bank_account', 'payment_pro'); ?>" /></div>
</div>
<div class="form-row bank hide">
    <div class="form-label"><?php _e('Payment message', 'payment_pro'); ?></div>
    <div class="form-controls"><textarea id="bank_msg" name="bank_msg" rows="8" style="width:400px;" ><?php echo osc_get_preference('bank_msg', 'payment_pro'); ?></textarea></div>
    <span class="help-box">
        <?php _e('<b>Important:</b> do not forget to include these special keywords in your message or important information will be missing:', 'payment_pro'); ?>
        <br/>
        <?php _e('<b>{CODE}:</b> the unique code that the user needs to make the payment', 'payment_pro'); ?>
        <br/>
        <?php _e('<b>{AMOUNT}:</b> total amount that has to be paid', 'payment_pro'); ?>
        <br/>
        <?php _e('<b>{BANK_ACCOUNT}:</b> your bank account', 'payment_pro'); ?>
    </span>
</div>
<div class="form-row bank hide">
    <div class="form-label"><?php _e('Only to buy pack'); ?></div>
    <div class="form-controls">
        <div class="form-label-checkbox">
            <label>
                <input type="checkbox" <?php echo (osc_get_preference('bank_only_packs', 'payment_pro') ? 'checked="true"' : ''); ?> name="bank_only_packs" value="1" />
                <?php _e('Allow bank transfers only for buying packs. Banks transfers have to be confirmed manually, takes a lot of time and it may not be worthy to allow small payments to be made using wire transfers.', 'payment_pro'); ?>
                <span style="font-size: x-small; color:red;"><?php if(osc_get_preference('allow_wallet', 'payment_pro')!=1) { _e('Warning! Wallets and packs are disabled', 'payment_pro'); }; ?></span>
            </label>
        </div>
    </div>
</div>
