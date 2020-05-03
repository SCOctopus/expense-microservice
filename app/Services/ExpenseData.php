<?php


namespace App\Services;

use App\Repositories\ConsortiumPercentageRepository;
use App\Repositories\ExpenseAccountStatusesRepository;
use App\Repositories\ExpenseAccountStatusRowRepository;
use App\Repositories\ExpenseFunctionalUnitsRepository;
use App\Repositories\ExpensePercentageFixedAmountsRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\FunctionalUnitPercentageRepository;
use App\Repositories\FunctionalUnitRepository;
use Carbon\Carbon;

class ExpenseData
{
    private $expense;
    private $consortium;
    private $functionalUnits;
    private $closeDate;
    private $lastExpense;
    public $categories;
    public $consortiumPercentages;
    private $reprocessExpense = null;
    private $providers = [];
    public $payslipsSalary = [];
    private $payslipsContribution = [];
    private $allInstallments = [];
    private $installmentsByUF = [];
    private $installmentsByCategory = [];
    private $installmentsByPorcentualUF = [];
    private $accountStatusByUF = [];
    private $totalSpendingByPercentageProrate = [];
    private $totalSpendingByPercentage = [];
    private $totalSpendingsByCategory = [];
    private $totalSpendingsByCategoryProrate = [];
    private $notAffectFinancialStatusSpendings = 0;
    private $totalParticularSpendigs = 0;
    private $totalAportesYContribucionesSpendings = 0;
    private $totalParticularPercentageSpendigs = 0;
    private $totalSpendingsProrate = 0;
    private $totalSpendings = 0;
    private $balanceToPayTotal = 0;
    private $earlyBalanceToPayTotal = 0;
    private $roundingTotal = 0;

    public function __construct(
        ExpenseFunctionalUnitsRepository $expenseFunctionalUnitsRepository,
        FunctionalUnitService $functionalUnitService,
        ExpenseAccountStatusesRepository $expenseAccountStatusesRepository,
        ExpensePercentageFixedAmountsRepository $expensePercentageFixedAmountsRepository,
        ExpenseAccountStatusRowRepository $expenseAccountStatusRowRepository,
        FunctionalUnitMovementService $functionalUnitMovementService,
        InstallmentService $installmentService,
        ConsortiumPercentageRepository $consortiumPercentageRepository,
        CategoryService $categoryService,
        FunctionalUnitRepository $functionalUnitRepository,
        ExpenseRepository $expenseRepository,
        FunctionalUnitPercentageRepository $functionalUnitPercentageRepository,
        ExpenseIncomes $expenseIncomes)
    {
        $this->expenseFunctionalUnitsRepository = $expenseFunctionalUnitsRepository;
        $this->functionalUnitService = $functionalUnitService;
        $this->expenseAccountStatusesRepository = $expenseAccountStatusesRepository;
        $this->expensePercentageFixedAmountRepository = $expensePercentageFixedAmountsRepository;
        $this->functionalUnitPercentageRepository = $functionalUnitPercentageRepository;
        $this->expenseAccountStatusRowRepository = $expenseAccountStatusRowRepository;
        $this->functionalUnitMovementService = $functionalUnitMovementService;
        $this->functionalUnitRepository = $functionalUnitRepository;
        $this->installmentService = $installmentService;
        $this->consortiumPercentagesRepository = $consortiumPercentageRepository;
        $this->categoryService = $categoryService;
        $this->expenseRepository = $expenseRepository;
        $this->expenseIncomes = $expenseIncomes;
    }

    public function calculate($expense,
                              $consortium,
                              $functionalUnits
    )
    {
        $this->expense = $expense;
        $this->consortium = $consortium;
        $this->functionalUnits = $functionalUnits;
        $this->closeDate = Carbon::createFromFormat('Y-m-d', $expense->close_date);
        $this->lastExpense = $this->expenseRepository->getLastPrintedExpense($consortium->id);

        // this first - prepares data
        $this->setCategories();
        $this->setConsortiumPercentages($consortium->id);

        // this seconds - do voodoo calculations
        $this->setCategorySpendingTotals();
        $this->calculateAccountStatusBalances();
    }

    private function setCategories()
    {
        $this->categories = $this->categoryService->listAll();
    }

    private function setConsortiumPercentages(int $idConsortium)
    {
        $percentages = $this->consortiumPercentagesRepository->getByConsortium($idConsortium);
        $this->consortiumPercentages = $percentages;
    }

    private function setCategorySpendingTotals()
    {
        foreach ($this->categories as $category) {
            $installments = $this->installmentService->listForExpense($this->consortium->id,
                $this->closeDate,
                $category->id,
                $this->reprocessExpense);

            foreach ($installments as $installment) {
                $installmentConsortiumPercentages = $this->installmentService->getConsortiumPercentages($installment->id);

                //Gastos de Sueldos
                if ($category->position == 1 && $installment->payslip && $installment->spendings_type == 'salary') {
                    $this->payslipsSalary[] = $installment;
                }

                //Gastos de Contribuciones
                if ($category->position == 1 && $installment->payslip && $installment->spendings_type == 'contribution') {
                    $this->payslipsContribution[] = $installment;
                }

                //Gastos Particulares UF
                if ($installment->spendings_functional_unit_id && count($installmentConsortiumPercentages) == 0) {
                    $this->allInstallments[] = $installment;

                    $idFunctionalUnit = $installment->spendings_functional_unit_id;
                    $this->installmentsByUF[$idFunctionalUnit][] = $installment;

                    if (!$installment->spendings_not_outflow) {
                        $this->totalParticularSpendigs += $installment->amount;
                    }
                } //Gastos con Porcentual Fijo
                elseif (count($installmentConsortiumPercentages) > 0 && !$installment->spendings_functional_unit_id) {
                    //Gastos Comunes
                    $this->allInstallments[] = $installment;
                    $this->installmentsByCategory[$category->id][] = $installment;

                    if ($category->position == '1' && !$installment->payslip) {
                        $this->totalAportesYContribucionesSpendings += $installment->amount;
                    } else {
                        $this->totalSpendings += $installment->amount;
                    }

                    if (!$installment->spendings_affect_financial_section) {
                        $this->notAffectFinancialStatusSpendings += $installment->amount;
                    }

                    $this->totalSpendingsByCategory[$category->id] += $installment->amount;

                    foreach ($installmentConsortiumPercentages as $installmentConsortiumPercentage) {
                        $this->totalSpendingByPercentage[$installmentConsortiumPercentage->id] += $installmentConsortiumPercentage->amount;
                    }

                    if ($installment->spendings_prorate_in_expenses) {
                        $this->totalSpendingsProrate += $installment->amount;
                        $this->totalSpendingsByCategoryProrate[$category->id] += $installment->amount;

                        foreach ($installmentConsortiumPercentages as $installmentConsortiumPercentage) {
                            $this->totalSpendingByPercentageProrate[$installmentConsortiumPercentage->id] += $installmentConsortiumPercentage->amount;
                        }

                        if ($installment->spendings_provider_id) {
                            $this->providers[] = $installment->spendings_provider_id;
                        }
                    }
                } elseif ($installment->spendings_functional_unit_id && $installmentConsortiumPercentages > 0) {
                    $this->allInstallments[] = $installment;
                    $this->installmentsByPorcentualUF[$installment->spendings_functional_unit_id][] = $installment;

                    if (!$installment->spendings_not_outflow) {
                        $this->totalParticularPercentageSpendigs += $installment->amount;
                    }
                }
            }
        }
    }

    private function calculateAccountStatusBalances()
    {
        $previousBalanceTotal = 0;
        $paymentTotal = 0;
        $privateSpendingsTotal = 0;
        $i = 0;

        //TODO refactor
        foreach ($this->functionalUnits as $functionalUnit) {

            $accountStatusData = [];

            $idLastExpense = !isset($this->lastExpense) ? null : $this->lastExpense->id;

            $previousBalance = (float)$this->functionalUnitMovementService->getPreviousBalance(
                $functionalUnit->id,
                $this->expense->id,
                $idLastExpense
            );

            $payment = $this->expenseIncomes->getPaymentsByFunctionalUnit($functionalUnit->id);

            $earlyPaymentDiscounts = $this->expenseIncomes->getEarlyPaymentDiscounts($functionalUnit->id);

            $debt = $this->functionalUnitMovementService->getPreviousDebtFromCloseDate(
                $functionalUnit->id,
                Carbon::createFromFormat('Y-m-d', $this->expense->close_date),
                $this->expense->penalty_interests_mode
            );

            if ($this->lastExpense) {
                $accountStatusLastExpense = $this->expenseAccountStatusesRepository->findByExpense($this->lastExpense->id);
                $statusRow = $this->expenseAccountStatusRowRepository->findOneBy(
                    $functionalUnit->id,
                    $accountStatusLastExpense->id);
                if ($statusRow) {
                    $previousBalance += (float)$statusRow->rounding;
                }
            }

            /**
             * La columna 'INTERESES' es:
             * - en interes sobre interes: el interes del mes
             * - en interes sobre capital: el interes acumulado
             */
            if ($this->expense->penalty_interests_mode == 'interest') {
                $interests = $this->functionalUnitMovementService->getExpenseGeneratedInterests($functionalUnit, $this->expense);
                $debt -= $interests;
            } else {
                $interests = $this->functionalUnitMovementService->getAccumulatedInterests(
                    $functionalUnit->id
                );
            }

            $debt -= $earlyPaymentDiscounts;
            $interests *= -1;

            $accountStatusData['previousBalance'] = $previousBalance;
            $accountStatusData['payment'] = $payment;
            $accountStatusData['previousDebt'] = $debt;
            $accountStatusData['interests'] = $interests;
            $accountStatusData['earlyPaymentDiscounts'] = $earlyPaymentDiscounts;

            $totalByUF = $this->getTotalAmountByUf($functionalUnit->id);
            $balanceToPay = $debt - $interests;
            $spendings = -$totalByUF;
            $particularSpending = $spendings;

            foreach ($this->consortiumPercentages as $consortiumPercentage) {
                if (!$consortiumPercentage->particular_percentage) {
                    try {
                        $functionalUnitPercentage = $this->functionalUnitPercentageRepository->getByFunctionalUnitConsortiumPercentage($functionalUnit->id, $consortiumPercentage->id);

                        if ($functionalUnitPercentage) {
                            $ufPercentage = $functionalUnitPercentage->value;
                        } else {
                            $ufPercentage = 0;
                        }
                    } catch (\Exception $exception) {
                        $ufPercentage = 0;
                    }

                    $totalByPercentage = $this->getFixedTotalByPercentage($consortiumPercentage);
                    $value = round(($ufPercentage * $totalByPercentage) / 100, 2);
                    $accountStatusData['ufPercentage'][$consortiumPercentage->id] = [
                        'percentage' => $ufPercentage,
                        'value' => $value
                    ];

                    $spendings -= $value;

                } elseif ($consortiumPercentage->particular_percentage) {

                    $value = $this->getFixedTotalByPercentageParticular($consortiumPercentage, $functionalUnit->id);

                    $accountStatusData['ufPercentage'][$consortiumPercentage->id] = [
                        'percentage' => 0,
                        'value' => $value
                    ];

                    $spendings -= $value;
                }
            }

            $balanceToPay += $spendings;

            $rounding = 0;

            if ($balanceToPay < 0 && ($this->consortium->rounding == 'yes' || $this->consortium->rounding == 'uf')) {
                $roundedBalanceToPay = round($balanceToPay);
                $rounding = $roundedBalanceToPay - $balanceToPay;
                $balanceToPay = $roundedBalanceToPay;

                if ($this->consortium->rounding == 'uf') {
                    $ufRounding = $functionalUnit->number / 100;
                    $functionalUnitCents = ($ufRounding >= 1) ? $ufRounding - intval($ufRounding) : $ufRounding;
                    $rounding -= $functionalUnitCents;
                    $balanceToPay -= $functionalUnitCents;
                }
            }

            $spendings += $rounding;
            $secondBalanceToPay = 0;
            $balanceToPayPositive = $balanceToPay * (-1);

            if ($balanceToPayPositive > 0) {
                if ($this->consortium->second_due_date) {
                    $secondBalanceToPay = $this->functionalUnitService->calculateSecondDueDatePayment($this->consortium,
                        $functionalUnit,
                        $balanceToPay,
                        $this->expense);
                }
            }

            $earlyBalanceToPay = $balanceToPay + ((-1) * ($spendings * $this->consortium->early_payment_discount) / 100);

            $accountStatusData['rounding'] = $rounding;
            $accountStatusData['spendings'] = $spendings;
            $accountStatusData['balanceToPay'] = $balanceToPay;
            $accountStatusData['earlyBalanceToPay'] = $earlyBalanceToPay;

            $this->accountStatusByUF[$functionalUnit->id] = $accountStatusData;

            $lastExpenseCloseDate = $this->lastExpense ? Carbon::createFromFormat('Y-m-d', $this->lastExpense->close_date) : null;

            $payments = $this->functionalUnitMovementService->getPaymentsByFunctionalUnit(
                $functionalUnit->id,
                $lastExpenseCloseDate,
                $this->closeDate,
                $this->lastExpense);

            $this->expenseIncomes->addIncomes($payments, $functionalUnit, $this->lastExpense);

            //totales para el estado de cuenta
            $previousBalanceTotal += $previousBalance;
            $paymentTotal += $payment;
            $privateSpendingsTotal += $this->getTotalAmountByUf($functionalUnit->id);
            $this->balanceToPayTotal += $balanceToPay;
            $this->earlyBalanceToPayTotal += $earlyBalanceToPay;
            $this->roundingTotal += (float)$rounding;

            //TODO Particular Spending ???
            $this->expenseFunctionalUnitsRepository->create(
                [
                    'expense_id' => $this->expense->id,
                    'functional_unit_id' => $functionalUnit->id,
                    'balance_to_pay' => $balanceToPay,
                    'previous_balance' => $previousBalance,
                    'last_payment' => $payment,
                    'month_interests' => $interests * (-1),
                    'receipt_number' => null,
                    'second_balance_to_pay' => $secondBalanceToPay,
                    'spendings' => $spendings,
                    'early_balance_to_pay' => $earlyBalanceToPay,
                    'pdf_path' => null,
                ]
            );

            $i++;
        }

        $this->expenseRepository->setAmountToCollect($this->expense->id, $this->balanceToPayTotal);
        $this->expenseRepository->setAmountCollected($this->expense->id, 0);
    }

    private function getTotalAmountByUf(int $idFunctionalUnit)
    {
        $total = 0;
        if (isset($this->installmentsByUF[$idFunctionalUnit])) {
            foreach ($this->installmentsByUF[$idFunctionalUnit] as $installment) {
                $total += $installment->amount;
            }
        }
        return $total;
    }

    private function getFixedTotalByPercentage($consortiumPercentage)
    {
        $total = 0;
        $expensePercentageFixedAmounts = $this->expensePercentageFixedAmountRepository->getByExpense($this->expense->id);

        foreach ($expensePercentageFixedAmounts as $expensePercentageFixedAmount) {
            if ($consortiumPercentage->id == $expensePercentageFixedAmount->consortium_percentage_id) {
                $total = $expensePercentageFixedAmount->value;
            }
        }
        return $total;
    }

    private function getFixedTotalByPercentageParticular($consortiumPercentage, int $idFunctionalUnit)
    {
        $total = 0;

        $installments = $this->installmentService->listForExpensePercentageParticular(
            $this->consortium->id,
            $this->closeDate,
            $idFunctionalUnit,
            $this->reprocessExpense
        );

        foreach ($installments as $i) {
            foreach ($this->installmentService->getConsortiumPercentages($i->id) as $percentage) {
                if ($percentage->consortium_percentage_id == $consortiumPercentage->id) {
                    $total += $percentage->amount;
                }
            }
        }

        return $total;
    }

}
