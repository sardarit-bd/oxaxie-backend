<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\AiProviderRepositoryInterface;
use App\Repositories\Contracts\AiModelRepositoryInterface;
use App\Repositories\Contracts\AiModelPricingRepositoryInterface;
use App\Repositories\AiProviderRepository;
use App\Repositories\AiModelRepository;
use App\Repositories\AiModelPricingRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AiProviderRepositoryInterface::class, AiProviderRepository::class);
        $this->app->bind(AiModelRepositoryInterface::class, AiModelRepository::class);
        $this->app->bind(AiModelPricingRepositoryInterface::class, AiModelPricingRepository::class);
    }

    public function boot(): void
    {
        //
    }
}