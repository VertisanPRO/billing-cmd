<?php

namespace Billing\Commands;

use Illuminate\Support\ServiceProvider;
use Wemx\Installer\Commands\InstallCommand;
use Wemx\Installer\Commands\HelpCommand;
use Wemx\Installer\Commands\UninstallCommand;
use Wemx\Installer\Commands\FixCommand;
use Wemx\Installer\Commands\YarnCommand;
use Wemx\Installer\Commands\InstallphpMyAdmin;
use Wemx\Installer\Commands\CreateMySQLUser;
use Wemx\Installer\Commands\DeleteMySQLUser;
use Wemx\Installer\Commands\LicenseCommand;

class CommandsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {

        if ($this->app->runningInConsole()) {
            // Registering package commands.
            $this->commands([InstallCommand::class, HelpCommand::class, UninstallCommand::class, FixCommand::class, YarnCommand::class, InstallphpMyAdmin::class, CreateMySQLUser::class, DeleteMySQLUser::class, LicenseCommand::class]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/aliases.php',
            'app.aliases'
        );
    }
}
