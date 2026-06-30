<?php

namespace App\Domain\Core\Security;

/**
 * Interface GlobalHardlockRegistryContract
 * @package App\Domain\Core\Security
 * @description Phase 20: Platformun çekirdek bileşenlerinin bütünlüğünü Checksum imza matrisleriyle denetleyen küresel hardlock kontratı.
 */
interface GlobalHardlockRegistryContract
{
    /**
     * Kayıtlı tüm kritik çekirdek dosyaların güncel SHA-256 checksum değerlerini doğrular.
     * SAB Madde 22 kurallarına ve katı değişmezlik (Absolute Immutability) ilkelerine tabidir.
     *
     * @return bool Bütünlük doğrulaması başarılı mı?
     * @throws \App\Exceptions\Governance\GlobalHardlockException Dosya bütünlüğü bozulduğunda fırlatılır.
     */
    public function verifySystemIntegrity(): bool;

    /**
     * Belirtilen kritik dosya yolunun (file path) tescilli imza paritesini dinamik olarak doğrular.
     *
     * @param string $dosyaYolu Örn: 'app/Services/Governance/Crypto/LedgerGenesisChainFortress.php'
     * @return bool
     */
    public function verifyFileSignature(string $dosyaYolu): bool;
}
