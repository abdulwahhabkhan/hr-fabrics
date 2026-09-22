<?php

use App\Console\Commands\PruneDeletedFilesCommand;
use App\Enums\DirectoryType;
use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\artisan;

beforeEach(function () {
    $this->user = $this->getAdmin();
});

test('removes files soft-deleted more than 30 days ago along with their stored attachment', function () {
    // arrange
    Storage::fake();
    $this->travelTo(now());
    $path = UploadedFile::fake()->image('old.jpg')->store('files');
    $file = File::factory()->create([
        'path' => $path,
        'directory' => DirectoryType::SalesBilties->value,
        'created_by' => $this->user->id,
        'deleted_at' => now()->subDays(31),
    ]);

    // action
    artisan(PruneDeletedFilesCommand::class);

    // assert
    $this->assertModelMissing($file);
    Storage::disk()->assertMissing($path);
});

test('keeps files soft-deleted less than 30 days ago', function () {
    // arrange
    Storage::fake();
    $this->travelTo(now());
    $path = UploadedFile::fake()->image('recent.jpg')->store('files');
    $file = File::factory()->create([
        'path' => $path,
        'directory' => DirectoryType::SalesBilties->value,
        'created_by' => $this->user->id,
        'deleted_at' => now()->subDays(29),
    ]);

    // action
    artisan(PruneDeletedFilesCommand::class);

    // assert
    $this->assertSoftDeleted($file);
    Storage::disk()->assertExists($path);
});

test('keeps files that are not deleted', function () {
    // arrange
    Storage::fake();
    $this->travelTo(now());
    $path = UploadedFile::fake()->image('active.jpg')->store('files');
    $file = File::factory()->create([
        'path' => $path,
        'directory' => DirectoryType::SalesBilties->value,
        'created_by' => $this->user->id,
    ]);

    // action
    artisan(PruneDeletedFilesCommand::class);

    // assert
    $this->assertDatabaseHas('files', ['id' => $file->id, 'deleted_at' => null]);
    Storage::disk()->assertExists($path);
});
