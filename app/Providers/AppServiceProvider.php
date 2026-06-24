<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\Contracts\UserRepositoryInterface::class,
            \App\Repositories\Eloquent\UserRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\ConversationRepositoryInterface::class,
            \App\Repositories\Eloquent\ConversationRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\MessageRepositoryInterface::class,
            \App\Repositories\Eloquent\MessageRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\PostRepositoryInterface::class,
            \App\Repositories\Eloquent\PostRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\ForumRepositoryInterface::class,
            \App\Repositories\Eloquent\ForumRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\ReportRepositoryInterface::class,
            \App\Repositories\Eloquent\ReportRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
        \URL::forceScheme('https');
    }

    }
}
