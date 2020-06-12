<?php

    class BlockchainPayment implements iPayment
    {

        public function __construct()
        {
        }

        public static function button($products, $extra = null) {

            $items = array();
            $amount = 0;
            foreach($products as $p) {
                $amount += $p['amount']*$p['quantity']*((100+$p['tax'])/100);
            }

            if(osc_get_preference('currency', 'payment_pro')!='BTC') {
                $amount = osc_file_get_contents("https://blockchain.info/tobtc?currency=".osc_get_preference('currency', 'payment_pro')."&value=".$amount);
                $xrate = osc_file_get_contents("https://blockchain.info/tobtc?currency=".osc_get_preference('currency', 'payment_pro')."&value=1");
                if(is_numeric($xrate)) {
                    $extra['xrate'] = $xrate;
                    osc_set_preference('blockchain_xrate', $xrate, 'payment_pro');
                } else {
                    osc_get_preference('blockchain_xrate', 'payment_pro');
                }
            } else {
                $extra['xrate'] = 1;
            }

            //$tx_id = ModelPaymentPro::newInstance()->pendingInvoice($products);

            $r = rand(0,1000);
            $extra['random'] = $r;
            //$extra['tx'] = $tx_id;
            $data_create = payment_pro_set_custom(array('products' => $products, 'extra' => $extra));
            $ajax_url = osc_base_url(true) . "?page=ajax&action=runhook&hook=blockchain&data=" . $data_create;
            ?>

            <li class="payment bitcoin-btn">
            <div class="blockchain-btn"
                 style="width:auto"
                 data-create-url="<?php echo $ajax_url; ?>">
                <div class="blockchain stage-begin">
                    <img src="<?php echo PAYMENT_PRO_URL; ?>payments/blockchain/pay_now_64.png">
                </div>
                <div class="blockchain stage-loading" style="text-align:center">
                    <img src="<?php echo PAYMENT_PRO_URL; ?>payments/blockchain/loading-large.gif">
                </div>
                <div class="blockchain stage-ready" style="text-align:center">
                    <p><?php printf(__('Please send %f BTC to <br /> <b>[[address]]</b></p>', 'payment_pro'), $amount); ?>
                    <div class='qr-code'></div>
                </div>
                <div class="blockchain stage-paid">
                    <p><?php _e('Payment Received <b>[[value]] BTC</b>. Thank You.', 'payment_pro'); ?></p>
                    <a href="<?php echo osc_route_url('payment-pro-done', array('tx' => $r)); ?>"><?php _e('Click here to continue', 'payment_pro'); ?></a>
                </div>
                <div class="blockchain stage-error">
                    <span color="red">[[error]]</span>
                </div>
            </div>
            </li>

        <?php
        }

        public static function recurringButton($products, $extra = null) {}

        public static function processPayment() {

            if(Params::getParam('test')==true) {
                return PAYMENT_PRO_FAILED;
            }
            $extra = explode("?", Params::getParam('extra'));
            $tmp = payment_pro_get_custom(str_replace("@", "+", $extra[0]));
            unset($extra);
            $pendingExtra = ModelPaymentPro::newInstance()->pendingExtra(@$tmp['tx']);
            $data = array("extra" => array(), "response" => array());
            if(isset($pendingExtra['s_extra'])) {
                $data = json_decode($pendingExtra['s_extra'], true);
            }
            unset($pendingExtra);
            $data['tx'] = @$tmp['tx'];
            $data['items'] = ModelPaymentPro::newInstance()->getPending(@$tmp['tx']);
            $transaction_hash = Params::getParam('transaction_hash');
            $value_in_btc = Params::getParam('value')/100000000;
            $address = Params::getParam('address');
            if(empty($data['items'])) {
                echo "S1";
                return PAYMENT_PRO_FAILED;
            }
            if(osc_get_preference('currency', 'payment_pro')=='BTC') {
                $status = payment_pro_check_items($data['items'], $value_in_btc);
            } else {
                $status = payment_pro_check_items_blockchain($data['items'], $value_in_btc, $data['extra']['xrate']);
            }
            if ($address=='' || !isset($data['response']['address']) || $data['response']['address']!=$address) {
                echo "S2";
                return PAYMENT_PRO_FAILED;
            }

            $amount = 0;
            $amount_tax = 0;
            foreach($data['items'] as $k => $v) {
                $data['items'][$k]['amount'] = $v['amount']/1000000;
                $data['items'][$k]['amount_total'] = $v['amount_total']/1000000;
                $data['items'][$k]['amount_tax'] = $v['amount_tax']/1000000;
                $data['items'][$k]['tax'] = $v['tax']/100;
                $amount += $data['items'][$k]['amount'];
                $amount_tax += $data['items'][$k]['amount_tax'];
            }

            $exists = ModelPaymentPro::newInstance()->getPaymentByCode($transaction_hash, 'BLOCKCHAIN', PAYMENT_PRO_COMPLETED);
            if(isset($exists['pk_i_id'])) { return PAYMENT_PRO_ALREADY_PAID; }
            if ((is_numeric(Params::getParam('confirmations')) && Params::getParam('confirmations')>=osc_get_preference('blockchain_confirmations', 'payment_pro')) || Params::getParam('anonymous')== true) {
                // SAVE TRANSACTION LOG
                $invoiceId = ModelPaymentPro::newInstance()->saveInvoice(
                    $transaction_hash, // transaction code
                    $amount,
                    $amount_tax,
                    $value_in_btc, //amount
                    $status,
                    'BTC', //currency
                    $data['extra']['email'], // payer's email
                    $data['extra']['user'], //user
                    'BLOCKCHAIN',
                    $data['items']
                );

                if($status==PAYMENT_PRO_COMPLETED) {
                    foreach($data['items'] as $item) {
                        $tmp = explode("-", $item['id']);
                        $item['item_id'] = $tmp[count($tmp) - 1];
                        osc_run_hook('payment_pro_item_paid', $item, $data['extra'], $invoiceId);
                    }
                    ModelPaymentPro::newInstance()->deletePending($data['tx']);
                    ModelPaymentPro::newInstance()->updatePendingInvoiceExtra($data['tx'], $invoiceId, 'BLOCKCHAIN');
                }

                return PAYMENT_PRO_COMPLETED;
            } else {
                // Maybe we could do something here (the payment was correct, but it didn't get enought confirmations yet)
                echo "S3";
                return PAYMENT_PRO_PENDING;
            }

            echo "S4";
            return $status = PAYMENT_PRO_FAILED;
        }

        public static function ajaxCreate() {

            ob_get_clean();


            $data = payment_pro_get_custom(Params::getParam("data"));
            if(!isset($data['products']) || !isset($data['extra'])) {
                print json_encode(array('input_address' => __('Missing data', 'payment_pro') ));
                die;
            }
            $products = $data['products'];
            $extra = $data['extra'];
            $tx_id = ModelPaymentPro::newInstance()->pendingInvoice($products);
            $extra['tx'] = $tx_id;

            //$data_create = payment_pro_set_custom(array('products' => $products, 'extra' => $extra));
            $data_create = payment_pro_set_custom(array("tx" => $tx_id));
            $callback_url =  osc_route_url('blockchain-notify', array('extra' => str_replace("+", "@", $data_create)));
            error_log(PHP_EOL . PHP_EOL . "BLOCKCHAIN CALLBACK: " . urlencode($callback_url) . PHP_EOL . PHP_EOL);

            $resp = osc_file_get_contents("https://api.blockchain.info/v2/receive?key=" . payment_pro_decrypt(osc_get_preference('blockchain_apikey', 'payment_pro')) . "&callback=" . urlencode($callback_url) . "&xpub=" . osc_get_preference('blockchain_xpub', 'payment_pro'));


            $response = json_decode($resp, true);

            error_log(PHP_EOL . PHP_EOL . "BLOCKCHAIN RESPONSE: " . $resp . PHP_EOL . PHP_EOL);
            if(!isset($response['address'])) {
                print json_encode(array('input_address' => __('Error API', 'payment_pro') ));
                die;
            }

            ModelPaymentPro::newInstance()->setPendingExtra($tx_id, json_encode(array("extra" => $extra, "response" => $response)), 'BLOCKCHAIN');

            print json_encode(array('input_address' => $response['address'] ));
            die;

        }

    }
