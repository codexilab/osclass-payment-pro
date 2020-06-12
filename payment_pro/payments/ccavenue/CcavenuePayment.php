<?php

    class CcavenuePayment implements iPayment
    {

        public function __construct() { }

        public static function button($products, $extra = null) {

            $amount = 0;
            foreach($products as $p) {
                $amount += $p['amount']*$p['quantity'];
            }

            $r = rand(0,1000);
            $extra['random'] = $r;
            $extra['items'] = $products;
            $extra['amount'] = $amount;
            $extra = payment_pro_set_custom($extra);

            $tx_id = ModelPaymentPro::newInstance()->pendingInvoice($products, 30);

            $merchant_id = payment_pro_decrypt(osc_get_preference('ccavenue_merchant_id', 'payment_pro'));
            $order_id = $tx_id;  // use order id/invoice id instead of product_id

            if(osc_get_preference('ccavenue_sandbox','payment_pro')==1) {
                $working_key = payment_pro_decrypt(osc_get_preference('ccavenue_sandbox_working_key', 'payment_pro'));
                $access_code = payment_pro_decrypt(osc_get_preference('ccavenue_sandbox_access_code', 'payment_pro'));
            } else {
                $working_key = payment_pro_decrypt(osc_get_preference('ccavenue_working_key', 'payment_pro'));
                $access_code = payment_pro_decrypt(osc_get_preference('ccavenue_access_code', 'payment_pro'));
            }
            $redirect_url = osc_route_url('ccavenue-redirect');

            $merchant_data = "";
            $merchant_data .= "tid=" . time() . "&";
            $merchant_data .= "merchant_id=" . urlencode($merchant_id) . "&";
            $merchant_data .= "order_id=" . urlencode($order_id) . "&";
            $merchant_data .= "currency=INR&";
            $merchant_data .= "amount=" . urlencode($amount) . "&";
            $merchant_data .= "redirect_url=" . urlencode($redirect_url) . "&";
            $merchant_data .= "cancel_url=" . urlencode($redirect_url) . "&";
            $merchant_data .= "language=" . urlencode("EN") . "&";
            //$merchant_data .= "merchant_param1=ABCDEF";// . urlencode($extra) . "";

            Session::newInstance()->_set('ccavenue_' . $order_id, $extra);

            $encrypted_data = self::_encrypt($merchant_data, $working_key);

            ?>
            <li class="payment ccavenue-btn">
                <form method="post" id="ccavenue_<?php echo $r; ?>" name="paymentform" class="nocsrf" action="https://<?php if(osc_get_preference('ccavenue_sandbox','payment_pro')==1){ echo 'test'; } else { echo 'secure'; }; ?>.ccavenue.com/transaction/transaction.do?command=initiateTransaction">
                    <input type=hidden name=encRequest value="<?php echo $encrypted_data; ?>">
                    <input type=hidden name=access_code value="<?php echo $access_code; ?>">
                </form>
                <a id="button-confirm" class="button" onclick="$('#ccavenue_<?php echo $r; ?>').submit();"><span><img  style="cursor:pointer;cursor:hand" src='<?php echo PAYMENT_PRO_URL; ?>payments/ccavenue/ccavenue.gif' border='0' /></span></a>
            </li>
            <?php
        }

        public static function recurringButton($products, $extra = null) {}

        public static function processPayment()
        {
            if(osc_get_preference('ccavenue_sandbox','payment_pro')==1) {
                $working_key = payment_pro_decrypt(osc_get_preference('ccavenue_sandbox_working_key', 'payment_pro'));
            } else {
                $working_key = payment_pro_decrypt(osc_get_preference('ccavenue_working_key', 'payment_pro'));
            }

            $encResponse = $_POST["encResp"];
            $rcvdString = self::_decrypt($encResponse, $working_key);
            $order_status = "";
            $ccData = self::_extractData($rcvdString);

            Params::setParam('ccavenue_order_id', @$ccData['order_id']);
            Params::setParam('ccavenue_status_message', @$ccData['status_message']);

            if(!isset($ccData['order_id']) || $ccData['order_id']=='') {
                Params::setParam('ccavenue_status_message', __('order id missing', 'payment_pro'));
                return PAYMENT_PRO_FAILED;
            }

            $exists = ModelPaymentPro::newInstance()->getPaymentByCode($ccData['order_id'], 'CCAVENUE', PAYMENT_PRO_COMPLETED);
            if (isset($exists['pk_i_id'])) {
                Params::setParam('ccavenue_status_message', __('already paid', 'payment_pro'));
                return PAYMENT_PRO_ALREADY_PAID;
            }

            $ccData['order_status'] = strtolower($ccData['order_status']);
            $ccData['status_message'] = strtolower($ccData['status_message']);

            if($ccData['order_status']==="success" || ($ccData['order_status']=='initiated' && $ccData['status_message']=='success')) {
                // SUCCESS, continue with the process
            } else if($order_status==="aborted") {
                return PAYMENT_PRO_CANCELED;
            } else {
                return PAYMENT_PRO_FAILED;
            }

            if(isset($ccData['merchant_param1']) && trim($ccData['merchant_param1'])!='') {
                $data = payment_pro_get_custom($ccData['merchant_param1']);
            } else {
                $data = payment_pro_get_custom(Session::newInstance()->_get('ccavenue_' . $ccData['order_id']));
            }

            if(empty($data['items']) || $ccData['status_message'] == 'n' || $ccData['order_status'] == 'failure') {
                Params::setParam('ccavenue_status_message', __('no items', 'payment_pro'));
                return PAYMENT_PRO_FAILED;
            }
            $status   = payment_pro_check_items($data['items'], $ccData['amount']);

            if ($ccData['vault'] == "B") {
                return PAYMENT_PRO_PENDING;
            }

            $invoiceId = ModelPaymentPro::newInstance()->saveInvoice(
                $ccData['order_id'], // transaction code
                $ccData['amount'], //amount
                0,
                $ccData['amount'], //amount
                $status,
                payment_pro_currency(), //currency
                $data['email'], // payer's email
                $data['user'], //user
                'CCAVENUE',
                $data['items']
            );

            if($status==PAYMENT_PRO_COMPLETED) {
                foreach($data['items'] as $item) {
                    $tmp = explode("-", $item['id']);
                    $item['item_id'] = $tmp[count($tmp) - 1];
                    osc_run_hook('payment_pro_item_paid', $item, $data, $invoiceId);
                }
            }
            return PAYMENT_PRO_COMPLETED;
        }



        private static function _encrypt($plainText,$key)
        {
            $secretKey = self::_hextobin(md5($key));
            $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
            $openMode = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '','cbc', '');
            $blockSize = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, 'cbc');
            $plainPad = self::_pkcs5_pad($plainText, $blockSize);
            $encryptedText = "";
            if (mcrypt_generic_init($openMode, $secretKey, $initVector) != -1)
            {
                $encryptedText = mcrypt_generic($openMode, $plainPad);
                mcrypt_generic_deinit($openMode);

            }
            return bin2hex($encryptedText);
        }

        private static function _decrypt($encryptedText,$key)
        {
            $secretKey = self::_hextobin(md5($key));
            $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
            $encryptedText = self::_hextobin($encryptedText);
            $openMode = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '','cbc', '');
            mcrypt_generic_init($openMode, $secretKey, $initVector);
            $decryptedText = mdecrypt_generic($openMode, $encryptedText);
            $decryptedText = rtrim($decryptedText, "\0");
            mcrypt_generic_deinit($openMode);
            return $decryptedText;

        }

        private static function _pkcs5_pad ($plainText, $blockSize)
        {
            $pad = $blockSize - (strlen($plainText) % $blockSize);
            return $plainText . str_repeat(chr($pad), $pad);
        }

        private static function _hextobin($hexString)
        {
            $length = strlen($hexString);
            $binString="";
            $count=0;
            while($count<$length)
            {
                $subString =substr($hexString,$count,2);
                $packedString = pack("H*",$subString);
                if ($count==0)
                {
                    $binString=$packedString;
                }

                else
                {
                    $binString.=$packedString;
                }

                $count+=2;
            }
            return $binString;
        }

        private static function _extractData($datastr) {
            $data = array();
            $tmp = explode("&", $datastr);
            foreach($tmp as $t) {
                $keyvar = explode("=", $t);
                $data[$keyvar[0]] = @$keyvar[1];
            }
            return $data;
        }

    }
