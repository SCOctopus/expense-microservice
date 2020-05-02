<?php


namespace App\Services;

use App\Repositories\ConsortiumPercentageRepository;
use App\Repositories\ExpenseAccountStatusRowRepository;
use App\Repositories\ExpensePercentageFixedAmountsRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\FunctionalUnitPercentageRepository;
use App\Repositories\FunctionalUnitRepository;

class ExpenseData
{
    private $expense;
    private $consortium;
    private $closeDate;
    private $lastExpense;
    private $categories;
    private $consortiumPercentages;
    private $reprocessExpense = null;
    private $providers = [];
    private $payslipsSalary = [];
    private $payslipsContribution = [];
    private $allInstallments = [];
    private $installmentsByUF = [];
    private $installmentsByCategory = [];
    private $installmentsByPorcentualUF = [];
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

    public function __construct(
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
                              $consortium)
    {
        $this->expense = $expense;
        $this->consortium = $consortium;
        $this->closeDate = $expense->close_date;
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

        $functionalUnits = $this->functionalUnitRepository->getByConsortium($this->consortium->id);

        foreach ($functionalUnits as $functionalUnit) {

            $accountStatusData = [];

            $previousBalance = (float)$this->functionalUnitMovementService->getPreviousBalance(
                $functionalUnit->id,
                $this->expense,
                $this->lastExpense
            );

            $payment = $this->expenseIncomes->getPaymentsByFunctionalUnit($functionalUnit->id);

            $earlyPaymentDiscounts = $this->expenseIncomes->getEarlyPaymentDiscounts($functionalUnit->id);

            $debt = $this->functionalUnitMovementService->getPreviousDebtFromCloseDate(
                $functionalUnit->id,
                $this->expense->close_date,
                $this->expense->penalty_interests_mode
            );

            if ($this->lastExpense) {
                $statusRow = $this->expenseAccountStatusRowRepository->findOneBy($functionalUnit->id, $this->lastExpense['']);
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

                    //TODO


                }
            }
        }
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

    }

}
