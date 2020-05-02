<?php


namespace App\Repositories;


use Illuminate\Support\Facades\DB;

class AdministrationRepository
{
    protected $table = "administrations";

    public function find($id)
    {
        return DB::table($this->table)
            ->where(compact('id'))
            ->first();
    }
}
