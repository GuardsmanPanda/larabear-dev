<?php

namespace GuardsmanPanda\LarabearDev\Infrastructure\Laravel\Provider;

use GuardsmanPanda\LarabearDev\Infrastructure\Database\Command\EloquentModelGeneratorCommand;
use GuardsmanPanda\LarabearDev\Infrastructure\Integrity\Command\BearPhpStanCommand;
use Illuminate\Support\ServiceProvider;

class BearDevServiceProvider extends ServiceProvider {
    public function boot(): void {
        if ($this->app->runningInConsole()) {
            $this->commands(commands: [
                BearPhpStanCommand::class,
                EloquentModelGeneratorCommand::class,
            ]);
            $this->publishes(paths: [__DIR__ . '/../../config/config.php' => $this->app->configPath(path: 'bear-dev.php'),], groups: 'bear-dev');
        }
    }
}
