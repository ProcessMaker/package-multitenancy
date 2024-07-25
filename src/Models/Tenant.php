<?php

namespace Spatie\Multitenancy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Actions\ForgetCurrentTenantAction;
use Spatie\Multitenancy\Actions\MakeTenantCurrentAction;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;
use Spatie\Multitenancy\TenantCollection;

class Tenant extends Model
{
    use UsesLandlordConnection;
    use HasFactory;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (! empty(config('multitenancy.tenant_table_name'))) {
            $this->table = config('multitenancy.tenant_table_name');
        }
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function makeCurrent(): static
    {
        if ($this->isCurrent()) {
            return $this;
        }

        static::forgetCurrent();

        $this
            ->getMultitenancyActionClass(
                actionName: 'make_tenant_current_action',
                actionClass: MakeTenantCurrentAction::class
            )
            ->execute($this);

        return $this;
    }

    public function forget(): static
    {
        $this
            ->getMultitenancyActionClass(
                actionName: 'forget_current_tenant_action',
                actionClass: ForgetCurrentTenantAction::class
            )
            ->execute($this);

        return $this;
    }

    public static function current(): ?static
    {
        $containerKey = config('multitenancy.current_tenant_container_key');

        if (! app()->has($containerKey)) {
            return null;
        }

        return app($containerKey);
    }

    public static function checkCurrent(): bool
    {
        return static::current() !== null;
    }

    public function isCurrent(): bool
    {
        return static::current()?->getKey() === $this->getKey();
    }

    public static function forgetCurrent(): ?Tenant
    {
        $currentTenant = static::current();

        if (is_null($currentTenant)) {
            return null;
        }

        $currentTenant->forget();

        return $currentTenant;
    }

    public function getDatabaseName(): string
    {
        return config('multitenancy.database_prefix') . $this->name;
    }

    public function newCollection(array $models = []): TenantCollection
    {
        return new TenantCollection($models);
    }

    public function execute(callable $callable)
    {
        $originalCurrentTenant = Tenant::current();

        $this->makeCurrent();

        return tap($callable($this), static function () use ($originalCurrentTenant) {
            $originalCurrentTenant
                ? $originalCurrentTenant->makeCurrent()
                : Tenant::forgetCurrent();
        });
    }

    public function callback(callable $callable): \Closure
    {
        return fn () => $this->execute($callable);
    }
}
