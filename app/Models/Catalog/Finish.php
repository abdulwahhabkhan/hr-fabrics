<?php

namespace App\Models\Catalog;

use App\Models\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Finish extends Model
{
    use HasFactory;

    protected $perPage = 30;

    protected $guarded = ['id'];

    protected static string $cacheKey = 'finish.list';

    /**
     * @return Builder[]|Collection|mixed
     */
    public static function getFinishList()
    {
        $data = Cache::get(self::$cacheKey);
        if (! $data) {
            $data = self::query()->select('id', 'name')->orderBy('name')->get();
            Cache::put(self::$cacheKey, $data);
        }

        return $data;
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::created(function ($data) {
            Cache::forget(self::$cacheKey);
        });
        static::updated(function ($data) {
            Log::info('Finish Update');
            Cache::forget(self::$cacheKey);
        });
    }
}
