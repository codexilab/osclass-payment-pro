<?php
//require_once ABS_PATH . 'oc-load.php';

$item_price     = Params::getParam('amount');
$amount         = Params::getParam('quantity');
$item_num       = Params::getParam('item_number');
$currency       = Params::getParam('currency_code');
//$item_seller    = Params::getParam('item_seller');
$item_buyer     = Params::getParam('item_buyer');

$code = strtoupper(osc_genRandomPassword(12));


$conn = getConnection();

$seller = $conn->osc_dbFetchResult("SELECT * FROM %st_user WHERE pk_i_id = %d", DB_TABLE_PREFIX, $item_num);

$conn->osc_dbExec("INSERT INTO %st_shop_transactions (fk_i_item_id, fk_i_user_id, fk_i_buyer_id, i_amount, f_item_price, s_currency, e_status, s_code) VALUES (%d, %d, %d, %d, %f, '%s', 'SOLD', '%s')", DB_TABLE_PREFIX, $item_num, $seller, osc_logged_user_id(), $amount, $item_price, $currency, $code);

$transaction = $conn->get_last_id();
$conn->osc_dbExec("INSERT INTO %st_shop_log (fk_i_transaction_id, fk_i_user_id, fk_i_item_id, dt_date,e_status) VALUES (%d, %d, %d, '%s','SOLD')", DB_TABLE_PREFIX, $transaction, $seller, $item_num, date('Y-m-d H:i:s'));

$conn->osc_dbExec("INSERT INTO %st_shop_paypal_log (b_paid, s_concept, dt_date,s_code,f_amount,s_currency_code,s_email,fk_i_transaction_id) VALUES ('1', %d, '%s', %d, %f, %f, %d, %d)", DB_TABLE_PREFIX, $seller, date('Y-m-d H:i:s'),$code,$item_price,$currency,$email, $transaction);


$conn->osc_dbExec("UPDATE %st_item SET i_amount = %d WHERE pk_i_id = %d", DB_TABLE_PREFIX, $amount-$amount, $item_num);
               $seller = $conn->osc_dbFetchResult("SELECT * FROM %st_user WHERE pk_i_id = %d", DB_TABLE_PREFIX, osc_item_user_id());
               $buyer = $conn->osc_dbFetchResult("SELECT * FROM %st_user WHERE pk_i_id = %d", DB_TABLE_PREFIX, osc_logged_user_id());
               $conn->osc_dbExec("UPDATE %st_user SET i_total_sales = %d WHERE pk_i_id = %d", DB_TABLE_PREFIX, $seller['i_total_sales']+1, $seller['pk_i_id']);
               $conn->osc_dbExec("UPDATE %st_user SET i_total_buys = %d WHERE pk_i_id = %d", DB_TABLE_PREFIX, $buyer['i_total_buys']+1, $buyer['pk_i_id']);
 
$transaction = ModelShop::newInstance()->shop_send_sold_email($code);
               
$status = PAYMENT_FAILED;
    if(osc_get_preference('paypal_standard', 'shop')==1) {
        $data = payment_get_custom(Params::getParam('custom'));

        $product_type = explode('x', Params::getParam('item_number'));
        $tx = Params::getParam('tx')==''?Params::getParam('tx'):Params::getParam('txn_id');
        $payment = ModelPayment::newInstance()->getPayment($tx);
        if (isset($payment['pk_i_id'])) {
            osc_add_flash_ok_message(__('Payment processed correctly', 'shop'));
            
        } else {
// THIS BIT IS WORKING//
            osc_add_flash_info_message(__('We are processing your payment, if this does not finish in a few seconds, please contact us.', 'shop'));
            if($product_type[0]==301) {
                if(osc_is_web_user_logged_in()) {
                    payment_js_redirect_to(osc_route_url('shop-buyer'));
                } else {
                    // THIS SHOULD NOT HAPPEN
                    payment_js_redirect_to(osc_base_path());
                }
            } else {
                if(osc_is_web_user_logged_in()) {
                    payment_js_redirect_to(osc_route_url('shop-buyer'));
                
                }
            }
        }
    } else {
osc_add_flash_info_message(__('Sorry something went wrong there, please contact us.', 'shop'));
}



