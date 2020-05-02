<?php


namespace App\Validators\Rules\Customs;


use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;

class ExistsPeriodRule implements Rule
{
    protected $consortium_id;
    protected $month;
    protected $year;

    public function __construct()
    {
        $this->consortium_id = \request()->post('idConsortium');
        $this->month = \request()->post('month');
        $this->year = \request()->post('year');
    }

    public function passes($attribute, $value)
    {
        $expense = DB::table('expenses')
            ->where('administrable_id', $this->consortium_id)
            ->where('month', $this->month)
            ->where('year', $this->year)
            ->first();

        if ($expense) {
            return false;
        } else {
            return true;
        }
    }

    public function message()
    {
        return "Expense already exists for Consortium {$this->consortium_id} for Period {$this->month} / {$this->year}.";
    }
}
