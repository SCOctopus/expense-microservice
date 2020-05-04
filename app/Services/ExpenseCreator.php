<?php


namespace App\Services;

use App\Repositories\ConsortiumPercentageRepository;
use App\Repositories\ExpenseRepository;
use App\Services\ExpenseBuilder\ExpenseCreatePayslip;
use Carbon\Carbon;

class ExpenseCreator
{
    public function __construct(
        ExpenseCreatePayslip $expenseCreatePayslip,
        ExpenseData $expenseData,
        ConsortiumPercentageRepository $consortiumPercentageRepository,
        ExpenseRepository $expenseRepository)
    {
        $this->expenseCreatePayslip = $expenseCreatePayslip;
        $this->expenseData = $expenseData;
        $this->consortiumPercentageRepository = $consortiumPercentageRepository;
        $this->expenseRepository = $expenseRepository;
    }

    public function createDraft($consortium,
                                $functionalUnits,
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
                'close_date' => $closeDate,
                'first_due_date' => $firstDueDate,
                'second_due_date' => $secondDueDate,
                'second_due_date_interests' => $consortium->second_due_date_interests,
                'penalty_interests' => $consortium->penalty_interests,
                'penalty_interests_mode' => $consortium->penalty_interests_mode,
                'all_receipts' => $allReceipts,
                'manually_edited' => false
            ]);

            //Create expense_percentage_fixed_amount
            foreach ($percentages as $percentageId => $value) {
                $this->expenseRepository->addPercentageFixedAmount(
                    $expense->id,
                    $percentageId,
                    $value);
            }

            //Calculate the expense data (total, totals by category, percentage, etc.)
            $this->calculateExpenseData($expense, $consortium, $functionalUnits);

            //Creacion sueldos
            $this->expenseCreatePayslip->create($expense, $this->expenseData);

            //TODO

            return $expense;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function calculateExpenseData($expense, $consortium, $functionalUnits)
    {
        $this->expenseData->calculate($expense, $consortium, $functionalUnits);
    }
}
