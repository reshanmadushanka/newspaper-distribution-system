<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class UserRepository
{
    public function getPaginatedUsers(?User $authUser = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = User::with('roles:id,name')->latest();

        if ($authUser && !$authUser->hasRole('super-admin')) {
            $query->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super-admin'));
        }

        return $query->paginate($perPage)
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'created_at' => $user->created_at?->format('Y-m-d'),
            ]);
    }

    public function getAllRoles(): Collection
    {
        return Role::query()->orderBy('name')->get(['id', 'name']);
    }

    public function findWithRoles(int $userId): User
    {
        return User::query()->with('roles')->findOrFail($userId);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }
}
