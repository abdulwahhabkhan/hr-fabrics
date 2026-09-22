<?php

use App\Enums\DirectoryType;
use App\Models\File;
use App\Models\Purchase\Purchase;
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
        'morph_class' => Purchase::morphClass(),
        'morph_id' => $this->user->id,
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
        'morph_class' => Purchase::morphClass(),
        'morph_id' => $this->user->id,
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

test('view returns an inline image response', function () {
    Storage::fake();
    $path = UploadedFile::fake()->image('avatar.jpg')->store('files');

    $response = $this->get(route('file.view', ['path' => $path]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toStartWith('image/');
});

test('view falls back to the public disk when missing from the default disk', function () {
    Storage::fake();
    Storage::fake('public');
    $path = UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')
        ->storeAs('files', 'doc.pdf', 'public');

    $response = $this->get(route('file.view', ['path' => $path]));

    $response->assertOk();
});

test('view aborts with 404 when the file does not exist on either disk', function () {
    Storage::fake();
    Storage::fake('public');

    $this->get(route('file.view', ['path' => 'files/missing.jpg']))->assertNotFound();
});

test('thumbnail returns a resized inline image', function () {
    Storage::fake();
    $path = UploadedFile::fake()->image('photo.jpg', 800, 600)->store('files');

    $response = $this->get(route('file.thumbnail', ['path' => $path]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toStartWith('image/');
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

test('upload, view and thumbnail all work against an s3 disk', function () {
    config(['filesystems.default' => 's3']);
    Storage::fake('s3');

    $upload = $this->post(route('file.upload'), [
        'directory' => DirectoryType::SalesBilties->value,
        'file' => UploadedFile::fake()->image('s3-test.jpg'),
        'morph_class' => Purchase::morphClass(),
        'morph_id' => $this->user->id,
    ]);
    $upload->assertOk();
    $path = $upload->json('path');

    $this->get(route('file.view', ['path' => $path]))->assertOk();
    $this->get(route('file.thumbnail', ['path' => $path]))->assertOk();
});
