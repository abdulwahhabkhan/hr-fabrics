<?php

use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = $this->getAdmin();
    $this->actingAs($this->user);
});

test('thumbnail returns a resized inline image', function () {
    Storage::fake();
    $path = UploadedFile::fake()->image('photo.jpg', 800, 600)->store('files');
    File::factory()->create(['path' => $path, 'created_by' => $this->user->id]);

    $response = $this->get(route('file.thumbnail', ['path' => $path]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toStartWith('image/');
});
