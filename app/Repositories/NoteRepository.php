<?php


namespace App\Repositories;


use Illuminate\Support\Facades\DB;

class NoteRepository
{
    protected $table = "notes";

    public function find($id)
    {
        return DB::table($this->table)
            ->where(compact('id'))
            ->first();
    }

    public function save($note)
    {
        return DB::table($this->table)
                ->insertGetId($note);
    }

    public function saveNoteConsortia($idNote, $idConsortium, $position = 1)
    {
        return DB::table('note_consortia')
                ->insertGetId([
                    'consortium_id' => $idConsortium,
                    'note_id' => $idNote,
                    'position' => $position
                ]);
    }

    public function findNotes($closeDate, int $idConsortium, string $noteType)
    {
        $whereRaw = '';
        switch ($noteType) {
            case 'debt':
                $whereRaw = 'notes.is_debt_note = true';
                break;
            case 'identified':
                $whereRaw = 'notes.identified_payments_note = true';
                break;
            case 'unidentified':
                $whereRaw = 'notes.unidentified_payments_note = true';
                break;
        }

        return DB::table('note_consortia')
            ->leftJoin('notes', 'notes.id', '=', 'note_consortia.note_id')
            ->where('note_consortia.consortium_id', '=', $idConsortium)
            ->where('notes.close_date','=', $closeDate)
            ->whereRaw($whereRaw)
            ->select(['notes.id', 'notes.content'])
            ->get();
    }
}
