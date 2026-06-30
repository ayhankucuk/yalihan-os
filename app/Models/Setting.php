<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Support\Facades\Cache;

/**
 * Setting Model - System Configuration Storage
 *
 * @property int $id
 * @property string $key
 * @property string|array|null $value
 * @property string $type
 * @property string|null $description
 * @property string $group
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Setting extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    /**
     * Fillable fields
     * Context7: Fixed - Added missing fields (type, group, description)
     */
    protected $fillable = [
        'key',
        'value',
        'type', // context7-ignore
        'description',
        'group',
    ];

    /**
     * NO automatic JSON casting - handle it manually based on type
     */
    protected $casts = [];

    /**
     * Get parsed value based on type
     * Context7: Type-aware value parsing
     */
    public function getValueAttribute($value)
    {
        switch ($this->attributes['type'] ?? 'string') { // context7-ignore
            case 'json':
                return json_decode($value, true);
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'string':
            default:
                return $value;
        }
    }

    /**
     * Set value with proper encoding
     * Context7: Type-aware value storage
     */
    public function setValueAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['value'] = json_encode($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }

    /**
     * Get setting value with caching
     * Context7: Cache-aware getter
     *
     * @param  string  $key  Setting key
     * @param  mixed  $default  Default value if not found
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        return Cache::remember('setting.'.$key, 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set setting value with auto-detection
     * Context7: Smart setter with type auto-detection
     *
     * @param  string  $key  Setting key
     * @param  mixed  $value  Setting value
     * @param  string  $group  Setting group (default: general)
     * @param  string|null  $type  Value type (auto-detected if null)
     * @param  string|null  $description  Setting description
     * @return static
     */
    public static function set($key, $value, $group = 'general', $type = null, $description = null)
    {
        // Auto-detect type if not provided
        if (! $type) {
            $type = is_bool($value) ? 'boolean'
                  : (is_array($value) ? 'json'
                  : (is_numeric($value) ? 'integer'
                  : 'string'));
        }

        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'group' => $group,
                'type' => $type, // context7-ignore
                'description' => $description,
            ]
        );

        // Clear cache
        Cache::forget('setting.'.$key);

        return $setting;
    }

    /**
     * Get all settings by group
     *
     * @param  string  $group  Group name
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByGroup($group)
    {
        return Cache::remember('settings.group.'.$group, 3600, function () use ($group) {
            return static::where('group', $group)->get();
        });
    }

    /**
     * Get all groups with counts
     *
     * @return array
     */
    public static function getGroups()
    {
        return Cache::remember('settings.groups', 3600, function () {
            return static::selectRaw('`group`, COUNT(*) as count')
                ->groupBy('group')
                ->orderBy('group') // context7-ignore
                ->get()
                ->pluck('count', 'group')
                ->toArray();
        });
    }

    /**
     * Clear all setting caches
     */
    public static function clearCache()
    {
        $keys = static::pluck('key');
        foreach ($keys as $key) {
            Cache::forget('setting.'.$key);
        }
        Cache::forget('settings.groups');

        $groups = static::distinct()->pluck('group');
        foreach ($groups as $group) {
            Cache::forget('settings.group.'.$group);
        }
    }
}
