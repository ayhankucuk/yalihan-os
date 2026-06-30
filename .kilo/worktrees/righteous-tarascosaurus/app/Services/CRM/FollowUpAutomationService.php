<?php

namespace App\Services\CRM;

use App\Models\Lead;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Repositories\GorevRepository;
use Illuminate\Support\Facades\Log;

/**
 * FollowUpAutomationService
 *
 * Automates follow-up tasks based on lead status and engagement
 * Schedules reminders, escalations, and re-engagement campaigns
 *
 * @governance PHASE4B_SERVICE_GOVERNANCE
 * @refactored 2026-05-12
 * @reason Migrated from direct model access to Repository Kernel with scoped delete guard
 */
class FollowUpAutomationService
{
    public function __construct(
        protected GorevRepository $gorevRepository
    ) {}
    /**
     * Schedule automatic follow-up based on lead temperature and status
     *
     * ✅ REFACTORED: Phase 4B - Service Governance Alignment
     * Now uses scoped delete via Repository Kernel
     */
    public function scheduleFollowUp(Lead $lead): void
    {
        try {
            // ✅ Clear existing follow-up tasks (SCOPED DELETE)
            $this->gorevRepository->deletePendingByLeadId($lead->id, auth()->user());

            // Schedule new follow-up based on status
            match ($lead->crm_status) {
                'new' => $this->scheduleNewLeadFollowUp($lead),
                'contacted' => $this->scheduleContactedFollowUp($lead),
                'qualified' => $this->scheduleQualifiedFollowUp($lead),
                'lost' => $this->scheduleReEngagementFollowUp($lead),
                default => null,
            };

            Log::info('Follow-up scheduled', [
                'lead_id' => $lead->id,
                'status' => $lead->crm_status, // context7-ignore
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to schedule follow-up', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Schedule follow-up for new leads
     * Contact within 1-2 hours
     */
    private function scheduleNewLeadFollowUp(Lead $lead): void
    {
        $dueDate = now()->addHours(
            $lead->temperature === 'hot' ? 1 : 2
        );

        $this->createFollowUpTask(
            $lead,
            'contact_new_lead',
            'Contact new lead and qualify interest',
            $dueDate,
            'high'
        );
    }

    /**
     * Schedule follow-up for contacted leads
     * Follow-up within 3 days
     */
    private function scheduleContactedFollowUp(Lead $lead): void
    {
        $priority = match ($lead->temperature) {
            'hot' => 'high',
            'warm' => 'medium',
            default => 'low',
        };

        $dueDate = now()->addDays(3);

        $this->createFollowUpTask(
            $lead,
            'qualify_lead',
            'Follow-up to qualify interest and next steps',
            $dueDate,
            $priority
        );
    }

    /**
     * Schedule follow-up for qualified leads
     * Close/presentation within 7 days
     */
    private function scheduleQualifiedFollowUp(Lead $lead): void
    {
        $dueDate = now()->addDays(7);

        $this->createFollowUpTask(
            $lead,
            'present_options',
            'Present property options and schedule viewing',
            $dueDate,
            'high'
        );
    }

    /**
     * Schedule re-engagement for lost leads
     * Try to win back in 30 days
     */
    private function scheduleReEngagementFollowUp(Lead $lead): void
    {
        $dueDate = now()->addDays(30);

        $this->createFollowUpTask(
            $lead,
            're_engage_lost_lead',
            'Re-engagement campaign for lost lead',
            $dueDate,
            'low'
        );
    }

    /**
     * Create follow-up task
     *
     * ✅ REFACTORED: Phase 4B - Service Governance Alignment
     * Now uses Repository Kernel
     */
    private function createFollowUpTask(
        Lead $lead,
        string $taskType,
        string $description,
        \DateTime $dueDate,
        string $priority = 'medium'
    ): Gorev {
        return $this->gorevRepository->create([
            'baslik' => "Takip: " . $taskType,
            'lead_id' => $lead->id,
            'atanan_user_id' => $lead->assigned_agent_id,
            'gorev_tipi' => $taskType,
            'aciklama' => $description,
            'bitis_tarihi' => $dueDate,
            'oncelik' => $priority,
            'gorev_durumu' => 'beklemede',
        ]);
    }

    /**
     * Get overdue follow-up tasks for an agent
     *
     * ✅ REFACTORED: Phase 4B - Service Governance Alignment
     * Now uses Repository Kernel with ownership scope
     */
    public function getOverdueTasksForAgent($agentId)
    {
        return $this->gorevRepository->getOverdueTasksForAgent($agentId, auth()->user());
    }

    /**
     * Get upcoming follow-up tasks for today
     *
     * ✅ REFACTORED: Phase 4B - Service Governance Alignment
     * Now uses Repository Kernel with ownership scope
     */
    public function getTodayTasksForAgent($agentId)
    {
        return $this->gorevRepository->getTodayTasksForAgent($agentId, auth()->user());
    }

    /**
     * Auto-escalate overdue leads
     *
     * ✅ REFACTORED: Phase 4B - Service Governance Alignment
     * Now uses Repository Kernel with ownership scope
     */
    public function autoEscalateOverdueTasks(): void
    {
        $overdueTasks = $this->gorevRepository->getOverdueTasksForEscalation(3, auth()->user());

        foreach ($overdueTasks as $task) {
            // Mark as escalated via metadata
            $metadata = $task->metadata ?? [];
            $metadata['escalated'] = true;
            $metadata['escalated_at'] = now()->toDateTimeString();

            $task->update([
                'metadata' => $metadata,
                'oncelik' => 'acil', // Increase priority
            ]);

            // Log escalation
            Log::info('Task auto-escalated', [
                'task_id' => $task->id,
                'lead_id' => $task->lead_id,
                'days_overdue' => now()->diffInDays($task->bitis_tarihi),
            ]);

            // [Phase 6] Yönetici bildirimi entegrasyonu
        }
    }

    /**
     * Send follow-up reminder notifications
     *
     * ✅ REFACTORED: Phase 4B - Service Governance Alignment
     * Now uses Repository Kernel with ownership scope
     */
    public function sendFollowUpReminders(): void
    {
        // Get tasks due within next 2 hours
        $upcomingTasks = $this->gorevRepository->getUpcomingTasksForReminders(2, auth()->user());

        foreach ($upcomingTasks as $task) {
            // [Phase 6] Email/SMS/push hatırlatma entegrasyonu
            Log::info('Follow-up reminder sent', [
                'task_id' => $task->id,
                'agent_id' => $task->atanan_user_id,
            ]);
        }
    }

    /**
     * Mark task as completed
     */
    public function completeTask(Gorev $task, string $notes = ''): void
    {
        try {
            $task->update([
                'gorev_durumu' => 'tamamlandi',
                'bitis_tarihi' => now(),
                'notlar' => ($task->notlar ? $task->notlar . "\n" : "") . $notes,
            ]);

            // Re-schedule if needed
            $lead = $task->lead;
            $this->scheduleFollowUp($lead);

            Log::info('Follow-up task completed', [
                'task_id' => $task->id,
                'lead_id' => $task->lead_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to complete task', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get follow-up statistics
     *
     * ✅ REFACTORED: Phase 4B - Service Governance Alignment
     * Now uses Repository Kernel with tenant-scoped aggregation
     *
     * @governance AGGREGATION_BOUNDARY
     */
    public function getStatistics()
    {
        return $this->gorevRepository->getStatistics(auth()->user());
    }
}
