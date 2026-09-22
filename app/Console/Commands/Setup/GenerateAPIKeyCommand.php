<?php

namespace App\Console\Commands\Setup;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class GenerateAPIKeyCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'apiKey:generate
                    {--show : Display the key instead of modifying files}
                    {--force : Force the operation to run when in production}';

    protected $description = 'Command description';

    public function handle()
    {

        $key = $this->generateRandomKey();

        if ($this->option('show')) {
            return $this->line('<comment>'.$key.'</comment>');
        }

        if (! $this->setKeyInEnvironmentFile($key)) {
            return;
        }

        $this->laravel['config']['sudo.api-key'] = $key;

        $this->components->info('API key set successfully.');

    }

    protected function generateRandomKey(): string
    {
        return str()->random(32);
    }

    protected function setKeyInEnvironmentFile($key): bool
    {

        $currentKey = $this->laravel['config']['store.auth_key'];
        if (mb_strlen($currentKey) !== 0 && (! $this->confirmToProceed())) {
            return false;
        }

        if (! $this->writeNewEnvironmentFileWith($key)) {
            return false;
        }

        return true;
    }

    protected function writeNewEnvironmentFileWith($key): bool
    {
        $replaced = preg_replace(
            $this->keyReplacementPattern(),
            'AUTH_KEY='.$key,
            $input = file_get_contents($this->laravel->environmentFilePath())
        );

        if ($replaced === $input || $replaced === null) {
            $this->error('Unable to set application key. No AUTH_KEY variable was found in the .env file.');

            return false;
        }

        file_put_contents($this->laravel->environmentFilePath(), $replaced);

        return true;
    }

    protected function keyReplacementPattern(): string
    {
        $escaped = preg_quote('='.$this->laravel['config']['store.auth_key'], '/');

        return "/^AUTH_KEY{$escaped}/m";
    }
}
