<?php


namespace App\Services;


class ExpenseIncomes
{
    public function __construct(
        FunctionalUnitMovementService $functionalUnitMovementService)
    {
        $this->functionalUnitMovementService = $functionalUnitMovementService;
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
