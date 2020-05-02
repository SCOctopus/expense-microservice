<?php


namespace App\Services;


use App\Repositories\ConsortiumPercentageRepository;
use App\Repositories\ExpenseRepository;
use Carbon\Carbon;

class ExpenseCreator
{
    public function __construct(
        ExpenseData $expenseDataService,
        ConsortiumPercentageRepository $consortiumPercentageRepository,
        ExpenseRepository $expenseRepository)
    {
        $this->expenseDataService = $expenseDataService;
        $this->consortiumPercentageRepository = $consortiumPercentageRepository;
        $this->expenseRepository = $expenseRepository;
    }

    public function createDraft($consortium,
                                Carbon $closeDate,
                                $month,
                                $year,
                                $percentages,
                                $copyFromPrevious = false,
                                Carbon $firstDueDate = null,
                                Carbon $secondDueDate = null,
                                $allReceipts = false)
    {

        try {
            //Create expense
            $expense = $this->expenseRepository->create([
                'administrable_id' => $consortium->id,
                'month' => $month,
                'year' => $year,
                'status' => 'draft',
                'closeDate' => $closeDate,
                'first_due_date' => $firstDueDate,
                'second_due_date' => $secondDueDate,
                'second_due_date_interests' => $consortium->second_due_date_interests,
                'penalty_interests' => $consortium->penalty_interests,
                'penalty_interests_mode' => $consortium->penalty_interests_mode,
                'all_receipts' => $allReceipts
            ]);

            //Create expense_percentage_fixed_amount
            foreach ($percentages as $percentageId => $value) {
                $this->expenseRepository->addPercentageFixedAmount(
                    $expense->id,
                    $percentageId,
                    $value);
            }

            //Calculate the expense data (total, totals by category, percentage, etc.)
            $this->calculateExpenseData($expense);

            //TODO



        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function calculateExpenseData($expense)
    {
        $this->expenseDataService->calculate($expense);
    }
}
