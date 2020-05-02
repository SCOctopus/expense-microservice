<?php


namespace App\Repositories;


use Illuminate\Support\Facades\DB;

class FunctionalUnitRepository
{
    protected $table = "functional_units";

    public function find($id)
    {
        return DB::table($this->table)
            ->where(compact('id'))
            ->first();
    }

    public function getByConsortium(int $idConsortium)
    {
        return DB::table($this->table)
            ->where('administrable_id', '=', $idConsortium)
            ->get();
    }

}
