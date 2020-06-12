<?php

interface iPayment
{
    public static function button($products, $extra = null);
    public static function recurringButton($products, $extra = null);
    public static function processPayment();
}