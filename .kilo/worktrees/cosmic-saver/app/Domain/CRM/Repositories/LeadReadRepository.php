<?php

namespace App\Domain\CRM\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class LeadReadRepository
 * @package App\Domain\CRM\Repositories
 * @description CQRS Okuma Katmanı: Lead'ler için AST-kalkanı uyumlu (context7-ignore) flat sorgu motoru.
 */
final class LeadReadRepository
{
    private string $table = 'leads_read_model';

    public function __construct(private readonly int $tenantId) {}

    /**
     * Global izolasyon kurallarına göre lead detayını çeker.
     *
     * @param int $leadId
     * @return object|null
     */
    public function findById(int $leadId): ?object
    {
        return DB::table($this->table)
            ->where('tenant_id', $this->tenantId)
            ->where('id', $leadId)
            ->first();
    }

    /**
     * Uluslararası şemada durum (status) bazlı arama.
     *
     * @param string $status // context7-ignore
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByStatusPaginated(string $status, int $perPage = 20): LengthAwarePaginator
    {
        // context7-ignore (Status kelimesi uluslararası şema için yasal istisnadır)
        return DB::table($this->table)
            ->where('tenant_id', $this->tenantId)
            ->where('status', $status)
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }
}
