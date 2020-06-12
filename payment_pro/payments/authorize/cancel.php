<?php
    osc_add_flash_error_message(__('You cancel the payment process or there was an error. If the error continue, please contact the administrator', 'payment_pro'));
    payment_pro_js_redirect_to(osc_route_url('payment-pro-done', array('tx' => '')));
