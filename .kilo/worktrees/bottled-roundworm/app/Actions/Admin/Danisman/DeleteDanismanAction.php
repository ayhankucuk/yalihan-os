<?php

namespace App\Actions\Admin\Danisman;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class DeleteDanismanAction
{
    /**
     * Handle the deletion of a Danisman (User).
     *
     * @param User $danisman
     * @return bool|null
     */
    public function handle(User $danisman): ?bool
    {
        $danismanId = $danisman->id;
        $name = $danisman->name;

        // Context7: Check if has role before deleting is handled in controller usually,
        // but we can add safety here.

        $result = $danisman->delete();

        if ($result) {
            Log::channel('module_changes')->info('Danışman silindi (Action)', [
                'user_id' => $danismanId,
                'name' => $name,
            ]);
        }

        return $result;
    }
}
