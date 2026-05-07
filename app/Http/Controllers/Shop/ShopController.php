<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Domain\Shops\Enums\ShopStatus;
use App\Domain\Shops\Enums\InvoiceDeliveryMethod;
use App\Domain\Shops\Models\Shop;
use App\Domain\Shops\Services\ShopService;
use App\Domain\Shops\Data\ShopData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function __construct(
        private ShopService $shopService
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Shops/Index', [
            'shops' => $this->shopService->getPaginatedShops(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Shops/Form', [
            'shop' => null,
            'statusOptions' => ShopStatus::options(),
            'deliveryOptions' => InvoiceDeliveryMethod::options(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(ShopData::rules());

        $this->shopService->createShop($validated);

        return redirect()->route('admin.shops.index')
            ->with('success', 'Shop created successfully.');
    }

    public function edit(Shop $shop): Response
    {
        return Inertia::render('Admin/Shops/Form', [
            'shop' => $this->shopService->getShopForEdit($shop->id),
            'statusOptions' => ShopStatus::options(),
            'deliveryOptions' => InvoiceDeliveryMethod::options(),
        ]);
    }

    public function update(Request $request, Shop $shop): RedirectResponse
    {
        $validated = $request->validate(ShopData::rules($shop->id));

        $this->shopService->updateShop($shop, $validated);

        return redirect()->route('admin.shops.index')
            ->with('success', 'Shop updated successfully.');
    }

    public function destroy(Shop $shop): RedirectResponse
    {
        $this->shopService->deleteShop($shop);

        return redirect()->route('admin.shops.index')
            ->with('success', 'Shop deleted successfully.');
    }
}
