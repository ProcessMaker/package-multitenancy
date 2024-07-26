<?php

namespace Spatie\Multitenancy\Commands;

use Artisan;
use ProcessMaker\Console\PackageInstallCommand;

class Install extends PackageInstallCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'package-multitenancy:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Multitenancy Package';

    /**
     * Publish assets
     * @return void
     */
    public function publishAssets()
    {
        if (! config('multitenancy.stm_enabled')) {
            $this->info('Publishing multitenancy-migrations');
            Artisan::call('vendor:publish', [
                '--provider' => 'Spatie\Multitenancy\MultitenancyServiceProvider',
                '--tag' => 'multitenancy-migrations',
            ]);
        }
    }

    public function preinstall()
    {
        $this->publishAssets();
    }

    public function install()
    {
    }

    public function postinstall()
    {
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        parent::handle();

        $this->info('Multitenancy package has been installed');
    }
}
