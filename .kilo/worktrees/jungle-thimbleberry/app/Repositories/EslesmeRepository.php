<?php

namespace App\Repositories;

use App\Models\Eslesme;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * 🛡️ SAB SEALED: EslesmeRepository
 *
 * Repository Authority Pattern — tüm Eslesme yazma işlemleri bu sınıf üzerinden geçer.
 * Direct ORM bypass yasaktır (SAB Kural 2).
 *
 * Oluşturulma nedeni: MatchingAuthorityService (CRM Authority Hardening T2-B)
 * tarafından ihtiyaç duyuldu. Class eksikliği route kayıt hatasına yol açıyordu.
 */
class EslesmeRepository
{
    protected Eslesme $model;

    public function __construct(Eslesme $model)
    {
        $this->model = $model;
    }

    /**
     * Ownership scope — Tenant Isolation (SAB Kural 1)
     *
     * Admin: tüm eşleşmeler
     * Danışman: yalnızca kendi atamaları
     */
    protected function applyOwnershipScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0'); // fail-safe: kimlik doğrulanmamış
        }

        $isAdmin = (method_exists($user, 'isAdmin') && $user->isAdmin()) ||
                   (method_exists($user, 'hasRole') && $user->hasRole(['admin', 'super-admin']));

        if ($isAdmin) {
            return $query;
        }

        return $query->where('danisman_id', $user->id);
    }

    /**
     * Yeni eşleşme oluştur (tek yazma entrypoint'i)
     */
    public function create(array $data): Eslesme
    {
        return $this->model->create($data);
    }

    /**
     * ID ile bul — yalnızca sahip olunan kayıtlar
     */
    public function findById(int $id, ?User $user = null): ?Eslesme
    {
        return $this->applyOwnershipScope(
            $this->model->newQuery(), $user
        )->find($id);
    }

    /**
     * Belirli bir ilan'a ait tüm eşleşmeler
     */
    public function findByIlanId(int $ilanId, ?User $user = null): Collection
    {
        return $this->applyOwnershipScope(
            $this->model->newQuery(), $user
        )->where('ilan_id', $ilanId)->get();
    }

    /**
     * Belirli bir talep'e ait eşleşmeler
     */
    public function findByTalepId(int $talepId, ?User $user = null): Collection
    {
        return $this->applyOwnershipScope(
            $this->model->newQuery(), $user
        )->where('talep_id', $talepId)->get();
    }

    /**
     * Eşleşme güncelle (scoped — sahiplik doğrulamalı)
     */
    public function update(int $id, array $data, ?User $user = null): ?Eslesme
    {
        $eslesme = $this->findById($id, $user);

        if (!$eslesme) {
            return null;
        }

        $eslesme->update($data);

        return $eslesme->fresh();
    }

    /**
     * Eşleşme sil (scoped — sahiplik doğrulamalı)
     */
    public function delete(int $id, ?User $user = null): bool
    {
        $eslesme = $this->findById($id, $user);

        if (!$eslesme) {
            return false;
        }

        return (bool) $eslesme->delete();
    }
}
