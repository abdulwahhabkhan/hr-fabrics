<?php

namespace App\Models\Traits;

use App\Models\Catalog\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToProduct
{
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
