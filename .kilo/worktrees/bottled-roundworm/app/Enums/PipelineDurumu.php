<?php

namespace App\Enums;

/**
 * Pipeline Run durumu.
 * Context7: pipeline_durumu field enum.
 */
enum PipelineDurumu: string
{
    case QUEUED = 'queued';
    case NORMALIZING = 'normalizing';
    case VALIDATED = 'validated';
    case AUDIT_RUNNING = 'audit_running';
    case FIX_RUNNING = 'fix_running';
    case EXECUTION_RUNNING = 'execution_running';
    case VERIFICATION_RUNNING = 'verification_running';
    case GOVERNING = 'governing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case HALTED = 'halted';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::QUEUED => 'Kuyrukta',
            self::NORMALIZING => 'Normalleştiriliyor',
            self::VALIDATED => 'Doğrulandı',
            self::AUDIT_RUNNING => 'Denetim Çalışıyor',
            self::FIX_RUNNING => 'Düzeltme Çalışıyor',
            self::EXECUTION_RUNNING => 'Uygulama Çalışıyor',
            self::VERIFICATION_RUNNING => 'Doğrulama Çalışıyor',
            self::GOVERNING => 'Yönetişim Çalışıyor',
            self::COMPLETED => 'Tamamlandı',
            self::FAILED => 'Başarısız',
            self::HALTED => 'Durduruldu',
            self::CANCELLED => 'İptal Edildi',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::HALTED, self::CANCELLED]);
    }

    public function isRunning(): bool
    {
        return in_array($this, [
            self::NORMALIZING,
            self::AUDIT_RUNNING,
            self::FIX_RUNNING,
            self::EXECUTION_RUNNING,
            self::VERIFICATION_RUNNING,
            self::GOVERNING,
        ]);
    }
}
