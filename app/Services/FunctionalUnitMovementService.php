<?php


namespace App\Services;


use App\Repositories\FunctionalUnitMovementRepository;

class FunctionalUnitMovementService
{
    public function __construct(FunctionalUnitMovementRepository $functionalUnitMovementRepository)
    {
        $this->functionalUnitMovementRepository = $functionalUnitMovementRepository;
    }

    public function create(int $idFunctionalUnit,
                           $amount,
                           $date,
                           $description,
                           $type)
    {
        $this->functionalUnitMovementRepository->create($idFunctionalUnit, $amount, $date, $description, $type);
    }


    public function getSum(int $idFunctionalUnit)
    {
        return $this->functionalUnitMovementRepository->getSum($idFunctionalUnit);
    }

    public function getPreviousDebtFromCloseDate(int $idFunctionalUnit, Carbon $closeDate, $mode)
    {
        if ($mode == 'interest') {
            $debt = $this->functionalUnitMovementRepository->getDebtInterestsFromCloseDate($idFunctionalUnit, $closeDate);
        } else {
            $debt = $this->functionalUnitMovementRepository->getDebtCapitalFromCloseDate($idFunctionalUnit, $closeDate);
        }
        return $debt;
    }

    public function getPreviousBalance(int $idFunctionalUnit,
                                       int $idCurrentExpense,
                                       int $idPreviousExpense = null)
    {
        return $this->functionalUnitMovementRepository->getPreviousBalance($idFunctionalUnit, $idCurrentExpense, $idPreviousExpense);
    }

    public function getExpenseGeneratedInterests(int $idFunctionalUnit, int $idExpense)
    {
        return $this->functionalUnitMovementRepository->getExpenseGeneratedInterests($idFunctionalUnit, $idExpense);
    }

    public function getAccumulatedInterests(int $idFunctionalUnit)
    {
        return $this->functionalUnitMovementRepository->getAccumulatedInterests($idFunctionalUnit);
    }
}
