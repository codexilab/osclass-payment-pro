<?php View::newInstance()->_exportVariableToView('item', Item::newInstance()->findByPrimaryKey(Params::getParam('item_id')));
$conn = getConnection();
$detail = $conn->osc_dbFetchResult("SELECT * FROM %st_shop_item WHERE fk_i_item_id = %d", DB_TABLE_PREFIX, osc_item_id());
$amount = min(Params::getParam('shop_amount')!=''?Params::getParam('shop_amount'):1, $detail['i_amount']);
if($amount<0) { $amount = 1; }; ?>

<?php if(osc_item_user_id()!=null && osc_item_user_id()!=0) { ?>
<?php if(Params::getParam('step')=='done') { 

$shop_item = $conn->osc_dbFetchResult("SELECT i_amount FROM %st_item WHERE pk_i_id = %d", DB_TABLE_PREFIX, osc_item_id());
        if(isset($shop_item['i_amount'])) {
            if($amount>$shop_item['i_amount']) {
                $amount = $shop_item['i_amount'];
            }



$txn_code = strtoupper(osc_genRandomPassword(12));
               $conn->osc_dbExec("INSERT INTO %st_shop_transactions (fk_i_item_id, fk_i_user_id, fk_i_buyer_id, i_amount, f_item_price, s_currency, e_status, s_code) VALUES (%d, %d, %d, %d, %f, '%s', 'SOLD', '%s')", DB_TABLE_PREFIX, osc_item_id(), osc_item_user_id(), osc_logged_user_id(), $amount, osc_item_price(), osc_item_currency(), $txn_code);
                $transaction = $conn->get_last_id();
                $conn->osc_dbExec("INSERT INTO %st_shop_log (fk_i_transaction_id, e_status, fk_i_user_id, dt_date) VALUES (%d, 'SOLD', %d, '%s')", DB_TABLE_PREFIX, $transaction, osc_item_user_id(), date('Y-m-d H:i:s'));
                $conn->osc_dbExec("UPDATE %st_item SET i_amount = %d WHERE pk_i_id = %d", DB_TABLE_PREFIX, $shop_item['i_amount']-$amount, osc_item_id());
                $seller = $conn->osc_dbFetchResult("SELECT * FROM %st_shop_user WHERE fk_i_user_id = %d", DB_TABLE_PREFIX, osc_item_user_id());
                $buyer = $conn->osc_dbFetchResult("SELECT * FROM %st_shop_user WHERE fk_i_user_id = %d", DB_TABLE_PREFIX, osc_logged_user_id());
                $conn->osc_dbExec("UPDATE %st_shop_user SET i_total_sales = %d WHERE fk_i_user_id = %d", DB_TABLE_PREFIX, $seller['i_total_sales']+1, $seller['fk_i_user_id']);
                $conn->osc_dbExec("UPDATE %st_shop_user SET i_total_buys = %d WHERE fk_i_user_id = %d", DB_TABLE_PREFIX, $buyer['i_total_buys']+1, $buyer['fk_i_user_id']);
                shop_send_sold_email($transaction);
}
?>



<div style="width:50%; float:left; height:150px;">
    <?php if(osc_item_user_id()!=osc_logged_user_id()) {
    echo sprintf(__('CONGRATULATIONS! You just bought %s at a total price of %s %s', 'shop'), osc_item_title(), ($amount*  osc_item_price()), osc_item_currency()); ?><br /> 
<?php } else { ?>
<div style="width:50%; float:left; height:150px;">
    <?php _e('Some error ocurred, we can not process the payment right now', 'shop'); ?>
</div>
<?php } ?><?php }?><?php }?>
