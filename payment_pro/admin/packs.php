<?php if ( ! defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

$amount_cost = Params::getParam('amount_cost')<0?1:Params::getParam('amount_cost');

if(Params::getParam('plugin_action')=='add_pack') {
    if (Params::getParam('amount_cost') != '' && Params::getParam('amount') != '' && Params::getParam('name') != '') {
        ModelPaymentPro::newInstance()->insertPack(
            $amount_cost*1000000,
            Params::getParam('amount')*1000000,
            Params::getParam('name'));
        osc_add_flash_ok_message(__('Pack added correctly', 'payment_pro'), 'admin');
    } else {
        osc_add_flash_error_message(__('All fields are required', 'payment_pro'), 'admin');
    }
    ob_get_clean();
    osc_redirect_to(osc_route_admin_url('payment-pro-admin-packs'));
} else if(Params::getParam('plugin_action')=='edit_pack') {
    if(Params::getParam('amount_cost')!='' && Params::getParam('amount')!='' && Params::getParam('name')!='') {
        ModelPaymentPro::newInstance()->updatePack(
            Params::getParam('packId'),
            $amount_cost*1000000,
            Params::getParam('amount')*1000000,
            Params::getParam('name'));
        osc_add_flash_ok_message(__('Pack updated correctly', 'payment_pro'), 'admin');
    } else {
        osc_add_flash_error_message(__('All fields are required', 'payment_pro'), 'admin');
    }
    ob_get_clean();
    osc_redirect_to(osc_route_admin_url('payment-pro-admin-packs'));
} else if(Params::getParam('plugin_action')=='delete') {
    if(Params::getParam('packId')!='') {
        ModelPaymentPro::newInstance()->deletePack(Params::getParam('packId'));
        osc_add_flash_ok_message(__('Pack deleted', 'payment_pro'), 'admin');
    } else {
        osc_add_flash_error_message(__('Ops! something went wrong, pack was not deleted', 'payment_pro'), 'admin');
    }
    ob_get_clean();
    osc_redirect_to(osc_route_admin_url('payment-pro-admin-packs'));
}


$packs = ModelPaymentPro::newInstance()->getPacks();

?>
<style type="text/css">
    .payment-pro-pub {
        background-color: #d8e6ff;
    }
    .payment-pro-prm {
        background-color: #d8e6ff;
    }
</style>
<script type="text/javascript" >
    $(document).ready(function(){
        $("#dialog-new").dialog({
            autoOpen: false,
            width: "500px",
            modal: true,
            title: '<?php echo osc_esc_js( __('Add credit pack', 'payment_pro') ); ?>'
        });
        $("#dialog-delete").dialog({
            autoOpen: false,
            width: "500px",
            modal: true,
            title: '<?php echo osc_esc_js( __('Delete pack', 'payment_pro') ); ?>'
        });
    });
    function new_pack() {
        $("#plugin_action").prop("value", "add_pack");
        $('#dialog-new').dialog('open');
    };
    function edit_pack(id, amount_cost, amount, name) {
        $("#plugin_action").prop("value", "edit_pack");
        $('#packId').prop('value', id);
        $('#amount_cost').prop('value', amount_cost/1000000);
        $('#amount').prop('value', amount/1000000);
        $('#name').prop('value', name);
        $('#dialog-new').dialog('open');
    };
    function delete_pack(id) {
        $('#delete_pack').prop('value', id);
        $('#dialog-delete').dialog('open');
    };
</script>
<div style="clear:both;">
    <div style="float: left; width: 100%;">
        <fieldset>
            <h3><?php _e('Setting up your credits packs', 'payment_pro'); ?> <span style="font-size: xx-small; color:red;"><?php if(osc_get_preference('allow_wallet', 'payment_pro')!=1) { _e('Warning! Wallets and packs are disabled', 'payment_pro'); }; ?></span></h3>
            <p>
                <?php _e('Credit packs are a great option to offer to your users, it allows you to offer some discounts for bulk purchase and at the same time reduce the number of transaction with third payment processors (which usually charges you in a per transaction basis)', 'payment_pro'); ?>
            </p>
        </fieldset>
    </div>
    <div style="clear: both;"></div>
</div>

<div id="new_prices" >
    <div id="general-setting">
        <div id="general-settings">
            <h2 class="render-title"><?php _e('Set pack prices', 'payment_pro'); ?> <span><a id="new-price" href="javascript:new_pack();" ><?php _e('Add new pack', 'payment_pro'); ?></a></span></h2>
            <ul id="error_list"></ul>
            <form name="payment_pro_form" action="#" method="post">
                <fieldset>
                    <div class="form-horizontal">
                        <?php foreach($packs as $pack) { ?>
                            <div class="form-row">
                                <div class="form-controls">
                                    <span class="payment-pro-pub" ><?php printf(__('PACK #%s'), str_pad($pack['pk_i_id'], 3, "0", STR_PAD_LEFT)); ?></span>
                                    <span class="payment-pro-pub" ><?php printf(__('Cost: %s'), osc_format_price($pack['i_amount_cost'], osc_get_preference('currency', 'payment_pro'))); ?></span>
                                    <span class="payment-pro-prm" ><?php printf(__('Credits: %s'), osc_format_price($pack['i_amount'], payment_pro_currency())); ?></span>
                                    <span class="payment-pro-prm" ><?php printf(__('Name: %s'), $pack['s_name']); ?></span>
                                    <span><a href="javascript:edit_pack(<?php echo $pack['pk_i_id'].", ".$pack['i_amount_cost'].", ".$pack['i_amount'].",'".$pack['s_name']."'"; ?>);" ><?php _e('edit', 'payment_pro'); ?></a></span>
                                    <span><a href="javascript:delete_pack(<?php echo $pack['pk_i_id']; ?>);" ><?php _e('delete', 'payment_pro'); ?></a></span>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="clear"></div>
                </fieldset>
            </form>
        </div>
    </div>
</div>
<form id="dialog-new" method="post" action="<?php echo osc_admin_base_url(true); ?>" class="has-form-actions hide">
    <input type="hidden" name="page" value="plugins" />
    <input type="hidden" name="action" value="renderplugin" />
    <input type="hidden" name="route" value="payment-pro-admin-packs" />
    <input type="hidden" name="plugin_action" id="plugin_action" value="add_pack" />
    <input type="hidden" name="packId" id="packId" value="" />
    <div class="form-horizontal">
        <div class="form-row">
            <div class="form-label"><?php _e('Pack cost (what the user pays)', 'payment_pro'); ?></div>
            <div class="form-controls"><input type="text" id="amount_cost" name="amount_cost" value="" placeholder="10.0" /> <?php echo osc_get_preference('currency', 'payment_pro'); ?></div>
        </div>
        <div class="form-row">
            <div class="form-label"><?php _e('Credit rewarded (what the user received as credits)', 'payment_pro'); ?></div>
            <div class="form-controls"><input type="text" id="amount" name="amount" value="" placeholder="12.0" /> <?php echo payment_pro_currency(); ?></div>
        </div>
        <div class="form-row">
            <div class="form-label"><?php _e('Name or short description', 'payment_pro'); ?></div>
            <div class="form-controls"><input type="text" id="name" name="name" value="" placeholder="Pack premium credit" /></div>
        </div>
        <div class="form-actions">
            <div class="wrapper">
                <a class="btn" href="javascript:void(0);" onclick="$('#dialog-new').dialog('close');"><?php _e('Cancel', 'payment_pro'); ?></a>
                <input id="payment-pro-submit" type="submit" value="<?php echo osc_esc_html( __('Add', 'payment_pro')); ?>" class="btn btn-red" />
            </div>
        </div>
    </div>
</form>
<form id="dialog-delete" method="post" action="<?php echo osc_admin_base_url(true); ?>" class="has-form-actions hide">
    <input type="hidden" name="page" value="plugins" />
    <input type="hidden" name="action" value="renderplugin" />
    <input type="hidden" name="route" value="payment-pro-admin-packs" />
    <input type="hidden" name="plugin_action" value="delete" />
    <input type="hidden" name="packId" id="delete_pack" value="" />
    <div class="form-horizontal">
        <div class="form-row">
            <?php _e('This will delete the pack, credits in users account will not be deleted. Do you want to continue?', 'payment_pro'); ?>
        </div>
        <div class="form-actions">
            <div class="wrapper">
                <a class="btn" href="javascript:void(0);" onclick="$('#dialog-delete').dialog('close');"><?php _e('Cancel', 'payment_pro'); ?></a>
                <input id="price-delete-submit" type="submit" value="<?php echo osc_esc_html( __('Delete', 'payment_pro')); ?>" class="btn btn-red" />
            </div>
        </div>
    </div>
</form>
