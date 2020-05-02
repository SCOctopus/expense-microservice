<?php


namespace App\Repositories;


use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InstallmentRepository
{
    protected $table = "installments";

    public function find($id)
    {
        return DB::table($this->table)
            ->where(compact('id'))
            ->first();
    }

    public function listForExpense(int $idConsortium,
                                   Carbon $closeDate,
                                   int $idCategory,
                                   int $idExpense = null
    )
    {
        $qb = DB::table($this->table)
            ->leftJoin('spendings', 'spendings.id', '=', 'installments.spending_id')
            ->where('installments.administrable_id', '=', $idConsortium)
            ->where('spendings.category_id', '=', $idCategory)
            ->where('installments.pay_date', '<=', $closeDate)
            ->orderBy('installments.pay_date', 'desc');

        if (is_null($idExpense)) {
            $qb->whereNull('installments.expense_id');
        } else {
            $qb->where('installments.expense_id', '=', $idExpense)
                ->orWhereNull('installments.expense_id');
        }

        return $qb->select('installments.*',
            'spendings.type AS spendings_type',
            'spendings.functional_unit_id AS spendings_functional_unit_id',
            'spendings.not_outflow AS spendings_not_outflow',
            'spendings.affect_financial_section AS spendings_affect_financial_section',
            'spendings.prorate_in_expenses AS spendings_prorate_in_expenses',
            'spendings.provider_id AS spendings_provider_id')->get();
    }

    public function getConsortiumPercentages(int $idInstallment)
    {
        return DB::table('installment_consortium_percentages')
            ->where('installment_id', '=', $idInstallment)
            ->get();
    }

    public function listForExpensePercentageParticular(int $idConsortium, Carbon $closeDate, int $idFunctionalUnit, int $idExpense = null)
    {
        $qb = DB::table($this->table)
            ->leftJoin('spendings', 'spendings.id', '=', 'installments.spending_id');

        //TODO

    }

}
