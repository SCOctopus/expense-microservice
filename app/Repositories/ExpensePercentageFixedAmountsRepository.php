<?php


namespace App\Repositories;


use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ExpensePercentageFixedAmountsRepository
{
    protected $table = "expense_percentage_fixed_amounts";

    public function find(int $id)
    {
        return DB::table($this->table)
            ->where(compact('id'))
            ->first();
    }

    public function getByExpense(int $idExpense)
    {
        return DB::table($this->table)
            ->where('expense_id', $idExpense)
            ->get();
    }
}
