<?php

namespace App\Modules\Finans\Services;

use App\Modules\Finans\Models\FinansalIslem;
use App\DataTransferObjects\Finans\CreateFinansalIslemCommand;
use App\DataTransferObjects\Finans\UpdateFinansalIslemCommand;
use App\Enums\FinansalIslemDurumu;
use App\Services\Logging\LogService;
use App\Traits\GuardsAgentWrites;

class FinansalIslemManager
{
    use GuardsAgentWrites;

    public function createIslem(CreateFinansalIslemCommand $cmd): FinansalIslem
    {
        $this->blockAgentWrite('createIslem');

        $islem = FinansalIslem::create([
            'ilan_id' => $cmd->ilanId,
            'kisi_id' => $cmd->kisiId,
            'gorev_id' => $cmd->gorevId,
            'islem_tipi' => $cmd->islemTipi,
            'miktar' => $cmd->miktar,
            'para_birimi' => $cmd->paraBirimi,
            'aciklama' => $cmd->aciklama,
            'tarih' => $cmd->tarih,
            'islem_statusu' => FinansalIslemDurumu::BEKLIYOR->value,
            'referans_no' => $cmd->referansNo,
            'fatura_no' => $cmd->faturaNo,
            'notlar' => $cmd->notlar,
        ]);

        // DB update ve Log işlemi ayrı servise taşındı (Hygiene Refactoring)
        LogService::action('finansal_islem_created', 'finansal_islem', $islem->id);

        return $islem;
    }

    public function updateIslem(FinansalIslem $islem, UpdateFinansalIslemCommand $cmd): FinansalIslem
    {
        $this->blockAgentWrite('updateIslem');

        $islem->update($cmd->toArray());

        LogService::action('finansal_islem_updated', 'finansal_islem', $islem->id);

        return $islem;
    }

    public function deleteIslem(FinansalIslem $islem): void
    {
        $this->blockAgentWrite('deleteIslem');

        $id = $islem->id;
        $islem->delete();

        LogService::action('finansal_islem_deleted', 'finansal_islem', $id);
    }

    public function approveIslem(FinansalIslem $islem, int $onaylayanId): FinansalIslem
    {
        $this->blockAgentWrite('approveIslem');

        $islem->onayla($onaylayanId);

        LogService::action('finansal_islem_approved', 'finansal_islem', $islem->id, [
            'onaylayan_id' => $onaylayanId,
        ]);

        return $islem;
    }

    public function rejectIslem(FinansalIslem $islem, int $onaylayanId, ?string $not): FinansalIslem
    {
        $this->blockAgentWrite('rejectIslem');

        $islem->reddet($onaylayanId, $not);

        LogService::action('finansal_islem_rejected', 'finansal_islem', $islem->id, [
            'onaylayan_id' => $onaylayanId,
            'not' => $not,
        ]);

        return $islem;
    }

    public function completeIslem(FinansalIslem $islem): FinansalIslem
    {
        $this->blockAgentWrite('completeIslem');

        $islem->tamamla();

        LogService::action('finansal_islem_completed', 'finansal_islem', $islem->id);

        return $islem;
    }
}
