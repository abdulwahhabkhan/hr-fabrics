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

    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isImage(): bool
    {
        return $this->type === FileType::Image;
    }

    public function getIsImageAttribute(): bool
    {
        return $this->isImage();
    }

    #[Scope]
    protected function orderBilti(Builder $query): Builder
    {
        return $query->where('directory', DirectoryType::SalesBilties->value);
    }

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
