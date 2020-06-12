<?php
$id = Params::getParam('id');
payment_pro_cart_drop($id);
// IF removed publish fee, remove too the premium fee so a user is not able to pay just the premium but not the publish fee
if(substr($id, 0, 3)=='PUB') {
    payment_pro_cart_drop('PRM' . substr($id, 3));
}
payment_pro_js_redirect_to(osc_route_url('payment-pro-checkout'));
