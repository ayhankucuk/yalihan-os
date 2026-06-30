<?php

namespace App\Services\Governance\Diff;

class PayloadDiffCalculator
{
    /**
     * @return array<string, array{type: string, old: mixed, new: mixed}>
     */
    public function calculate(array $original, array $draft): array
    {
        $originalFlat = $this->flatten($original);
        $draftFlat    = $this->flatten($draft);

        $diff = [];
        $allKeys = array_unique(array_merge(array_keys($originalFlat), array_keys($draftFlat)));

        foreach ($allKeys as $key) {
            $hasInOriginal = array_key_exists($key, $originalFlat);
            $hasInDraft = array_key_exists($key, $draftFlat);

            if ($hasInOriginal && !$hasInDraft) {
                $diff[$key] = [
                    'type' => 'removed',
                    'old'  => $originalFlat[$key],
                    'new'  => null,
                ];
            } elseif (!$hasInOriginal && $hasInDraft) {
                $diff[$key] = [
                    'type' => 'added',
                    'old'  => null,
                    'new'  => $draftFlat[$key],
                ];
            } else {
                $oldVal = $originalFlat[$key];
                $newVal = $draftFlat[$key];

                if ($oldVal !== $newVal) {
                    $diff[$key] = [
                        'type' => 'changed',
                        'old'  => $oldVal,
                        'new'  => $newVal,
                    ];
                }
            }
        }

        ksort($diff);
        return $diff;
    }

    private function flatten(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? (string)$key : $prefix . '.' . $key;
            if (is_array($value)) {
                if (empty($value)) {
                    $result[$newKey] = [];
                } else {
                    $result = array_merge($result, $this->flatten($value, $newKey));
                }
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
