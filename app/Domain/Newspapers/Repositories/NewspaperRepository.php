<?php

namespace App\Domain\Newspapers\Repositories;

use App\Domain\Newspapers\Enums\NewspaperStatus;
use App\Domain\Newspapers\Models\Newspaper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class NewspaperRepository implements NewspaperRepositoryInterface
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return Newspaper::query()
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function find(int $id): ?Newspaper
    {
        return Newspaper::find($id);
    }

    public function findOrFail(int $id): Newspaper
    {
        return Newspaper::findOrFail($id);
    }

    public function create(array $data): Newspaper
    {
        return DB::transaction(function () use ($data) {
            return Newspaper::create($data);
        });
    }

    public function update(Newspaper $newspaper, array $data): bool
    {
        return DB::transaction(function () use ($newspaper, $data) {
            return $newspaper->update($data);
        });
    }

    public function delete(Newspaper $newspaper): bool
    {
        return $newspaper->delete();
    }

    public function getActiveNewspapers(): array
    {
        return Newspaper::query()
            ->where('status', NewspaperStatus::ACTIVE)
            ->orderBy('name')
            ->get()
            ->all();
    }
}
