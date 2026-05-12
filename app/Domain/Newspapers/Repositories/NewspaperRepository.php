<?php

namespace App\Domain\Newspapers\Repositories;

use App\Domain\Newspapers\Enums\NewspaperStatus;
use App\Domain\Newspapers\Models\Newspaper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NewspaperRepository implements NewspaperRepositoryInterface
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return Newspaper::query()
            ->with('prices')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function find(int $id): ?Newspaper
    {
        return Newspaper::find($id);
    }

    public function findOrFail(int $id): Newspaper
    {
        return Newspaper::with('prices')->findOrFail($id);
    }

    public function create(array $data): Newspaper
    {
        return DB::transaction(function () use ($data) {
            $prices = $data['prices'] ?? [];
            unset($data['prices']);

            $newspaper = Newspaper::create($data);

            if (!empty($prices)) {
                foreach ($prices as $priceData) {
                    $newspaper->prices()->create([
                        'label' => $priceData['label'] ?? null,
                        'price' => $priceData['price'],
                        'cost_price' => $priceData['cost_price'] ?? null,
                    ]);
                }
            } elseif ($data['price'] ?? null) {
                $newspaper->prices()->create([
                    'price' => $data['price'],
                    'cost_price' => $data['cost_price'] ?? null,
                ]);
            }

            return $newspaper->fresh('prices');
        });
    }

    public function update(Newspaper $newspaper, array $data): bool
    {
        return DB::transaction(function () use ($newspaper, $data) {
            $prices = $data['prices'] ?? null;
            unset($data['prices']);

            $updated = $newspaper->update($data);

            if ($prices !== null) {
                $newspaper->prices()->delete();

                foreach ($prices as $priceData) {
                    $newspaper->prices()->create([
                        'label' => $priceData['label'] ?? null,
                        'price' => $priceData['price'],
                        'cost_price' => $priceData['cost_price'] ?? null,
                    ]);
                }
            }

            return $updated;
        });
    }

    public function delete(Newspaper $newspaper): bool
    {
        return $newspaper->delete();
    }

    public function getActiveNewspapers(array $columns = ['*']): Collection
    {
        return Newspaper::query()
            ->with('prices')
            ->where('status', NewspaperStatus::ACTIVE)
            ->orderBy('name')
            ->get($columns);
    }
}
