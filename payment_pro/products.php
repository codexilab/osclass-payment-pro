<?php

function payment_pro_item_paid($item, $data, $invoiceId) {
    if(isset($item['id']) && (!isset($item['item_id']) || trim($item['item_id'])=='')) {
        $tmp = explode("-", $item['id']);
        if(count($tmp)>1) {
            $item['item_id'] = $tmp[1];
        }
    }
    if (substr($item['id'], 0, 3) == 'PUB') {
        ModelPaymentPro::newInstance()->payPublishFee($item['item_id'], $invoiceId);
    } else if (substr($item['id'], 0, 3) == 'PRM') {
        ModelPaymentPro::newInstance()->payPremiumFee($item['item_id'], $invoiceId);
    } else if (substr($item['id'], 0, 3) == 'TOP') {
        Item::newInstance()->update(array('dt_pub_date' => date('Y-m-d H:i:s')), array('pk_i_id' => $item['item_id']));
    } else if (substr($item['id'], 0, 3) == 'RNW') {
        $item_data = Item::newInstance()->findByPrimaryKey($item['item_id']);
        if(isset($item_data['fk_i_category_id'])) {
            $category = Category::newInstance()->findByPrimaryKey($item_data['fk_i_category_id']);
            if(isset($category['i_expiration_days'])) {
                if($category['i_expiration_days']==0) {
                    Item::newInstance()->update(array('dt_expiration' => "9999-12-31 23:59:59"), array('pk_i_id' => $item['item_id']));
                } else {
                    $exp_date = date('Y-m-d H:i:s', max(strtotime(@$item_data['dt_expiration']), time())+((int)$category['i_expiration_days']*24*3600));
                    Item::newInstance()->update(array('dt_expiration' => $exp_date), array('pk_i_id' => $item['item_id']));
                }
            }
        }
        if(osc_get_preference('renew_only_expiration')!=1) {
            Item::newInstance()->update(array('dt_pub_date' => date('Y-m-d H:i:s')), array('pk_i_id' => $item['item_id']));
        }
    } else if (substr($item['id'], 0, 3) == 'HLT') {
        ModelPaymentPro::newInstance()->payHighlightFee($item['item_id'], $invoiceId);
    } else if (substr($item['id'], 0, 3) == 'WLT') {
        $pack = ModelPaymentPro::newInstance()->pack(substr($item['id'], 4));
        $qty = 1;
        if(isset($item["quantity"])) {
            $qty = $item["quantity"];
        }
        if(isset($pack['i_amount'])) {
            ModelPaymentPro::newInstance()->addWallet($data['user'], ($qty*$pack['i_amount'])/1000000);
        }
    }
}
osc_add_hook('payment_pro_item_paid', 'payment_pro_item_paid');

function payment_pro_item_unpaid($item, $data, $invoiceId) {
    if(isset($item['id']) && (!isset($item['item_id']) || trim($item['item_id'])=='')) {
        $tmp = explode("-", $item['id']);
        if(count($tmp)>1) {
            $item['item_id'] = $tmp[1];
        }
    }
    if (substr($item['id'], 0, 3) == 'PUB') {
        ModelPaymentPro::newInstance()->unpayPublishFee($item['item_id'], $invoiceId);
    } else if (substr($item['id'], 0, 3) == 'PRM') {
        ModelPaymentPro::newInstance()->premiumOff($item['item_id']);
    } else if (substr($item['id'], 0, 3) == 'RNW') {
    } else if (substr($item['id'], 0, 3) == 'TOP') {
        //Item::newInstance()->update(array('dt_pub_date' => date('Y-m-d H:i:s')), array('pk_i_id' => $item['item_id']));
    } else if (substr($item['id'], 0, 3) == 'HLT') {
        ModelPaymentPro::newInstance()->unpayHighlightFee($item['item_id'], $invoiceId);
    } else if (substr($item['id'], 0, 3) == 'WLT') {
        $pack = ModelPaymentPro::newInstance()->pack(substr($item['id'], 4));
        if(isset($pack['i_amount'])) {
            ModelPaymentPro::newInstance()->addWallet($data['user'], -$pack['i_amount']/1000000);
        }
    }
}
osc_add_hook('payment_pro_item_unpaid', 'payment_pro_item_unpaid');

function payment_pro_cart_add_filter($item) {
    $str = substr($item['id'], 0, 3);
    if($str=='PRM' || $str=='PUB' || $str=='TOP' || $str=='RNW') {
        $item['quantity'] = 1;
    }
    return $item;
}
osc_add_filter('payment_pro_add_to_cart', 'payment_pro_cart_add_filter');

function payment_pro_cart_quantity_filter($quantity, $item) {
    $str = substr($item['id'], 0, 3);
    if($str=='PRM' || $str=='PUB' || $str=='TOP' || $str=='RNW') {
        $quantity = 1;
    }
    return $quantity;
}
osc_add_filter('payment_pro_add_quantity', 'payment_pro_cart_quantity_filter');
