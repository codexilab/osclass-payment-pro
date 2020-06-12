<?php

    class BankPayment implements iPayment
    {

        public function __construct()
        {
        }

        public static function button($products, $extra = null)
        {

            $amount = 0;
            $showButton = true;
            foreach ($products as $p) {
                $amount += $p['amount'] * $p['quantity'] * ((100 + $p['tax']) / 100);
                if (substr($p['id'], 0, 3) != "WLT" && osc_get_preference('bank_only_packs', 'payment_pro') == 1) {
                    $showButton = false;
                }
            }

            $r = rand(0, 1000);
            $extra['random'] = $r;
            $data_create = payment_pro_set_custom(array('products' => $products, 'extra' => $extra));
            if ($showButton) {
                ?>

                <li class="payment bank-btn">
                    <div id="bank_start" style="cursor:pointer;cursor:hand" onclick="javascript:bank_pay();">
                        <img src="<?php echo PAYMENT_PRO_URL; ?>payments/bank/pay_by_bank.png">
                    </div>
                    <div id="bank_loading" style="cursor:pointer;cursor:hand; display: none;" >
                        <img src="<?php echo PAYMENT_PRO_URL; ?>payments/bank/loading-large.gif">
                    </div>
                    <div id="bank_info" style="cursor:pointer;cursor:hand; display: none;" >
                        <span id="bank_info_span"></span>
                    </div>
                    <div class="blockchain stage-loading" style="text-align:center">
                    </div>

                </li>
                <script type="text/javascript">
                    function bank_pay() {
                        $("#bank_start").hide();
                        $("#bank_loading").show();
                        $.post(
                            '<?php echo osc_base_url(true); ?>',
                            {
                                page: 'ajax',
                                action: 'runhook',
                                hook: 'banktransfer',
                                data: '<?php echo $data_create?>'
                            },
                            function (response) {
                                $("#bank_info_span").text(response.msg);
                                $("#bank_loading").hide();
                                $("#bank_info").show();
                            },
                            'json'
                        );

                        return false;
                    };

                </script>

                <?php
            }
        }

        public static function recurringButton($products, $extra = null)
        {
        }

        public static function processPayment()
        {

            if (Params::getParam('test') == true) {
                return PAYMENT_PRO_FAILED;
            }
            $extra = explode("?", Params::getParam('extra'));
            $data = payment_pro_get_custom(str_replace("@", "+", $extra[0]));
            unset($extra);
            $data['items'] = ModelPaymentPro::newInstance()->getPending(@$data['tx']);
            $transaction_hash = Params::getParam('transaction_hash');
            $value_in_btc = Params::getParam('value') / 100000000;
            $bitcoin_address = ModelPaymentPro::newInstance()->pendingExtra(@$data['tx']);
            $address = Params::getParam('address');
            if (empty($data['items'])) {
                echo "S1";
                return PAYMENT_PRO_FAILED;
            }
            if (osc_get_preference('currency', 'payment_pro') == 'BTC') {
                $status = payment_pro_check_items($data['items'], $value_in_btc);
            } else {
                $status = payment_pro_check_items_blockchain($data['items'], $value_in_btc, $data['xrate']);
            }
            if ($address == '' || !isset($bitcoin_address['s_extra']) || $bitcoin_address['s_extra'] != $address) {
                echo "S2";
                return PAYMENT_PRO_FAILED;
            }

            $amount = 0;
            $amount_tax = 0;
            foreach ($data['items'] as $k => $v) {
                $data['items'][$k]['amount'] = $v['amount'] / 1000000;
                $data['items'][$k]['amount_total'] = $v['amount_total'] / 1000000;
                $data['items'][$k]['amount_tax'] = $v['amount_tax'] / 1000000;
                $data['items'][$k]['tax'] = $v['tax'] / 100;
                $amount += $data['items'][$k]['amount'];
                $amount_tax += $data['items'][$k]['amount_tax'];
            }

            $exists = ModelPaymentPro::newInstance()->getPaymentByCode($transaction_hash, 'BLOCKCHAIN', PAYMENT_PRO_COMPLETED);
            if (isset($exists['pk_i_id'])) {
                return PAYMENT_PRO_ALREADY_PAID;
            }
            if ((is_numeric(Params::getParam('confirmations')) && Params::getParam('confirmations') >= osc_get_preference('blockchain_confirmations', 'payment_pro')) || Params::getParam('anonymous') == true) {
                // SAVE TRANSACTION LOG
                $invoiceId = ModelPaymentPro::newInstance()->saveInvoice(
                    $transaction_hash, // transaction code
                    $amount,
                    $amount_tax,
                    $value_in_btc, //amount
                    $status,
                    'BTC', //currency
                    $data['email'], // payer's email
                    $data['user'], //user
                    'BLOCKCHAIN',
                    $data['items']
                );

                if ($status == PAYMENT_PRO_COMPLETED) {
                    foreach ($data['items'] as $item) {
                        $tmp = explode("-", $item['id']);
                        $item['item_id'] = $tmp[count($tmp) - 1];
                        osc_run_hook('payment_pro_item_paid', $item, $data, $invoiceId);
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

        public static function ajaxCreate()
        {

            ob_get_clean();


            $data = payment_pro_get_custom(Params::getParam("data"));
            if (!isset($data['products']) || !isset($data['extra'])) {
                print json_encode(array('error' => 1, 'msg' => __('Missing data', 'payment_pro')));
                die;
            }
            $products = $data['products'];
            $extra = $data['extra'];
            //$tx_id = ModelPaymentPro::newInstance()->pendingInvoice($products, 10);
            //$extra['tx'] = $tx_id;

            $amount = 0;
            $amount_tax = 0;
            $amount_total = 0;

            foreach($products as $p) {
                $amt = $p['amount']*$p['quantity'];
                $amount += $amt;
                $amount_tax += $amt*($p['tax']/100);
                $amount_total += $amt*((100+$p['tax'])/100);
            }

            $code = self::generateCode(6);
            //ModelPaymentPro::newInstance()->setPendingExtra($tx_id, $code, 'BANK');

            $invoiceId = ModelPaymentPro::newInstance()->saveInvoice(
                $code, // transaction code
                $amount, //subtotal
                $amount_tax, //tax
                $amount_total, //total
                PAYMENT_PRO_PENDING,
                osc_get_preference('currency', 'payment_pro'),
                $extra['email'], // payer's email
                $extra['user'], //user
                'BANK',
                $products
            );


            $msg = str_replace(
                array(
                    '{BANK_ACCOUNT}',
                    '{CODE}',
                    '{AMOUNT}'
                ),
                array(
                    osc_get_preference('bank_account', 'payment_pro'),
                    $code,
                    osc_format_price($amount_total*1000000, osc_get_preference('currency', 'payment_pro'))
                ),
                osc_get_preference('bank_msg', 'payment_pro')
            );

            print json_encode(array('error' => 0, 'msg' => $msg));
            die;

        }

        private static function generateCode($length = null)
        {

            $code = strtoupper(str_ireplace(array('i', '0','1'), array('l', 'o', 'l'), osc_genRandomPassword($length)));
            $dao = ModelPaymentPro::newInstance();
            $table = $dao->getTable_invoice();
            while (true) {
                $dao->dao->select("s_code");
                $dao->dao->from($table);
                $dao->dao->where("s_code", $code);
                $dao->dao->where("s_source", "BANK");
                $dao->dao->limit(1);
                $result = $dao->dao->get();
                if ($result) {
                    if ($result->numRows() > 0) {
                        $code = strtoupper(str_ireplace(array('i', '0','1'), array('l', 'o', 'l'), osc_genRandomPassword($length)));
                    } else {
                        break;
                    }
                } else {
                    break;
                }
            }
            return $code;
        }
    }


