<?php

namespace Tests\Feature\Domain\Governance;

use Tests\TestCase;
use App\Services\Governance\Crypto\LedgerGenesisChainFortress;
use App\Exceptions\Governance\CryptoChainDriftException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Database\Seeders\TenantBaselineSeeder;

/**
 * Class LedgerGenesisChainFortressTest
 * @package Tests\Feature\Domain\Governance
 * @description Phase 17: Multi-tenant cryptographic ledger and Genesis Chain Fortress integrity tests.
 */
class LedgerGenesisChainFortressTest extends TestCase
{
    use RefreshDatabase;

    private LedgerGenesisChainFortress $fortress;
    private int $tenantId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed tenant baseline
        $this->seed(TenantBaselineSeeder::class);

        $this->fortress = new LedgerGenesisChainFortress();

        // Configure stable fortress parameters
        config([
            'yalihan.fortress_secure_salt' => [
                'aktiflik_durumu' => true,
                'algoritma' => 'sha256',
                'kripto_anahtar' => 'TEST_SECRET_HMAC_SALT_2026',
                'genesis_seed' => 'TEST_GENESIS_BLOCK_SEED_2026',
            ]
        ]);
    }

    /**
     * @test
     */
    public function it_generates_genesis_hash_on_empty_history(): void
    {
        $payload = [
            'action' => 'mali_onay',
            'miktar' => 150000,
            'para_birimi' => 'TRY'
        ];

        // Call secureChainLink to generate the genesis block hash
        $currentHash = $this->fortress->secureChainLink($this->tenantId, $payload);

        $this->assertNotEmpty($currentHash);
        $this->assertEquals(64, strlen($currentHash)); // SHA-256 HMAC length is 64 characters
    }

    /**
     * @test
     */
    public function it_links_subsequent_blocks_cryptographically(): void
    {
        $payload1 = [
            'action' => 'genesis_action',
            'deger' => 'first'
        ];

        // 1. Generate first hash
        $hash1 = $this->fortress->secureChainLink($this->tenantId, $payload1);

        // Insert first block into the database
        DB::table('governance_decisions')->insert([
            'tenant_id' => $this->tenantId,
            'finding_id' => 'FIND-001',
            'source' => 'test',
            'domain' => 'finance',
            'severity' => 'critical',
            'title' => 'First Decision',
            'reason' => 'Genesis test',
            'target' => 'ledger',
            'recommended_action' => 'approve',
            'risk' => 'low',
            'decision' => 'approved',
            'prev_hash' => hash('sha256', 'TEST_GENESIS_BLOCK_SEED_2026'),
            'current_hash' => $hash1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Generate second hash (should use hash1 as prev_hash)
        $payload2 = [
            'action' => 'subsequent_action',
            'deger' => 'second'
        ];

        $hash2 = $this->fortress->secureChainLink($this->tenantId, $payload2);

        $this->assertNotEmpty($hash2);
        $this->assertNotEquals($hash1, $hash2);

        // Verify that the second block can be inserted and has hash1 stored as prev_hash
        DB::table('governance_decisions')->insert([
            'tenant_id' => $this->tenantId,
            'finding_id' => 'FIND-002',
            'source' => 'test',
            'domain' => 'finance',
            'severity' => 'critical',
            'title' => 'Second Decision',
            'reason' => 'Subsequent link test',
            'target' => 'ledger',
            'recommended_action' => 'approve',
            'risk' => 'low',
            'decision' => 'approved',
            'prev_hash' => $hash1, // Links to hash1
            'current_hash' => $hash2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('governance_decisions', [
            'finding_id' => 'FIND-002',
            'prev_hash' => $hash1,
            'current_hash' => $hash2
        ]);
    }

    /**
     * @test
     */
    public function it_throws_crypto_chain_drift_exception_when_hash_link_is_broken(): void
    {
        // Insert a broken block (missing current_hash)
        DB::table('governance_decisions')->insert([
            'tenant_id' => $this->tenantId,
            'finding_id' => 'FIND-BROKEN',
            'source' => 'test',
            'domain' => 'finance',
            'severity' => 'critical',
            'title' => 'Broken Decision',
            'reason' => 'Tampered record without hash',
            'target' => 'ledger',
            'recommended_action' => 'approve',
            'risk' => 'high',
            'decision' => 'approved',
            'prev_hash' => 'some_prev_hash',
            'current_hash' => '', // Broken current_hash!
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'action' => 'after_broken_action',
            'deger' => 'fails'
        ];

        // Calling secureChainLink must throw CryptoChainDriftException
        $this->expectException(CryptoChainDriftException::class);
        $this->expectExceptionMessage("🚨 CRITICAL GOVERNANCE FAILURE: Broken cryptographic hash link saptandı! Tenant ID: {$this->tenantId}");

        $this->fortress->secureChainLink($this->tenantId, $payload);
    }

    /**
     * @test
     */
    public function it_returns_empty_string_when_cryptographic_ledger_is_disabled(): void
    {
        // Disable the fortress secure chain
        config([
            'yalihan.fortress_secure_salt.aktiflik_durumu' => false
        ]);

        $payload = [
            'action' => 'disabled_action'
        ];

        $result = $this->fortress->secureChainLink($this->tenantId, $payload);

        $this->assertEquals('', $result);
    }

    /**
     * @test
     */
    public function it_respects_deterministic_time_freeze_on_replays(): void
    {
        $payloadWithTime = [
            'action' => 'replay_action',
            'amount' => 5000,
            'executed_at' => 1716942000.1234 // Fixed timestamp (Deterministic Time Freeze)
        ];

        // First generation
        $hash1 = $this->fortress->secureChainLink($this->tenantId, $payloadWithTime);

        // Second generation (simulating event replay / audit rebuild)
        $hash2 = $this->fortress->secureChainLink($this->tenantId, $payloadWithTime);

        $this->assertNotEmpty($hash1);
        $this->assertEquals($hash1, $hash2); // Must be exactly identical
    }
}
