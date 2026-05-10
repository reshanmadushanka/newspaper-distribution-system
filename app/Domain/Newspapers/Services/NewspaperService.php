<?php

namespace App\Domain\Newspapers\Services;

use App\Domain\Newspapers\Models\Newspaper;
use App\Domain\Newspapers\Repositories\NewspaperRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NewspaperService
{
    public function __construct(
        private NewspaperRepositoryInterface $newspaperRepository
    ) {}

    public function getPaginatedNewspapers(int $perPage = 10): LengthAwarePaginator
    {
        return $this->newspaperRepository->paginate($perPage);
    }

    public function createNewspaper(array $data): Newspaper
    {
        return $this->newspaperRepository->create($data);
    }

    public function updateNewspaper(Newspaper $newspaper, array $data): Newspaper
    {
        $this->newspaperRepository->update($newspaper, $data);
        return $newspaper->fresh();
    }

    public function deleteNewspaper(Newspaper $newspaper): bool
    {
        return $this->newspaperRepository->delete($newspaper);
    }

    public function getNewspaperForEdit(int $id): Newspaper
    {
        return $this->newspaperRepository->findOrFail($id);
    }

    public function getActiveNewspapers(): Collection
    {
        return $this->newspaperRepository->getActiveNewspapers();
    }
}
