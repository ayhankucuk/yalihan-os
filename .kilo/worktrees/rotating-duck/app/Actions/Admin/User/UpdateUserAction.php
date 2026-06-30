<?php

namespace App\Actions\Admin\User;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;

class UpdateUserAction
{
    /**
     * @return array{updated: bool, role_changed: bool}
     */
    public function handle(User $user, array $updateData, ?string $newRole = null): array
    {
        $updated = $user->update($updateData);

        $roleChanged = false;

        if ($newRole !== null) {
            $currentRole = $user->getRoleNames()->first();

            if ($currentRole !== $newRole) {
                $user->syncRoles([$newRole]);
                app(PermissionRegistrar::class)->forgetCachedPermissions();
                $user->refresh();
                $roleChanged = true;

                Log::info('Kullanıcı rolü güncellendi', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'old_role' => $currentRole ?? 'Yok',
                    'new_role' => $newRole,
                    'roles_after' => $user->getRoleNames()->toArray(),
                ]);
            }
        }

        return ['updated' => $updated, 'role_changed' => $roleChanged];
    }
}
