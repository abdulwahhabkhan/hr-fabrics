<?php

use App\Models\Accounts\Account;
use App\Models\Catalog\Brand;
use App\Models\Catalog\Product;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = $this->getAdmin();
    $this->actingAs($this->admin);
    $this->vendor = Account::factory()->supplier()->create();
    $this->brand = Brand::factory()->create();
    $this->isBox = 1;
    $this->size = 5.5;
    $this->suitPrice = 0;
    $this->unitPrice = fake()->randomNumber(3);
    $this->fakeHavePermission();
});

function getPayload(): array
{
    /** current test object */
    $target = test()->target;

    return [
        'name' => fake()->name(),
        'description' => fake()->sentence(),
        'is_box' => $target->isBox,
        'size' => $target->isBox ? $target->size : 0,
        'cost' => fake()->randomNumber(2),
        'unit_price' => $target->unitPrice,
        'finish' => fake()->bothify('Finish ????'),
        'brand_id' => $target->brand->id,
        'vendor_id' => $target->vendor->id,
    ];
}

test('check product list', function () {
    // Arrange
    Product::factory(10)->create();

    // Act
    $response = $this->get(route('catalog.products.index'));

    // Assert
    $response->assertStatus(200);
});

test('filter product list by name', function () {
    // Arrange
    Product::factory()->create(['name' => 'Findable Product']);
    Product::factory(5)->create();

    // Act
    $response = $this->get(route('catalog.products.index', ['product_name' => 'Findable']));

    // Assert
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Catalog/Products/ProductIndex')
        ->has('products.data', 1)
    );
});

test('filter product list by vendor presence', function () {
    // Arrange
    Product::factory(3)->create(['vendor_id' => $this->vendor->id]);
    Product::factory(2)->create(['vendor_id' => null]);

    // Act
    $withVendor = $this->get(route('catalog.products.index', ['has_vendor' => 'yes']));
    $withoutVendor = $this->get(route('catalog.products.index', ['has_vendor' => 'no']));

    // Assert
    $withVendor->assertInertia(fn (Assert $page) => $page
        ->component('Catalog/Products/ProductIndex')
        ->has('products.data', 3)
        ->where('filters.has_vendor', 'yes')
    );
    $withoutVendor->assertInertia(fn (Assert $page) => $page
        ->component('Catalog/Products/ProductIndex')
        ->has('products.data', 2)
    );
});

test('display create form', function () {
    // Act
    $response = $this->get(route('catalog.products.create'));

    // Assert
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Catalog/Products/ProductForm')
        ->where('product', null)
        ->has('brands')
        ->has('finishes')
        ->has('vendors')
    );
});

test('display edit form', function () {
    // Arrange
    $product = Product::factory()->box()->create();

    // Act
    $response = $this->get(route('catalog.products.edit', $product->id));

    // Assert
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Catalog/Products/ProductForm')
        ->where('product.id', $product->id)
        ->has('brands')
        ->has('finishes')
        ->has('vendors')
    );
});

test('delete product', function () {
    // Arrange
    $product = Product::factory()->box()->create();

    // Act
    $response = $this->delete(route('catalog.products.destroy', $product->id));

    // Assert
    $response->assertStatus(302);
    $response->assertSessionHas('success', 'Product deleted.');
    $this->assertSoftDeleted(Product::tName(), ['id' => $product->id]);
});

test('add box product', function () {
    // Arrange
    $this->isBox = 1;
    $body = getPayload();

    // Act
    $response = $this->post(route('catalog.products.store'), $body);

    // Assert
    $response->assertSessionHasNoErrors();
    $response->assertStatus(302);
    $this->assertDatabaseHas(Product::tName(), $body);
});

test('add suit product', function () {
    // Arrange
    $this->isBox = 0;
    $this->size = 0;
    $body = getPayload() + ['suit_price' => 1300];

    // Act
    $response = $this->post(route('catalog.products.store'), $body);

    // Assert
    $response->assertSessionHasNoErrors();
    $response->assertStatus(302);
    $this->assertDatabaseHas(Product::tName(), $body);
});

test('add product with only name and finish', function (array $optionalFields) {
    // Arrange
    $body = ['name' => 'Plain Fabric', 'finish' => 'Wash & Wear'] + $optionalFields;

    // Act
    $response = $this->post(route('catalog.products.store'), $body);

    // Assert
    $response->assertSessionHasNoErrors();
    $response->assertStatus(302);
    $this->assertDatabaseHas(Product::tName(), [
        'name' => 'Plain Fabric',
        'finish' => 'Wash & Wear',
        'brand_id' => null,
        'vendor_id' => null,
        'description' => null,
        'is_box' => 0,
        'size' => 0,
        'cost' => 0,
        'unit_price' => 0,
        'suit_price' => 0,
    ]);
})->with([
    'optional fields omitted' => [[]],
    'optional fields left blank' => [[
        'description' => '',
        'size' => '',
        'cost' => '',
        'unit_price' => '',
        'suit_price' => '',
        'brand_id' => null,
        'vendor_id' => null,
        'is_box' => false,
    ]],
]);

test('name and finish are required to add a product', function () {
    // Act
    $response = $this->post(route('catalog.products.store'), []);

    // Assert
    $response->assertSessionHasErrors(['name', 'finish']);
    $this->assertDatabaseCount(Product::tName(), 0);
});

test('optional product fields are still validated when filled', function () {
    // Act
    $response = $this->post(route('catalog.products.store'), [
        'name' => 'Plain Fabric',
        'finish' => 'Wash & Wear',
        'cost' => 'abc',
        'brand_id' => 999999,
    ]);

    // Assert
    $response->assertSessionHasErrors(['cost', 'brand_id']);
});

test('display edit form for a product without a brand', function () {
    // Arrange
    $product = Product::factory()->create(['brand_id' => null]);

    // Act
    $response = $this->get(route('catalog.products.edit', $product->id));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->where('product.brand', null));
});

test('update box product', function () {
    // Arrange
    $body = getPayload();
    $product = Product::factory()
        ->box()
        ->create();

    // Act
    $response = $this->put(route('catalog.products.update', $product->id), $body);

    // Assert
    $response->assertSessionHasNoErrors();
    $response->assertStatus(302);
    $this->assertDatabaseHas(Product::tName(), $body);
});

test('update suit product', function () {
    // Arrange
    $this->isBox = 0;
    $this->size = 0;
    $body = getPayload() + ['suit_price' => 2300];

    $product = Product::factory()
        ->suit()
        ->create();

    // Act
    $response = $this->put(route('catalog.products.update', $product->id), $body);

    // Assert
    $response->assertSessionHasNoErrors();
    $response->assertStatus(302);
    $this->assertDatabaseHas(Product::tName(), $body);
});
