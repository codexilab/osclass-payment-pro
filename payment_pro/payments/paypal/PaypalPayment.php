<?php

use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;



    class PaypalPayment implements iPayment
    {

        public function __construct()
        {
        }

        public static function button($products, $extra = null) {

            echo '<li class="payment paypal-btn">';

            //if(osc_get_preference('paypal_standard', 'payment_pro')==1) {
                self::standardButton($products, $extra);
            //} else {
            //    self::restAPIButton($products, $extra);
            //}

            echo '</li>';
        }

        public static function restAPIButton($products, $extra = null) {
            $r = rand(0,1000);
            $extra['random'] = $r;
            $extra = payment_pro_set_custom($extra);

            $payer = new Payer();
            $payer->setPaymentMethod("paypal");


            $items = array();
            $amount_total = 0;
            $tax_total = 0;
            $base_total = 0;
            foreach($products as $p) {

                $item = new Item();
                $item->setName($p['description'])
                    ->setCurrency(strtoupper(osc_get_preference('currency', 'payment_pro')))
                    ->setQuantity($p['quantity'])
                    ->setSku($p['id'])
                    ->setPrice($p['amount'])
                    ->setTax(($p['tax']/100)*$p['amount']);
                $base_total += $p['amount']*$p['quantity'];
                $amount_total += ($p['amount']*$p['quantity'])*((100+$p['tax'])/100);
                $tax_total += ($p['amount']*$p['quantity'])*($p['tax']/100);
                $items[] = $item;
            };

            $itemList = new ItemList();
            $itemList->setItems($items);

            $details = new Details();
            $details->setTax($tax_total)
                ->setSubtotal($base_total);

            $amount = new Amount();
            $amount->setCurrency(strtoupper(osc_get_preference('currency', 'payment_pro')))
                ->setTotal($amount_total)
                ->setDetails($details);

            $transaction = new Transaction();
            $transaction->setAmount($amount)
                ->setCustom($extra)
                ->setItemList($itemList)
                ->setDescription("Payment description")
                ->setInvoiceNumber(uniqid());

            $redirectUrls = new RedirectUrls();
            $redirectUrls->setReturnUrl(osc_route_url('paypal-return', array('extra' => $extra)))//"$baseUrl/ExecutePayment.php?success=true")
                ->setCancelUrl(osc_route_url('paypal-cancel', array('extra' => $extra)));//"$baseUrl/ExecutePayment.php?success=false");

            $payment = new Payment();
            $payment->setIntent("sale")
                ->setPayer($payer)
                ->setRedirectUrls($redirectUrls)
                ->setTransactions(array($transaction));

            try {
                $payment->create(self::getApiContext());
            } catch (Exception $ex) {
                exit(1);
            }

            $approvalUrl = $payment->getApprovalLink();
            Session::newInstance()->_set('payment_pro_paypal_tx', @$payment->id);
            echo '<div class="buttons"><div class="right"><a style="cursor:pointer;cursor:hand" id="button-confirm" class="button paypal-btn" href="' . $approvalUrl . '"><span><img src="' . PAYMENT_PRO_URL . 'payments/paypal/paypal.gif" border="0" /></span></a></div></div>';
        }

        public static function standardButton($products, $extra = null) {
            $r = rand(0,1000);
            $extra['random'] = $r;
            $extra = payment_pro_set_custom($extra);

            if(osc_get_preference('paypal_sandbox', 'payment_pro')==1) {
                $ENDPOINT     = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
            } else {
                $ENDPOINT     = 'https://www.paypal.com/cgi-bin/webscr';
            }

            ?>


            <form class="nocsrf" action="<?php echo $ENDPOINT; ?>" method="post" id="paypal_<?php echo $r; ?>">
                <input type="hidden" name="cmd" value="_cart" />
                <input type="hidden" name="notify_url" value="<?php echo osc_route_url('paypal-notify', array('extra' => $extra)); ?>" />
                <input type="hidden" name="return" value="<?php echo osc_route_url('paypal-return', array('extra' => $extra)); ?>" />
                <input type="hidden" name="cancel_return" value="<?php echo osc_route_url('paypal-cancel', array('extra' => $extra)); ?>" />
                <input type="hidden" name="business" value="<?php echo osc_get_preference('paypal_email', 'payment_pro'); ?>" />
                <input type="hidden" name="upload" value="1" />
                <input type="hidden" name="paymentaction" value="sale" />

                <?php $i = 1; foreach($products as $p) { ?>
                    <input type="hidden" name="amount_<?php echo $i; ?>" value="<?php echo $p['amount']; ?>" />
                    <input type="hidden" name="item_name_<?php echo $i; ?>" value="<?php echo $p['description']; ?>" />
                    <input type="hidden" name="item_number_<?php echo $i; ?>" value="<?php echo $p['id']; ?>" />
                    <input type="hidden" name="quantity_<?php echo $i; ?>" value="<?php echo $p['quantity']; ?>" />
                    <input type="hidden" name="tax_rate_<?php echo $i; ?>" value="<?php echo $p['tax']; ?>" />
                    <?php $i++; } ?>

                <input type="hidden" name="currency_code" value="<?php echo osc_get_preference('currency', 'payment_pro'); ?>" />
                <input type="hidden" name="custom" value="<?php echo $extra; ?>" />
                <input type="hidden" name="rm" value="2" />
                <input type="hidden" name="upload" value="1" />
                <input type="hidden" name="no_note" value="1" />
                <input type="hidden" name="charset" value="utf-8" />
            </form>
            <div class="buttons">
                <div class="right"><a style="cursor:pointer;cursor:hand" id="button-confirm" class="button paypal-btn" onclick="$('#paypal_<?php echo $r; ?>').submit();"><span><img src='<?php echo PAYMENT_PRO_URL; ?>payments/paypal/paypal.gif' border='0' /></span></a></div>
            </div>
        <?php
        }

        public static function recurringButton($products, $extra = null) {
            $r = rand(0,1000);
            //$extra['random'] = $r;

            if(count($products)==1) {
                $product = current($products);
                $subscription = $product;
                $subscription['description'] = $subscription['full_description'] = $product['description'];
                $subscription['id'] = $product['id'];
                // fix for taxes ?
                $subscription['amount'] = $product['amount']*$product['quantity'];
                $subscription['amount_total'] = ($product['amount']*$product['quantity'])*((100+$product['tax'])/100);
                $subscription['amount_tax'] = ($product['amount']*$product['quantity'])*($product['tax']/100);
                $subscription['tax'] = $product['tax'];
                $subscription['extra'] = @$product['extra'];
            } else {
                $subscription['amount'] = 0;
                $subscription['amount_tax'] = 0;
                $subscription['amount_total'] = 0;
                $subscription['tax'] = 0;
                $subscription['extra'] = array();
                $fd = array();
                $fid = array();
                foreach($products as $p) {
                    $fd[] = $p['description'];
                    $fid[] = $p['id'];
                    $subscription['amount'] += $p['amount']*$p['quantity'];
                    $subscription['amount_total'] += ($p['amount']*$p['quantity'])*((100+$p['tax'])/100);
                    $subscription['amount_tax'] += ($p['amount']*$p['quantity'])*($p['tax']/100);
                    $subscription['extra'][$p['id']] = @$p['extra'];
                }
                $subscription['description'] = $subscription['full_description'] = implode(", ", $fd);
                $subscription['id'] = "SUB-" . implode("/", $fid);
            }

            $subscription['duration'] = 1;
            $subscription['period'] = 'D';//'M';

            $subscription = osc_apply_filter('payment_pro_paypal_subscription_items', $subscription, $products);
            $extra = osc_apply_filter('payment_pro_paypal_subscription_custom', $extra, $products);

            // MOVE THIS TO A MIDDLE PAGE (AFTER BUTTON CLICKED , BEFORE GOING TO PAYPAL
            $sub_p = array();
            $sub_p[] = array(
                'description' => @$subscription['description'],
                'amount' => $subscription['amount'],
                'amount_total' => $subscription['amount_total'],
                'amount_tax' => $subscription['amount_tax'],
                'tax' => isset($subscription['tax'])?$subscription['tax']:0,
                'quantity' => 1,
                'id' => $subscription['id'],
                'status' => @$subscription['status'],
                'extra' => $subscription['extra'],
                'currency' => osc_get_preference('currency', 'payment_pro')
            );
            if( isset($subscription['trial_amount']) ) {
                $sub_p[] = array(
                    'description' => @$subscription['trial_description'],
                    'amount' => @$subscription['trial_amount'],
                    'amount_total' => $subscription['trial_amount_total'],
                    'amount_tax' => @$subscription['trial_amount_tax'],
                    'tax' => @$subscription['trial_tax'],
                    'quantity' => 1,
                    'id' => $subscription['trial_id'],
                    'status' => @$subscription['trial_status'],
                    'extra' => @$subscription['trial_extra'],
                    'currency' => osc_get_preference('currency', 'payment_pro')
                );
            }

            // MOVE THIS TO AN INTERMEDIATE STAGE
            $sub_id = ModelPaymentPro::newInstance()->createSubscription($sub_p, "PAYPAL");

            $extra['sub_id'] = $sub_id;
            $full_extra = $extra;
            $full_extra['p'] = array();
            foreach($sub_p as $p) {
                $full_extra['p'][] = $p['id'];
            }
            $extra = payment_pro_set_custom($extra);
            $full_extra = payment_pro_set_custom($full_extra);

            if(osc_get_preference('paypal_sandbox', 'payment_pro')==1) {
                $ENDPOINT     = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
            } else {
                $ENDPOINT     = 'https://www.paypal.com/cgi-bin/webscr';
            }

            ?>


            <form class="nocsrf" action="<?php echo $ENDPOINT; ?>" method="post" id="paypal_<?php echo $r; ?>">
                <input type="hidden" name="cmd" value="_xclick-subscriptions" />
                <input type="hidden" name="notify_url" value="<?php echo osc_route_url('paypal-notify', array('extra' => $extra)); ?>" />
                <input type="hidden" name="return" value="<?php echo osc_route_url('paypal-return', array('extra' => $extra)); ?>" />
                <input type="hidden" name="cancel_return" value="<?php echo osc_route_url('paypal-cancel', array('extra' => $extra)); ?>" />
                <input type="hidden" name="business" value="<?php echo osc_get_preference('paypal_email', 'payment_pro'); ?>" />
                <input type="hidden" name="no_shipping" value="1">
                <input type="hidden" name="item_name" value="<?php echo osc_esc_html($subscription['full_description']); ?>" />

                <?php if( isset($subscription['trial_amount']) ) { ?>
                    <input type="hidden" name="a1" value="<?php echo osc_esc_html(@$subscription['trial_amount_total']); ?>" />
                    <input type="hidden" name="p1" value="<?php echo osc_esc_html($subscription['trial_duration']); ?>" />
                    <input type="hidden" name="t1" value="<?php echo osc_esc_html($subscription['trial_period']); ?>" />
                <?php } ?>

                <input type="hidden" name="a3" value="<?php echo osc_esc_html(@$subscription['amount_total']); ?>" />
                <input type="hidden" name="p3" value="<?php echo osc_esc_html($subscription['duration']); ?>" />
                <input type="hidden" name="t3" value="<?php echo osc_esc_html($subscription['period']); ?>" />
                <input type="hidden" name="src" value="1" />

                <input type="hidden" name="currency_code" value="<?php echo osc_get_preference('currency', 'payment_pro'); ?>" />
                <input type="hidden" name="custom" value="<?php echo $full_extra; ?>" />
                <input type="hidden" name="no_note" value="1" />
                <input type="hidden" name="charset" value="utf-8" />
            </form>
            <div class="buttons">
                <div class="right"><a style="cursor:pointer;cursor:hand;" id="button-confirm" class="button paypal-btn" onclick="$('#paypal_<?php echo $r; ?>').submit();"><span><img src='<?php echo PAYMENT_PRO_URL; ?>payments/paypal/subscription.gif' border='0' /></span></a></div>
            </div>
        <?php
        }


        public static function processPayment() {
            //if(osc_get_preference('paypal_standard', 'payment_pro')==1) {
                return self::processStandardPayment();
            //} else {
            //    return self::processRestAPIPayment();
            //}

        }

        public static function processRestAPIPayment() {

            if(Params::getParam('paymentId')!='') {
                $paymentId = Params::getParam('paymentId');
            } else {
                $paymentId = Session::newInstance()->_get('payment_pro_paypal_tx');
            }

            $apiContext = self::getApiContext();


            try {
                $payment = Payment::get($paymentId, $apiContext);

                if($payment->state!='approved') {
                    $execution = new PaymentExecution();
                    $execution->setPayerId(Params::getParam('PayerID'));
                    $result = $payment->execute($execution, $apiContext);
                    // this is not needed as $result already have the same information
                    $payment = Payment::get($paymentId, $apiContext);
                }
                if($payment->state=='approved') {
                    // Have we processed the payment already?
                    $paid = ModelPaymentPro::newInstance()->getPaymentByCode($paymentId, 'PAYPAL', PAYMENT_PRO_COMPLETED);
                    if (!isset($paid['pk_i_id'])) {

                        foreach($payment->transactions as $tx) {
                            $data = payment_pro_get_custom($tx->custom);

                            $items = array();
                            foreach($tx->item_list->items as $item) {
                                $id = $item->sku;
                                $code = substr($id, 0, 3);
                                $itemid = null;
                                if($code=='PUB' || $code=='PRM') {
                                    $tmp = explode("-", $id);
                                    $itemid = @$tmp[1];
                                }
                                $items[] = array(
                                    'id' => $id,
                                    'description' => $item->name,
                                    'amount' => $item->price*$item->quantity,
                                    'tax' => round(1000*$item->tax/$item->price),
                                    'amount_tax' => $item->tax*$item->quantity,
                                    'amount_total' => ($item->price+$item->tax)*$item->quantity,
                                    'quantity' => $item->quantity,
                                    'item_id' => $itemid
                                );

                            }

                            $amount_total = $tx->amount->total;
                            $status = payment_pro_check_items($items, $amount_total);

                            // SAVE TRANSACTION LOG
                            $invoiceId = ModelPaymentPro::newInstance()->saveInvoice(
                                $paymentId,
                                $tx->amount->details->subtotal,
                                $tx->amount->details->tax,
                                $amount_total,
                                $status,
                                @$tx->amount->currency,
                                @$payment->payer->payer_info->email,
                                @$data['user'], //user
                                'PAYPAL',
                                $items
                            );
                            if($status==PAYMENT_PRO_COMPLETED) {
                                foreach($items as $item) {
                                    osc_run_hook('payment_pro_item_paid', $item, $data, $invoiceId);
                                }
                            }
                            return PAYMENT_PRO_COMPLETED;

                        }
                    }
                    return PAYMENT_PRO_ALREADY_PAID;
                }

            } catch(Exception $ex) {
                return PAYMENT_PRO_FAILED;
            }
            return PAYMENT_PRO_FAILED;
        }

        public static function processStandardPayment() {
            if (Params::getParam('payment_status') == 'Completed' || Params::getParam('st') == 'Completed') {
                // Have we processed the payment already?
                $tx = Params::getParam('tx')!=''?Params::getParam('tx'):Params::getParam('txn_id');
                $payment = ModelPaymentPro::newInstance()->getPaymentByCode($tx, 'PAYPAL', PAYMENT_PRO_COMPLETED);
                if (!isset($payment['pk_i_id'])) {

                    if(Params::getParam('cm')!='') {
                        $data = Params::getParam('cm');
                    } else if(Params::getParam('custom')!='') {
                        $data = Params::getParam('custom');
                    } else {
                        $data = Params::getParam('extra');
                    }
                    $data = payment_pro_get_custom($data);

                    $items = array();
                    $num_items = (int)Params::getParam('num_cart_items');
                    $amount = 0;
                    $amount_tax = 0;
                    $amount_total = 0;
                    for($i=1;$i<=$num_items;$i++) {
                        $id = Params::getParam('item_number' . $i);
                        $code = substr($id, 0, 3);
                        $itemid = null;
                        if($code=='PUB' || $code=='PRM') {
                            $tmp = explode("-", $id);
                            $itemid = @$tmp[1];
                        }

                        $_amount = Params::getParam('mc_gross_' . $i)/Params::getParam('quantity' . $i);
                        $_amount_total = Params::getParam('mc_gross_' . $i)+(Params::getParam('mc_tax' . $i)*Params::getParam('quantity' . $i));
                        if(Params::existParam('mc_tax' . $i)) {
                            $_amount_tax = Params::getParam('mc_tax' . $i)*Params::getParam('quantity' . $i);
                        } else {
                            $_amount_tax = Params::getParam('tax' . $i)*Params::getParam('quantity' . $i);
                        }

                        $amount += $_amount;
                        $amount_tax += $_amount_tax;
                        $amount_total += $_amount_total;

                        $items[] = array(
                            'id' => $id,
                            'description' => Params::getParam('item_name_' . $i),
                            'amount' => $_amount,
                            'amount_total' => $_amount_total,
                            'quantity' => Params::getParam('quantity' . $i),
                            'amount_tax' => $_amount_tax,
                            'item_id' => $itemid
                            );
                    }

                    $amount_total = Params::getParam('payment_gross')!=''?Params::getParam('payment_gross'):Params::getParam('mc_gross');
                    $status = payment_pro_check_items($items, $amount_total);

                    $product_type = explode('x', Params::getParam('item_number'));
                    // SAVE TRANSACTION LOG
                    $invoiceId = ModelPaymentPro::newInstance()->saveInvoice(
                        $tx,
                        $amount,
                        $amount_tax,
                        $amount_total,
                        $status,
                        Params::getParam('mc_currency'), //currency
                        Params::getParam('payer_email')!=''?Params::getParam('payer_email'):'', // payer's email
                        @$data['user'], //user
                        'PAYPAL',
                        $items
                        );
                    if($status==PAYMENT_PRO_COMPLETED) {
                        foreach($items as $item) {
                            osc_run_hook('payment_pro_item_paid', $item, $data, $invoiceId);
                        }
                    }
                    return PAYMENT_PRO_COMPLETED;
                }
                return PAYMENT_PRO_ALREADY_PAID;
            }
            return PAYMENT_PRO_PENDING;
        }

        /**
         * Track subscriptions
         * @return type
         */
        public static function processSubcriptionPayment() {
            if(Params::getParam('custom')!='') {
                $data = Params::getParam('custom');
            } else {
                $data = Params::getParam('extra');
            }
            $data = payment_pro_get_custom($data);
            $subscr_id = Params::getParam('subscr_id');
            if($subscr_id=='') { // No ID ? wrong data
                return PAYMENT_PRO_FAILED;
            }

            $sub_id = 0;
            if(isset($data['sub_id'])) {
                $sub_id = $data['sub_id'];
            } else {
                return PAYMENT_PRO_FAILED;
            }
            if(Params::getParam('txn_type')=='subscr_signup') {
                // THIS COULD BE LATER AFTER THE PAYMENT WAS MADE O.o
                $subs = ModelPaymentPro::newInstance()->subscription($sub_id, PAYMENT_PRO_CREATED);
                if(empty($subs)) {
                    return PAYMENT_PRO_FAILED; // Sub does not exist or ID has already changed
                }
                foreach($subs as $s) {
                    ModelPaymentPro::newInstance()->updateSubscriptionItemData($s['pk_i_id'], array('s_code' => $subscr_id, 'i_status' => PAYMENT_PRO_PENDING));
                }
                $request = Params::getParamsAsArray();
                osc_run_hook('payment_pro_signup_subscription', $data, $request);
                return PAYMENT_PRO_PENDING;
            } else if(Params::getParam('txn_type')=='subscr_cancel') {
                // LAUNCH SOME HOOK HERE
                $request = Params::getParamsAsArray();
                osc_run_hook('payment_pro_cancel_subscription', $data, $request);
            } else if(Params::getParam('txn_type')=='subscr_payment') {
                if (Params::getParam('payment_status') == 'Completed') {
                    $mc_gross = Params::getParam('mc_gross')*1000000;
                    // Have we processed the payment already?
                    $tx = Params::getParam('txn_id');
                    $payment = ModelPaymentPro::newInstance()->getPaymentByCode($tx, 'PAYPAL', PAYMENT_PRO_COMPLETED);

                    if (!isset($payment['pk_i_id'])) {

                        $subs = ModelPaymentPro::newInstance()->subscription($subscr_id);
                        if(empty($subs)) {
                            $subs = ModelPaymentPro::newInstance()->subscription($sub_id);
                            foreach($subs as $s) {
                                ModelPaymentPro::newInstance()->updateSubscriptionItemData($s['pk_i_id'], array('s_code' => $subscr_id, 'i_status' => PAYMENT_PRO_PENDING));
                                osc_run_hook('payment_pro_paypal_first_subscription', $subscr_id, $s);
                            }
                        }

                        $amount = Params::getParam('mc_gross');
                        $amount_tax = 0;
                        $tax = 0;
                        $extra = '';
                        $item_sub_id = '';
                        $item_sub = array();

                        $product_id = "FAILED";
                        if(isset($data['p'])) {
                            $found = false;
                            foreach($data['p'] as $p) {
                                $item_sub = ModelPaymentPro::newInstance()->subscriptionItem($subscr_id, $p);
                                if(isset($item_sub['i_amount_total']) && $mc_gross==$item_sub['i_amount_total']) {
                                    $product_id = $item_sub['i_product_type'];
                                    $amount = $item_sub['i_amount']/1000000;
                                    $amount_tax = $item_sub['i_amount_tax']/1000000;
                                    $tax = $item_sub['i_tax']/1000000;
                                    $extra = json_decode($item_sub['s_extra'], true);
                                    $found = true;
                                    break;
                                }
                            };
                            if(!$found) {
                                return PAYMENT_PRO_FAILED;
                            }
                        } else {
                            return PAYMENT_PRO_FAILED;
                        }

                        $payment_count = ModelPaymentPro::newInstance()->subscriptionCount($subscr_id)+1;
                        $items = array();
                        $items[] = array(
                                'id' => $product_id,
                                'description' => Params::getParam('item_name'),
                                'amount' => $amount,
                                'amount_total' => Params::getParam('mc_gross'),
                                'amount_tax' => $amount_tax,
                                'tax' => $tax,
                                'quantity' => 1,
                                'item_id' => 0,
                                'currency' => Params::getParam('mc_currency'),
                                'extra' => $extra,
                                'sub_count' => $payment_count,
                                'sub_id' => $sub_id
                        );

                        foreach($items as $k => $item) {
                            $subitemid = ModelPaymentPro::newInstance()->updateSubscriptionItem($subscr_id, $item['id'], PAYMENT_PRO_COMPLETED, $item, $tx, "PAYPAL");
                            $items[$k]['update_item_sub_id'] = $subitemid;
                        }

                        // SAVE TRANSACTION LOG
                        $invoiceId = ModelPaymentPro::newInstance()->saveInvoice(
                            $tx,
                            $amount,
                            $amount_tax,
                            Params::getParam('mc_gross'),
                            PAYMENT_PRO_COMPLETED,
                            Params::getParam('mc_currency'), //currency
                            Params::getParam('payer_email')!=''?Params::getParam('payer_email'):'', // payer's email
                            @$data['user'], //user
                            'PAYPAL',
                            $items
                        );
                        foreach($items as $item) {
                            $itemid = explode("-", $item['id']);
                            $item['item_id'] = $itemid[count($itemid)-1];
                            if (substr($item['id'], 0, 4) == 'SUB-') {
                                $tmp = explode("/", str_replace("SUB-", "", $item['id']));
                                foreach($tmp as $t) {
                                    $itemid = explode("-", $t);
                                    $tmp_item = $item;
                                    $tmp_item['id'] = $t;
                                    $tmp_item['item_id'] = $itemid[count($itemid)-1];
                                    osc_run_hook('payment_pro_item_paid', $tmp_item, $data, $invoiceId);
                                }
                            } else {
                                osc_run_hook('payment_pro_item_paid', $item, $data, $invoiceId);
                            }
                        }
                        return PAYMENT_PRO_COMPLETED;
                    }
                    return PAYMENT_PRO_ALREADY_PAID;
                }
            } else { // ???
            }

            return PAYMENT_PRO_FAILED;
        }

        //Makes an API call using an NVP String and an Endpoint
        public static function httpPost($my_endpoint, $my_api_str) {
            // setting the curl parameters.
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $my_endpoint);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            // turning off the server and peer verification(TrustManager Concept).
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            // setting the NVP $my_api_str as POST FIELD to curl
            curl_setopt($ch, CURLOPT_POSTFIELDS, $my_api_str);
            // getting response from server
            $httpResponse = curl_exec($ch);
            if (!$httpResponse) {
                $response = "API failed: " . curl_error($ch) . '(' . curl_errno($ch) . ')';
                return $response;
            }
            $httpResponseAr = explode("&", $httpResponse);
            $httpParsedResponseAr = array();
            foreach ($httpResponseAr as $i => $value) {
                $tmpAr = explode("=", $value);
                if (sizeof($tmpAr) > 1) {
                    $httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
                }
            }

            if ((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) {
                $response = "Invalid HTTP Response for POST request($my_api_str) to $my_endpoint.";
                return $response;
            }

            return $httpParsedResponseAr;
        }

        public static function getApiContext() {

            $clientId = '';
            $clientSecret = '';


            $apiContext = new ApiContext(
                new OAuthTokenCredential(
                    $clientId,
                    $clientSecret
                )
            );

            // Comment this line out and uncomment the PP_CONFIG_PATH
            // 'define' block if you want to use static file
            // based configuration

            $apiContext->setConfig(
                array(
                    'mode' => 'sandbox',
                    'log.LogEnabled' => true,
                    'log.FileName' => PAYMENT_PRO_PATH . 'paypal.log',
                    'log.LogLevel' => 'DEBUG', // PLEASE USE `FINE` LEVEL FOR LOGGING IN LIVE ENVIRONMENTS
                    'validation.level' => 'log',
                    'cache.enabled' => false,//true,
                    // 'http.CURLOPT_CONNECTTIMEOUT' => 30
                    // 'http.headers.PayPal-Partner-Attribution-Id' => '123123123'
                )
            );

            // Partner Attribution Id
            // Use this header if you are a PayPal partner. Specify a unique BN Code to receive revenue attribution.
            // To learn more or to request a BN Code, contact your Partner Manager or visit the PayPal Partner Portal
            // $apiContext->addRequestHeader('PayPal-Partner-Attribution-Id', '123123123');

            return $apiContext;
        }

        public static function changeSubscriptionStatus( $subscr_id, $action = 'Cancel')
        {
            // action = 'Cancel' / 'Suspend' / 'Reactivate';
            $api_request = 'USER=' . urlencode(payment_pro_decrypt(osc_get_preference('paypal_api_username', 'payment_pro')))
                . '&PWD=' . urlencode(payment_pro_decrypt(osc_get_preference('paypal_api_password', 'payment_pro')))
                . '&SIGNATURE=' . urlencode(payment_pro_decrypt(osc_get_preference('paypal_api_signature', 'payment_pro')))
                . '&VERSION=76.0'
                . '&METHOD=ManageRecurringPaymentsProfileStatus'
                . '&PROFILEID=' . urlencode($subscr_id)
                . '&ACTION=' . urlencode($action)
                . '&NOTE=' . urlencode('Profile cancelled at store');

            $ch = curl_init();

            if (osc_get_preference('paypal_sandbox', 'payment_pro') == 1) {
                curl_setopt($ch, CURLOPT_URL, 'https://api-3t.sandbox.paypal.com/nvp'); // For live transactions, change to 'https://api-3t.paypal.com/nvp'
            } else {
                curl_setopt($ch, CURLOPT_URL, 'https://api-3t.paypal.com/nvp');
            }

            curl_setopt($ch, CURLOPT_VERBOSE, 1);

            // Uncomment these to turn off server and peer verification
             curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
             curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );



            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);

            // Set the API parameters for this transaction
            curl_setopt($ch, CURLOPT_POSTFIELDS, $api_request);

            // Request response from PayPal
            $response = curl_exec($ch);

            // If no response was received from PayPal there is no point parsing the response
            if (!$response)
                die('Calling PayPal to change_subscription_status failed: ' . curl_error($ch) . '(' . curl_errno($ch) . ')');

            curl_close($ch);

            // An associative array is more usable than a parameter string
            parse_str($response, $parsed_response);

            return $parsed_response;
        }

    }
