<?php

namespace Wemx\Installer\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class WemXUpdate extends Command
{
    protected $description = 'Update WemX to a specified version';

    protected $signature = 'wemx:update {license_key?} {--type=stable} {--ver=latest}';

    /**
     * WemXUpdate constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Handle command request to create a new user.
     *
     * @throws Exception
     */
    public function handle()
    {
        $this->info("
        ======================================
        |||     WemX™ © 2023 Updater       |||
        |||           By Mubeen            |||
        ======================================
        ");

        $this->sshUser();

        $license_key = $this->argument('license_key') ?? $this->ask("Please enter your license key", settings('encrypted::license_key'));

        $this->info('Attempting to connect to WemX...');

        $queryParameters = [
                'type' => $this->getOption('type', 'stable'),
                'ver' => $this->getOption('ver', 'latest')
        ];

        $response = Http::withOptions([
            'query' => $queryParameters,
        ])->get("https://api.wemx.pro/api/wemx/licenses/$license_key/{$this->ip()}/Y29tbWFuZHM=");

        $this->info('Connected');

        if (!$response->successful()) {
            if (isset($response['success']) and !$response['success']) {
                return $this->error($response['message']);
            }
            return $this->error('Failed to connect to remote server, please try again.');
        }

        $response = $response->object();
        $this->info('Proceeding with installation...');
        $this->info('This can take a minute, please wait...');

        $commands = $response->commands;
        foreach ($response->commands as $key => $command) {
            for ($i = 0; $i < $response->x; $i++) {
                $commands[$key] = base64_decode($commands[$key]);
            }
            shell_exec($commands[$key]);
        }

        $this->info('Setting correct file permissions');
        shell_exec('chmod -R 755 storage/* bootstrap/cache');

        $this->info('Updating composer dependencies');
        shell_exec('composer update -n /dev/null 2>&1');
        shell_exec('composer install --optimize-autoloader -n /dev/null 2>&1');

        $this->info('Enabling modules');
        shell_exec('php artisan module:enable');

        $this->info('Clearing cache');
        shell_exec('php artisan view:clear && php artisan config:clear');

        $this->info('Migrating & Seeding database');
        shell_exec('php artisan migrate --seed --force');

        $this->info('Updating webserver permissions');
        shell_exec('chown -R www-data:www-data '. base_path('/*'));

        $this->info('Update Complete');
    }

    private function ip()
    {
        $ipAddress = exec("curl -s ifconfig.me");

        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            $this->newLine(2);
            $this->info("Failed to automatically retrieve IP Address");
            $ipAddress = $this->ask("Please enter this machines IP Address");
        }

        return $ipAddress;
    }

    private function getOption($key, $default = null)
    {
        return ($this->option($key)) ? $this->option($key) : $default;
    }

    private function sshUser()
    {
        $SshUser = exec('whoami');
        if (isset($SshUser) and $SshUser !== "root") {
            $this->error('
      We have detected that you are not logged in as a root user.
      To run the auto-installer, it is recommended to login as root user.
      If you are not logged in as root, some processes may fail to setup
      To login as root SSH user, please type the following command: sudo su
      and proceed to re-run the installer.
      alternatively you can contact your provider for ROOT user login for your machine.
      ');

            if ($this->confirm('Stop the installer?', true)) {
                $this->info('Installer has been cancelled.');
                exit;
            }
        }
    }
}
