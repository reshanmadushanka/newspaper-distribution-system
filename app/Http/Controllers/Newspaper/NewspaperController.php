<?php

namespace App\Http\Controllers\Newspaper;

use App\Http\Controllers\Controller;
use App\Domain\Newspapers\Enums\Language;
use App\Domain\Newspapers\Enums\NewspaperStatus;
use App\Domain\Newspapers\Enums\PublicationFrequency;
use App\Domain\Newspapers\Models\Newspaper;
use App\Domain\Newspapers\Services\NewspaperService;
use App\Domain\Newspapers\Data\NewspaperData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NewspaperController extends Controller
{
    public function __construct(
        private NewspaperService $newspaperService
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Newspapers/Index', [
            'newspapers' => $this->newspaperService->getPaginatedNewspapers(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Newspapers/Form', [
            'newspaper' => null,
            'languageOptions' => Language::options(),
            'statusOptions' => NewspaperStatus::options(),
            'frequencyOptions' => PublicationFrequency::options(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(NewspaperData::rules());

        $this->newspaperService->createNewspaper($validated);

        return redirect()->route('admin.newspapers.index')
            ->with('success', 'Newspaper created successfully.');
    }

    public function edit(Newspaper $newspaper): Response
    {
        return Inertia::render('Admin/Newspapers/Form', [
            'newspaper' => $this->newspaperService->getNewspaperForEdit($newspaper->id),
            'languageOptions' => Language::options(),
            'statusOptions' => NewspaperStatus::options(),
            'frequencyOptions' => PublicationFrequency::options(),
        ]);
    }

    public function update(Request $request, Newspaper $newspaper): RedirectResponse
    {
        $validated = $request->validate(NewspaperData::rules($newspaper->id));

        $this->newspaperService->updateNewspaper($newspaper, $validated);

        return redirect()->route('admin.newspapers.index')
            ->with('success', 'Newspaper updated successfully.');
    }

    public function destroy(Newspaper $newspaper): RedirectResponse
    {
        $this->newspaperService->deleteNewspaper($newspaper);

        return redirect()->route('admin.newspapers.index')
            ->with('success', 'Newspaper deleted successfully.');
    }
}
