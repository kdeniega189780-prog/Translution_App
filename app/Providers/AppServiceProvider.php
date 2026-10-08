<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Password::defaults(fn () => Password::min(8)->letters()->numbers()->symbols());
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
