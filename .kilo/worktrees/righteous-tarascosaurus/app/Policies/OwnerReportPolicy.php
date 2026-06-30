<?php

namespace App\Policies;

use App\Models\User;
use App\Models\OwnerReportRow;
use Illuminate\Auth\Access\HandlesAuthorization;

class OwnerReportPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('owner');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, $report): bool
    {
        // $report can be OwnerReportRow or OwnerReportMetric
        return $user->hasRole('owner') && $report->owner_id === $user->id;
    }

    /**
     * Determine whether the user can export reports.
     */
    public function export(User $user): bool
    {
        return $user->hasRole('owner');
    }

    /**
     * Determine whether the user can download the export.
     */
    public function download(User $user, \App\Models\OwnerReportExport $export): bool
    {
        return $user->hasRole('owner') && $export->owner_id === $user->id;
    }
}
