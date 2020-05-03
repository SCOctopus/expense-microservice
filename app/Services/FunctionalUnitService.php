<?php


namespace App\Services;


class FunctionalUnitService
{
    /**
     * @param $consortium
     * @param $functionalUnit
     * @param $balanceToPay
     * @param $expense
     * @return false|float|int
     */
    public function calculateSecondDueDatePayment($consortium,
                                                  $functionalUnit,
                                                  $balanceToPay,
                                                  $expense)
    {
        /**
         * Si es positivo es saldo a fovor de la uf. Por lo tanto no se calcula interes ni se redondea.
         */
        if ($balanceToPay > 0) {
            return $balanceToPay;
        }

        $interest = ($balanceToPay * $expense->second_due_date_interests) / 100;
        $secondBalanceToPay = round($balanceToPay + $interest, 2);

        if ($consortium->rounding == 'yes' || $consortium->rounding == 'uf') {
            $roundedSecondBalanceToPay = round($secondBalanceToPay);
            $secondBalanceToPay = $roundedSecondBalanceToPay;

            if ($consortium->rounding == 'uf') {
                $ufRounding = $functionalUnit->number / 100;
                $functionalUnitCents = ($ufRounding >= 1) ? $ufRounding - intval($ufRounding) : $ufRounding;

                $secondBalanceToPay -= $functionalUnitCents;
            }
        }
        return $secondBalanceToPay;
    }
}
