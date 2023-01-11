<?php

namespace Wemx\Installer\Commands;

use Illuminate\Console\Command;

class UninstallCommand extends Command
{

    protected $signature = 'billing:uninstall';
    protected $description = 'Removes the module and reinstalls the Pterodactyl panel';

    public function handle()
    {
        $this->uninstall();
    }

    private function uninstall()
    {
        if (!$this->confirm('Are you sure you want to continue with the uninstallation?')) {
            $this->warn('Billing Module was not uninstalled');

            return;
        }
        // Remove all files command
        $commands = [
            'rm -rf app/Models/Billing >/dev/null 2>&1 &',
            'rm -rf app/Models/Plugins >/dev/null 2>&1 &',
            'rm -rf app/Http/Controllers/Billing >/dev/null 2>&1 &',
            'rm -rf app/Http/Controllers/GMD >/dev/null 2>&1 &',
            'rm -rf app/Http/Controllers/Plugins >/dev/null 2>&1 &',
            'rm -rf app/Http/Middleware/GMD.php >/dev/null 2>&1 &',
            'rm -rf database/migrations/billing >/dev/null 2>&1 &',
            // 'rm -rf public/billing-src >/dev/null 2>&1 &',
            // 'rm -rf public/modules/register >/dev/null 2>&1 &',
            // 'rm -rf public/modules/plugins >/dev/null 2>&1 &',
            // 'rm -rf public/themes/corbon >/dev/null 2>&1 &',
            // 'rm -rf resources/views/templates/gmd >/dev/null 2>&1 &',
            // 'rm -rf resources/views/templates/Carbon >/dev/null 2>&1 &',
            'rm -rf routes/custom >/dev/null 2>&1 &',
            'rm -rf routes/gmd.php >/dev/null 2>&1 &',
        ];

        exec('php artisan down');
        exec('cd ' . base_path());
        exec('curl -L https://github.com/pterodactyl/panel/releases/latest/download/panel.tar.gz | tar -xzv');
        exec('chmod -R 755 storage/* bootstrap/cache');
        exec('echo \"yes\" | composer install --no-dev --optimize-autoloader');
        exec('php artisan view:clear && php artisan config:clear');
        exec('php artisan migrate --seed --force');

        exec('chown -R www-data:www-data ' . base_path() . '/*');
        exec('chown -R nginx:nginx ' . base_path() . '/*');
        exec('chown -R apache:apache ' . base_path() . '/*');
        foreach ($commands as $value) {
            exec($value);
        }
        exec('php artisan queue:restart');
        exec('php artisan up');

        $this->info('Uninstalled the Billing Module and installed a fresh new Pterodactyl Panel');
    }
}
