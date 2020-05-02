<?php


namespace App\Repositories;


use Illuminate\Support\Facades\DB;

class ConsortiumPercentageRepository
{
    protected $table = "consortium_percentages";

    public function find($id)
    {
        return DB::table($this->table)
            ->where(compact('id'))
            ->first();
    }

    public function getByConsortium(int $idConsortium)
    {
        return DB::table($this->table)
            ->where('administrable_id', $idConsortium)
            ->get();
    }
}
