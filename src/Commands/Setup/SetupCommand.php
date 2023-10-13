<?php

namespace Wemx\Installer\Commands\Setup;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SetupCommand extends Command
{
    protected $signature = 'wemx:setup {domain?} {path?} {ssl?}';
    protected $description = 'Setup command';

    public function handle(): void
    {
        $this->info('Configuring WebServer');

        $domain = $this->argument('domain') ?? $this->askDomain();
        $path = $this->argument('path') ?? $this->askRootPath();
        $ssl = $this->argument('ssl') ?? $this->confirm('Would you like to configure SSL?', true);

        $serverChoice = $this->choice('Which web server would you like to configure?', ['Nginx', 'Apache'], 0);
        if ($serverChoice === 'Apache') {
            Artisan::call('wemx:apache', ['domain' => $domain, 'path' => $path, 'ssl' => $ssl], $this->output);
//            passthru("php artisan wemx:apache $domain $path $ssl --ansi");
        } else {
            Artisan::call('wemx:nginx', ['domain' => $domain, 'path' => $path, 'ssl' => $ssl], $this->output);
//            passthru("php artisan wemx:nginx $domain $path $ssl --ansi");
        }
        shell_exec('cp .env.example .env');

        $this->info('Database Creation');
        if ($this->confirm('Do you want to create a new database?', true)) {
            Artisan::call("wemx:database", [], $this->output);
//            passthru("php artisan wemx:database --ansi");
        }

        $this->info('Configuring Crontab');
        $command = "* * * * * php {$path}/artisan schedule:run >> /dev/null 2>&1";
        $currentCronJobs = shell_exec('crontab -l');
        if (!str_contains($currentCronJobs, $command)) {
            shell_exec('(crontab -l; echo "' . $command . '") | crontab -');
        }

        $this->info('Configuring WebServer permission');
        Artisan::call("wemx:chown", [], $this->output);
//        shell_exec("php artisan wemx:chown");


        $url = $ssl ? 'https://' . rtrim($domain, '/') : 'http://' . rtrim($domain, '/');
        $this->info('Configuring is complete, go to the url below to continue:');
        $this->warn($url . '/install');
    }

    private function askRootPath(): string
    {
        $rootPath = $this->askWithCompletion('Please enter the root path to your Laravel project or press Enter to accept the default path:', [], base_path('public'));
        while (!is_dir($rootPath)) {
            $this->error('Invalid path. Please try again.');
            $rootPath = $this->askWithCompletion('Please enter the root path to your Laravel project or press Enter to accept the default path:', [], base_path('public'));
        }
        return $rootPath;
    }

    private function askDomain(): string
    {
        $domain = $this->ask('Please enter your domain without http:// or https:// (e.g., example.com)');
        while (!preg_match('/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$/', $domain)) {
            $this->error('Invalid domain. Please try again.');
            $domain = $this->ask('Please enter your domain without http:// or https:// (e.g., example.com)');
        }
        return $domain;
    }

}
