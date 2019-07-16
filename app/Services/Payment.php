<?php
/**
 * Created by PhpStorm.
 * User: tonyzou
 * Date: 2018/9/24
 * Time: 下午7:07
 */

namespace App\Services;

use App\Services\Gateway\TrimePay;

class Payment
{
    public static function getClient()
    {
        $method = Config::get('payment_system');
        switch ($method) {
            case ('trimepay'):
                return new TrimePay(Config::get('trimepay_secret'));
            default:
                return null;
        }
    }

    public static function notify($request, $response, $args)
    {
        return self::getClient()->notify($request, $response, $args);
    }

    public static function returnHTML($request, $response, $args)
    {
        return self::getClient()->getReturnHTML($request, $response, $args);
    }

    public static function purchaseHTML()
    {
        if (self::getClient() != null) {
            return self::getClient()->getPurchaseHTML();
        }

        return '';
    }

    public static function getStatus($request, $response, $args)
    {
        return self::getClient()->getStatus($request, $response, $args);
    }

    public static function purchase($user, $shop, $type, $price)
    {
        return self::getClient()->purchase($user, $shop, $type, $price);
    }
}
