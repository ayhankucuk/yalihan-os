<?php

namespace App\Services\Property\Document;

use App\Domain\Property\Models\Property;
use App\Domain\PropertyDocument\Models\PropertyDocument;
use App\Models\User;
use App\Services\SaaS\TenantContextService;

/**
 * PropertyDocumentService
 *
 * Sprint 12D — Document classification and expiry tracking.
 */
class PropertyDocumentService
{
    private TenantContextService $tenantContext;

    public function __construct()
    {
        $this->tenantContext = app(TenantContextService::class);
    }

    /**
     * Attach a document to a Property.
     */
    public function attachDocument(
        Property $property,
        string $documentType,
        ?string $filePath = null,
        ?string $referenceNumber = null,
        ?string $issueDate = null,
        ?string $expiryDate = null,
        ?string $note = null,
        ?int $actorId = null,
    ): PropertyDocument {
        $this->enforceTenantIsolation($property);

        $key = PropertyDocument::generateIdempotencyKey(
            $property->id, $documentType, $referenceNumber
        );

        $existing = PropertyDocument::where('idempotency_key', $key)->first();
        if ($existing) {
            return $existing;
        }

        return PropertyDocument::create([
            'tenant_id' => $this->tenantContext->getTenant()->id,
            'property_id' => $property->id,
            'dokuman_tipi' => $documentType,
            'dosya_yolu' => $filePath,
            'referans_no' => $referenceNumber,
            'yayin_tarihi' => $issueDate,
            'son_gecerlilik_tarihi' => $expiryDate,
            'durum' => PropertyDocument::STATUS_AKTIF,
            'notu' => $note,
            'olusturan_id' => $actorId ?? auth()->id(),
            'idempotency_key' => $key,
        ]);
    }

    /**
     * Mark a document as expired (called by scheduler or manually).
     */
    public function markExpired(PropertyDocument $document): PropertyDocument
    {
        $this->enforceTenantIsolation($document);
        $document->markExpired();
        return $document;
    }

    /**
     * Invalidate a document (mark as cancelled).
     */
    public function invalidate(PropertyDocument $document, ?int $actorId = null): PropertyDocument
    {
        $this->enforceTenantIsolation($document);
        $document->invalidate();
        return $document;
    }

    /**
     * Get all documents for a Property.
     */
    public function getDocumentsForProperty(Property $property): \Illuminate\Database\Eloquent\Collection
    {
        return PropertyDocument::where('property_id', $property->id)
            ->orderBy('dokuman_tipi')
            ->get();
    }

    /**
     * Get documents expiring within N days.
     */
    public function getExpiringSoon(Property $property, int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return PropertyDocument::where('property_id', $property->id)
            ->expiringSoon($days)
            ->get();
    }

    /**
     * Get expired documents for a Property.
     */
    public function getExpiredDocuments(Property $property): \Illuminate\Database\Eloquent\Collection
    {
        return PropertyDocument::where('property_id', $property->id)
            ->get()
            ->filter(fn ($doc) => $doc->isExpired());
    }

    private function enforceTenantIsolation(Property $property): void
    {
        if ($property->tenant_id !== $this->tenantContext->getTenant()->id) {
            throw new \RuntimeException('Property does not belong to current tenant.');
        }
    }

    private function enforceTenantIsolation(PropertyDocument $document): void
    {
        if ($document->tenant_id !== $this->tenantContext->getTenant()->id) {
            throw new \RuntimeException('Document does not belong to current tenant.');
        }
    }
}
