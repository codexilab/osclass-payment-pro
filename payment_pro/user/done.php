<div class="payments-done" style='text-align: center;width: 100%; '>
<div style="margin:2em;font-size: 2em;line-height: 1.2em;">
    <p><?php _e('Want to publish more listings?', 'payment_pro'); ?></p>
</div>

<a class="ui-button ui-button-main" href="<?php echo osc_item_post_url_in_category(); ?>"><?php _e("Publish another listing", 'payment_pro'); ?></a>
<a class="ui-button ui-button-blacktext" href="<?php echo osc_base_url(); ?>"><?php _e('Continue browsing', 'payment_pro'); ?></a>
</div>
<?php osc_run_hook('payment_pro_done_page', Params::getParam('tx'));