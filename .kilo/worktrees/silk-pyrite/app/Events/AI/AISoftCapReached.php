<?php

namespace App\Events\AI;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AISoftCapReached
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $scopeTipi; // 'user' or 'ip'
    public string $scopeDegeri; // user_id or hash
    public string $pencere; // 'saatlik' or 'gunluk'
    public float $limit;
    public float $kullanim;
    public float $kullanimOrani;

    /**
     * Create a new event instance.
     */
    public function __construct(
        string $scopeTipi,
        string $scopeDegeri,
        string $pencere,
        float $limit,
        float $kullanim,
        float $kullanimOrani
    ) {
        $this->scopeTipi = $scopeTipi;
        $this->scopeDegeri = $scopeDegeri;
        $this->pencere = $pencere;
        $this->limit = $limit;
        $this->kullanim = $kullanim;
        $this->kullanimOrani = $kullanimOrani;
    }
}
