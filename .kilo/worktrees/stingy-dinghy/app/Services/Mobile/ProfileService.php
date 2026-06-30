<?php

namespace App\Services\Mobile;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Traits\GuardsAgentWrites;

class ProfileService
{
    use GuardsAgentWrites;
    public function updateProfile(User $user, array $data): User
    {
        $this->blockAgentWrite(__FUNCTION__);

        if (isset($data['phone'])) {
            $data['telefon'] = $data['phone'];
            unset($data['phone']);
        }

        $user->update($data);
        return $user;
    }

    public function updatePhoto(User $user, UploadedFile $photo): User
    {
        $this->blockAgentWrite(__FUNCTION__);

        $path = $photo->store('profile-photos', 'public');

        // Delete old photo if exists
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $user->update(['profile_photo_path' => $path]);
        return $user;
    }
}
