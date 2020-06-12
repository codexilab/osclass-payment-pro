<?php if ( ! defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

ob_get_clean();

$stripe = StripePayment::getEnvironment();
\Stripe\Stripe::setApiKey($stripe['secret_key']);
$body = @file_get_contents('php://input');
$event_json = json_decode($body);

if(!isset($event_json->id)) {
    echo "WRONG EVENT";die;
    //payment_pro_do_404();
}

$event_id = $event_json->id;
try {
    $event = \Stripe\Event::retrieve($event_id);

    $customer_email = '';
    if (isset($event->data) && isset($event->data->object) && isset($event->data->object->customer)) {
        $customer_id = $event->data->object->customer;
        $customer_email = $customer_id;
        $customer = \Stripe\Customer::retrieve($customer_id);
        if (isset($customer->deleted) && $customer->deleted) {
            print_r("CUSTOMER DELETED");
            die;
        }
        if (isset($customer->email)) {
            $customer_email = $customer->email;
        } else if (isset($customer->sources) && isset($customer->sources->data) && isset($customer->sources->data[0]) && isset($customer->sources->data[0]->name)) {
            $customer_email = $customer->sources->data[0]->name;
        } else if (isset($customer->cards) && isset($customer->cards->data) && isset($customer->cards->data[0]) && isset($customer->cards->data[0]->name)) {
            $customer_email = $customer->cards->data[0]->name;
        } else {
            $customer_email = "noemail@stripe.com";;
        }
    } else {
        // CUSTOMER ID MISSING ?
        print_r("CUSTOMER ID");
        die;
        //payment_pro_do_404();
    }
} catch(\Stripe\Error\Api $e) {
    die;
}

osc_run_hook('payment_pro_stripe_webhook', $event);

$type = $event->type;


switch($type) {
    case 'customer.subscription.created':
        $request = Params::getParamsAsArray();
        osc_run_hook('payment_pro_signup_subscription', $event, $request);
        break;
    case 'customer.subscription.deleted':
        $request = Params::getParamsAsArray();
        osc_run_hook('payment_pro_cancel_subscription', $event, $request);
        break;
    //case 'charge.succeeded':
    case 'invoice.payment_succeeded':

        $exists = ModelPaymentPro::newInstance()->getPaymentByCode($event->data->object->id, 'STRIPE', PAYMENT_PRO_COMPLETED);

        // DEBUG
        if (isset($exists['pk_i_id'])) {
            echo "PAYMENT ALREADY EXISTS " . $event->data->object->id . "/" . $exists['pk_i_id']; die;
            //payment_pro_do_404();
            //return PAYMENT_PRO_ALREADY_PAID;
        }

        // /!\  /!\  /!\ WARNING  /!\  /!\  /!\
        // WE SHOULD CHECK IF THE AMOUNTS ARE CORRECT
        // but we retrieve the event from the Stripe server, so it should be safe

        if(isset($event->data) && isset($event->data->object) && isset($event->data->object->subscription)) {
            $sub_id = $event->data->object->subscription;
            // SAVE TRANSACTION LOG
            // Second or later payment
            $productsdb = ModelPaymentPro::newInstance()->subscription($sub_id);
            $first_payment = false;
            if(count($productsdb)==0) {
                // FIRST PAYMENT
                $productsdb = ModelPaymentPro::newInstance()->subscriptionBySourceCode($sub_id);
                $first_payment = true;
            }

            if(isset($productsdb[0]) && isset($productsdb[0]['s_code'])) {
                $payment_count = ModelPaymentPro::newInstance()->subscriptionCount($productsdb[0]['s_code'])+1;
            } else {
                $payment_count = 1;
            }
            // Prepare items from the DB
            $items = array();
            $amount = 0;
            $amount_tax = 0;
            $amount_total = 0;
            foreach($productsdb as $p) {
                if($first_payment || $p['i_count']==1) {
                    $extra = json_decode($p['s_extra'], true);
                    $amount += $p['i_quantity'] * $p['i_amount'] / 1000000;
                    $amount_tax += $p['i_amount_tax'] / 1000000;
                    $amount_total += $p['i_amount_total'] / 1000000;
                    $extra['customer_id'] = $customer_id;
                    $item = array(
                        'id' => $p['i_product_type'],
                        'description' => $p['s_concept'],
                        'amount' => $p['i_amount'] / 1000000,
                        'tax' => $p['i_tax'] / 100,
                        'amount_total' => $p['i_amount_total'] / 1000000,
                        'quantity' => $p['i_quantity'],
                        'currency' => $p['s_currency_code'],
                        'extra' => $extra,
                        'sub_count' => $payment_count,
                        'sub_id' => $p['s_code']
                    );
                    $items[] = $item;
                    if ($first_payment) {
                        $extra['original'] = true;
                        ModelPaymentPro::newInstance()->updateSubscriptionItemData(
                            $p['pk_i_id'],
                            array(
                                's_extra' => json_encode($extra),
                                //'i_status' => PAYMENT_PRO_COMPLETED,
                                's_source_code' => $event->data->object->id,
                                's_code' => $sub_id
                            )
                        );
                        osc_run_hook('payment_pro_stripe_first_subscription', $sub_id, $p, $event->data->object->id, $item);
                    }
                }
            }
            // RECREATE EXTRA DATA
            $data = array(
                'items' => $items,
                'amount' => $event->data->object->total,
                'email' => $customer_email
            );

            foreach($items as $k => $item) {
                if (!$first_payment) {
                    $subitemid = ModelPaymentPro::newInstance()->updateSubscriptionItem($sub_id, $item['id'], PAYMENT_PRO_COMPLETED, $item, $event->data->object->id, "STRIPE");
                    $items[$k]['update_item_sub_id'] = $subitemid;
                }
            }
            $invoiceId = ModelPaymentPro::newInstance()->saveInvoice(
                $event->data->object->id,
                $amount,
                $amount_tax,
                $event->data->object->total/100,
                PAYMENT_PRO_COMPLETED,
                strtoupper($event->data->object->currency),
                $customer_email,
                null,
                'STRIPE',
                $items
            );

            //ModelPaymentPro::newInstance()->updateSubscriptionStatusBySourceCode($sub_id, PAYMENT_PRO_COMPLETED);
            $status = PAYMENT_PRO_COMPLETED;
            if ($status == PAYMENT_PRO_COMPLETED) {
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
            }
        } else {
            echo "NO SUBSCRIPTION";die;
            //payment_pro_do_404();
            // SUB ID DOES NOT EXISTS
        }

        break;
    default:
        break;
}

echo "COMPLETED"; DIE;

