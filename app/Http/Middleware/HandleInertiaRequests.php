<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'locale' => app()->getLocale(),
            'available_locales' => [
                'en' => 'English',
                'si' => 'සිංහල',
            ],
            'translations' => fn () => $this->getTranslations(),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * Get translations to share with Inertia.
     */
    protected function getTranslations(): array
    {
        return [
            'navigation' => __('navigation'),
            'common' => __('common'),
            'newspapers' => __('newspapers'),
        ];
    }
}
