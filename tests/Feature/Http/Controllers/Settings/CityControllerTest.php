<?php

use App\Facades\Permission as PermissionFacade;
use App\Models\City;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $user = $this->getAdmin();
    $this->actingAs($user);
    PermissionFacade::fake(['*' => true]);
});

test('cities list loaded', function () {

    City::factory()->count(10)->create();
    $response = $this->get(route('settings.cities.index'));
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Settings/City/CityIndex')
        ->has('cities')
        ->has('cities.data', 10)
        ->has('filters')
    );
});

test('city form loaded', function ($action) {
    if ($action === 'create') {
        $city = null;
        $route = route('settings.cities.create');
    } else {
        $city = City::factory()->create();
        $route = route('settings.cities.edit', $city->id);
    }

    $response = $this->get($route);
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Settings/City/CityForm')
        ->has('city')
    );
})->with(['create', 'update']);

test('city saved successfully', function () {
    // arrange
    $city = fake()->city();
    $city_urdu = fake()->city();
    $payload = ['name' => $city, 'name_urdu' => $city_urdu];
    // action
    $response = $this->post(route('settings.cities.store'), $payload);
    // assert
    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
    assertDatabaseHas(City::class, $payload);
});

test('city updated successfully', function () {
    // arrange
    $name = fake()->city();
    $name_urdu = fake()->city();
    $payload = ['name' => $name, 'name_urdu' => $name_urdu];
    $city = City::factory()->create();
    // action
    $response = $this->put(route('settings.cities.update', $city), $payload);
    // assert
    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
    assertDatabaseHas(City::class, $payload);
});
