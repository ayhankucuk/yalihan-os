<?php

namespace App\Services\Mobile;

use App\Models\SavedSearch;
use App\Models\User;

class SavedSearchService
{
    public function create(User $user, array $data): SavedSearch
    {
        return $user->savedSearches()->create($data);
    }

    public function delete(User $user, int $id): bool
    {
        return $user->savedSearches()->where('id', $id)->delete() > 0;
    }

    public function list(User $user)
    {
        return $user->savedSearches()->latest()->get();
    }
}
