<?php

namespace App\Domain\Core\Security;

use App\Exceptions\Governance\GlobalHardlockException;
use Illuminate\Support\Facades\Log;

/**
 * Class GlobalHardlockRegistry
 * @package App\Domain\Core\Security
 * @description Phase 20: Çekirdek bileşenlerin bütünlüğünü SHA-256 Checksum listesiyle çalışma zamanı başlangıcında denetleyen anayasal koruma motoru.
 */
class GlobalHardlockRegistry implements GlobalHardlockRegistryContract
{
    /**
     * Kritik çekirdek dosyaların tescilli SHA-256 imza matrisi.
     * SAB Madde 22 kurallarına tabidir.
     */
    private array $registeredFiles = [
        'app/Services/Governance/Crypto/LedgerGenesisChainFortress.php' => '6f6d10768994b11f562e1fdebcc992efbba993f999a154c3b5cd46d62a8efe08',
        'app/Domain/Core/Security/ZeroTrustAuditor.php' => '0fc37007cb0b8fbb19337de427c0ceb2023ed40c44b0e25fe91dd725762471ef',
        'app/Domain/Core/Security/ZeroTrustAuditorContract.php' => 'b0a7d6ca1c07ab48691a25efa953a48dba0459c5450dca7286c47ca1783298e3',
    ];

    private readonly GlobalHardlockManagerContract $hardlockManager;

    /**
     * GlobalHardlockRegistry constructor.
     */
    public function __construct(?GlobalHardlockManagerContract $hardlockManager = null)
    {
        $this->hardlockManager = $hardlockManager ?? app(GlobalHardlockManagerContract::class);
    }

    /**
     * Kayıtlı tüm kritik çekirdek dosyaların güncel SHA-256 checksum değerlerini doğrular.
     *
     * @return bool Bütünlük doğrulaması başarılı mı?
     * @throws GlobalHardlockException Dosya bütünlüğü bozulduğunda fırlatılır.
     */
    public function verifySystemIntegrity(): bool
    {
        foreach ($this->registeredFiles as $path => $expectedHash) {
            if (!$this->verifyFileSignature($path)) {
                $this->hardlockManager->initiateHardlock(0, "🚨 SAB INTEGRITY VIOLATION: Core file tampering detected on [{$path}]");
                throw new GlobalHardlockException("🚨 SAB SYSTEM HARDLOCK: Core file integrity breach on path: {$path}");
            }
        }
        return true;
    }

    /**
     * Belirtilen kritik dosya yolunun tescilli imza paritesini dinamik olarak doğrular.
     *
     * @param string $dosyaYolu
     * @return bool
     */
    public function verifyFileSignature(string $dosyaYolu): bool
    {
        $fullPath = base_path($dosyaYolu);
        if (!file_exists($fullPath)) {
            return false;
        }

        $currentHash = hash_file('sha256', $fullPath);
        $expectedHash = $this->registeredFiles[$dosyaYolu] ?? null;

        if ($expectedHash === null) {
            // Eğer dosya tescilli listede yoksa, bütünlük taraması dışı kabul edilir.
            return true;
        }

        return hash_equals($expectedHash, $currentHash);
    }
}
