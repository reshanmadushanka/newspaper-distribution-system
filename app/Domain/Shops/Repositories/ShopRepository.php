<?php

namespace App\Domain\Shops\Repositories;

use App\Domain\Shops\Models\Shop;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ShopRepository implements ShopRepositoryInterface
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return Shop::query()
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function find(int $id): ?Shop
    {
        return Shop::find($id);
    }

    public function findOrFail(int $id): Shop
    {
        return Shop::findOrFail($id);
    }

    public function create(array $data): Shop
    {
        return DB::transaction(function () use ($data) {
            return Shop::create($data);
        });
    }

    public function update(Shop $shop, array $data): bool
    {
        return DB::transaction(function () use ($shop, $data) {
            return $shop->update($data);
        });
    }

    public function delete(Shop $shop): bool
    {
        return $shop->delete();
    }

    public function getActiveShops(): Collection
    {
        return Shop::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}