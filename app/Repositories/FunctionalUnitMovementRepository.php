<?php


namespace App\Repositories;


use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FunctionalUnitMovementRepository
{
    protected $table = "functional_unit_movements";

    public function find(int $id)
    {
        return DB::table($this->table)
            ->where(compact('id'))
            ->first();
    }

    public function create($idFunctionalUnit,
                           $idConsortium,
                           $amount,
                           $date,
                           $description,
                           $type)
    {
        return DB::table($this->table)
            ->insert([
                'id' => DB::raw('nextval(\'functional_unit_movements_id_seq\')'),
                'functional_unit_id' => $idFunctionalUnit,
                'administrable_id' => $idConsortium,
                'date' => $date,
                'amount' => $amount,
                'description' => $description,
                'type' => $type,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
    }

    public function getSum(int $idFunctionalUnit)
    {
        return DB::table($this->table)
            ->where('functional_unit_id', '=', $idFunctionalUnit)
            ->sum('amount');
    }

    public function getDebtInterestsFromCloseDate(int $idFunctionalUnit,
                                                  Carbon $closeDate)
    {
        return DB::table($this->table)
            ->where('functional_unit_id', '=', $idFunctionalUnit)
            ->where('type', '=', 'debt_capital')
            ->where('date', '<=', $closeDate)
            ->sum('amount');
    }

    public function getDebtCapitalFromCloseDate($idFunctionalUnit, $closeDate)
    {
        return DB::table($this->table)
            ->where('functional_unit_id', '=', $idFunctionalUnit)
            ->whereIn('type', ['capital', 'accumulated_capital', 'initial_balance', 'early_payment_discount'])
            ->where('date', '<=', $closeDate)
            ->sum('amount');
    }

    public function getMovementsIdentifiedByExpense(int $idExpense)
    {
        return DB::table($this->table)
            ->where('expense_id', '=', $idExpense)
            ->whereNotNull('operation_id')
            ->where('identified', 'IS', true)
            ->where('type', '=', 'accumulated_capital')
            ->get();
    }

    public function getPreviousBalance(int $idFunctionalUnit,
                                       int $idCurrentExpense,
                                       int $idPreviousExpense = null)
    {
        if (!$idPreviousExpense) {
            return $this->getInitialBalance($idFunctionalUnit);
        }

        $qb = DB::table($this->table)
            ->where('functional_unit_id', '=', $idFunctionalUnit);

        if ($idCurrentExpense) {
            $qb->where('expense_id', '!=', $idCurrentExpense)
                ->orWhereNull('expense_id');
        }

        $previousExpense = DB::table('expenses')->find($idPreviousExpense);
        $qb->where('date', '<=', $previousExpense->close_date);

        $qb->whereNull('identified')
            ->orWhere(function ($query) use ($previousExpense) {
                $query->where('identified', '=', true)
                    ->where('expense_id', '<', $previousExpense->id);
            });

        $result = $qb->sum('amount');

        //Calculo Intereses
        $currentExpense = DB::table('expenses')->find($idCurrentExpense);
        $interest = DB::table($this->table)
            ->where('functional_unit_id', '=', $idFunctionalUnit)
            ->where('type', '=', 'expiration_interest')
            ->where('date', '<=', $currentExpense->close_date)
            ->where('date', '>', $previousExpense->close_date)
            ->sum('amount');

        //Retorno el Balance Previo
        $result += $interest;
        return (float)$result;
    }

    public function getInitialBalance(int $idFunctionalUnit)
    {
        $result = DB::table($this->table)
            ->where('functional_unit_id', '=', $idFunctionalUnit)
            ->where('type', '=', 'initial_balance')
            ->sum('amount');

        return $result;
    }

    public function getExpenseGeneratedInterests(int $idFunctionalUnit, int $idExpense)
    {
        $expense = DB::table('expenses')->find($idExpense);
        $result = DB::table($this->table)
            ->where('functional_unit_id', '=', $idFunctionalUnit)
            ->where('type', '=', 'month_interest')
            ->where('amount', '<', '0')
            ->where('date', '=', $expense->close_date)
            ->sum('amount');

        return $result;
    }

    public function getAccumulatedInterests(int $idFunctionalUnit)
    {
        $result = DB::table($this->table)
            ->whereIn('type', ['accumulated_interest', 'month_interest', 'expiration_interest'])
            ->where('functional_unit_id', '=', $idFunctionalUnit)
            ->sum('amount');

        return (float)$result;
    }

    public function getPaymentsByFunctionalUnit(int $idFunctionalUnit,
                                                Carbon $from = null,
                                                Carbon $to,
                                                $lastExpense = null)
    {
        $qb = DB::table($this->table)
            ->where('functional_unit_id', $idFunctionalUnit)
            ->where('amount', '>', 0)
            ->where('date', $to)
            ->whereNotNull('operation_id')
            ->whereIn('type', [
                'capital',
                'accumulated_interest',
                'accumulated_capital',
                'month_interest',
                'expiration_interest',
                'early_payment_discount'
            ]);

        if ($from) {
            $qb->where('date', $from);
        }

        $qb->orWhere(function ($query) use ($idFunctionalUnit, $lastExpense) {
            if ($lastExpense) {
                $query->where('expense_id', $lastExpense->id);
            }

            $query->where('functional_unit_id', $idFunctionalUnit)
                ->whereNotNull('operation_id')
                ->where('identified', '=', true)
                ->whereIn('type', [
                    'capital',
                    'accumulated_interest',
                    'accumulated_capital',
                    'month_interest',
                    'expiration_interest',
                    'early_payment_discount'
                ]);
        });

        return $qb->get();
    }

    public function getFUsPenaltyInterests(int $idConsortium, Carbon $closeDate)
    {
        return DB::select('
            SELECT fu.*,
                   (
                       SELECT COALESCE(SUM(fum.amount), 0)
                       FROM functional_unit_movements fum
                       WHERE fum.functional_unit_id = fu.id
                   ) AS balance,
                   (
                       SELECT COALESCE(SUM(fum.amount), 0)
                       FROM functional_unit_movements fum
                       WHERE fum.functional_unit_id = fu.id
                         AND fum.type = \'debt_capital\'
                         AND fum.date <= \'' . $closeDate->format('Y-m-d') . '\'
                   ) AS debt_interests_from_close_date,
                   (
                       SELECT COALESCE(SUM(fum.amount), 0)
                       FROM functional_unit_movements fum
                       WHERE fum.functional_unit_id = fu.id
                         AND fum.type IN (\'capital\', \'accumulated_capital\', \'initial_balance\', \'early_payment_discount\')
                         AND fum.date <= \'' . $closeDate->format('Y-m-d') . '\'
                   ) AS debt_capital_from_close_date
            FROM functional_units fu
            WHERE fu.administrable_id = ' . $idConsortium . '
        ');
    }
}
