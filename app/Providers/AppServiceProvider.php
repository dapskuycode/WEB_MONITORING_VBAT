<?php

namespace App\Providers;

use App\Http\Middleware\EnsureUserHasRole;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::addPersistentMiddleware([EnsureUserHasRole::class]);
        RateLimiter::for('lms-quiz-read', fn ($r) => Limit::perMinute(120)->by('quiz-read:'.$r->user()->id));
        RateLimiter::for('lms-quiz-write', fn ($r) => Limit::perMinute(30)->by('quiz-write:'.$r->user()->id));
        RateLimiter::for('lms-quiz-save', fn ($r) => Limit::perMinute(60)->by('quiz-save:'.$r->user()->id.':'.$r->route('id')));
        RateLimiter::for('lms-import', fn ($r) => Limit::perMinute(10)->by('lms-import:'.$r->user()->id));
        RateLimiter::for('lms-upload', fn ($r) => Limit::perMinute(10)->by('lms-upload:'.$r->user()->id));
        RateLimiter::for('lms-reading', fn ($r) => Limit::perMinute(60)->by('lms-reading:'.$r->user()->id));
        RateLimiter::for('lms-assignment-save', fn ($r) => Limit::perMinute(60)->by('lms-assignment-save:'.$r->user()->id.':'.$r->route('id')));
        RateLimiter::for('lms-assignment-submit', fn ($r) => Limit::perMinute(15)->by('lms-assignment-submit:'.$r->user()->id.':'.$r->route('id')));
        $this->configureDefaults();
        $this->configureGates();
    }

    /**
     * Register UI authorization gates (route-level security stays in middleware).
     */
    protected function configureGates(): void
    {
        Gate::define('access-admin', fn (User $user): bool => $user->isSuperAdmin() || (method_exists($user, 'isOwner') && $user->isOwner()));

        Gate::define('access-lms-admin', fn (User $user): bool => $user->isLmsManager());

        Gate::define('access-sponsor-portal', fn (User $user): bool => $user->isSponsor() || $user->isSuperAdmin() || (method_exists($user, 'isOwner') && $user->isOwner()));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        if ($appUrl = config('app.url')) {
            if (! app()->runningUnitTests()) {
                URL::forceRootUrl($appUrl);
                if (str_starts_with($appUrl, 'https://')) {
                    URL::forceScheme('https');
                }
            }
        }

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
