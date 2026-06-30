<?php

namespace App\Presenters\Sab;

use Illuminate\Console\Command;

class ConsolePresenter implements PresenterContract
{
    public function __construct(private Command $command) {}

    public function renderLearn(array $data): void
    {
        $this->command->info('✅ Bekci learning completed');
        $this->command->line('Action Type: '.($data['action_type'] ?? '-'));
        $this->command->line('Context: '.($data['context'] ?? '-'));

        $suggestions = $data['suggestions'] ?? [];
        if (!empty($suggestions)) {
            $this->command->line('Suggestions:');
            foreach ($suggestions as $suggestion) {
                $this->command->line(' - '.$suggestion);
            }
        }
    }
}
