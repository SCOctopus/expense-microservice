<?php


namespace App\Repositories;


use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ExpenseFunctionalUnitsRepository
{
    protected $table = "expense_functional_units";

    private $fillables = [
        'expense_id',
        'functional_unit_id',
        'balance_to_pay',
        'previous_balance',
        'last_payment',
        'month_interests',
        'receipt_number',
        'second_balance_to_pay',
        'spendings',
        'early_balance_to_pay',
        'pdf_path',
    ];

    public function find(int $id)
    {
        return DB::table($this->table)
            ->where(compact('id'))
            ->first();
    }

    public function create($data)
    {
        $data = Arr::only($data, $this->fillables);
        $data['id'] = DB::raw("nextval('expense_functional_units_id_seq')");
        $id = DB::table($this->table)
            ->insertGetId($data);

        return DB::table($this->table)->find($id);
    }
}
