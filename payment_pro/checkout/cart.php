<?php View::newInstance()->_exportVariableToView('item', Item::newInstance()->findByPrimaryKey(Params::getParam('item_id'))); ?>
<div style="width:100%; float:left;">
    <style>table {   width: 100%;   border-collapse: collapse; }tr:nth-of-type(odd) {   background: #eee; }
        th {   background: #21292D;   color: white;   font-weight: bold; }td, th {   padding: 6px;   border: 1px solid #ccc;   text-align: left; }@media only screen and (max-width: 760px),(min-device-width: 760px) and (max-device-width: 1024px)  {table, thead, tbody, th, td, tr { 		display: block; 	}thead tr { position: absolute;top: -9999px;left: -9999px;}tr { border: 1px solid #ccc; }	td { border: none;	border-bottom: 1px solid #ddd; position: relative;padding-left: 50%; 	}td:before { position: absolute;top: 6px;left: 6px;width: 45%; padding-right: 10px; white-space: nowrap;}td:nth-of-type(1):before { content: "Image"; }td:nth-of-type(2):before { content: "Qty"; }td:nth-of-type(3):before { content: "Product"; }td:nth-of-type(4):before { content: "Price"; }}
    </style>
    <span class="admin-options">
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Qty</th>
                    <th>Product</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <?php if (osc_images_enabled_at_items()) { ?><div class="photo"><?php if (osc_count_item_resources()) { ?><a href="<?php echo osc_item_url(); ?>"><img src="<?php echo osc_resource_thumbnail_url(); ?>" width="75px" height="56px" title="<?php echo osc_item_title(); ?>" alt="" /></a><?php } else { ?><img src="<?php echo osc_current_web_theme_url('images/no_photo.gif'); ?>" width="75px" height="56px" title="?>"><?php echo osc_item_title(); ?>" alt="" /><?php } ?></div><?php } ?>
                    </td>
                    <td>
                        <?php echo ckt_item_amount(osc_item_id()); ?>
                    </td>
                    <td>
                        <?php echo osc_item_title(); ?>
                    </td>
                    <td>
                        &pound;<?php echo ckt_item_amount(osc_item_id()) * ckt_paypal_price(osc_item_id()); ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <br />
        <div class="buttons">You are going to buy <?php echo ckt_item_amount(osc_item_id()); ?>&nbsp;of <?php echo osc_item_title(); ?> at a total price of &pound;<?php echo ckt_item_amount(osc_item_id()) * ckt_paypal_price(osc_item_id()); ?></div>
        <hr />
        <?php
        if (osc_get_preference('allow_shop', 'shop')) {
            $txn_code = strtoupper(osc_genRandomPassword(12));
            if (!ModelShop::newInstance()->PaypalAccept(Params::getParam("itemId"))) {
                ModelShop::standardButton();
                ?><?php } ?> 
            <?php if (!ModelShop::newInstance()->BankAccept(Params::getParam("itemId"))) { ?>
                The seller also accepts bank transfers as payment, please contact the seller to know more details about this payment option.<br />Remember your transaction #ID is <?php echo $txn_code; ?>
        <?php } ?><?php } ?></div></div></div>