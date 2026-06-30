<?php

namespace App\Actions\Admin\Photo;

use App\Models\Photo;

class UpdatePhotoAction
{
    public function handle(Photo $photo, array $data): Photo
    {
        $photo->update($data);
        return $photo->fresh();
    }
}
