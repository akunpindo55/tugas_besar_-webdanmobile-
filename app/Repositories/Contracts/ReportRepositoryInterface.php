<?php

namespace App\Repositories\Contracts;

use App\Models\Report;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ReportRepositoryInterface
{
    public function create(array $data, User $reporter): Report;

    public function paginateAll(int $limit = 20): LengthAwarePaginator;

    public function findById(int $id): ?Report;

    public function resolve(Report $report, User $admin): void;
}
