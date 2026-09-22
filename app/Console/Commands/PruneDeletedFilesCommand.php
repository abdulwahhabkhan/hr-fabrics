<?php

namespace App\Console\Commands;

use App\Models\File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneDeletedFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'files:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently remove soft-deleted files and their stored attachments after 30 days';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        File::query()
            ->trashedBefore(now()->subDays(30))->each(function (File $file): void {
                Storage::disk()->delete($file->path);
                $file->forceDelete();
            });
    }
}
