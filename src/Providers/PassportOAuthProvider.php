<?php

namespace Kieuvu\PassportOauth\Providers;

use Illuminate\Support\ServiceProvider;

class PassportOAuthProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
