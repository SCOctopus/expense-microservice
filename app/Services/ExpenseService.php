<?php


namespace App\Services;

use App\Repositories\ConsortiumPercentageRepository;
use App\Repositories\ConsortiumRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Nette\Utils\DateTime;
use function foo\func;

class ExpenseService
{
    public function __construct(
        ExpenseCreator $expenseCreator,
        ConsortiumRepository $consortiumRepository,
        NoteService $noteService,
        ConsortiumPercentageRepository $consortiumPercentageRepository,
        ExpenseCalculatorService $expenseCalculatorService,
        FunctionalUnitMovementService $functionalUnitMovementService)
    {
        $this->expenseCreator = $expenseCreator;
        $this->noteService = $noteService;
        $this->consortiumRepository = $consortiumRepository;
        $this->consortiumPercentageRepository = $consortiumPercentageRepository;
        $this->expenseCalculatorService = $expenseCalculatorService;
        $this->functionalUnitMovementService = $functionalUnitMovementService;
    }

    public function createDraft(int $idConsortium,
                                array $data,
                                bool $fresh = true)
    {
        DB::transaction(function () use ($idConsortium, $data, $fresh) {
            $consortium = $this->consortiumRepository->find($idConsortium);
            $administration = $this->consortiumRepository->getAdministration($idConsortium);
            $functionalUnits = $this->consortiumRepository->getFunctionalUnits($idConsortium);

            if ($administration->simple_expense_mode) {
                $data['closeDate'] = Carbon::now();
                $copyFromPrevious = true;
            } else {
                $penaltyInterests = $this->expenseCalculatorService->calculatePenaltyInterests($consortium, $functionalUnits, $data['closeDate']);
                $copyFromPrevious = false;
            }

            foreach ($data['type-percentages'] as $key => $value) {
                $consortiumPercentage = $this->consortiumPercentageRepository->find($key);
                $type = $value;

                $consortiumPercentage->type = $type;

                if ($consortiumPercentage->type == 'fixed' || $consortiumPercentage->type == 'forced_fixed') {
                    $consortiumPercentage->last_fixed_amount($data['percentages'][$key]);
                }
            }

            $this->consortiumFinancialMovements = [];

            if (isset($data['secondDueDate'])) {
                $secondDueDate = $data['secondDueDate'];
            } else {
                $secondDueDate = null;
            }

            $stringPeriod = implode('/', [$data['month'], $data['year']]);

            if ($data['makeIdentifiedNote']) {
                $this->noteService->makeIdentifiedNote($consortium, $data['closeDate'], $stringPeriod);
            }

            if ($data['makeUnidentifiedNote']) {
                $this->noteService->makeUnidentifiedNote($consortium, $data['closeDate'], $stringPeriod);
            }

            $expense = $this->expenseCreator->createDraft(
                $consortium,
                $functionalUnits,
                $data['closeDate'],
                $data['month'],
                $data['year'],
                $data['percentages'],
                $copyFromPrevious,
                $data['firstDueDate'],
                $secondDueDate,
                $data['allReceipts']
            );

            // TODO
            dd($expense);
        });

    }
}
