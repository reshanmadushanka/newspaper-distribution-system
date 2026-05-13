<?php

namespace App\Domain\Newspapers\Repositories;

use App\Domain\Newspapers\Models\Newspaper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface NewspaperRepositoryInterface
{
    public function paginate(int $perPage = 10, string $search = ''): LengthAwarePaginator;

    public function find(int $id): ?Newspaper;

    public function findOrFail(int $id): Newspaper;

    public function create(array $data): Newspaper;

    public function update(Newspaper $newspaper, array $data): bool;

    public function delete(Newspaper $newspaper): bool;

    public function getActiveNewspapers(array $columns = ['*']): Collection;
}
