<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * Update the user's locale preference.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'locale' => ['required', Rule::in(['en', 'si'])],
        ]);

        // Save to user profile if authenticated
        if ($request->user()) {
            $request->user()->update(['locale' => $validated['locale']]);
        }

        // Save to session for immediate effect
        session(['locale' => $validated['locale']]);

        return back();
    }
}
