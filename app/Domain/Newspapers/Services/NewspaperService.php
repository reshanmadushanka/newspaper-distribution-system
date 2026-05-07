<?php

namespace App\Domain\Newspapers\Services;

use App\Domain\Newspapers\Models\Newspaper;
use App\Domain\Newspapers\Repositories\NewspaperRepositoryInterface;
use App\Domain\Pricing\Services\PriceHistoryService;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NewspaperService
{
    public function __construct(
        private NewspaperRepositoryInterface $newspaperRepository,
        private PriceHistoryService $priceHistoryService
    ) {}

    public function getPaginatedNewspapers(int $perPage = 10): LengthAwarePaginator
    {
        return $this->newspaperRepository->paginate($perPage);
    }

    public function createNewspaper(array $data): Newspaper
    {
        $price = $data['price'] ?? null;
        $costPrice = $data['cost_price'] ?? null;
        unset($data['price'], $data['cost_price']);

        $newspaper = $this->newspaperRepository->create($data);

        if ($price !== null) {
            try {
                $this->priceHistoryService->addPrice(
                    newspaperId: $newspaper->id,
                    effectiveFrom: now()->format('Y-m-d'),
                    price: $price,
                    costPrice: $costPrice,
                );
            } catch (Exception $e) {
            }
        }

        return $newspaper;
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

    public function getActiveNewspapers(): array
    {
        return $this->newspaperRepository->getActiveNewspapers();
    }
}
