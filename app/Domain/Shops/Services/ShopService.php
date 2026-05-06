<?php

namespace App\Domain\Shops\Services;

use App\Domain\Shops\Models\Shop;
use App\Domain\Shops\Repositories\ShopRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ShopService
{
    public function __construct(
        private ShopRepositoryInterface $shopRepository
    ) {}

    public function getPaginatedShops(int $perPage = 10): LengthAwarePaginator
    {
        return $this->shopRepository->paginate($perPage);
    }

    public function createShop(array $data): Shop
    {
        return $this->shopRepository->create($data);
    }

    public function updateShop(Shop $shop, array $data): Shop
    {
        $this->shopRepository->update($shop, $data);
        return $shop->fresh();
    }

    public function deleteShop(Shop $shop): bool
    {
        return $this->shopRepository->delete($shop);
    }

    public function getShopForEdit(int $id): Shop
    {
        return $this->shopRepository->findOrFail($id);
    }
}