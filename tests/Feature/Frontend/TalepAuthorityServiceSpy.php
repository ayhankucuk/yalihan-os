<?php

namespace Tests\Feature\Frontend;

use App\Services\CRM\KisiRegistrationService;

/**
 * Spy wrapper for TalepAuthorityService that captures createTalep arguments
 * without changing the real service behavior.
 */
class TalepAuthorityServiceSpy extends \App\Services\CRM\TalepAuthorityService
{
    public ?array $lastCapturedData = null;
    public $lastCapturedActor = null;

    public function __construct(KisiRegistrationService $kisiRegistrationService)
    {
        parent::__construct($kisiRegistrationService);
    }

    public function createTalep(array $data, $actor = null): \App\Models\Talep
    {
        $this->lastCapturedData = $data;
        $this->lastCapturedActor = $actor;
        return parent::createTalep($data, $actor);
    }
}
