<?php

namespace App\Domain\Pricing\Repositories;

use App\Domain\Pricing\Models\NewspaperPrice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NewspaperPriceRepository implements NewspaperPriceRepositoryInterface
{
    public function findByNewspaper(int $newspaperId): Collection
    {
        return NewspaperPrice::query()
            ->where('newspaper_id', $newspaperId)
            ->orderByDesc('effective_from')
            ->get();
    }

    public function find(int $id): ?NewspaperPrice
    {
        return NewspaperPrice::find($id);
    }

    public function findOrFail(int $id): NewspaperPrice
    {
        return NewspaperPrice::findOrFail($id);
    }

    public function create(array $data): NewspaperPrice
    {
        return DB::transaction(function () use ($data) {
            $data['created_at'] = now();
            return NewspaperPrice::create($data);
        });
    }

    public function update(NewspaperPrice $price, array $data): bool
    {
        return DB::transaction(function () use ($price, $data) {
            return $price->update($data);
        });
    }

    public function delete(NewspaperPrice $price): bool
    {
        return $price->delete();
    }

    public function getPriceForDate(int $newspaperId, string $date): ?NewspaperPrice
    {
        return NewspaperPrice::query()
            ->where('newspaper_id', $newspaperId)
            ->where('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            })
            ->orderByDesc('effective_from')
            ->first();
    }

    public function hasOverlappingPeriod(int $newspaperId, string $effectiveFrom, ?string $effectiveTo, int $excludeId = 0): bool
    {
        $query = NewspaperPrice::query()
            ->where('newspaper_id', $newspaperId)
            ->where('id', '!=', $excludeId);

        if ($effectiveTo === null) {
            $query->where(function ($q) use ($effectiveFrom) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $effectiveFrom);
            });
        } else {
            $query->where(function ($q) use ($effectiveFrom, $effectiveTo) {
                $q->whereNull('effective_to')
                    ->orWhere(function ($q2) use ($effectiveFrom, $effectiveTo) {
                        $q2->where('effective_from', '<=', $effectiveTo)
                            ->where('effective_to', '>=', $effectiveFrom);
                    });
            });
        }

        return $query->exists();
    }

    public function closeCurrentPrice(int $newspaperId, string $effectiveTo): bool
    {
        return NewspaperPrice::query()
            ->where('newspaper_id', $newspaperId)
            ->whereNull('effective_to')
            ->update(['effective_to' => $effectiveTo]);
    }

    public function getCurrentPrice(int $newspaperId): ?NewspaperPrice
    {
        return NewspaperPrice::query()
            ->where('newspaper_id', $newspaperId)
            ->whereNull('effective_to')
            ->first();
    }
}
