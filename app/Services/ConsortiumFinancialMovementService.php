<?php


namespace App\Services;


use App\Repositories\ConsortiumFinancialMovementRepository;

class ConsortiumFinancialMovementService
{
    public function __construct(ConsortiumFinancialMovementRepository $consortiumFinancialMovementRepository)
    {
        $this->consortiumFinancialMovementRepo = $consortiumFinancialMovementRepository;
    }

    public function getUnidentifiedsMovements(array $filters, int $idConsortium)
    {
        return $this->consortiumFinancialMovementRepo->getUnidentifiedsMovements($filters, $idConsortium);
    }
}
