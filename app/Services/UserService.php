<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function getPaginatedUsers(int $perPage = 10)
    {
        return $this->userRepository->getPaginatedUsers($perPage);
    }

    public function getAllRoles()
    {
        return $this->userRepository->getAllRoles();
    }

    public function getUserForEdit(User $user): array
    {
        $user->loadMissing('roles');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name'),
        ];
    }

    public function createUser(array $data, array $roles): User
    {
        return DB::transaction(function () use ($data, $roles) {
            $user = $this->userRepository->create($data);
            $user->syncRoles($roles);

            return $user;
        });
    }

    public function updateUser(User $user, array $data, array $roles): bool
    {
        return DB::transaction(function () use ($user, $data, $roles) {
            if (blank($data['password'] ?? null)) {
                unset($data['password']);
            }

            $this->userRepository->update($user, $data);
            $user->syncRoles($roles);

            return true;
        });
    }

    public function deleteUser(User $user): bool
    {
        return $this->userRepository->delete($user);
    }
}
