<?php if ( ! defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

    $pay = Params::getParam('pay');
    $id = Params::getParam('id');
    $highlight = Params::getParam('highlight');
    $moveToTop = Params::getParam('top');

    $data = ModelPaymentPro::newInstance()->getPublishData($id);
    if(!$data) {
        $item = Item::newInstance()->findByPrimaryKey($id);
        ModelPaymentPro::newInstance()->createItem($item['pk_i_id'], 0, null, null, $item['b_enabled']);
    }

    if($highlight==1) {
        if ($pay == 1) {
            ModelPaymentPro::newInstance()->payHighlightFee($id, 'ADMIN');
        } else {
            ModelPaymentPro::newInstance()->unpayHighlightFee($id);
        }
    } else if($moveToTop==1) {
        if($pay==1) {
            Item::newInstance()->update(array('dt_pub_date' => date('Y-m-d H:i:s')), array('pk_i_id' => $id));
            $invoiceId = ModelPaymentPro::newInstance()->adminInvoice(
                array(
                    'description' => sprintf(__('Highlight enhancement for listing %d', 'payment_pro'), $id),
                    'item_id' => $id,
                    'id' => 'TOP-' . $id
                )
            );
        }
    } else {
        switch($pay) {
            case 0: // UNPAID
                ModelPaymentPro::newInstance()->unpayPublishFee($id);
                osc_add_flash_ok_message(__('Listing unpaid', 'payment_pro'), 'admin');
                break;
            case 1: // PAID
                ModelPaymentPro::newInstance()->enableItem($id);
                ModelPaymentPro::newInstance()->payPublishFee($id, 'ADMIN');
                osc_add_flash_ok_message(__('Listing paid', 'payment_pro'), 'admin');
                break;
            case 2: // BLOCK
                if(ModelPaymentPro::newInstance()->publishFeeIsPaid($id)) {
                    $mItems = new ItemActions(false);
                    $mItems->disable($id);
                } else {
                    ModelPaymentPro::newInstance()->disableItem($id);
                }
                osc_add_flash_ok_message(__('Listing disabled', 'payment_pro'), 'admin');
                break;
            case 3: // UNBLOCK
                if(ModelPaymentPro::newInstance()->publishFeeIsPaid($id)) {
                    $mItems = new ItemActions(false);
                    $mItems->enable($id);
                } else {
                    ModelPaymentPro::newInstance()->enableItem($id);
                }
                osc_add_flash_ok_message(__('Listing enabled', 'payment_pro'), 'admin');
                break;
            default:
                break;
        }
    }

    ob_get_clean();
    osc_redirect_to(osc_admin_base_url(true) . '?page=items');

