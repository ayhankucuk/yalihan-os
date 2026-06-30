<?php

namespace App\Modules\BaseModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseModel onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseModel query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseModel withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BaseModel withoutTrashed()
 *
 * @mixin \Eloquent
 */
abstract class BaseModel extends Model
{
    use SoftDeletes;

    /**
     * Varsayılan tarih biçimi
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * Veri çekerken default olarak yüklenecek ilişkiler
     *
     * @var array
     */
    protected $with = [];

    /**
     * Toplu atama koruması
     *
     * @var bool
     */
    protected $guarded = [];

    /**
     * Cast edilecek özellikler
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $routeMiddleware = [
        // ...diğer middleware'ler
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
    ];
}
