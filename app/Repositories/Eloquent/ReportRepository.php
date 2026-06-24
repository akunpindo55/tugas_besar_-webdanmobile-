<?php

namespace App\Repositories\Eloquent;

use App\Models\Report;
use App\Models\User;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReportRepository implements ReportRepositoryInterface
{
    public function create(array $data, User $reporter): Report
    {
        return Report::create([
            'reporter_id' => $reporter->id,
            'reportable_type' => $data['reportable_type'],
            'reportable_id' => $data['reportable_id'],
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);
    }

    public function paginateAll(int $limit = 20): LengthAwarePaginator
    {
        return Report::with(['reporter', 'reviewer', 'reportable'])
            ->latest()
            ->paginate($limit);
    }

    public function findById(int $id): ?Report
    {
        return Report::with(['reporter', 'reviewer', 'reportable'])->find($id);
    }

    public function resolve(Report $report, User $admin): void
    {
        $report->update([
            'status' => 'resolved',
            'reviewed_by' => $admin->id,
        ]);
    }
}
