<?php

namespace App\Domain\CQRS\Aggregates;

use App\Domain\CQRS\AggregateRoot;

/**
 * Class LeadAggregate
 *
 * SAB Phase 15 Sprint 1: Lead Domain Event Sourcing
 * Manages lead lifecycle through immutable event stream.
 *
 * Domain Events:
 * - LeadCreated: Initial lead registration
 * - LeadStatusChanged: Status transition (yeni -> aranacak -> gorusuldu, etc.)
 * - LeadAssigned: Lead assigned to advisor
 * - LeadNoteAdded: Note/comment added
 * - LeadContactAttempted: Contact attempt recorded
 * - LeadConverted: Lead converted to customer
 *
 * @package App\Domain\CQRS\Aggregates
 */
class LeadAggregate extends AggregateRoot
{
    /**
     * Current lead state (reconstructed from events)
     *
     * @var array
     */
    protected array $state = [
        'durum' => null,
        'assigned_to' => null,
        'contact_attempts' => 0,
        'last_contact_at' => null,
        'converted_at' => null,
    ];

    /**
     * Create a new lead
     *
     * @param array $leadData
     * @return void
     */
    public function createLead(array $leadData): void
    {
        $this->recordEvent('LeadCreated', [
            'ad_soyad' => $leadData['ad_soyad'],
            'telefon' => $leadData['telefon'],
            'eposta' => $leadData['eposta'] ?? null,
            'kaynak' => $leadData['kaynak'] ?? 'web',
            'durum' => 'yeni',
            'created_at' => now()->toIso8601String(),
        ]);

        $this->state['durum'] = 'yeni';
    }

    /**
     * Change lead status
     *
     * @param string $newStatus
     * @param string|null $reason
     * @return void
     */
    public function changeStatus(string $newStatus, ?string $reason = null): void
    {
        $this->recordEvent('LeadStatusChanged', [
            'old_status' => $this->state['durum'],
            'new_status' => $newStatus,
            'reason' => $reason,
            'changed_at' => now()->toIso8601String(),
        ]);

        $this->state['durum'] = $newStatus;
    }

    /**
     * Assign lead to advisor
     *
     * @param int $advisorId
     * @return void
     */
    public function assignToAdvisor(int $advisorId): void
    {
        $this->recordEvent('LeadAssigned', [
            'previous_advisor_id' => $this->state['assigned_to'],
            'new_advisor_id' => $advisorId,
            'assigned_at' => now()->toIso8601String(),
        ]);

        $this->state['assigned_to'] = $advisorId;
    }

    /**
     * Record contact attempt
     *
     * @param string $method (telefon, eposta, whatsapp)
     * @param bool $successful
     * @param string|null $notes
     * @return void
     */
    public function recordContactAttempt(string $method, bool $successful, ?string $notes = null): void
    {
        $this->recordEvent('LeadContactAttempted', [
            'method' => $method,
            'successful' => $successful,
            'notes' => $notes,
            'attempted_at' => now()->toIso8601String(),
        ]);

        $this->state['contact_attempts']++;
        $this->state['last_contact_at'] = now()->toIso8601String();
    }

    /**
     * Convert lead to customer
     *
     * @param int $customerId
     * @return void
     */
    public function convertToCustomer(int $customerId): void
    {
        $this->recordEvent('LeadConverted', [
            'customer_id' => $customerId,
            'converted_at' => now()->toIso8601String(),
        ]);

        $this->state['converted_at'] = now()->toIso8601String();
        $this->state['durum'] = 'donusturuldu';
    }

    /**
     * Apply event to reconstruct state
     *
     * @param string $eventType
     * @param array $payload
     * @return void
     */
    protected function applyEvent(string $eventType, array $payload): void
    {
        match ($eventType) {
            'LeadCreated' => $this->applyLeadCreated($payload),
            'LeadStatusChanged' => $this->applyLeadStatusChanged($payload),
            'LeadAssigned' => $this->applyLeadAssigned($payload),
            'LeadContactAttempted' => $this->applyLeadContactAttempted($payload),
            'LeadConverted' => $this->applyLeadConverted($payload),
            default => null,
        };
    }

    /**
     * Apply LeadCreated event
     *
     * @param array $payload
     * @return void
     */
    protected function applyLeadCreated(array $payload): void
    {
        $this->state['durum'] = $payload['durum'];
    }

    /**
     * Apply LeadStatusChanged event
     *
     * @param array $payload
     * @return void
     */
    protected function applyLeadStatusChanged(array $payload): void
    {
        $this->state['durum'] = $payload['new_status'];
    }

    /**
     * Apply LeadAssigned event
     *
     * @param array $payload
     * @return void
     */
    protected function applyLeadAssigned(array $payload): void
    {
        $this->state['assigned_to'] = $payload['new_advisor_id'];
    }

    /**
     * Apply LeadContactAttempted event
     *
     * @param array $payload
     * @return void
     */
    protected function applyLeadContactAttempted(array $payload): void
    {
        $this->state['contact_attempts']++;
        $this->state['last_contact_at'] = $payload['attempted_at'];
    }

    /**
     * Apply LeadConverted event
     *
     * @param array $payload
     * @return void
     */
    protected function applyLeadConverted(array $payload): void
    {
        $this->state['converted_at'] = $payload['converted_at'];
        $this->state['durum'] = 'donusturuldu';
    }

    /**
     * Get current aggregate state
     *
     * @return array
     */
    public function getState(): array
    {
        return $this->state;
    }
}
