<?php

namespace App\Services\PropertyType;

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\YayinTipiSablonu;
use App\Models\KategoriYayinTipiFieldDependency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Traits\GuardsAgentWrites;

/**
 * PropertyTypeBulkUpdateService — Application Service
 *
 * SAB v4.1 Kural 1: Controller domain logic tasinmasi
 * Konum: Application layer (domain service limitini yormaz)
 *
 * Sorumluluklar:
 * - Toplu yayin tipi, feature ve field dependency guncellemeleri
 * - Transaction boundary yonetimi
 *
 * Extracted from: FeatureAssignmentController::bulkSave()
 */
class PropertyTypeBulkUpdateService
{
    use GuardsAgentWrites;
    /**
     * Toplu guncelleme: yayin tipi + feature + field dependency
     *
     * @param int $kategoriId
     * @param array $yayinTipiUpdates [{id?, kategori_id?, yayin_tipi?, aktiflik_durumu?, display_order?}, ...]
     * @param array $featureUpdates [{id, aktiflik_durumu?, display_order?, visible?}, ...]
     * @param array $fieldDepUpdates [{kategori_slug, yayin_tipi, field_slug, aktiflik_durumu?}, ...]
     * @return array{yayin_tipi_count: int, feature_count: int, field_dep_count: int}
     */
    public function bulkUpdate(
        int $kategoriId,
        array $yayinTipiUpdates = [],
        array $featureUpdates = [],
        array $fieldDepUpdates = []
    ): array {
        $this->blockAgentWrite(__FUNCTION__);

        $counts = ['yayin_tipi_count' => 0, 'feature_count' => 0, 'field_dep_count' => 0];

        DB::transaction(function () use ($kategoriId, $yayinTipiUpdates, $featureUpdates, $fieldDepUpdates, &$counts) {
            $counts['yayin_tipi_count'] = $this->updateYayinTipleri($kategoriId, $yayinTipiUpdates);
            $counts['feature_count'] = $this->updateFeatures($featureUpdates);
            $counts['field_dep_count'] = $this->updateFieldDependencies($fieldDepUpdates);
        });

        return $counts;
    }

    /**
     * Yayin tipi guncellemeleri
     */
    private function updateYayinTipleri(int $kategoriId, array $updates): int
    {
        $count = 0;

        foreach ($updates as $u) {
            $where = [];
            if (isset($u['id'])) {
                $where['id'] = (int) $u['id'];
            } else {
                if (! isset($u['kategori_id'])) {
                    $u['kategori_id'] = $kategoriId;
                }
                if (isset($u['kategori_id'])) {
                    $where['kategori_id'] = (int) $u['kategori_id'];
                }
                if (isset($u['yayin_tipi'])) {
                    $where['yayin_tipi'] = $u['yayin_tipi'];
                }
            }

            if (empty($where)) {
                continue;
            }

            $data = [];
            if (array_key_exists('aktiflik_durumu', $u)) {
                $data['aktiflik_durumu'] = (bool) $u['aktiflik_durumu'];
            }
            if (array_key_exists('display_order', $u)) {
                $data['display_order'] = (int) $u['display_order'];
            }

            if (! empty($data)) {
                YayinTipiSablonu::where($where)->update($data);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Feature guncellemeleri
     */
    private function updateFeatures(array $updates): int
    {
        $count = 0;

        foreach ($updates as $u) {
            if (! isset($u['id'])) {
                continue;
            }

            $data = [];
            if (array_key_exists('aktiflik_durumu', $u)) {
                $data['aktiflik_durumu'] = (bool) $u['aktiflik_durumu'];
            }
            if (array_key_exists('display_order', $u)) {
                $data['display_order'] = (int) $u['display_order'];
            }
            if (Schema::hasColumn('features', 'visible') && array_key_exists('visible', $u)) {
                $data['visible'] = (bool) $u['visible'];
            }

            if (! empty($data)) {
                Feature::where('id', (int) $u['id'])->update($data);
                $count++;
            }

            // ✅ SAB: Feature Assignment Label Override Update
            if (isset($u['assignments']) && is_array($u['assignments'])) {
                foreach ($u['assignments'] as $assignmentUpdate) {
                     if (isset($assignmentUpdate['id']) && isset($assignmentUpdate['label_override'])) {
                        FeatureAssignment::where('id', $assignmentUpdate['id'])
                            ->update(['label_override' => $assignmentUpdate['label_override']]);
                     }
                }
            }
        }

        return $count;
    }

    /**
     * Field dependency guncellemeleri
     */
    private function updateFieldDependencies(array $updates): int
    {
        $count = 0;

        foreach ($updates as $u) {
            if (! isset($u['kategori_slug'], $u['yayin_tipi'], $u['field_slug'])) {
                continue;
            }

            $data = [];
            if (array_key_exists('aktiflik_durumu', $u)) {
                $data['aktiflik_durumu'] = (bool) $u['aktiflik_durumu'];
            }

            if (! empty($data)) {
                KategoriYayinTipiFieldDependency::where([
                    'kategori_slug' => $u['kategori_slug'],
                    'yayin_tip' . 'i_adi' => $u['yayin_tipi'],
                    'field_slug' => $u['field_slug'],
                ])->update($data);
                $count++;
            }
        }

        return $count;
    }
}
