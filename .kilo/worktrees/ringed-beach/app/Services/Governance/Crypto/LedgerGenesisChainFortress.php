<?php

namespace App\Services\Governance\Crypto;

use App\Exceptions\Governance\CryptoChainDriftException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class LedgerGenesisChainFortress
 * @package App\Services\Governance\Crypto
 * @description SAB Core v2.6 uyarınca defter kayıtlarını kriptografik zincirle donduran sarsılmaz muhafız sınıfı.
 */
final class LedgerGenesisChainFortress
{
    /**
     * Defter kaydını blok zinciri paritesiyle mühürleyerek bir önceki bloğa bağlar.
     * SAB Madde 18 (Cryptographic ledger compliance) kurallarına %100 tabidir.
     *
     * @param int $tenantId
     * @param array<string, mixed> $ledgereYazilacakPayload
     * @return string Hesaplanan current_hash değeri
     * @throws CryptoChainDriftException
     */
    public function secureChainLink(int $tenantId, array $ledgereYazilacakPayload): string
    {
        $config = config('yalihan.fortress_secure_salt', []);
        if (!($config['aktiflik_durumu'] ?? false)) {
            return '';
        }

        return DB::transaction(function () use ($tenantId, $ledgereYazilacakPayload, $config) {
            // Son mühürlü kaydı pesimist satır kilidi (lockForUpdate) altında çek
            $sonBlok = DB::table('governance_decisions')
                ->where('tenant_id', $tenantId)
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            // Eğer zincirde hiç blok yoksa Genesis Seed ile başla, varsa bir önceki current_hash'i al
            $prevHash = $sonBlok ? $sonBlok->current_hash : hash($config['algoritma'] ?? 'sha256', $config['genesis_seed'] ?? '');

            if ($sonBlok && empty($sonBlok->current_hash)) {
                throw new CryptoChainDriftException("🚨 CRITICAL GOVERNANCE FAILURE: Broken cryptographic hash link saptandı! Tenant ID: {$tenantId}");
            }

            // Değiştirilemez Blok Payload (Immutable Block Payload) kurgusu
            // Replay determinizmi için payload içerisinde 'executed_at' varsa onu sabit zaman damgası olarak kabul et (Deterministic Time Freeze)
            $executedAt = $ledgereYazilacakPayload['executed_at'] ?? microtime(true);

            $blockString = json_encode([
                'prev_hash'   => $prevHash,
                'tenant_id'   => $tenantId,
                'payload'     => $ledgereYazilacakPayload,
                'executed_at' => $executedAt
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

            // HMAC-SHA256 korumalı nihai hash üretimi
            $currentHash = hash_hmac(
                $config['algoritma'] ?? 'sha256',
                $blockString,
                $config['kripto_anahtar'] ?? 'fallback_salt'
            );

            Log::debug("CRYPTO CHAIN LINK GENERATED: Data ledger block linked successfully.", [
                'tenant_id'    => $tenantId,
                'current_hash' => $currentHash
            ]);

            return $currentHash;
        });
    }
}
