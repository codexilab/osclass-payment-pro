<?php

    class AuthorizePayment implements iPayment
    {

        public function __construct()
        {
        }

        public static function button($products, $extra = null) {
            if(count($products)==1) {
                $p = current($products);
                $amount = $p['amount']*$p['quantity'];
                $description = $p['description'];
                $product_id = $p['id'];
                //$ids = array(array('id' => $product_id));
            } else {
                $amount = 0;
                //$ids = array();
                foreach($products as $p) {
                    $amount += $p['amount']*$p['quantity'];
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
            $extra = payment_pro_set_custom($extra);

            echo '<li class="payment authorize-btn"><a href="javascript:authorize_pay(\''.osc_format_price($amount*1000000).'\',\''.$description.'\',\''.$product_id.'\',\''.$extra.'\');" ><img src="'.PAYMENT_PRO_URL . 'payments/authorize/button.gif" ></a></li>';
        }

        public static function dialogJS() { ?>
            <div id="authorize-dialog" >
                <div id="authorize-info">
                    <div id="authorize-data">
                        <p id="authorize-desc"></p>
                        <p id="authorize-price"></p>
                    </div>
                    <form action="#" method="POST" id="authorize-payment-form" >
                        <input type="hidden" name="page" value="ajax" />
                        <input type="hidden" name="action" value="runhook" />
                        <input type="hidden" name="hook" value="paymentauthorize" />
                        <input type="hidden" name="extra" value="" id="authorize-extra" />
                        <fieldset>
                            <p>
                                <label><?php _e('Card number', 'payment_pro'); ?></label>
                                <input type="text" size="20" autocomplete="off" name="authorize_number" />
                            </p>
                            <p>
                                <label><?php _e('Expiration (MM/YY)', 'payment_pro'); ?></label>
                                <input type="text" size="2" name="authorize_month" /> / <input type="text" size="2" name="authorize_year" />
                            </p>
                            <?php /* <p>
                                <label><?php _e('CVV', 'payment_pro'); ?></label>
                                <input type="text" size="4" autocomplete="off" data-encrypted-name="authorize_cvv" />
                            </p> */ ?>
                        </fieldset>

                        <?php /* <fieldset>
                            <p>
                                <label>First Name</label>
                                <input type="text" class="text required" size="15" name="x_first_name" value="" />
                            </p>
                            <p>
                                <label>Last Name</label>
                                <input type="text" class="text required" size="14" name="x_last_name" value="" />
                            </p>
                        </fieldset>
                        <fieldset>
                            <p>
                                <label>Address</label>
                                <input type="text" class="text required" size="26" name="x_address" value="" />
                            </p>
                            <p>
                                <label>City</label>
                                <input type="text" class="text required" size="15" name="x_city" value="" />
                            </p>
                        </fieldset>
                        <fieldset>
                            <p>
                                <label>State</label>
                                <input type="text" class="text required" size="4" name="x_state" value="" />
                            </p>
                            <p>
                                <label>Zip Code</label>
                                <input type="text" class="text required" size="9" name="x_zip" value="" />
                            </p>
                            <p>
                                <label>Country</label>
                                <input type="text" class="text required" size="22" name="x_country" value="" />
                            </p>
                        </fieldset>
                        <button id="authorize_submit" onclick="authorize_ajax_submit();"><?php _e('Pay', 'payment_pro'); ?></button> */ ?>
                        <button id="button-confirm" class="button" onclick="javascript:authorize_ajax_submit();" href="#"><?php _e('Pay', 'payment_pro'); ?></button>
                    </form>
                </div>
                <div id="authorize-results" style="display:none;" ><?php _e('Processing payment, please wait.', 'payment_pro'); ?></div>
            </div>
            <script type="text/javascript">


                var authorize_ajax_submit = function () {
                    $("#authorize_submit").attr("disabled", "disabled");
                    $("#authorize-results").html('<?php _e('Processing the payment, please wait', 'payment_pro');?>');
                    $("#authorize-results").show();
                    $.ajax({
                        type: "POST",
                        url: '<?php echo osc_base_url(true); ?>',
                        data: $("#authorize-payment-form").serialize(),
                        success: function(data)
                        {
                            $("#authorize-results").html(data);
                        }
                    });

                };

                function authorize_pay(amount, description, product_id, extra) {
                    $("#authorize-extra").prop('value', extra);
                    $("#authorize-desc").html(description);
                    $("#authorize-price").html((amount)+" <?php echo osc_get_preference('currency', 'payment_pro');?>");
                    $("#authorize-results").html('');
                    $("#authorize-results").hide();
                    $("#submit").removeAttr('disabled');
                    $("#authorize-info").show();
                    $("#authorize-dialog").dialog('open');
                }

                $(document).ready(function(){
                    $("#authorize-dialog").dialog({
                        autoOpen: false,
                        modal: true
                    });
                });
            </script>
        <?php
        }

        public static function recurringButton($products, $extra = null) {}

        public static  function ajaxPayment() {

            $status = AuthorizePayment::processPayment();

            if ($status==PAYMENT_PRO_COMPLETED) {
                payment_pro_cart_drop();
                osc_add_flash_ok_message(sprintf(__('Success! Please write down this transaction ID in case you have any problem: %s', 'payment_pro'), Params::getParam('braintree_transaction_id')));
                payment_pro_js_redirect_to(osc_route_url('payment-pro-done', array('tx' => Params::getParam('braintree_transaction_id'))));
            } else {
                if ($status==PAYMENT_PRO_ALREADY_PAID) {
                    payment_pro_cart_drop();
                    osc_add_flash_warning_message(__('Warning! This payment was already paid', 'payment_pro'));
                    payment_pro_js_redirect_to(osc_route_url('payment-pro-done', array('tx' => Params::getParam('authorize_transaction_id'))));
                } else {
                    printf(__('There were an error processing your payment: %s', 'payment_pro'), Params::getParam('authorize_error'));
                }
            }

        }

        public static function processPayment() {
            $sale = new AuthorizeNetAIM;
            $data = payment_pro_get_custom(Params::getParam('extra'));
            $sale->amount = $data['amount'];
            $sale->card_num = Params::getParam('authorize_number');
            $sale->exp_date = Params::getParam('authorize_month') . Params::getParam('authorize_year');
            $response = $sale->authorizeAndCapture();

            $status = payment_pro_check_items($data['items'], $response->amount);
            if ($response->approved) {

                Params::setParam('authorize_transaction_id', $response->transaction_id);
                $exists = ModelPaymentPro::newInstance()->getPaymentByCode($response->transaction_id, 'AUTHORIZE', PAYMENT_PRO_COMPLETED);
                if(isset($exists['pk_i_id'])) { return PAYMENT_PRO_ALREADY_PAID; }
                // SAVE TRANSACTION LOG
                $invoiceId = ModelPaymentPro::newInstance()->saveInvoice(
                    $response->transaction_id, // transaction code
                    $response->amount, //amount
                    $status,
                    'USD', //currency
                    $data['email'], // payer's email
                    $data['user'], //user
                    'AUTHORIZE',
                    $data['items']); //source

                if($status==PAYMENT_PRO_COMPLETED) {
                    foreach($data['items'] as $item) {
                        $tmp = explode("-", $item['id']);
                        $item['item_id'] = $tmp[count($tmp) - 1];
                        osc_run_hook('payment_pro_item_paid', $item, $data, $invoiceId);
                    }
                }

                return PAYMENT_PRO_COMPLETED;
            } else {
                $tmp = explode("Reason Text: ", $response->error_message);
                Params::setParam('authorize_error', $tmp[count($tmp)-1]);
            }
            return PAYMENT_PRO_FAILED;
        }

    }
