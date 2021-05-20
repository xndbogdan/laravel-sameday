<?php

namespace xndbogdan\Sameday;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use xndbogdan\Sameday\Helpers\Sameday as HelperSameday;
use xndbogdan\Sameday\Models\Sameday;

class SamedayServiceProvider extends ServiceProvider
{
    public function boot() {
    }

    public function register() {
        $this->registerSamedayModel();
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('Sameday', 'xndbogdan\Sameday\Helpers\Sameday');
    }

    protected function registerSamedayModel() {
        $this->app->singleton('sameday', Sameday::class);
    }
}
