<?php

namespace Database\Factories\Stock;

use App\Enums\PackingType;
use App\Models\Catalog\Product;
use App\Models\Stock\Conversion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversion>
 */
class ConversionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Conversion>
     */
    protected $model = Conversion::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $product = Product::factory()->thaan()->create();

        return [
            'created_by' => function () {
                return User::inRandomOrder()->first()->id;
            },
            'product_id' => $product->id,
            'sku' => $product->name,
            'from' => ['unit' => PackingType::Thaan->value, 'size' => 25, 'qty' => 2],
            'to' => ['unit' => PackingType::Suit->value, 'size' => 5, 'qty' => 10],

        ];
    }
}
