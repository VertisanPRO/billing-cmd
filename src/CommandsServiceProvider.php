<?php

namespace Wemx\Installer;

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
use Wemx\Installer\Commands\BackupCommand;
use Wemx\Installer\FileEditor;

class CommandsServiceProvider extends ServiceProvider
{

    private $wemx_backup;
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class, HelpCommand::class, UninstallCommand::class, FixCommand::class, YarnCommand::class, InstallphpMyAdmin::class, CreateMySQLUser::class, DeleteMySQLUser::class, LicenseCommand::class, BackupCommand::class]);
        }

        $this->publishes([__DIR__ . '/../config/wemx-backup.php' => config_path('wemx-backup.php')], 'wemx-backup');
        $this->mergeConfig();
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/aliases.php', 'app.aliases');
    }



    private function mergeConfig()
    {
        $this->wemx_backup = include(__DIR__ . '/../config/wemx-backup.php');
        $wemx_backup = config('wemx-backup');
        foreach ($this->wemx_backup as $key => $value) {
            if (isset($wemx_backup[$key])) {
                continue;
            }
            $wemx_backup[$key] = $value;
        }
        $file = new FileEditor(config_path('wemx-backup.php'));
        $file->writeToFile($wemx_backup);
        return;
    }
}
