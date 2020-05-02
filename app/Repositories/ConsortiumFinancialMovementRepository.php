<?php


namespace App\Repositories;


use Illuminate\Support\Facades\DB;

class ConsortiumFinancialMovementRepository
{
    protected $table = "consortium_financial_movements";

    public function find($id)
    {
        return DB::table($this->table)
            ->where(compact('id'))
            ->first();
    }

    public function update($id, $fields)
    {
        return DB::table($this->table)
                ->where('id','=', $id)
                ->update($fields);
    }

    public function getUnidentifiedsMovements(array $filters, int $idConsortium)
    {
        $qb = DB::table($this->table)
            ->where('administrable_id', '=', $idConsortium)
            ->where('unidentified', 'IS', true);

        if (!empty($filters['date_to'])) {
            $qb->where('date', '<=', $filters['date_to']);
        }

        if (!empty($filters['not_in_notes'])) {
            $qb->whereNull('unidentified_payments_note_id');
        }

        return $qb->get();
    }
}
