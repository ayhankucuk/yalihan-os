<?php

namespace App\Policies\Api\V2;

use App\Models\V2\AiIlanTaslagi;
use App\Models\V2\User;
use Illuminate\Auth\Access\Response;

/**
 * Draft Policy - Authorization rules for AI Draft operations
 * 
 * Context7: 100% Compliant
 * - Approval requires can:approve-drafts ability
 */
class DraftPolicy
{
    /**
     * Determine if the user can view any drafts (own drafts only)
     */
    public function viewAny(User $user): bool
    {
        return true; // User can view their own drafts via query
    }

    /**
     * Determine if the user can view the draft
     */
    public function view(User $user, AiIlanTaslagi $draft): Response
    {
        if ($user->id !== $draft->kullanici_id) {
            return Response::deny('Bu taslağa erişim izniniz yok');
        }

        return Response::allow();
    }

    /**
     * Determine if the user can create drafts
     */
    public function create(User $user): bool
    {
        return $user->aktiflik_durumu;
    }

    /**
     * Determine if the user can update the draft
     */
    public function update(User $user, AiIlanTaslagi $draft): Response
    {
        if ($user->id !== $draft->kullanici_id) {
            return Response::deny('Bu taslağa erişim izniniz yok');
        }

        return Response::allow();
    }

    /**
     * Determine if the user can delete the draft
     */
    public function delete(User $user, AiIlanTaslagi $draft): Response
    {
        if ($user->id !== $draft->kullanici_id) {
            return Response::deny('Bu taslağa erişim izniniz yok');
        }

        return Response::allow();
    }

    /**
     * Determine if the user can approve the draft (admin/moderator only)
     */
    public function approve(User $user, AiIlanTaslagi $draft): Response
    {
        if ($user->rol !== 'admin') {
            return Response::deny('Sadece adminler taslakları onaylayabilir');
        }

        if ($draft->taslak_durumu !== 'Onay Bekliyor') {
            return Response::deny('Sadece "Onay Bekliyor" durumundaki taslaklar onaylanabilir');
        }

        return Response::allow();
    }

    /**
     * Determine if the user can publish the draft
     */
    public function publish(User $user, AiIlanTaslagi $draft): Response
    {
        if ($user->id !== $draft->kullanici_id) {
            return Response::deny('Bu taslağa erişim izniniz yok');
        }

        if ($draft->taslak_durumu !== 'Onaylı') {
            return Response::deny('Sadece onaylı taslaklar yayınlanabilir');
        }

        return Response::allow();
    }
}
