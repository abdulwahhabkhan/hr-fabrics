<?php

namespace App\Models;

use App\Models\Action\Log;
use App\Models\Traits\CustomDateSerializer;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Str;

/**
 * @method MorphMany<int, Log> logs()
 * @method string journalDetail()
 */
class Model extends \Illuminate\Database\Eloquent\Model
{
    use CustomDateSerializer;

    final public static function qCol(string $column, bool $raw = true): string|Expression
    {
        if (Str::contains($column, '.')) {
            return new Expression($column);
        }
        if ($raw) {
            return new Expression(self::tName().'.'.$column);
        }

        return self::tName().'.'.$column;
    }

    final public static function tName(): string
    {
        return (new static)->getTable();
    }

    final public static function morphClass(): string
    {
        return (new static)->getMorphClass();
    }

    public static function modelCacheKey(?string $suffix = null): string
    {
        return str(static::class)->replace(['/', '\\'], '.')
            ->append($suffix ? '.'.$suffix : '')
            ->lower()->toString();
    }
}
