<?php

namespace App\Models\Accounts;

use App\Models\Contracts\Fileable;
use App\Models\Contracts\Logable;
use App\Models\Model;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\HasFiles;
use App\Models\Traits\MorphManayToLog;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read string $name
 * @property-read string $city
 * @property-read int $debit
 * @property-read int $credit
 * @property-read int $account_id
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
}
