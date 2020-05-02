<?php


namespace App\Validators\Rules;


use App\Validators\Rules\Customs\ExistsPeriodRule;
use App\Validators\Rules\Customs\PercentagesRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExpenseRules
{
    public static function createDraftRules($idConsortium)
    {
        return [
            'idConsortium' => 'required|integer|exists:consortia,id|bail',
            'closeDate' => 'required|date_format:d/m/Y|before_or_equal:today',
            'month' => 'required|date_format:m|bail',
            'year' => [
                'required',
                'date_format:Y',
                new ExistsPeriodRule,
                'bail'
            ],
            'makeIdentifiedNote' => 'required|boolean',
            'makeUnidentifiedNote' => 'required|boolean',
            'allReceipts' => 'required|boolean',
            'firstDueDate' => 'required|date_format:d/m/Y',
            'secondDueDate' => [
                Rule::requiredIf(function () use ($idConsortium) {
                    $consortium = DB::table('consortia')->find($idConsortium);
                    return isset($consortium->second_due_date);
                }),
                'date_format:d/m/Y',
                'after:firstDueDate',
                'bail'
            ],
            'percentages' => [
                'required',
                'array',
                new PercentagesRule(),
                'bail'
            ],
            'percentages.*' => [
                'required',
                'numeric',
                'gte:0',
                'bail'
            ],
            'type-percentages' => [
                'required',
                'array',
                new PercentagesRule(),
                'bail'
            ],
            'type-percentages.*' => [
                'required',
                Rule::in(['fixed', 'spendings']),
                'bail'
            ]
        ];
    }
}
