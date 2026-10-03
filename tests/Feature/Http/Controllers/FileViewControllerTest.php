<?php

use App\Enums\DirectoryType;
use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = $this->getAdmin();
    $this->actingAs($this->user);
});

test('view returns an inline image response', function () {
    Storage::fake();
    $path = UploadedFile::fake()->image('avatar.jpg')->store('files');
    File::factory()->create(['path' => $path, 'created_by' => $this->user->id]);

    $response = $this->get(route('file.view', ['path' => $path]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toStartWith('image/');
});

test('view falls back to the public disk when missing from the default disk', function () {
    Storage::fake();
    Storage::fake('public');
    $path = UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')
        ->storeAs('files', 'doc.pdf', 'public');
    File::factory()->create(['path' => $path, 'created_by' => $this->user->id]);

    $response = $this->get(route('file.view', ['path' => $path]));

    $response->assertOk();
});

test('view aborts with 404 when the file does not exist on either disk', function () {
    Storage::fake();
    Storage::fake('public');
    File::factory()->create(['path' => 'files/missing.jpg', 'created_by' => $this->user->id]);

    $this->get(route('file.view', ['path' => 'files/missing.jpg']))->assertNotFound();
});

test('upload, view and thumbnail all work against an s3 disk', function () {
    config(['filesystems.default' => 's3']);
    Storage::fake('s3');

    $upload = $this->post(route('file.upload'), [
        'directory' => DirectoryType::SalesBilties->value,
        'file' => UploadedFile::fake()->image('s3-test.jpg'),
    ]);
    $upload->assertOk();
    $path = $upload->json('path');

    $this->get(route('file.view', ['path' => $path]))->assertOk();
    $this->get(route('file.thumbnail', ['path' => $path]))->assertOk();
});

test('view and thumbnail refuse a path that belongs to no known record', function () {
    Storage::fake();
    $path = UploadedFile::fake()->image('secret.jpg')->store('files');

    $this->get(route('file.view', ['path' => $path]))->assertForbidden();
    $this->get(route('file.thumbnail', ['path' => $path]))->assertForbidden();
});

test('view refuses a path of another user unattached file', function () {
    Storage::fake();
    $path = UploadedFile::fake()->image('secret.jpg')->store('files');
    File::factory()->create(['path' => $path, 'created_by' => $this->userWithoutPermissions()->id]);

    $this->get(route('file.view', ['path' => $path]))->assertForbidden();
});
