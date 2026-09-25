<?php

namespace App\Models\Accounts;

use App\Models\Contracts\Fileable;
use App\Models\Contracts\Logable;
use App\Models\Model;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\HasFiles;
use App\Models\Traits\MorphManayToLog;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read string $name
 * @property-read string $city
 * @property-read int $debit
 * @property-read int $credit
 * @property array<string, mixed>|null $file
 */
final class Journal extends Model implements Fileable, Logable
{
    use BelongsToUser;
    use HasFactory;
    use HasFiles;
    use MorphManayToLog;

    protected $perPage = 20;

    protected $guarded = ['id'];

    protected $casts = [
        'posted_at' => 'date:Y-m-d',
        'resource_id' => 'integer',
        'user_id' => 'integer',
        'has_file' => 'boolean',
        'source_info' => AsCollection::class,
    ];

    /**
     * @return HasMany<JournalDetail, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(JournalDetail::class);
    }

    /**
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function resource(): MorphTo
    {
        return $this->morphTo();
    }

    protected function fileDownloadLink(): Attribute
    {
        return Attribute::get(fn () => download_link($this->file['file_path'] ?? ''));
    }

    protected function fileThumbnailLink(): Attribute
    {
        return Attribute::get(fn () => generate_thumbnail($this->file['file_path'] ?? ''));
    }
}
