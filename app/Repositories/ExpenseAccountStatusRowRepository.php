<?php


namespace App\Repositories;


use Illuminate\Support\Facades\DB;

class ExpenseAccountStatusRowRepository
{
    protected $table = "expense_account_status_rows";

    public function find($id)
    {
        return DB::table($this->table)
            ->where(compact('id'))
            ->first();
    }

    public function findOneBy($idFunctionalUnit, $expenseAccountStatus)
    {
        return DB::table($this->table)
            ->where('functional_unit_id', $idFunctionalUnit)
            ->where('expense_account_status_id', $expenseAccountStatus)
            ->first();
    }
}
