<?php

namespace App\Util;

class CurrencyHelper
{
    public static function format($amount, $decimals = 2 , $dec_point = ',' , $thousands_sep = '.', $currency = '$ ')
    {
        try {
            return $currency . number_format($amount, $decimals, $dec_point, $thousands_sep);
        } catch (\Exception $exception) {
            return $currency . preg_replace('/[^\d\.\,\-]/', '', $amount);
        }
    }

    public static function formatWithColor($amount, $decimals = 2 , $dec_point = ',' , $thousands_sep = '.', $currency = '$ ')
    {
        $className = 'credit';

        if ($amount < 0)
        {
            $className = 'debit';
        }

        return  '<span class="' . $className . '">' . CurrencyHelper::format($amount, $decimals, $dec_point, $thousands_sep, $currency) . '</span>';
    }

    public static function formatWithoutCurrency($amount, $decimals = 2 , $dec_point = ',' , $thousands_sep = '.')
    {
        return self::format($amount, $decimals, $dec_point, $thousands_sep, '');
    }

    public static function alignAmountToRight($amount)
    {
        return '<div class="text-right">' . $amount . '</div>';
    }

    public static function alignAmountToRightForExcel($amount)
    {
        return '<div style="text-align: right">' . $amount . '</div>';
    }

    public static function tofloat($num)
    {
        $dotPos = strrpos($num, '.');
        $commaPos = strrpos($num, ',');
        $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos :
            ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);

        if (!$sep) {
            return floatval(preg_replace("/[^0-9]/", "", $num));
        }

        return floatval(
            preg_replace("/[^0-9\-]/", "", substr($num, 0, $sep)) . '.' .
            preg_replace("/[^0-9]/", "", substr($num, $sep + 1, strlen($num)))
        );
    }

    public static function formatWithColorDashboard($amount, $decimals = 2 , $dec_point = ',' , $thousands_sep = '.', $currency = '$')
    {
        $className = 'credit-dashboard';

        if ($amount < 0)
        {
            $className = 'debit-dashboard';
        }

        return  '<span class="' . $className . '">' . CurrencyHelper::format($amount, $decimals, $dec_point, $thousands_sep, $currency) . '</span>';
    }
}
