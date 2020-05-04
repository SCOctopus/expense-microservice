<?php


namespace App\Services;


use App\Repositories\ConsortiumRepository;

class ExpenseCalculatorService
{
    public function __construct(ConsortiumRepository $consortiumRepository,
                                FunctionalUnitMovementService $functionalUnitMovementService)
    {
        $this->functionalUnitMovementService = $functionalUnitMovementService;
        $this->consortiumRepository = $consortiumRepository;
    }

    public function calculatePenaltyInterests($consortium, $closeDate)
    {
        /**
         * porcentaje de interes y tipo de interes lo saco del consorcio porque todavia no
         * se creeó la expensa nueva.
         */
        $interests = $consortium->penalty_interests;
        $penaltyInterestsMode = $consortium->penalty_interests_mode;

        $functionalUnits = $this->functionalUnitMovementService->getFUsPenaltyInterests($consortium->id, $closeDate);
        $movements = [];

        foreach ($functionalUnits as $functionalUnit) {
            $balance = $functionalUnit->balance;

            /** Si saldo es positivo o tiene juicio no calculo intereses */
            if ($balance < 0 && !$functionalUnit->legal_state) {
                if ($functionalUnit->forgive_interest) {
                    continue;
                }

                if($penaltyInterestsMode == 'interest') {
                    $debt = $functionalUnit->debt_interests_from_close_date;
                } else {
                    $debt = $functionalUnit->debt_capital_from_close_date;
                }
                $debt *= -1;

                if ($debt > 0) {
                    $interestToPay = ($debt * $interests) / 100;

                    if ($interestToPay >= 0.01) {
                        $movements[] = $this->functionalUnitMovementService
                            ->create(
                                $functionalUnit->id,
                                $functionalUnit->administrable_id,
                                -$interestToPay,
                                $closeDate,
                                'Intereses punitorios',
                                'month_interest');
                    }
                }
            }
        }
        return $movements;
    }

}
