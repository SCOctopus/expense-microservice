<?php


namespace App\Validators\Rules\Customs;


use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class PercentagesRule implements Rule
{
    protected $consortium_id;

    public function __construct()
    {
        $this->consortium_id = \request()->post('idConsortium');
    }

    public function passes($attribute, $value)
    {
        $consortiumPercentages = DB::table('consortium_percentages')
            ->where('administrable_id', $this->consortium_id)
            ->whereNull('deleted_at')
            ->get();

        $arrConsortiumPer = [];
        foreach ($consortiumPercentages as $consortiumPercentage) {
            $arrConsortiumPer[] = $consortiumPercentage->id;
        }

        foreach ($value as $k => $v) {
            if (!in_array($k, $arrConsortiumPer)) {
                return false;
            }
        }
        return true;
    }

    public function message()
    {
        return 'Percentage ID not correspond to the Consortium';
    }
}
