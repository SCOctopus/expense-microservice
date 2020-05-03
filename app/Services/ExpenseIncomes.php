<?php


namespace App\Services;


class ExpenseIncomes
{
    private $onTimePayments;
    private $duePayments;
    private $interestsPayments;
    private $duePaymentsIdentificated;
    private $interestsPaymentsIdentificated;
    private $onTimePaymentsIdentificated;
    private $earlyPaymentDiscounts;
    private $earlyPaymentDiscountsSum;

    public function __construct(
        FunctionalUnitMovementService $functionalUnitMovementService)
    {
        $this->functionalUnitMovementService = $functionalUnitMovementService;

        $this->onTimePayments = [];
        $this->duePayments = [];
        $this->interestsPayments = [];
        $this->earlyPaymentDiscounts = [];

        $this->duePaymentsIdentificated = 0;
        $this->interestsPaymentsIdentificated = 0;
        $this->onTimePaymentsIdentificated = 0;
        $this->earlyPaymentDiscountsSum = 0;
    }

    public function addIncomes($incomes, $functionalUnit, $lastExpense = null)
    {
        foreach ($incomes as $income) {
            $this->addIncome($income, $functionalUnit, $lastExpense);
        }
    }

    public function addIncome($income, $functionalUnit, $lastExpense = null)
    {
        $type = $income->type;
        $amount = $income->amount;

        if ($type == 'capital') {
            $this->addOnTimePayment($functionalUnit, $amount);

            if ($income->identified &&
                !is_null($lastExpense) &&
                $income->date <= $lastExpense->close_date) {
                $this->onTimePaymentsIdentificated += $amount;
            }
        } elseif ($type == 'accumulated_capital') {
            $this->addDuePayment($functionalUnit, $amount);

            if ($income->identified &&
                !is_null($lastExpense) &&
                $income->date <= $lastExpense->close_date) {
                $this->duePaymentsIdentificated += $amount;
            }
        } elseif ($type == 'month_interest' || $type == 'accumulated_capital') {
            $this->addInterestsPayment($functionalUnit, $amount);

            if ($income->identified &&
                !is_null($lastExpense) &&
                $income->date <= $lastExpense->close_date) {
                $this->interestsPaymentsIdentificated += $amount;
            }
        } elseif ($type == 'early_payment_discount') {
            $this->addEarlyPaymentDiscounts($functionalUnit, $amount);
            $this->earlyPaymentDiscountsSum += $amount;
        }
    }

    private function addOnTimePayment($functionalUnit, $amount)
    {
        if (!isset($this->onTimePayments[$functionalUnit->id])) {
            $this->onTimePayments[$functionalUnit->id] = 0;
        }
        $this->onTimePayments[$functionalUnit->id] += $amount;
    }

    private function addDuePayment($functionalUnit, $amount)
    {
        if (!isset($this->duePayments[$functionalUnit->id])) {
            $this->duePayments[$functionalUnit->id] = 0;
        }

        $this->duePayments[$functionalUnit->id] = $amount;
    }

    private function addInterestsPayment($functionalUnit, $amount)
    {
        if (!isset($this->interestsPayments[$functionalUnit->id])) {
            $this->interestsPayments[$functionalUnit->id] = 0;
        }

        $this->interestsPayments[$functionalUnit->id] += $amount;
    }

    private function addEarlyPaymentDiscounts($functionalUnit, $amount)
    {
        if (!isset($this->earlyPaymentDiscounts[$functionalUnit->id])) {
            $this->earlyPaymentDiscounts[$functionalUnit->id] = 0;
        }

        $this->earlyPaymentDiscounts[$functionalUnit->id] += $amount;
    }

    public function getPaymentsByFunctionalUnit(int $idFunctionalUnit)
    {
        $total = isset($this->onTimePayments[$idFunctionalUnit]) ? $this->onTimePayments[$idFunctionalUnit] : 0;
        $total += isset($this->duePayments[$idFunctionalUnit]) ? $this->duePayments[$idFunctionalUnit] : 0;
        $total += isset($this->interestsPayments[$idFunctionalUnit]) ? $this->interestsPayments[$idFunctionalUnit] : 0;

        return $total;
    }

    public function getEarlyPaymentDiscounts(int $idFunctionalUnit)
    {
        $total = isset($this->earlyPaymentDiscounts[$idFunctionalUnit]) ? $this->earlyPaymentDiscounts[$idFunctionalUnit] : 0;
        return $total;
    }

}
