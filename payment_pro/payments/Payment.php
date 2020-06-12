<?php

require_once dirname(__FILE__) . '/iPayment.php';

class Payment {
    public static function newInstance($payment) {
        $class = $payment . 'Payment';
        $file = dirname(__FILE__) . '/' . strtolower($payment) . '/' . $class . '.php';
        if(strpos($file, '../')===false && file_exists($file)) {
            require_once $file;
            if(class_exists($class)) {
                return new $class();
            }
        }
        return false;
        //throw new Exception("Unkwon validator '" . $class . "'");
    }

}
