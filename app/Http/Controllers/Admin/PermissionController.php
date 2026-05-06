<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Permissions/Index', [
            'permissions' => Permission::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Permissions/Form', [
            'permission' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Permission::create($this->validated($request));

        return redirect()->route('admin.permissions.index')->with('success', 'Permission created.');
    }

    public function edit(Permission $permission): Response
    {
        return Inertia::render('Admin/Permissions/Form', [
            'permission' => $permission->only('id', 'name'),
        ]);
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $permission->update($this->validated($request, $permission));

        return redirect()->route('admin.permissions.index')->with('success', 'Permission updated.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        abort_if($permission->name === 'manage users', 403, 'The manage users permission cannot be deleted.');

        $permission->delete();

        return redirect()->route('admin.permissions.index')->with('success', 'Permission deleted.');
    }

    private function validated(Request $request, ?Permission $permission = null): array
    {
        return [
            ...$request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('permissions')->ignore($permission)],
            ]),
            'guard_name' => 'web',
        ];
    }
}
