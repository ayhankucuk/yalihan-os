<?php

namespace App\Presenters\Sab;

use Illuminate\Console\Command;

class JsonPresenter implements PresenterContract
{
    public function __construct(private Command $command) {}

    public function renderLearn(array $data): void
    {
        $this->command->line((string) json_encode([
            'status' => 'ok',
            'data' => $data,
            'errors' => [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
