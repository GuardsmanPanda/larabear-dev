<?php

namespace GuardsmanPanda\LarabearDev\Provider;

use GuardsmanPanda\LarabearDev\Command\BearPhpStanCommand;
use Illuminate\Support\ServiceProvider;

class BearDevServiceProvider extends ServiceProvider {
    public function boot(): void {
        if ($this->app->runningInConsole()) {
            $this->commands(commands: [
                BearPhpStanCommand::class,
            ]);

            $this->publishes(paths: [__DIR__ . '/../../config/config.php' => $this->app->configPath(path: 'bear-dev.php'),], groups: 'bear');
        }
    }
}
