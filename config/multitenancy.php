<?php

use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Queue\CallQueuedClosure;
use Spatie\Multitenancy\Actions\ForgetCurrentTenantAction;
use Spatie\Multitenancy\Actions\MakeQueueTenantAwareAction;
use Spatie\Multitenancy\Actions\MakeTenantCurrentAction;
use Spatie\Multitenancy\Actions\MigrateTenantAction;
use Spatie\Multitenancy\Models\Tenant;

return [
    /*
     * This class is responsible for determining which tenant should be current
     * for the given request.
     *
     * This class should extend `Spatie\Multitenancy\TenantFinder\TenantFinder`
     *
     */
    'tenant_finder' => Spatie\Multitenancy\TenantFinder\DomainTenantFinder::class,

    /*
     * These fields are used by tenant:artisan command to match one or more tenant.
     */
    'tenant_artisan_search_fields' => [
        'name',
    ],

    /*
     * These tasks will be performed when switching tenants.
     *
     * A valid task is any class that implements Spatie\Multitenancy\Tasks\SwitchTenantTask
     */
    'switch_tenant_tasks' => [
        // \Spatie\Multitenancy\Tasks\PrefixCacheTask::class,
        Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask::class,
        // \Spatie\Multitenancy\Tasks\SwitchRouteCacheTask::class,
    ],

    /*
     * This class is the model used for storing configuration on tenants.
     *
     * It must be or extend `Spatie\Multitenancy\Models\Tenant::class`
     */
    'tenant_model' => Tenant::class,

    /*
     * If there is a current tenant when dispatching a job, the id of the current tenant
     * will be automatically set on the job. When the job is executed, the set
     * tenant on the job will be made current.
     */
    'queues_are_tenant_aware_by_default' => true,

    /*
     * The connection name to reach the tenant database.
     *
     * Set to `null` to use the default connection.
     */
    'tenant_database_connection_name' => null,

    /*
     * The connection name to reach the landlord database.
     */
    'landlord_database_connection_name' => 'landlord',

    /*
     * This key will be used to bind the current tenant in the container.
     */
    'current_tenant_container_key' => 'currentTenant',

    /**
     * Set it to `true` if you like to cache the tenant(s) routes
     * in a shared file using the `SwitchRouteCacheTask`.
     */
    'shared_routes_cache' => false,

    /*
     * You can customize some of the behavior of this package by using your own custom action.
     * Your custom action should always extend the default one.
     */
    'actions' => [
        'make_tenant_current_action' => MakeTenantCurrentAction::class,
        'forget_current_tenant_action' => ForgetCurrentTenantAction::class,
        'make_queue_tenant_aware_action' => MakeQueueTenantAwareAction::class,
        'migrate_tenant' => MigrateTenantAction::class,
    ],

    /*
     * You can customize the way in which the package resolves the queueable to a job.
     *
     * For example, using the package laravel-actions (by Loris Leiva), you can
     * resolve JobDecorator to getAction() like so: JobDecorator::class => 'getAction'
     */
    'queueable_to_job' => [
        SendQueuedMailable::class => 'mailable',
        SendQueuedNotifications::class => 'notification',
        CallQueuedClosure::class => 'closure',
        CallQueuedListener::class => 'class',
        BroadcastEvent::class => 'event',
    ],

    /*
     * Jobs tenant aware even if these don't implement the TenantAware interface.
     */
    'tenant_aware_jobs' => [
        // ...
    ],

    /*
     * Jobs not tenant aware even if these don't implement the NotTenantAware interface.
     */
    'not_tenant_aware_jobs' => [
        // ...
    ],

    /**
     * Filesystem multitenancy config.
     */
    'filesystem' => [
        /**
         * Each disk listed in the 'disks' array will be overridden.
         */
        'disks' => [
            'public',
            'profile',
            'settings',
            'private_settings',
            'web_services',
            'tmp',
            'samlidp',
            'decision_tables',
        ],
        /**
         * Use this for local disks.
         */
        'overwrite' => [
            'public' => [
                'root' => 'app/public/%tenant%',
                'url' => '/storage/%tenant%',
            ],
            'profile' => [
                'root' => 'app/public/%tenant%/profile',
                'url' => '/storage/%tenant%/profile',
            ],
            'settings' => [
                'root' => 'app/public/%tenant%/setting',
                'url' => '/storage/%tenant%/setting',
            ],

            'private_settings' => [
                'root' => 'app/private/%tenant%/settings',
            ],

            'web_services' => [
                'root' => 'app/private/%tenant%/web_services',
            ],

            'tmp' => [
                'root' => 'app/public/%tenant%/tmp',
                'url' => '/storage/%tenant%/tmp',
            ],

            'samlidp' => [
                'root' => '/%tenant%/samlidp',
            ],

            'decision_tables' => [
                'root' => '%tenant%/decision-tables',
                'url' => '/storage/%tenant%/decision-tables',
            ],
        ],

        /**
         * Should storage_path() be suffixed.
         *
         * Note: Disabling this will likely break local disk tenancy. Only disable this if you're using an external file storage service like S3.
         *
         * For the vast majority of applications, this feature should be enabled. But in some
         * edge cases, it can cause issues (like using Passport with Vapor - see #196), so
         * you may want to disable this if you are experiencing these edge case issues.
         */
        'suffix_storage_path' => true,

        /**
         * By default, asset() calls are made multi-tenant too. You can use global_asset() and mix()
         * for global, non-tenant-specific assets. However, you might have some issues when using
         * packages that use asset() calls inside the tenant app. To avoid such issues, you can
         * disable asset() helper tenancy and explicitly use tenant_asset() calls in places
         * where you want to use tenant-specific assets (product images, avatars, etc).
         */
        'asset_helper_tenancy' => true,
    ],

    'database_prefix' => env('MULTITENANCY_DATABASE_PREFIX'),

    'tenant_domain' => env('MULTITENANCY_TENANT_DOMAIN', '.processmaker.io'),

    'tenant_table_name' => env('MULTITENANCY_TENANT_TABLE_NAME'),

    'tenant_relation_id' => env('MULTITENANCY_TENANT_RELATION_ID'),

    'stm_enabled' => env('MULTITENANCY_STM_ENABLED', false),
];
