<?php

namespace App\Http\Controllers\Newspaper;

use App\Http\Controllers\Controller;
use App\Domain\Pricing\Data\NewspaperPriceData;
use App\Domain\Pricing\Models\NewspaperPrice;
use App\Domain\Pricing\Services\PriceHistoryService;
use App\Domain\Newspapers\Models\Newspaper;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NewspaperPriceController extends Controller
{
    public function __construct(
        private PriceHistoryService $priceHistoryService
    ) {}

    public function index(Newspaper $newspaper): Response
    {
        return Inertia::render('Admin/Newspapers/Prices', [
            'newspaper' => $newspaper,
            'prices' => $this->priceHistoryService->getPricesForNewspaper($newspaper->id),
        ]);
    }

    public function store(Request $request, Newspaper $newspaper)
    {
        $validated = $request->validate(NewspaperPriceData::rules());

        try {
            $this->priceHistoryService->addPrice(
                newspaperId: $newspaper->id,
                effectiveFrom: $validated['effective_from'],
                price: $validated['price'],
                effectiveTo: $validated['effective_to'] ?? null,
                costPrice: $validated['cost_price'] ?? null,
            );

            return redirect()->route('admin.newspapers.prices.index', $newspaper)
                ->with('success', 'Price added successfully.');
        } catch (Exception $e) {
            return back()->withErrors(['effective_from' => $e->getMessage()]);
        }
    }

    public function update(Request $request, NewspaperPrice $price): RedirectResponse
    {
        $validated = $request->validate(NewspaperPriceData::rules($price->id));

        try {
            $this->priceHistoryService->updatePrice($price, $validated);

            return redirect()->route('admin.newspapers.prices.index', $price->newspaper)
                ->with('success', 'Price updated successfully.');
        } catch (Exception $e) {
            return back()->withErrors(['effective_from' => $e->getMessage()]);
        }
    }

    public function destroy(NewspaperPrice $price): RedirectResponse
    {
        try {
            $this->priceHistoryService->deletePrice($price);

            return redirect()->route('admin.newspapers.prices.index', $price->newspaper)
                ->with('success', 'Price deleted successfully.');
        } catch (Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function getCurrentPrice(Newspaper $newspaper): JsonResponse
    {
        $currentPrice = $this->priceHistoryService->getCurrentPrice($newspaper->id);

        return response()->json([
            'price' => $currentPrice ? $currentPrice->price : null,
        ]);
    }

    public function getPriceForDate(Request $request, Newspaper $newspaper): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $price = $this->priceHistoryService->getPriceForDate($newspaper->id, $validated['date']);

        return response()->json([
            'price' => $price ? $price->price : null,
            'price_id' => $price ? $price->id : null,
        ]);
    }
}
