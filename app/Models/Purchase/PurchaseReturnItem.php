<?php

namespace App\Models\Purchase;

use App\Enums\PackingType;
use App\Models\Model;
use App\Models\Traits\BelongsToProduct;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnItem extends Model
{
    use BelongsToProduct;
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'product_id' => 'integer',
        'purchase_return_id' => 'integer',
        'qty' => 'integer',
        'total_qty' => 'float',
        'rate' => 'float',
        'total_rate' => 'float',
        'unit' => PackingType::class,
    ];

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }
}
