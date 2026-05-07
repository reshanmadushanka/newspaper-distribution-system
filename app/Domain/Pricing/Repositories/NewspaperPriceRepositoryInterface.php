<?php

namespace App\Domain\Pricing\Repositories;

use App\Domain\Pricing\Models\NewspaperPrice;
use Illuminate\Support\Collection;

interface NewspaperPriceRepositoryInterface
{
    public function findByNewspaper(int $newspaperId): Collection;

    public function find(int $id): ?NewspaperPrice;

    public function findOrFail(int $id): NewspaperPrice;

    public function create(array $data): NewspaperPrice;

    public function update(NewspaperPrice $price, array $data): bool;

    public function delete(NewspaperPrice $price): bool;

    public function getPriceForDate(int $newspaperId, string $date): ?NewspaperPrice;

    public function hasOverlappingPeriod(int $newspaperId, string $effectiveFrom, ?string $effectiveTo, int $excludeId = 0): bool;

    public function closeCurrentPrice(int $newspaperId, string $effectiveTo): bool;

    public function getCurrentPrice(int $newspaperId): ?NewspaperPrice;
}
