<?php

class PagseguroPayment implements iPayment
{

    public function __construct()
    {
    }

    public static function button($products, $extra = null)
    {
        $paymentRequest = new PagSeguroPaymentRequest();
        $paymentRequest->setCurrency(osc_get_preference("currency", "payment_pro"));

        foreach($products as $p) {
            $paymentRequest->addItem(
                $p['id'],
                $p['description'],
                $p['quantity'],
                ceil($p['amount']*$p['quantity']*((100+$p['tax'])/100))
            );
        }

        $tx_id = ModelPaymentPro::newInstance()->pendingInvoice($products);
        ModelPaymentPro::newInstance()->setPendingExtra($tx_id, json_encode(array('fk_i_user_id' => osc_logged_user_id())), 'PAGSEGURO');
        $paymentRequest->setReference($tx_id);

        $paymentRequest->setRedirectUrl(osc_route_url('pagseguro-return', array('txid' => $tx_id)));

        try {
            $credentials = PagSeguroConfig::getAccountCredentials();
            $onlyCheckoutCode = false;
            $code = $paymentRequest->register($credentials, $onlyCheckoutCode);
            ?>
            <li class="payment pagseguro-btn">
                <?php if(osc_get_preference('pagseguro_lightbox', 'payment_pro')==1) { ?>
                    <div id="pagseguro_btn" style="cursor:pointer;cursor:hand" onclick="javascript:PagSeguroLightbox('<?php echo $code; ?>');" >
                        <img src="<?php echo PAYMENT_PRO_URL; ?>payments/pagseguro/assets/button.png">
                    </div>
                <?php } else { ?>
                    <div id="pagseguro_btn" style="cursor:pointer;cursor:hand" >
                        <a href="<?php echo $code; ?>">
                            <img src="<?php echo PAYMENT_PRO_URL; ?>payments/pagseguro/assets/button.png">
                        </a>
                    </div>
                <?php }; ?>
            </li>
            <?php
        } catch (PagSeguroServiceException $e) {
            //die($e->getMessage());
        }
    }

    public static function recurringButton($products, $extra = null)
    {
    }

    public static function processPayment()
    {
        $code = trim(Params::getParam('notificationCode'));
        $type = trim(Params::getParam('notificationType'));

        if ($code!="" && $type!="") {
            $notificationType = new PagSeguroNotificationType($type);
            $strType = $notificationType->getTypeFromValue();
            switch ($strType) {
                case 'TRANSACTION':
                    self::transactionNotification($code);
                    break;
                case 'APPLICATION_AUTHORIZATION':
                    //self::authorizationNotification($code);
                    break;
                case 'PRE_APPROVAL':
                    //self::preApprovalNotification($code);
                    break;
                default:
                    error_log("Unknown notification type [" . $notificationType->getValue() . "]");
                    return PAYMENT_PRO_FAILED;
            }
        }
        return PAYMENT_PRO_FAILED;
    }



    private static function transactionNotification($notificationCode)
    {
        $credentials = PagSeguroConfig::getAccountCredentials();

        try {
            $transaction = PagSeguroNotificationService::checkTransaction($credentials, $notificationCode);

            $tx_status = $transaction->getStatus()->getValue();
            /*$statusList = array(
                'INITIATED' => 0,
                'WAITING_PAYMENT' => 1,
                'IN_ANALYSIS' => 2,
                'PAID' => 3,
                'AVAILABLE' => 4,
                'IN_DISPUTE' => 5,
                'REFUNDED' => 6,
                'CANCELLED' => 7,
                'SELLER_CHARGEBACK' => 8,
                'CONTESTATION' => 9
            );*/
            if($tx_status==0 || $tx_status==1) {
                return PAYMENT_PRO_PENDING;
            } else if($tx_status!=3) {
                return PAYMENT_PRO_FAILED;
            }

            $tx_id = $transaction->getCode();
            $reference = $transaction->getReference();
            $gross_amount = $transaction->getGrossAmount();
            $items = $transaction->getItems();
            $sender = $transaction->getSender();
            $email = $sender->getEmail();

            $data_items = ModelPaymentPro::newInstance()->getPending($reference);
            if(empty($data_items)) {
                return PAYMENT_PRO_FAILED;
            }

            $amount = 0;
            $amount_tax = 0;
            $user_id = 0;
            foreach($data_items as $k => $v) {
                $data_items[$k]['amount'] = $v['amount']/1000000;
                $data_items[$k]['amount_total'] = $v['amount_total']/1000000;
                $data_items[$k]['amount_tax'] = $v['amount_tax']/1000000;
                $data_items[$k]['tax'] = $v['tax']/100;
                $amount += $data_items[$k]['amount'];
                $amount_tax += $data_items[$k]['amount_tax'];
            }

            $status = payment_pro_check_items($data_items, $gross_amount);
            $extra = ModelPaymentPro::newInstance()->pendingExtra($reference);


            $user_id = 0;
            if(isset($extra['fk_i_user_id'])) {
                $user_id = $extra['fk_i_user_id'];
            }

            $exists = ModelPaymentPro::newInstance()->getPaymentByCode($tx_id, 'PAGSEGURO', PAYMENT_PRO_COMPLETED);
            if(isset($exists['pk_i_id'])) { return PAYMENT_PRO_ALREADY_PAID; }

            // SAVE TRANSACTION LOG
            $invoiceId = ModelPaymentPro::newInstance()->saveInvoice(
                $tx_id, // transaction code
                $amount,
                $amount_tax,
                $gross_amount, //amount
                $status,
                osc_get_preference('currency', 'payment_pro'), //currency
                $email, // payer's email
                $user_id, //user
                'PAGSEGURO',
                $data_items
            );

            ModelPaymentPro::newInstance()->updatePendingInvoiceExtra($reference, $invoiceId, 'PAGSEGURO');
            if($status==PAYMENT_PRO_COMPLETED) {
                foreach($data_items as $item) {
                    $tmp = explode("-", $item['id']);
                    $item['item_id'] = $tmp[count($tmp) - 1];
                    osc_run_hook('payment_pro_item_paid', $item, array(), $invoiceId);
                }
                ModelPaymentPro::newInstance()->deletePending($reference);
            }

            return $status;
        } catch (PagSeguroServiceException $e) {
            error_log($e->getMessage());
        }
    }
}


