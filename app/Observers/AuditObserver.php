<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AuditObserver
{
    public function saved(Model $model): void
    {
        $this->log('saved', $model);
    }

    public function deleted(Model $model): void
    {
        $this->log('deleted', $model);
    }

    protected function log(string $action, Model $model): void
    {
        $payload = [
            'model' => get_class($model),
            'id' => $model->getKey(),
            'action' => $action,
            'user_id' => auth()->id(),
            'changes' => method_exists($model, 'getChanges') ? $model->getChanges() : [],
            'timestamp' => now()->toIso8601String(),
        ];
        Log::channel('module_changes')->info('audit', $payload);
    }
}
