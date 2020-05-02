<?php


namespace App\Repositories;


use Illuminate\Support\Facades\DB;

class ConsortiumRepository
{
    protected $table = "consortia";

    public function find($id)
    {
        return DB::table($this->table)
            ->where(compact('id'))
            ->first();
    }

    public function getAdministration($id)
    {
        return DB::table($this->table)
            ->where(['consortia.id' => $id])
            ->leftJoin('administrations', 'administrations.id', '=', 'consortia.administration_id')
            ->select('administrations.*')
            ->first();
    }

    public function getFunctionalUnits($id)
    {
        return DB::table($this->table)
            ->where(['consortia.id' => $id])
            ->leftJoin('functional_units', 'functional_units.administrable_id', '=', 'consortia.id')
            ->select('functional_units.*')
            ->get();
    }
}
