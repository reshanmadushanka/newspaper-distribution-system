<?php

namespace App\Domain\Shops\Repositories;

use App\Domain\Shops\Models\Shop;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ShopRepositoryInterface
{
    public function paginate(int $perPage = 10): LengthAwarePaginator;

    public function find(int $id): ?Shop;

    public function findOrFail(int $id): Shop;

    public function create(array $data): Shop;

    public function update(Shop $shop, array $data): bool;

    public function delete(Shop $shop): bool;
}