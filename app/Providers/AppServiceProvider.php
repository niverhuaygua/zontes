<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Brands\CurrentBrand;
use App\Domain\Customers\CustomerRecord;
use App\Domain\Imports\CsvImport;
use App\Domain\Loyalty\PointRule;
use App\Models\Brand;
use App\Models\User;
use App\Policies\BrandPolicy;
use App\Policies\CsvImportPolicy;
use App\Policies\CustomerRecordPolicy;
use App\Policies\PointRulePolicy;
use App\Policies\UserPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CurrentBrand::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Factory::guessFactoryNamesUsing(
            static fn (string $modelName): string => 'Database\\Factories\\'.class_basename($modelName).'Factory',
        );

        Gate::policy(Brand::class, BrandPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(CustomerRecord::class, CustomerRecordPolicy::class);
        Gate::policy(CsvImport::class, CsvImportPolicy::class);
        Gate::policy(PointRule::class, PointRulePolicy::class);
    }
}
