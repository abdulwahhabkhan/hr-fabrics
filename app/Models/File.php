<?php

namespace App\Models;

use App\Enums\DirectoryType;
use App\Enums\FileType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read bool $is_image
 */
final class File extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $appends = ['thumbnail', 'preview', 'is_image'];

    protected $casts = [
        'directory' => DirectoryType::class,
        'type' => FileType::class,
    ];

    /**
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function isImage(): Attribute
    {
        return Attribute::get(fn () => $this->type === FileType::Image);
    }

    #[Scope]
    protected function orderBilti(Builder $query): Builder
    {
        return $query->where('directory', DirectoryType::SalesBilties->value);
    }

    /**
     * @param  Builder<File>  $query
     * @return Builder<File>
     */
    #[Scope]
    protected function trashedBefore(Builder $query, CarbonInterface $date): Builder
    {
        return $query->onlyTrashed()->where('deleted_at', '<=', $date);
    }

    protected function thumbnail(): Attribute
    {
        return Attribute::get(fn () => generate_thumbnail($this->path, 60));
    }

    protected function preview(): Attribute
    {
        return Attribute::get(fn () => download_link($this->path, 60));
    }
}
