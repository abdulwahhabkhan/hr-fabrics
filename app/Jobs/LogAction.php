<?php

namespace App\Jobs;

use App\Models\Action\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** @deprecated */
final class LogAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected array $data) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::create($this->data);
    }
}
