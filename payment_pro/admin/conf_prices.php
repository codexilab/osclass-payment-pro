<?php if ( ! defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

if(Params::getParam('plugin_action')=='add_category') {
    $catId = Params::getParam('categoryId');
    if($catId=="" || !is_numeric($catId)) {
        $catId = Params::getParam('catId');
    }
    if($catId!="" && is_numeric($catId)) {
        ModelPaymentPro::newInstance()->insertPrice(
            $catId,
            Params::getParam('publish_price')==''?osc_get_preference('default_publish_cost'):Params::getParam('publish_price'),
            Params::getParam('premium_price')==''?osc_get_preference('default_premium_cost'):Params::getParam('premium_price'),
            Params::getParam('top_price')==''?osc_get_preference('default_top_cost'):Params::getParam('top_price'),
            Params::getParam('highlight_price')==''?osc_get_preference('default_highlight_cost'):Params::getParam('highlight_price'),
            Params::getParam('renew_price')==''?osc_get_preference('default_renew_cost'):Params::getParam('renew_price')
        );
        osc_add_flash_ok_message(__('Category prices updated correctly', 'payment_pro'), 'admin');
    } else {
        osc_add_flash_error_message(__('Category is not defined', 'payment_pro'), 'admin');
    }
    ob_get_clean();
    osc_redirect_to(osc_route_admin_url('payment-pro-admin-prices'));
} else if(Params::getParam('plugin_action')=='delete') {
    if(Params::getParam('catId')!='') {
        ModelPaymentPro::newInstance()->deletePrices(Params::getParam('catId'));
        osc_add_flash_ok_message(__('Category prices changed to default', 'payment_pro'), 'admin');
    } else {
        osc_add_flash_error_message(__('Category is not defined', 'payment_pro'), 'admin');
    }
    ob_get_clean();
    osc_redirect_to(osc_route_admin_url('payment-pro-admin-prices'));
} else if(Params::getParam('plugin_action')=='alldone') {
    $pub_prices = Params::getParam("pub_prices");
    $prm_prices  = Params::getParam("prm_prices");
    $top_prices  = Params::getParam("top_prices");
    $highlight_prices  = Params::getParam("highlight_prices");
    $renew_prices  = Params::getParam("renew_prices");

    $mp = ModelPaymentPro::newInstance();
    foreach($prm_prices as $k => $v) {
        if(
            (!isset($pub_prices[$k]) || $pub_prices[$k]=='') &&
            (!isset($prm_prices[$k]) || $prm_prices[$k]=='') &&
            (!isset($top_prices[$k]) || $top_prices[$k]=='') &&
            (!isset($highlight_prices[$k]) || $highlight_prices[$k]=='') &&
            (!isset($renew_prices[$k]) || $renew_prices[$k]=='')
        ) {
            $mp->deletePrices($k);
        } else {
            $mp->insertPrice(
                $k,
                (!isset($pub_prices[$k]) || $pub_prices[$k]=='')?osc_get_preference('default_publish_cost'):$pub_prices[$k],
                (!isset($prm_prices[$k]) || $prm_prices[$k]=='')?osc_get_preference('default_premium_cost'):$prm_prices[$k],
                (!isset($top_prices[$k]) || $top_prices[$k]=='')?osc_get_preference('default_top_cost'):$top_prices[$k],
                (!isset($highlight_prices[$k]) || $highlight_prices[$k]=='')?osc_get_preference('default_highlight_cost'):$highlight_prices[$k],
                (!isset($renew_prices[$k]) || $renew_prices[$k]=='')?osc_get_preference('default_renew_cost'):$renew_prices[$k]
            );
        }
    }
    ob_get_clean();
    osc_add_flash_ok_message(__('Congratulations, the plugin is now configured', 'payment_pro'), 'admin');
    osc_redirect_to(osc_route_admin_url('payment-pro-admin-prices'));
}

$catMgr = Category::newInstance();

$categories = Category::newInstance()->toTreeAll();
$prices     = ModelPaymentPro::newInstance()->getCategoriesPrices();
$cat_prices = array();
foreach($prices as $p) {
    $cat_prices[$p['fk_i_category_id']]['i_publish_cost'] = $p['i_publish_cost']/1000000;
    $cat_prices[$p['fk_i_category_id']]['i_premium_cost'] = $p['i_premium_cost']/1000000;
    $cat_prices[$p['fk_i_category_id']]['i_top_cost'] = $p['i_top_cost']/1000000;
    $cat_prices[$p['fk_i_category_id']]['i_highlight_cost'] = $p['i_highlight_cost']/1000000;
    $cat_prices[$p['fk_i_category_id']]['i_renew_cost'] = $p['i_renew_cost']/1000000;
}

function payment_pro_draw_cat($categories, $depth = 0, $cat_prices) {
    foreach($categories as $c) {
        echo '<tr><td>';
        for($d=0;$d<$depth;$d++) { echo "&nbsp;&nbsp;&nbsp;&nbsp;"; }; echo $c['s_name'];
        echo '</td><td>';
        echo '<input style="width:150px;text-align:right;" type="text" name="pub_prices[' . $c['pk_i_id'] . ']" id="pub_prices[' . $c['pk_i_id'] . ']" value="' . (isset($cat_prices[$c['pk_i_id']]) ? $cat_prices[$c['pk_i_id']]['i_publish_cost'] : '') . '" />';
        echo '</td><td>';
        echo '<input style="width:150px;text-align:right;" type="text" name="prm_prices[' . $c['pk_i_id'] . ']" id="prm_prices[' . $c['pk_i_id'] . ']" value="' . (isset($cat_prices[$c['pk_i_id']]) ? $cat_prices[$c['pk_i_id']]['i_premium_cost'] : '') . '" />';
        echo '</td><td>';
        echo '<input style="width:150px;text-align:right;" type="text" name="top_prices[' . $c['pk_i_id'] . ']" id="top_prices[' . $c['pk_i_id'] . ']" value="' . (isset($cat_prices[$c['pk_i_id']]) ? $cat_prices[$c['pk_i_id']]['i_top_cost'] : '') . '" />';
        echo '</td><td>';
        echo '<input style="width:150px;text-align:right;" type="text" name="highlight_prices[' . $c['pk_i_id'] . ']" id="highlight_prices[' . $c['pk_i_id'] . ']" value="' . (isset($cat_prices[$c['pk_i_id']]) ? $cat_prices[$c['pk_i_id']]['i_highlight_cost'] : '') . '" />';
        echo '</td><td>';
        echo '<input style="width:150px;text-align:right;" type="text" name="renew_prices[' . $c['pk_i_id'] . ']" id="renew_prices[' . $c['pk_i_id'] . ']" value="' . (isset($cat_prices[$c['pk_i_id']]) ? $cat_prices[$c['pk_i_id']]['i_renew_cost'] : '') . '" />';
        echo '</td></tr>';
        payment_pro_draw_cat($c['categories'], $depth+1, $cat_prices);
    }
};






?>
<style type="text/css">
    .payment-pro-pub, .payment-pro-prm, .payment-pro-top, .payment-pro-hlt, .payment-pro-rnw {
        background-color: #d8e6ff;
    }
</style>
<script type="text/javascript" >
    $(document).ready(function(){
        $("#dialog-new").dialog({
            autoOpen: false,
            width: "500px",
            modal: true,
            title: '<?php echo osc_esc_js( __('Set category prices', 'payment_pro') ); ?>'
        });
        $("#dialog-delete").dialog({
            autoOpen: false,
            width: "500px",
            modal: true,
            title: '<?php echo osc_esc_js( __('Delete category prices', 'payment_pro') ); ?>'
        });
    });
    function new_cat() {
        $('#select_row').show();
        $('#dialog-new').dialog('open');
    };
    function edit_cat(id, pub, prm, top, hlt, rnw) {
        $('#categoryId').prop('value', id);
        $('#publish_price').prop('value', pub);
        $('#premium_price').prop('value', prm);
        $('#top_price').prop('value', top);
        $('#highlight_price').prop('value', hlt);
        $('#renew_price').prop('value', rnw);
        $('#select_row').hide();
        $('#dialog-new').dialog('open');
    };
    function delete_cat(id) {
        $('#delete_cat').prop('value', id);
        $('#dialog-delete').dialog('open');
    };
    function classic_view() {
        $("#all_prices").show();
        $("#new_prices").hide();
    };
    function clean_view() {
        $("#all_prices").hide();
        $("#new_prices").show();
    };
</script>
<div style="clear:both;">
    <div style="float: left; width: 100%;">
        <fieldset>
            <h3><?php _e('Setting up your fees', 'payment_pro'); ?></h3> <div id="buttons"><a href="#" onclick="javascript:classic_view();"><?php _e('Show all categories', 'payment_pro');?></a> | <a href="#" onclick="javascript:clean_view();"><?php _e('Only prices different than default', 'payment_pro');?></a></div>
            <p>
                <?php _e('You could set up different prices for each category', 'payment_pro'); ?>. <?php _e('If the price of a category is left empty, the default value will be applied', 'payment_pro'); ?>.
            </p>
        </fieldset>
    </div>
    <div style="clear: both;"></div>
</div>
<div id="all_prices">
    <div style="padding: 20px;">
        <div style="float: left; width: 100%;">
            <fieldset>
                <div style="float: left; width: 100%;">
                    <form name="payment_form" id="payment_form" action="<?php echo osc_admin_base_url(true);?>" method="POST" enctype="multipart/form-data" >
                        <input type="hidden" name="page" value="plugins" />
                        <input type="hidden" name="action" value="renderplugin" />
                        <input type="hidden" name="route" value="payment-pro-admin-prices" />
                        <input type="hidden" name="plugin_action" value="alldone" />
                        <table border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="width:300px;"><?php _e('Category Name', 'payment_pro'); ?></td>
                                <td style="width:175px;"><?php echo sprintf(__('Publish fee (%s)', 'payment_pro'), payment_pro_currency()); ?></td>
                                <td style="width:175px;"><?php echo sprintf(__('Premium fee (%s)', 'payment_pro'), payment_pro_currency()); ?></td>
                                <td style="width:175px;"><?php echo sprintf(__('Move to top fee (%s)', 'payment_pro'), payment_pro_currency()); ?></td>
                                <td style="width:175px;"><?php echo sprintf(__('Highlight fee (%s)', 'payment_pro'), payment_pro_currency()); ?></td>
                                <td style="width:175px;"><?php echo sprintf(__('Renew fee (%s)', 'payment_pro'), payment_pro_currency()); ?></td>
                            </tr>
                            <?php payment_pro_draw_cat($categories, 0, $cat_prices); ?>
                        </table>
                        <button type="submit" style="float: right;"><?php _e('Update', 'payment_pro'); ?></button>
                    </form>
                </div>
            </fieldset>
        </div>
    </div>
</div>



<div id="new_prices" class="hide">
    <div id="general-setting">
        <div id="general-settings">
            <h2 class="render-title"><?php _e('Set categories prices', 'payment_pro'); ?> <span><a id="new-price" href="javascript:new_cat();" ><?php _e('Add new price', 'payment_pro'); ?></a></span></h2>
            <ul id="error_list"></ul>
            <form name="payment_pro_form" action="#" method="post">
                <fieldset>
                    <div class="form-horizontal">
                        <?php foreach($prices as $price) {
                            $category = $catMgr->findByPrimaryKey($price['fk_i_category_id']); ?>
                            <div class="form-row">
                                <div class="form-label"><?php echo $category['s_name']; ?></div>
                                <div class="form-controls">
                                    <span class="payment-pro-pub" ><?php printf(__('Publish cost: %s'), osc_format_price($price['i_publish_cost'], payment_pro_currency())); ?></span>
                                    <span class="payment-pro-prm" ><?php printf(__('Premium cost: %s'), osc_format_price($price['i_premium_cost'], payment_pro_currency())); ?></span>
                                    <span class="payment-pro-top" ><?php printf(__('Move to top cost: %s'), osc_format_price($price['i_top_cost'], payment_pro_currency())); ?></span>
                                    <span class="payment-pro-hlt" ><?php printf(__('Highlight cost: %s'), osc_format_price($price['i_highlight_cost'], payment_pro_currency())); ?></span>
                                    <span class="payment-pro-rnw" ><?php printf(__('Renew cost: %s'), osc_format_price($price['i_renew_cost'], payment_pro_currency())); ?></span>
                                    <span><a href="javascript:edit_cat(<?php echo $price['fk_i_category_id'].", ".($price['i_publish_cost']/1000000).", ".($price['i_premium_cost']/1000000).", ".($price['i_top_cost']/1000000).", ".($price['i_highlight_cost']/1000000).", ".($price['i_renew_cost']/1000000); ?>);" ><?php _e('edit', 'payment_pro'); ?></a></span>
                                    <span><a href="javascript:delete_cat(<?php echo $price['fk_i_category_id']; ?>);" ><?php _e('delete', 'payment_pro'); ?></a></span>
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
    <input type="hidden" name="route" value="payment-pro-admin-prices" />
    <input type="hidden" name="plugin_action" value="add_category" />
    <input type="hidden" name="categoryId" id="categoryId" value="" />
    <div class="form-horizontal">
        <div class="form-row" id="select_row" >
            <div class="form-label"><?php _e('Category', 'payment_pro'); ?></div>
            <div class="form-controls">
                <?php ItemForm::category_select(); ?>
            </div>
        </div>
        <div class="form-row">
            <div class="form-label"><?php _e('Publish price', 'payment_pro'); ?></div>
            <div class="form-controls"><input type="text" id="publish_price" name="publish_price" value="" placeholder="<?php echo osc_get_preference('default_publish_cost', 'payment_pro'); ?>" /> <?php echo payment_pro_currency(); ?></div>
        </div>
        <div class="form-row">
            <div class="form-label"><?php _e('Premium price', 'payment_pro'); ?></div>
            <div class="form-controls"><input type="text" id="premium_price" name="premium_price" value="" placeholder="<?php echo osc_get_preference('default_premium_cost', 'payment_pro'); ?>" /> <?php echo payment_pro_currency(); ?></div>
        </div>
        <div class="form-row">
            <div class="form-label"><?php _e('Move to top price', 'payment_pro'); ?></div>
            <div class="form-controls"><input type="text" id="top_price" name="top_price" value="" placeholder="<?php echo osc_get_preference('default_top_cost', 'payment_pro'); ?>" /> <?php echo payment_pro_currency(); ?></div>
        </div>
        <div class="form-row">
            <div class="form-label"><?php _e('Highlight price', 'payment_pro'); ?></div>
            <div class="form-controls"><input type="text" id="highlight_price" name="highlight_price" value="" placeholder="<?php echo osc_get_preference('default_highlight_cost', 'payment_pro'); ?>" /> <?php echo payment_pro_currency(); ?></div>
        </div>
        <div class="form-row">
            <div class="form-label"><?php _e('Renew price', 'payment_pro'); ?></div>
            <div class="form-controls"><input type="text" id="renew_price" name="renew_price" value="" placeholder="<?php echo osc_get_preference('default_renew_cost', 'payment_pro'); ?>" /> <?php echo payment_pro_currency(); ?></div>
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
    <input type="hidden" name="route" value="payment-pro-admin-prices" />
    <input type="hidden" name="plugin_action" value="delete" />
    <input type="hidden" name="catId" id="delete_cat" value="" />
    <div class="form-horizontal">
        <div class="form-row">
            <?php _e('This will revert back the prices to the default ones. Do you want to continue?', 'payment_pro'); ?>
        </div>
        <div class="form-actions">
            <div class="wrapper">
                <a class="btn" href="javascript:void(0);" onclick="$('#dialog-delete').dialog('close');"><?php _e('Cancel', 'payment_pro'); ?></a>
                <input id="price-delete-submit" type="submit" value="<?php echo osc_esc_html( __('Delete', 'payment_pro')); ?>" class="btn btn-red" />
            </div>
        </div>
    </div>
</form>
