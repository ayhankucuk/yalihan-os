<?php

namespace App\Actions\Admin\Photo;

use App\Models\Photo;
use Illuminate\Support\Facades\Storage;

class DeletePhotoAction
{
    public function handle(Photo $photo): bool
    {
        if ($photo->dosya_yolu) {
            Storage::disk('public')->delete($photo->dosya_yolu);
        }

        if ($photo->thumbnail) {
            Storage::disk('public')->delete($photo->thumbnail);
        }

        return $photo->delete();
    }
}
