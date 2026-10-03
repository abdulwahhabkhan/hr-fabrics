<?php

use App\Enums\DirectoryType;
use App\Facades\Permission;
use App\Models\File;
use App\Models\Purchase\FabricReceiving;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = $this->getAdmin();
    $this->actingAs($this->user);
});

test('image upload', function () {
    // arrange
    Storage::fake();
    $file = UploadedFile::fake()->image('test.jpg');
    // action
    $response = $this->post(route('file.upload'), [
        'directory' => DirectoryType::SalesBilties->value,
        'file' => $file,
    ]);
    // assert
    $response->assertOk();

});

test('file uploaded test', function () {
    // arrange
    Storage::fake();
    $file_name = 'customer_bill.png';
    $directory = DirectoryType::FabricsReceivings->value;

    // action
    $response = $this->post(route('file.upload'), [
        'directory' => $directory,
        'file' => UploadedFile::fake()->image($file_name),
    ]);

    // assert

    $response->assertOk();
    $response->assertJson([
        'name' => $file_name,
        'created_by' => $this->user->id,
    ]);
    $response->assertJsonStructure([
        'name',
        'directory',
        'path',
        'thumbnail',
        'created_by',
        'created_at',
    ]);
});

test('delete soft-deletes the file record and keeps the stored file', function () {
    // arrange
    Storage::fake();
    $path = UploadedFile::fake()->image('bilti.jpg')->store('files');
    $file = File::factory()->create([
        'created_by' => $this->user->id,
        'directory' => DirectoryType::SalesBilties->value,
        'path' => $path,
    ]);

    // action
    $response = $this->delete(route('file.delete', $file->id));

    // assert
    $response->assertOk();
    $response->assertJson(['message' => 'File deleted successfully']);
    $this->assertSoftDeleted($file);
    Storage::disk()->assertExists($path);
});

test('deleting a missing file returns a 404', function () {
    $response = $this->delete(route('file.delete', 999999));

    $response->assertNotFound();
});

test('upload attaches the file to a record the user can edit', function () {
    Storage::fake();
    Permission::fake(['purchases.fabric-receivings.edit' => true]);
    $receiving = FabricReceiving::factory()->create();

    $response = $this->post(route('file.upload'), [
        'directory' => DirectoryType::FabricsReceivings->value,
        'file' => UploadedFile::fake()->image('grn.jpg'),
        'morph_class' => FabricReceiving::morphClass(),
        'morph_id' => $receiving->id,
    ]);

    $response->assertOk();
    $this->assertDatabaseHas(File::class, [
        'fileable_type' => FabricReceiving::morphClass(),
        'fileable_id' => $receiving->id,
    ]);
});

test('upload cannot attach a file to a record the user cannot edit', function () {
    Storage::fake();
    Permission::fake(['purchases.fabric-receivings.edit' => false]);
    $receiving = FabricReceiving::factory()->create();

    $response = $this->post(route('file.upload'), [
        'directory' => DirectoryType::FabricsReceivings->value,
        'file' => UploadedFile::fake()->image('grn.jpg'),
        'morph_class' => FabricReceiving::morphClass(),
        'morph_id' => $receiving->id,
    ]);

    $response->assertForbidden();
    $this->assertDatabaseCount(File::class, 0);
});

test('upload rejects an unknown morph class', function () {
    Storage::fake();

    $response = $this->postJson(route('file.upload'), [
        'directory' => DirectoryType::FabricsReceivings->value,
        'file' => UploadedFile::fake()->image('grn.jpg'),
        'morph_class' => User::class,
        'morph_id' => $this->user->id,
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('morph_class');
});

test('another user unattached file cannot be viewed or deleted', function () {
    $file = File::factory()->create(['created_by' => $this->userWithoutPermissions()->id]);

    $this->get(route('file.show', $file->id))->assertForbidden();
    $this->delete(route('file.delete', $file->id))->assertForbidden();

    $this->assertNotSoftDeleted($file);
});

test('attached file follows the owning record permissions', function () {
    Permission::fake(['purchases.fabric-receivings.*' => false]);
    $receiving = FabricReceiving::factory()->create();
    $file = File::factory()->create(['created_by' => $this->user->id]);
    $file->fileable()->associate($receiving)->save();

    $this->get(route('file.show', $file->id))->assertForbidden();
    $this->delete(route('file.delete', $file->id))->assertForbidden();
});
