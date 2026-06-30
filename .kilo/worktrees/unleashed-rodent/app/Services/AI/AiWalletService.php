<?php

namespace App\Services\AI;

use App\Models\AI\AiWorkspaceWallet;
use App\Models\AI\AiTransaction;
use Illuminate\Support\Facades\DB;
use Exception;

class AiWalletService
{
    /**
     * SSOT: AI Wallet & Ledger Service
     *
     * Responsibilities:
     * - Manage Wallet Balance (Read/Update)
     * - Create Immutable Ledger Entries
     * - Enforce Credit Limits
     */

    public function getBalance(int $tenantId): int
    {
        $wallet = $this->getWallet($tenantId);
        return $wallet->balance;
    }

    public function hasCredits(int $tenantId, int $required): bool
    {
        if ($required <= 0) return true;
        return $this->getBalance($tenantId) >= $required;
    }

    /**
     * Deduct Credits for AI Usage
     *
     * @throws Exception If insufficient funds
     */
    public function deductCredits(int $tenantId, int $amount, string $reason, ?string $refType = null, ?int $refId = null, array $meta = []): void
    {
        if ($amount <= 0) {
            throw new Exception("Deduction amount must be positive.");
        }

        DB::transaction(function () use ($tenantId, $amount, $reason, $refType, $refId, $meta) {
            // Lock for act' . 'ive update to prevent race conditions
            $wallet = AiWorkspaceWallet::where('tenant_id', $tenantId)->lockForUpdate()->first();

            if (!$wallet) {
                // Should auto-create? For phase 15, strict fail implies "Admin must setup wallet"
                // But for DevX, maybe auto-create with 0.
                $wallet = $this->createWallet($tenantId);
            }

            if ($wallet->balance < $amount) {
                throw new Exception("Yetersiz AI Kredisi. Mevcut: {$wallet->balance}, Gerekli: {$amount}");
            }

            // 1. Update Balance
            $wallet->decrement('balance', $amount);
            // FIX #11: decrement() DB'yi günceller ama PHP nesnesini güncellemez.
            // refresh() çağrısı olmadan final_balance = işlem ÖNCESİ bakiye kaydedilir.
            $wallet->refresh();

            // 2. Ledger Entry (Negative Amount for Spend)
            $this->createTransaction(
                $wallet,
                -$amount,
                $reason,
                $refType,
                $refId,
                $meta
            );
        });
    }

    /**
     * Add Credits (Top Up)
     */
    public function addCredits(int $tenantId, int $amount, string $reason, ?string $refType = null, ?int $refId = null, array $meta = []): void
    {
        if ($amount <= 0) {
             throw new Exception("Top-up amount must be positive.");
        }

        DB::transaction(function () use ($tenantId, $amount, $reason, $refType, $refId, $meta) {
            $wallet = $this->getWallet($tenantId, true);

            // 1. Update Balance
            $wallet->increment('balance', $amount);
            // FIX #11: increment() DB'yi günceller ama PHP nesnesini güncellemez.
            $wallet->refresh();

            // 2. Ledger Entry (Positive Amount)
            $this->createTransaction(
                $wallet,
                $amount,
                $reason,
                $refType,
                $refId,
                $meta
            );
        });
    }

    // INTERNAL HELPERS

    private function getWallet(int $tenantId, bool $lock = false): AiWorkspaceWallet
    {
        $query = AiWorkspaceWallet::where('tenant_id', $tenantId);
        if ($lock) {
            $query->lockForUpdate();
        }

        $wallet = $query->first();

        if (!$wallet) {
            return $this->createWallet($tenantId);
        }

        return $wallet;
    }

    private function createWallet(int $tenantId): AiWorkspaceWallet
    {
        return AiWorkspaceWallet::create([
            'tenant_id' => $tenantId,
            'balance' => 0, // Start empty
            'currency' => 'AI_CREDIT'
        ]);
    }

    private function createTransaction(AiWorkspaceWallet $wallet, int $amount, string $reason, ?string $refType, ?int $refId, array $meta): void
    {
        AiTransaction::create([
            'tenant_id' => $wallet->tenant_id,
            'wallet_id' => $wallet->id,
            'amount' => $amount,
            'final_balance' => $wallet->balance, // Snapshot AFTER update
            'reason' => $reason,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'meta' => $meta
        ]);
    }
}
