<?php


namespace App\Services;


use App\Repositories\ConsortiumFinancialMovementRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\FunctionalUnitMovementRepository;
use App\Repositories\FunctionalUnitRepository;
use App\Repositories\NoteRepository;
use App\Util\CurrencyHelper;
use Carbon\Carbon;

class NoteService
{
    public function __construct(NoteRepository $noteRepository,
                                ConsortiumFinancialMovementRepository $consortiumFinancialMovementRepository,
                                ConsortiumFinancialMovementService $consortiumFinancialMovementService,
                                FunctionalUnitRepository $functionalUnitRepository,
                                ExpenseRepository $expenseRepository,
                                FunctionalUnitMovementRepository $functionalUnitMovementRepository)
    {
        $this->noteRepository = $noteRepository;
        $this->consortiumFinancialMovementRepository = $consortiumFinancialMovementRepository;
        $this->consortiumFinancialMovementService = $consortiumFinancialMovementService;
        $this->functionalUnitRepository = $functionalUnitRepository;
        $this->expenseRepository = $expenseRepository;
        $this->functionalUnitMovementRepository = $functionalUnitMovementRepository;
    }

    public function makeIdentifiedNote($consortium,
                                       Carbon $closeDate,
                                       string $stringPeriod)
    {
        if ((bool)$this->findIdentifiedNotes($closeDate->format('d/m/Y'),
            $consortium)) {
            return false;
        }

        $title = "Pagos Identificados al Período " . $stringPeriod;

        $body = ["<b><u>Pagos Identificados:</u></b>"];

        $lastPrintedExpense = $this->expenseRepository->getLastPrintedExpense($consortium->id);

        if (!$lastPrintedExpense) {
            return false;
        }

        $movements = $this->functionalUnitMovementRepository->getMovementsIdentifiedByExpense($lastPrintedExpense->id);

        if (!count($movements)) {
            return false;
        }

        foreach ($movements as $movement) {
            $row = [
                '<p>',
                implode(
                    ' - ',
                    [
                        trim($movement->functional_unit_id),
                        CurrencyHelper::format($movement->amount),
                        $movement->date
                    ]
                ),
                '</p>',
            ];

            array_push($body, implode('', $row));
        }

        return $this->makeAutomaticNote(
            $consortium->administration_id,
            $consortium->id,
            $title,
            implode('', $body),
            $closeDate->format('d/m/Y'),
            'identified'
        );
    }


    public function makeUnidentifiedNote($consortium,
                                         Carbon $closeDate,
                                         string $stringPeriod)
    {
        if ((bool)$this->findUnidentifiedNotes($closeDate->format('d/m/Y'), $consortium)) {
            return false;
        }

        $title = "Pagos No Identificados al Período " . $stringPeriod;

        $body = ["<b><u>Pagos No Identificados:</u></b>"];

        $filters = [
            'date_to' => $closeDate,
            'not_in_notes' => true
        ];

        $movements = $this->consortiumFinancialMovementService->getUnidentifiedsMovements($filters, $consortium->id);

        if (!count($movements)) {
            return false;
        }

        foreach ($movements as $mov) {
            $row = [
                '<p>',
                implode(
                    ' - ',
                    [
                        $mov->date,
                        CurrencyHelper::format($mov->amount)
                    ]
                ),
                '</p>',
            ];

            array_push($body, implode('', $row));
        }

        $note = $this->makeAutomaticNote(
            $consortium->administration_id,
            $consortium->id,
            $title,
            implode('', $body),
            $closeDate->format('d/m/Y'),
            'unidentified'
        );

        foreach ($movements as $mov) {
            $data['unidentified_payments_note_id'] = $note['id'];
            $this->consortiumFinancialMovementRepository->update($mov->id, $data);
        }
    }

    private function findIdentifiedNotes($closeDate, $consortium)
    {
        return $this->noteRepository->findNotes($closeDate, $consortium->id, 'identified');
    }

    private function findUnidentifiedNotes($closeDate, $consortium)
    {
        return $this->noteRepository->findNotes($closeDate, $consortium->id, 'unidentified');
    }

    private function makeAutomaticNote(
        $idAdministration,
        $idConsortium,
        $title,
        $content,
        $closeDate,
        $noteType
    )
    {
        $note = [
            'administrable_id' => $idAdministration,
            'title' => $title,
            'content' => $content,
            'plain_content' => $content,
            'close_date' => $closeDate
        ];

        switch ($noteType) {
            case 'debt':
                $note['is_debt_note'] = true;
                break;
            case 'identified':
                $note['identified_payments_note'] = true;
                break;
            case 'unidentified':
                $note['unidentified_payments_note'] = true;
                break;
        }

        if (is_array($closeDate)) {
            foreach ($closeDate as $date) {
                $note['close_date'] = $date;
            }
        } else {
            $note['close_date'] = $closeDate;
        }

        $note['id'] = $this->noteRepository->save($note);
        $this->noteRepository->saveNoteConsortia($note['id'], $idConsortium, 1);

        return $note;
    }
}
