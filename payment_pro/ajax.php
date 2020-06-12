<?php

/* PUBLISH */
if(Params::getParam('pub')!='') {
    $pub = Params::getParam('pub');
    $item = Item::newInstance()->findByPrimaryKey($pub);
    if((osc_get_preference('pay_per_post', 'payment_pro')!=1) || (osc_get_preference('pay_per_post', 'payment_pro')==1 && ModelPaymentPro::newInstance()->publishFeeIsPaid($item['pk_i_id']))) {
        echo json_encode(array('error' => 1, 'msg' => __('No need to pay the publish fee', 'payment_pro')));
        die;
    }

    if(!ModelPaymentPro::newInstance()->isEnabled($pub)) {
        echo json_encode(array('error' => 1, 'msg' => __('This listing is not enabled yet', 'payment_pro')));
        die;
    }
    if($item['fk_i_user_id']==null || ($item['fk_i_user_id']!=null && $item['fk_i_user_id']==osc_logged_user_id())) {
        $category_fee = ModelPaymentPro::newInstance()->getPublishPrice($item['fk_i_category_id']);
        if (isset($category_fee['price']) && $category_fee['price'] > 0) {
            payment_pro_cart_add('PUB' . $item['fk_i_category_id'] . '-' . $item['pk_i_id'], sprintf(__('Publish fee for listing %d', 'payment_pro'), $item['pk_i_id']), $category_fee['price'], 1, osc_get_preference('default_tax', 'payment_pro'));
            echo json_encode(array('error' => 0, 'msg' => __('Product added to your cart', 'payment_pro')));
            die;
        }
    }

    echo json_encode(array('error' => 1, 'msg' => __('This listing does not belong to you', 'payment_pro')));
    die;
}

/* PREMIUM */
if(Params::getParam('prm')!='') {
    $prm = Params::getParam('prm');
    $item = Item::newInstance()->findByPrimaryKey($prm);
    if((osc_get_preference('pay_per_post', 'payment_pro')!=1 && $item['b_enabled']==0) || (osc_get_preference('pay_per_post', 'payment_pro')==1 && !ModelPaymentPro::newInstance()->isEnabled($item['pk_i_id']))) {
        echo json_encode(array('error' => 1, 'msg' => __('This listing is not enabled yet', 'payment_pro')));
        die;
    }
    if($item['fk_i_user_id']==null || ($item['fk_i_user_id']!=null && $item['fk_i_user_id']==osc_logged_user_id())) {
        $category_fee = ModelPaymentPro::newInstance()->getPremiumPrice($item['fk_i_category_id']);
        if (isset($category_fee['price']) && $category_fee['price'] > 0) {
            payment_pro_cart_add('PRM' . $item['fk_i_category_id'] . '-' . $item['pk_i_id'], sprintf(__('Premium enhancement for listing %d', 'payment_pro'), $item['pk_i_id']), $category_fee['price'], 1, osc_get_preference('default_tax', 'payment_pro'));

            if(osc_get_preference('pay_per_post', 'payment_pro')==1 && !ModelPaymentPro::newInstance()->publishFeeIsPaid($item['pk_i_id'])) {
                $category_fee_pub = ModelPaymentPro::newInstance()->getPublishPrice($item['fk_i_category_id']);
                if (isset($category_fee_pub['price']) && $category_fee_pub['price'] > 0) {
                    payment_pro_cart_add('PUB' . $item['fk_i_category_id'] . '-' . $item['pk_i_id'], sprintf(__('Publish fee for listing %d', 'payment_pro'), $item['pk_i_id']), $category_fee_pub['price'], 1, osc_get_preference('default_tax', 'payment_pro')/*$category_fee_pub['tax']*/);
                }
            }

            echo json_encode(array('error' => 0, 'msg' => __('Product added to your cart', 'payment_pro')));
            die;
        }
    }

    echo json_encode(array('error' => 1, 'msg' => __('This listing does not belong to you', 'payment_pro')));
    die;
}

/* MOVE TO TOP */
if(Params::getParam('top')!='') {
    $top = Params::getParam('top');
    $item = Item::newInstance()->findByPrimaryKey($top);
    if((osc_get_preference('pay_per_post', 'payment_pro')!=1 && $item['b_enabled']==0) || (osc_get_preference('pay_per_post', 'payment_pro')==1 && !ModelPaymentPro::newInstance()->isEnabled($item['pk_i_id']))) {
        echo json_encode(array('error' => 1, 'msg' => __('This listing is not enabled yet', 'payment_pro')));
        die;
    }
    if (time()-strtotime($item['dt_pub_date'])<(payment_pro_top_hours()*3600)) {
        echo json_encode(array('error' => 1, 'msg' => __('It\'s too soon to move your listing to the top', 'payment_pro')));
        die;
    }
    if(osc_get_preference('pay_per_post', 'payment_pro')==1 && !ModelPaymentPro::newInstance()->publishFeeIsPaid($item['pk_i_id'])) {
        echo json_encode(array('error' => 1, 'msg' => __('The publish fee was not paid', 'payment_pro')));
        die;
    }
    if($item['fk_i_user_id']==null || ($item['fk_i_user_id']!=null && $item['fk_i_user_id']==osc_logged_user_id())) {
        $category_fee = ModelPaymentPro::newInstance()->getTopPrice($item['fk_i_category_id']);
        if (isset($category_fee['price']) && $category_fee['price'] > 0) {
            payment_pro_cart_add('TOP' . $item['fk_i_category_id'] . '-' . $item['pk_i_id'], sprintf(__('Move to top listing %d', 'payment_pro'), $item['pk_i_id']), $category_fee['price'], 1, osc_get_preference('default_tax', 'payment_pro'));

            echo json_encode(array('error' => 0, 'msg' => __('Product added to your cart', 'payment_pro')));
            die;
        }
    }

    echo json_encode(array('error' => 1, 'msg' => __('This listing does not belong to you', 'payment_pro')));
    die;
}

/* HIGHLIGHT */
if(Params::getParam('hlt')!='') {
    $hlt = Params::getParam('hlt');
    $item = Item::newInstance()->findByPrimaryKey($hlt);
    if((osc_get_preference('pay_per_post', 'payment_pro')!=1 && $item['b_enabled']==0) || (osc_get_preference('pay_per_post', 'payment_pro')==1 && !ModelPaymentPro::newInstance()->isEnabled($item['pk_i_id']))) {
        echo json_encode(array('error' => 1, 'msg' => __('This listing is not enabled yet', 'payment_pro')));
        die;
    }
    if($item['fk_i_user_id']==null || ($item['fk_i_user_id']!=null && $item['fk_i_user_id']==osc_logged_user_id())) {
        $category_fee = ModelPaymentPro::newInstance()->getHighlightPrice($item['fk_i_category_id']);
        if (isset($category_fee['price']) && $category_fee['price'] > 0) {
            payment_pro_cart_add('HLT' . $item['fk_i_category_id'] . '-' . $item['pk_i_id'], sprintf(__('Highlight enhancement for listing %d', 'payment_pro'), $item['pk_i_id']), $category_fee['price'], 1, osc_get_preference('default_tax', 'payment_pro'));

            if(osc_get_preference('pay_per_post', 'payment_pro')==1 && !ModelPaymentPro::newInstance()->publishFeeIsPaid($item['pk_i_id'])) {
                $category_fee_pub = ModelPaymentPro::newInstance()->getPublishPrice($item['fk_i_category_id']);
                if (isset($category_fee_pub['price']) && $category_fee_pub['price'] > 0) {
                    payment_pro_cart_add('PUB' . $item['fk_i_category_id'] . '-' . $item['pk_i_id'], sprintf(__('Publish fee for listing %d', 'payment_pro'), $item['pk_i_id']), $category_fee_pub['price'], 1, osc_get_preference('default_tax', 'payment_pro')/*$category_fee_pub['tax']*/);
                }
            }

            echo json_encode(array('error' => 0, 'msg' => __('Product added to your cart', 'payment_pro')));
            die;
        }
    }

    echo json_encode(array('error' => 1, 'msg' => __('This listing does not belong to you', 'payment_pro')));
    die;
}

/* RENEW */
if(Params::getParam('rnw')!='') {
    $rnw = Params::getParam('rnw');
    $item = Item::newInstance()->findByPrimaryKey($rnw);
    if((osc_get_preference('pay_per_post', 'payment_pro')!=1 && $item['b_enabled']==0) || (osc_get_preference('pay_per_post', 'payment_pro')==1 && !ModelPaymentPro::newInstance()->isEnabled($item['pk_i_id']))) {
        echo json_encode(array('error' => 1, 'msg' => __('This listing is not enabled yet', 'payment_pro')));
        die;
    }


    if(osc_get_preference('pay_per_post', 'payment_pro')==1 && !ModelPaymentPro::newInstance()->publishFeeIsPaid($item['pk_i_id'])) {
        $category_fee_pub = ModelPaymentPro::newInstance()->getPublishPrice($item['fk_i_category_id']);
        if (isset($category_fee_pub['price']) && $category_fee_pub['price'] > 0) {
            echo json_encode(array('error' => 1, 'msg' => __('This listing is not paid yet', 'payment_pro')));
            die;
        }
    }

    if($item['fk_i_user_id']==null || ($item['fk_i_user_id']!=null && $item['fk_i_user_id']==osc_logged_user_id())) {
        $category_fee = ModelPaymentPro::newInstance()->getRenewPrice($item['fk_i_category_id']);
        if (isset($category_fee['price']) && $category_fee['price'] > 0) {
            payment_pro_cart_add('RNW' . $item['fk_i_category_id'] . '-' . $item['pk_i_id'], sprintf(__('Renew fee for listing %d', 'payment_pro'), $item['pk_i_id']), $category_fee['price'], 1, osc_get_preference('default_tax', 'payment_pro'));

            echo json_encode(array('error' => 0, 'msg' => __('Product added to your cart', 'payment_pro')));
            die;
        }
    }

    echo json_encode(array('error' => 1, 'msg' => __('This listing does not belong to you', 'payment_pro')));
    die;
}



/* WALLET PACKS CREDIT */
if(Params::getParam('wlt')!='') {
    $wlt = Params::getParam('wlt');
    $pack = ModelPaymentPro::newInstance()->pack($wlt);
    if(isset($pack['pk_i_id']) && isset($pack['i_amount_cost']) && $pack['i_amount_cost'] > 0) {
        payment_pro_cart_add('WLT-' . $pack['pk_i_id'], $pack['s_name'], $pack['i_amount_cost']/1000000, 1, osc_get_preference('default_tax', 'payment_pro')/*0*/);
        echo json_encode(array('error' => 0, 'msg' => __('Product added to your cart', 'payment_pro')));
        die;
    };
    echo json_encode(array('error' => 1, 'msg' => __('Option no longer available', 'payment_pro')));
    die;
}