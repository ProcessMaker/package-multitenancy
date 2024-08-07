<?php

namespace Spatie\Multitenancy;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use Spatie\Multitenancy\Actions\MakeQueueTenantAwareAction;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Events\TenantNotFoundForRequestEvent;
use Spatie\Multitenancy\Models\Concerns\UsesTenantModel;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\TasksCollection;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class Multitenancy
{
    use UsesTenantModel;
    use UsesMultitenancyConfig;

    public function __construct(public Application $app)
    {
    }

    public function start(): void
    {
        $this
            ->registerTenantFinder()
            ->registerTasksCollection()
            ->configureRequests()
            ->configureQueue()
            ->configureMiddlewares()
            ->overwriteEnvironmentConfig()
            ->overwriteFilesystemConfig();
    }

    public function end(): void
    {
        Tenant::forgetCurrent();
    }

    protected function determineCurrentTenant(): void
    {
        if (!$this->app['config']->get('multitenancy.tenant_finder')) {
            return;
        }

        /** @var TenantFinder $tenantFinder */
        $tenantFinder = $this->app[TenantFinder::class];

        $tenant = $tenantFinder->findForRequest($this->app['request']);

        if ($tenant instanceof Tenant) {
            $tenant->makeCurrent();
        } else {
            event(new TenantNotFoundForRequestEvent($this->app['request']));
        }
    }

    protected function registerTasksCollection(): self
    {
        $this->app->singleton(TasksCollection::class, function () {
            $taskClassNames = $this->app['config']->get('multitenancy.switch_tenant_tasks');

            return new TasksCollection($taskClassNames);
        });

        return $this;
    }

    protected function registerTenantFinder(): self
    {
        $tenantFinderConfig = $this->app['config']->get('multitenancy.tenant_finder');

        if ($tenantFinderConfig) {
            $this->app->bind(TenantFinder::class, $tenantFinderConfig);
        }

        return $this;
    }

    protected function configureRequests(): self
    {
        if (!$this->app->runningInConsole()) {
            $this->determineCurrentTenant();
        }

        return $this;
    }

    protected function configureQueue(): self
    {
        $this
            ->getMultitenancyActionClass(
                actionName: 'make_queue_tenant_aware_action',
                actionClass: MakeQueueTenantAwareAction::class
            )
            ->execute();

        return $this;
    }

    protected function configureMiddlewares(): self
    {
        $tenancyMiddleware = [
            Http\Middleware\NeedsTenant::class,
            Http\Middleware\EnsureValidTenantSession::class,
        ];

        foreach ($tenancyMiddleware as $middleware) {
            $this->app[\Illuminate\Contracts\Http\Kernel::class]->appendMiddlewareToGroup('web', $middleware);
        }

        return $this;
    }

    public function overwriteEnvironmentConfig(): self
    {
        $tenant = Tenant::current();
        if ($tenant) {
            if ($this->app['config']->get('horizon.prefix')) {
                $this->app['config']->set('horizon.prefix', "hzn-{$tenant->name}:");
            }
            if ($this->app['config']->get('intercom.company')) {
                $this->app['config']->set('intercom.company', $tenant->name);
            }
            if ($this->app['config']->get('app.pm_analytics_dashboard')) {
                $this->app['config']->set('app.pm_analytics_dashboard', 'https://us-east-1.quicksight.aws.amazon.com/' . $tenant->name);
            }
            //TODO: We need to include the subdomain concept in the app.url and app.docker_host_url
            if ($this->app['config']->get('app.url')) {
                $this->app['config']->set('app.url', $tenant->domains->first()->name);
            }
            if ($this->app['config']->get('app.docker_host_url')) {
                $this->app['config']->set('app.docker_host_url', $tenant->domains->first()->name);
            }
        }

        return $this;
    }

    public function overwriteFilesystemConfig(): self
    {
        $originalPaths = [
            'disks' => [],
            'storage' => $this->app->storagePath(),
            'asset_url' => $this->app['config']['app.asset_url'],
        ];

        $tenant = Tenant::current();

        if ($tenant) {
            // Storage facade
            Storage::forgetDisk($this->app['config']['multitenancy.filesystem.disks']);

            foreach ($this->app['config']['multitenancy.filesystem.disks'] as $disk) {
                $originalRoot = $this->app['config']["filesystems.disks.{$disk}.root"];
                $originalUrl = $this->app['config']["filesystems.disks.{$disk}.url"];
                $originalPaths['disks'][$disk] = [
                    'root' =>$originalRoot,
                    'url' => $originalUrl,
                ];

                $rootOverwrite = str_replace(
                    '%tenant%',
                    $tenant->name,
                    $this->app['config']["multitenancy.filesystem.overwrite.{$disk}.root"] ?? '',
                );

                $urlOverwrite = str_replace(
                    '%tenant%',
                    $tenant->name,
                    $this->app['config']["multitenancy.filesystem.overwrite.{$disk}.url"] ?? '',
                );

                $this->app['config']["filesystems.disks.{$disk}.root"] = storage_path($rootOverwrite);

                if ((bool) $urlOverwrite) {
                    $this->app['config']["filesystems.disks.{$disk}.url"] = env('APP_URL') . $urlOverwrite;
                }
            }
        }

        return $this;
    }
}
