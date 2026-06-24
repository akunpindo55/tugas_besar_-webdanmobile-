<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreReportRequest;
use App\Http\Resources\ReportResource;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends ApiController
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    public function store(StoreReportRequest $request): JsonResponse
    {
        try {
            $report = $this->reportService->fileReport($request->user(), $request->validated());
            return $this->successResponse(
                new ReportResource($report),
                'Laporan berhasil dikirim.',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function indexAdmin(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->query('limit', 20);
            $paginator = $this->reportService->listReports($request->user(), $limit);

            return $this->paginatedResponse(
                ReportResource::collection($paginator),
                'Daftar laporan berhasil dimuat.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }
    }

    public function resolveAdmin(Request $request, int $id): JsonResponse
    {
        try {
            $this->reportService->resolveReport($request->user(), $id);
            return $this->successResponse(null, 'Laporan berhasil diselesaikan.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }
    }
}
