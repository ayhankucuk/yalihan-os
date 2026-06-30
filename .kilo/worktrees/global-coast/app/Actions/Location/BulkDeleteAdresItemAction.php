<?php

namespace App\Actions\Location;

class BulkDeleteAdresItemAction
{
    public function __construct(private readonly DestroyAdresItemAction $destroyAction) {}

    public function handle(string $type, array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            try {
                if ($this->destroyAction->handle($type, (int) $id)) {
                    $count++;
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('BulkDeleteAdresItemAction: silinen kayıt atlandı', [
                    'kayit_turu'  => $type,
                    'kayit_id'    => $id,
                    'hata_mesaji' => $e->getMessage(),
                ]);
            }
        }
        return $count;
    }
}
