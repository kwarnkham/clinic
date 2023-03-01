<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Model::unguard();
        Model::preventAccessingMissingAttributes(! App::isProduction());
        Model::preventLazyLoading(! App::isProduction());
        Password::defaults(function () {
            $rule = Password::min(5);

            return App::isProduction()
                ? $rule->mixedCase()->uncompromised()
                : $rule;
        });
    }
}
