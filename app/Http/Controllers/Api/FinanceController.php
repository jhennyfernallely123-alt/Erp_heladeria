<?php

namespace App\Http\Controllers\Api;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FinanceController extends BaseApiController
{
    public function __construct(protected ReportService $reportService)
    {
    }

    public function reports(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $data = $this->reportService->getSummary($startDate, $endDate);
        return $this->successResponse($data);
    }
}
