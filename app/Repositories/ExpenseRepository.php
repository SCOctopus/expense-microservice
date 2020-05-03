<?php


namespace App\Repositories;


use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ExpenseRepository
{
    protected $table = "expenses";

    private $fillables = [
        'administrable_id',
        'month',
        'first_payment_receipt_number',
        'year',
        'status',
        'close_date',
        'sent_to_process',
        'manually_edited',
        'amount_to_collect',
        'amount_collected',
        'first_due_date',
        'second_due_date',
        'second_due_date_interests',
        'penalty_interests',
        'penalty_interests_mode',
        'sent_email_uf_contacts',
        'exported_at',
        'schedule_send_email_uf_contacts',
        'exported',
        'zip_path',
        'created_at',
        'updated_at',
        'all_receipts',
        'lq_path',
        'file_count',
        'finish_check_count',
    ];

    public function find(int $id)
    {
        return DB::table($this->table)
            ->where(compact('id'))
            ->first();
    }

    public function create($data)
    {
        $data = Arr::only($data, $this->fillables);
        $data['id'] = DB::raw("nextval('expenses_id_seq')");
        $data['created_at'] = Carbon::now()->toString();
        $data['updated_at'] = Carbon::now()->toString();

        $id = DB::table($this->table)
            ->insertGetId($data);

        return DB::table($this->table)->find($id);
    }

    public function setAmountToCollect(int $idExpense, $amountToCollect)
    {
        return DB::table($this->table)
            ->where('id', $idExpense)
            ->update([
                'amount_to_collect' => $amountToCollect
            ]);
    }

    public function setAmountCollected(int $idExpense, $amountCollected)
    {
        return DB::table($this->table)
            ->where('id', $idExpense)
            ->update([
                'amount_collected' => $amountCollected
            ]);
    }

    public function addPercentageFixedAmount($idExpense, $idPercentage, $amount)
    {
        return DB::table('expense_percentage_fixed_amounts')
            ->insertGetId([
                'id' => DB::raw("nextval('expense_percentage_fixed_amounts_id_seq')"),
                'expense_id' => $idExpense,
                'consortium_percentage_id' => $idPercentage,
                'value' => $amount,
            ]);
    }

    public function getLastPrintedExpense(int $idConsortium)
    {
        return DB::table($this->table)
            ->where('administrable_id', '=', $idConsortium)
            ->where('status', '=', 'printed')
            ->orderBy('close_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(1)
            ->first();
    }
}
