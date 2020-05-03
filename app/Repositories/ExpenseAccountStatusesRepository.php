<?php


namespace App\Repositories;


use Illuminate\Support\Facades\DB;

class ExpenseAccountStatusesRepository
{
    protected $table = "expense_account_statuses";

    public function find($id)
    {
        return DB::table($this->table)
            ->where(compact('id'))
            ->first();
    }

    public function findByExpense(int $idExpense)
    {
        return DB::table($this->table)
            ->where('expense_id', $idExpense)
            ->first();
    }
}
