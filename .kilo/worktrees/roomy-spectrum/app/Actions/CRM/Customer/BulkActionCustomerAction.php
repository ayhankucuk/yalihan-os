<?php

namespace App\Actions\CRM\Customer;

use App\Models\Kisi;

class BulkActionCustomerAction
{
    public function handle(string $action, array $ids): string
    {
        $query = Kisi::whereIn('id', $ids);

        switch ($action) {
            case 'activate':
                $query->update(['aktiflik_durumu' => true]);
                $message = count($ids) . ' kişi etkinleştirildi';
                break;

            case 'pasif_yap':
                $query->update(['aktiflik_durumu' => false]);
                $message = count($ids) . ' kişi pasif yapıldı';
                break;

            case 'sil':
            case 'delete':
                $query->delete();
                $message = count($ids) . ' kişi silindi';
                break;

            default:
                throw new \InvalidArgumentException('Geçersiz işlem');
        }

        return $message;
    }
}
