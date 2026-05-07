<?php

namespace App\Domain\Pricing\Services;

use App\Domain\Pricing\Models\NewspaperPrice;
use App\Domain\Pricing\Repositories\NewspaperPriceRepositoryInterface;
use Exception;
use Illuminate\Support\Collection;

class PriceHistoryService
{
    public function __construct(
        private NewspaperPriceRepositoryInterface $priceRepository
    ) {}

    public function getPricesForNewspaper(int $newspaperId): Collection
    {
        return $this->priceRepository->findByNewspaper($newspaperId);
    }

    public function getPriceForDate(int $newspaperId, string $date): ?NewspaperPrice
    {
        return $this->priceRepository->getPriceForDate($newspaperId, $date);
    }

    public function getCurrentPrice(int $newspaperId): ?NewspaperPrice
    {
        return $this->priceRepository->getCurrentPrice($newspaperId);
    }

    public function addPrice(int $newspaperId, string $effectiveFrom, string|int|float $price, ?string $effectiveTo = null, string|int|float|null $costPrice = null, ?int $createdBy = null): NewspaperPrice
    {
        if ($effectiveTo === null) {
            $currentPrice = $this->priceRepository->getCurrentPrice($newspaperId);

            if ($currentPrice !== null) {
                $currentId = $currentPrice->id;
                if ($this->priceRepository->hasOverlappingPeriod($newspaperId, $effectiveFrom, null, $currentId)) {
                    throw new Exception('A price period already exists for these dates.');
                }

                $this->priceRepository->closeCurrentPrice($newspaperId, $effectiveFrom);
            }
        } else {
            if ($this->priceRepository->hasOverlappingPeriod($newspaperId, $effectiveFrom, $effectiveTo)) {
                throw new Exception('A price period already exists for these dates.');
            }
        }

        return $this->priceRepository->create([
            'newspaper_id' => $newspaperId,
            'price' => $price,
            'cost_price' => $costPrice,
            'effective_from' => $effectiveFrom,
            'effective_to' => $effectiveTo,
            'created_by' => $createdBy ?? auth()->id(),
        ]);
    }

    public function updatePrice(NewspaperPrice $price, array $data): NewspaperPrice
    {
        $newspaperId = $price->newspaper_id;
        $effectiveFrom = $data['effective_from'] ?? $price->effective_from;
        $effectiveTo = $data['effective_to'] ?? null;
        $excludeId = $price->id;

        if ($this->priceRepository->hasOverlappingPeriod($newspaperId, $effectiveFrom, $effectiveTo, $excludeId)) {
            throw new Exception('A price period already exists for these dates.');
        }

        $wasOpenEnded = $price->effective_to === null;

        if ($wasOpenEnded && $effectiveTo !== null) {
            throw new Exception('Cannot close a price that was already closed.');
        }

        if (!$wasOpenEnded && $effectiveTo === null) {
            $this->priceRepository->closeCurrentPrice($newspaperId, $effectiveFrom);
        }

        $this->priceRepository->update($price, $data);

        return $price->fresh();
    }

    public function deletePrice(NewspaperPrice $price): bool
    {
        if ($price->effective_to === null) {
            throw new Exception('Cannot delete the current active price. Close it first by adding a new price.');
        }

        return $this->priceRepository->delete($price);
    }
}
