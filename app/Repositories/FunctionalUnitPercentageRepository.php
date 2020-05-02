<?php


namespace App\Repositories;


use Illuminate\Support\Facades\DB;

class FunctionalUnitPercentageRepository
{
    protected $table = "functional_unit_percentages";

    public function find($id)
    {
        return DB::table($this->table)
            ->where(compact('id'))
            ->first();
    }

    public function getByFunctionalUnitConsortiumPercentage(int $idFU, int $idConsortiumPercentage)
    {
        return DB::table($this->table)
            ->where('functional_unit_id', $idFU)
            ->where('consortium_percentage_id', $idConsortiumPercentage)
            ->first();
    }

}
