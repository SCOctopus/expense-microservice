<?php


namespace App\Repositories;


use Illuminate\Support\Facades\DB;

class CategoryRepository
{
    protected $table = "categories";

    public function find($id)
    {
        return DB::table($this->table)
            ->where(compact('id'))
            ->first();
    }

    public function listAll()
    {
        return DB::table($this->table)
            ->orderBy('position', 'ASC')
            ->get();
    }
}
