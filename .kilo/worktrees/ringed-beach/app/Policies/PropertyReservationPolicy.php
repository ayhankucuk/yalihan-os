<?php

namespace App\Policies;

use App\Models\Ilan;
use App\Models\PropertyReservation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PropertyReservationPolicy
{
    use HandlesAuthorization;

    /**
     * Admin veya sistem yöneticileri tüm yetkilere sahiptir.
     */
    public function before(User $user, $ability)
    {
        if ($user->role_id === 1) {
            return true;
        }
    }

    public function viewAny(User $user)
    {
        return true; // Ana listeleme controller bazlı filtrelenecek
    }

    public function view(User $user, PropertyReservation $reservation)
    {
        return $user->id === $reservation->created_by_user_id ||
               $user->id === $reservation->ilan->user_id;
    }

    public function create(User $user, Ilan $ilan)
    {
        return $user->id === $ilan->user_id; // Sadece mülk sahibi veya admin oluşturabilir
    }

    public function update(User $user, PropertyReservation $reservation)
    {
        return $user->id === $reservation->created_by_user_id ||
               $user->id === $reservation->ilan->user_id;
    }

    public function delete(User $user, PropertyReservation $reservation)
    {
        return $user->id === $reservation->created_by_user_id ||
               $user->id === $reservation->ilan->user_id;
    }
}
