<?php

    $itemsPerPage = (Params::getParam('itemsPerPage')!='')?Params::getParam('itemsPerPage'):10;
    $page         = (Params::getParam('iPage') > 0) ? Params::getParam('iPage') -1 : 0;
    $itemType     = 'all';
    $total_items  = Item::newInstance()->countItemTypesByUserID(osc_logged_user_id(), $itemType);
    $total_pages  = ceil($total_items/$itemsPerPage);
    $items        = Item::newInstance()->findItemTypesByUserID(osc_logged_user_id(), $page*$itemsPerPage, $itemsPerPage, $itemType);

    View::newInstance()->_exportVariableToView('items', $items);
    View::newInstance()->_exportVariableToView('search_total_pages', $total_pages);
    View::newInstance()->_exportVariableToView('list_total_items', $total_items);
    View::newInstance()->_exportVariableToView('items_per_page', $itemsPerPage);
    View::newInstance()->_exportVariableToView('list_page', $page);

?>
<div class="wrapper wrapper-flash">
    <div id="flash_js"></div>
</div>
<h2><?php _e('Your listings', 'payment_pro'); ?></h2>
<?php if(osc_count_items() == 0) { ?>
    <h3><?php _e('You don\'t have any listing yet', 'payment_pro'); ?></h3>
<?php } else { ?>
    <?php while(osc_has_items()) {

        $options = payment_pro_menu_options(osc_item(), true);

        ?>
            <div class="item payments-item" >
                    <h3>
                        <a href="<?php echo osc_item_url(); ?>"><?php echo osc_item_title(); ?></a>
                    </h3>
                    <p>
                    <?php _e('Publication date', 'payment_pro') ; ?>: <?php echo osc_format_date(osc_item_pub_date()) ; ?><br />
                    <?php if(osc_item_is_expired()) { ?>
                        <?php _e('Expiration date', 'payment_pro') ; ?>: <strong><?php _e('expired', 'payment_pro') ; ?></strong><br />
                    <?php } else if(osc_item_dt_expiration()=="9999-12-31 23:59:59") { ?>
                        <?php _e('Expiration date', 'payment_pro') ; ?>: <?php _e('will not expire', 'payment_pro') ; ?><br />
                    <?php } else { ?>
                        <?php _e('Expiration date', 'payment_pro') ; ?>: <?php echo osc_format_date(osc_item_dt_expiration()) ; ?><br />
                    <?php } ?>
                    <?php _e('Price', 'payment_pro') ; ?>: <?php echo osc_format_price(osc_item_price()); ?>
                    </p>
                    <?php
                    if((osc_get_preference('pay_per_post', 'payment_pro')!=1 && osc_item_is_enabled()) || (osc_get_preference('pay_per_post', 'payment_pro')==1 && ModelPaymentPro::newInstance()->isEnabled(osc_item_id()))) {
                        if(count($options)>0) {
                            echo '<p class="payments-options options">' . join("<span>&nbsp;&nbsp;|&nbsp;&nbsp;</span>", $options) . '</p>';
                        }
                    } else { ?>
                        <p class="options">
                            <strong><?php _e('This listing is blocked', 'payment_pro'); ?></strong>
                        </p>
                    <?php }; ?>
                    <br />
            </div>
    <?php } ?>
    <br />
    <div class="paginate">
        <?php
        $params = array(
            'total' => $total_pages,
            'selected' => $page,
            'url' => osc_route_url('payment-pro-user-menu', array('iPage' => '{PAGE}'))
        );
        echo osc_pagination($params); ?>
    </div>
    <script type="text/javascript">
        function addProduct(prd, id) {
            $("#" + prd + "_" + id).attr('disabled', true);
            $.ajax({
                type: "POST",
                url: '<?php echo osc_ajax_plugin_url(PAYMENT_PRO_PLUGIN_FOLDER . 'ajax.php'); ?>&' + prd + '=' + id,
                data: {a : 'b'},
                dataType: 'json',
                success: function(data){
                    if(data.error==0) {
                        window.location = '<?php echo osc_route_url('payment-pro-checkout'); ?>';
                    } else {
                        $("#" + prd + "_" + id).attr('disabled', false);
                        var flash = $("#flash_js");
                        var message = $('<div>').addClass('flashmessage').addClass('flashmessage-error').attr('id', 'flashmessage').html(data.msg);
                        flash.html(message);
                        $("#flashmessage").slideDown('slow').delay(3000).slideUp('slow');
                        $("html, body").animate({ scrollTop: 0 }, "slow");
                    }
                }
            });
        }
    </script>
<?php } ?>
