<?php

    class BraintreePayment implements iPayment
    {

        public function __construct()
        {
            Braintree_Configuration::environment(osc_get_preference('braintree_sandbox', 'payment_pro'));
            Braintree_Configuration::merchantId(payment_pro_decrypt(osc_get_preference('braintree_merchant_id', 'payment_pro')));
            Braintree_Configuration::publicKey(payment_pro_decrypt(osc_get_preference('braintree_public_key', 'payment_pro')));
            Braintree_Configuration::privateKey(payment_pro_decrypt(osc_get_preference('braintree_private_key', 'payment_pro')));
        }

        public static function button($products, $extra = null) {
            if(count($products)==1) {
                $p = current($products);
                $amount = $p['amount']*$p['quantity'];
                $description = $p['description'];
                $product_id = $p['id'];
                $tax = $p['amount']*$p['quantity']*$p['tax']/100;
                //$ids = array(array('id' => $product_id));
            } else {
                $amount = 0;
                $tax = 0;
                //$ids = array();
                foreach($products as $p) {
                    $amount += $p['amount']*$p['quantity'];
                    $tax += $p['amount']*$p['quantity']*$p['tax']/100;
                    //$ids[] = array('id' => $p['id']);
                }
                $description = sprintf(__('%d products', 'payment_pro'), count($products));
                $product_id = 'SVR_PRD';
            }
            $r = rand(0,1000);
            $extra['random'] = $r;
            //$extra['ids'] = $ids;
            $extra['items'] = $products;
            $extra['amount'] = $amount;
            $extra['tax'] = $tax;
            $total = $amount + $tax;
            $extra['amount_total'] = $total;

            $extra = payment_pro_set_custom($extra);


            echo '<li class="payment braintree-btn"><a href="javascript:braintree_pay(\''.osc_format_price($total*1000000).'\',\''.$description.'\',\''.$product_id.'\',\''.$extra.'\');" ><img src="'.PAYMENT_PRO_URL . 'payments/braintree/pay_with_card.png" ></a></li>';
        }

        public static function recurringButton($products, $extra = null) {}

        public static function dialogJS() { ?>
            <div id="braintree-dialog" >
                <div id="braintree-info">
                    <div id="braintree-data">
                        <p id="braintree-desc"></p>
                        <p id="braintree-price"></p>
                    </div>
                    <form action="<?php echo osc_base_url(true); ?>" method="POST" id="braintree-payment-form" >
                        <input type="hidden" name="page" value="ajax" />
                        <input type="hidden" name="action" value="runhook" />
                        <input type="hidden" name="hook" value="braintree" />
                        <input type="hidden" name="extra" value="" id="braintree-extra" />
                        <p>
                            <label><?php _e('Card number', 'payment_pro'); ?></label>
                            <input type="text" size="20" autocomplete="off" data-encrypted-name="braintree_number" />
                        </p>
                        <p>
                            <label><?php _e('CVV', 'payment_pro'); ?></label>
                            <input type="text" size="4" autocomplete="off" data-encrypted-name="braintree_cvv" />
                        </p>
                        <p>
                            <label><?php _e('Expiration (MM/YYYY)', 'payment_pro'); ?></label>
                            <input type="text" size="2" data-encrypted-name="braintree_month" /> / <input type="text" size="4" data-encrypted-name="braintree_year" />
                        </p>
                        <input type="submit" id="submit" />
                    </form>
                </div>
                <div id="braintree-results" style="display:none;" ><?php _e('Processing payment, please wait.', 'payment_pro'); ?></div>
            </div>
            <script type="text/javascript" src="https://js.braintreegateway.com/v1/braintree.js"></script>
            <script type="text/javascript">


                var braintree_ajax_submit = function (e) {
                    form = $('#braintree-payment-form');
                    e.preventDefault();
                    $("#submit").attr("disabled", "disabled");
                    $("#braintree-info").hide();
                    $("#braintree-results").html('<?php _e('Processing the payment, please wait', 'payment_pro');?>');
                    $("#braintree-results").show();
                    $.post(form.attr('action'), form.serialize(), function (data) {
                        $("#braintree-results").html(data);
                    });
                };
                var braintree = Braintree.create('<?php echo payment_pro_decrypt(osc_get_preference('braintree_encryption_key', 'payment_pro')); ?>');
                braintree.onSubmitEncryptForm('braintree-payment-form', braintree_ajax_submit);

                function braintree_pay(amount, description, product_id, extra) {
                    $("#braintree-extra").prop('value', extra);
                    $("#braintree-desc").html(description);
                    $("#braintree-price").html((amount)+" <?php echo osc_get_preference('currency', 'payment_pro');?>");
                    $("#braintree-results").html('');
                    $("#braintree-results").hide();
                    $("#submit").removeAttr('disabled');
                    $("#braintree-info").show();
                    $("#braintree-dialog").dialog('open');
                }

                $(document).ready(function(){
                    $("#braintree-dialog").dialog({
                        autoOpen: false,
                        modal: true
                    });
                });
            </script>
        <?php
        }

        public static  function ajaxPayment() {
            $status = BraintreePayment::processPayment();
            if ($status==PAYMENT_PRO_COMPLETED) {
                payment_pro_cart_drop();
                osc_add_flash_ok_message(sprintf(__('Success! Please write down this transaction ID in case you have any problem: %s', 'payment_pro'), Params::getParam('braintree_transaction_id')));
            } else {
                if ($status==PAYMENT_PRO_ALREADY_PAID) {
                    payment_pro_cart_drop();
                    osc_add_flash_warning_message(__('Warning! This payment was already paid', 'payment_pro'));
                } else {
                    osc_add_flash_error_message(__('There were an error processing your payment', 'payment_pro'));
                }
            }
            payment_pro_js_redirect_to(osc_route_url('payment-pro-done', array('tx' => Params::getParam('braintree_transaction_id'))));
        }

        public static function processPayment() {
            //require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'lib/Braintree.php';

            Braintree_Configuration::environment(osc_get_preference('braintree_sandbox', 'payment_pro'));
            Braintree_Configuration::merchantId(payment_pro_decrypt(osc_get_preference('braintree_merchant_id', 'payment_pro')));
            Braintree_Configuration::publicKey(payment_pro_decrypt(osc_get_preference('braintree_public_key', 'payment_pro')));
            Braintree_Configuration::privateKey(payment_pro_decrypt(osc_get_preference('braintree_private_key', 'payment_pro')));

            $data = payment_pro_get_custom(Params::getParam('extra'));

            if(!isset($data['items']) || !isset($data['amount_total']) || $data['amount_total']<=0) {
                return PAYMENT_PRO_FAILED;
            }
            $status = payment_pro_check_items($data['items'], $data['amount_total']);

            $result = Braintree_Transaction::sale(array(
                'amount' => $data['amount_total'],
                'creditCard' => array(
                    'number' => Params::getParam('braintree_number'),
                    'cvv' => Params::getParam('braintree_cvv'),
                    'expirationMonth' => Params::getParam('braintree_month'),
                    'expirationYear' => Params::getParam('braintree_year')
                ),
                'options' => array(
                    'submitForSettlement' => true
                )
            ));

            $tax = 0;
            $amount = 0;
            if(isset($data['tax'])) {
                $tax = $data['tax'];
            }
            if(isset($data['amount'])) {
                $amount = $data['amount'];
            }

            if($result->success==1) {

                Params::setParam('braintree_transaction_id', $result->transaction->id);
                $exists = ModelPaymentPro::newInstance()->getPaymentByCode($result->transaction->id, 'BRAINTREE', PAYMENT_PRO_COMPLETED);
                if(isset($exists['pk_i_id'])) { return PAYMENT_PRO_ALREADY_PAID; }
                // SAVE TRANSACTION LOG
                $invoiceId = ModelPaymentPro::newInstance()->saveInvoice(
                    $result->transaction->id, // transaction code
                    $amount, //bimp
                    $tax,
                    $result->transaction->amount, //amount
                    $status,
                    $result->transaction->currencyIsoCode, //currency
                    $data['email'], // payer's email
                    $data['user'], //user
                    'BRAINTREE',
                    $data['items']); 



                if($status==PAYMENT_PRO_COMPLETED) {
                    foreach($data['items'] as $item) {
                        $tmp = explode("-", $item['id']);
                        $item['item_id'] = $tmp[count($tmp) - 1];
                        osc_run_hook('payment_pro_item_paid', $item, $data, $invoiceId);
                    }
                }


                return PAYMENT_PRO_COMPLETED;
            } else {
                return PAYMENT_PRO_FAILED;
            }
        }

    }
