<?php


namespace App\Services;


use App\Repositories\InstallmentRepository;
use Carbon\Carbon;

class InstallmentService
{
    public function __construct(InstallmentRepository $installmentRepository)
    {
        $this->installmentRepository = $installmentRepository;
    }

    public function listForExpense(int $idConsortium, Carbon $closeDate, int $idCategory, int $idExpense = null)
    {
        return $this->installmentRepository->listForExpense(
            $idConsortium,
            $closeDate,
            $idCategory,
            $idExpense
        );
    }

    public function getConsortiumPercentages(int $idInstallment)
    {
        return $this->installmentRepository->getConsortiumPercentages($idInstallment);
    }

    public function listForExpensePercentageParticular(int $idConsortium, Carbon $closeDate, int $idFunctionalUnit, int $idExpense = null)
    {
        return $this->installmentRepository->listForExpensePercentageParticular($idConsortium, $closeDate, $idFunctionalUnit, $idExpense);
    }
}
