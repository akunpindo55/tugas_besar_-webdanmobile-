<?php

namespace App\Services;

use App\Models\Report;
use App\Models\User;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReportService
{
    public function __construct(
        protected ReportRepositoryInterface $reportRepository
    ) {}

    public function fileReport(User $reporter, array $data): Report
    {
        // Simple validation that the morphable model exists
        $morphClass = $data['reportable_type'];
        if (!class_exists($morphClass)) {
            // Check if user passed simplified string and resolve to full namespace
            $namespaceClass = "App\\Models\\" . ucfirst($morphClass);
            if (class_exists($namespaceClass)) {
                $morphClass = $namespaceClass;
            } else {
                throw new \Exception('Tipe konten laporan tidak valid.');
            }
        }

        $model = $morphClass::find($data['reportable_id']);
        if (!$model) {
            throw new \Exception('Konten yang dilaporkan tidak ditemukan.');
        }

        return $this->reportRepository->create([
            'reportable_type' => $morphClass,
            'reportable_id' => $data['reportable_id'],
            'reason' => $data['reason'],
        ], $reporter);
    }

    public function listReports(User $admin, int $limit = 20): LengthAwarePaginator
    {
        if (!$admin->isAdmin()) {
            throw new \Exception('Hanya admin yang dapat melihat laporan.');
        }

        return $this->reportRepository->paginateAll($limit);
    }

    public function resolveReport(User $admin, int $reportId): void
    {
        if (!$admin->isAdmin()) {
            throw new \Exception('Hanya admin yang dapat memproses laporan.');
        }

        $report = $this->reportRepository->findById($reportId);
        if (!$report) {
            throw new \Exception('Laporan tidak ditemukan.');
        }

        $this->reportRepository->resolve($report, $admin);
    }
}
