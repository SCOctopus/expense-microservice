<?php


namespace App\Services\ExpenseBuilder;


class ExpenseCreatePayslip
{
    private $expensePayslipSectionPercentages = [];
    private $expensePayslipSection;
    private $consortiumPercentages;
    private $categories;
    private $expense;
    private $expenseData;

    public function create($expense, $expenseData)
    {
        try {

            $this->expense = $expense;
            $this->expenseData = $expenseData;

            $this->payslipsSalaries = null;
            $this->payslipsContributions = null;
            $this->expensePayslipSection = [
                'expense_id' => $this->expense->id,
                'title' => 'REMUNERACIONES AL PERSONAL Y CARGAS SOCIALES',
                'statute' => '(Conf. Art. 10 inc. e y f Ley N° 941)'
            ];

            $this->setPercentages();
            $this->setCategories();
            $this->createPercentages();
            $this->createPayslipEmployees();

            //TODO

        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    private function setPercentages()
    {
        $this->consortiumPercentages = [];
        $percentages = $this->expenseData->consortiumPercentages;

        foreach ($percentages as $percentage) {
            if (!$percentage->hidden_expense_spending) {
                $this->consortiumPercentages[] = $percentage;
            }
        }
    }

    private function setCategories()
    {
        $this->categories = $this->expenseData->categories;
    }

    private function createPercentages()
    {
        $p = 0;

        foreach ($this->consortiumPercentages as $percentage) {
            if (!$percentage->particular_percentage) {
                $firstLineTitle = $percentage->name;
                $secondLineTitle = $percentage->second_line_name;

                $payslipSectionPercentages = [
                    'expense_payslip_section_id' => 1, //TODO
                    'percentage_title' => $firstLineTitle,
                    'position' => $p,
                    'percentage_title_second_line' => $secondLineTitle
                ];

                $this->expensePayslipSectionPercentages[$percentage->id] = $payslipSectionPercentages;

                $p++;
            }
        }
    }

    private function createPayslipEmployees()
    {
        $installments = $this->expenseData->payslipsSalary;

        if (count($installments) > 0) {
            foreach ($installments as $installment) {
                //TODO


                $payslip = $installment;
                $this->createPayslipEmployee($payslip, );
            }
        } else {
            $this->createEmptyPayslipEmployee();
        }
    }

    private function createEmptyPayslipEmployee()
    {

    }
}
