<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SwitchAiProvider extends Command
{
    protected $signature = 'ai:switch {provider : The AI provider (gemini or anthropic)}';
    protected $description = 'Switch the default AI provider';

    public function handle()
    {
        $provider = $this->argument('provider');

        if (!in_array($provider, ['gemini', 'anthropic'])) {
            $this->error('Invalid provider. Use: gemini or anthropic');
            return 1;
        }

        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);

        // Update AI_PROVIDER
        $envContent = preg_replace(
            '/^AI_PROVIDER=.*/m',
            "AI_PROVIDER={$provider}",
            $envContent
        );

        file_put_contents($envFile, $envContent);

        $this->info("✓ Switched to {$provider}");
        $this->warn('Remember to run: php artisan config:clear');

        return 0;
    }
}