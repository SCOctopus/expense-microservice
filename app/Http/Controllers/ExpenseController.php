<?php

namespace App\Http\Controllers;

use App\Services\ExpenseService;
use App\Validators\Rules\ExpenseRules;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    /** @var ExpenseService */
    protected $expenseService;

    /**
     * ExpenseController constructor.
     * @param ExpenseService $expenseService
     */
    public function __construct(ExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createDraft(Request $request)
    {
        //Validate Data
        $data = $request->all();
        $idConsortium = $data['idConsortium'];
        $validator = Validator::make($data, ExpenseRules::createDraftRules($idConsortium));
        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation error',
                'data' => $validator->errors()
            ], 422);
        }

        //Format Data
        $data = $this->formatData($data);

        //Generate Draft
        $expenseDraft = $this->expenseService->createDraft($idConsortium, $data);
        return response()->json([
            'status' => 'success',
            'data' => [
                'expense' => $expenseDraft
            ]
        ]);
    }

    /**
     * @param array $data
     * @return array
     */
    private function formatData(array $data)
    {
        $data['closeDate'] = Carbon::createFromFormat('d/m/Y', $data['closeDate']);
        $data['firstDueDate'] = Carbon::createFromFormat('d/m/Y', $data['firstDueDate']);
        $data['secondDueDate'] = Carbon::createFromFormat('d/m/Y', $data['secondDueDate']);
        $data['allReceipts'] = filter_var($data['allReceipts'], FILTER_VALIDATE_BOOLEAN);
        $data['makeUnidentifiedNote'] = filter_var($data['makeUnidentifiedNote'], FILTER_VALIDATE_BOOLEAN);
        $data['makeIdentifiedNote'] = filter_var($data['makeIdentifiedNote'], FILTER_VALIDATE_BOOLEAN);
        return $data;
    }
}
