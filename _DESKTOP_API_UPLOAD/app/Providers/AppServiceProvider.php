<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->numbers();
        });

        try {
            \App\Support\SiteSettings::applyToConfig();
            \App\Support\FeatureFlags::unlockAdvancedOnce();
            \App\Support\SiteSettings::applyToConfig();
        } catch (\Throwable) {
            // Table may not exist yet during migrate/fresh install.
        }

        try {
            \App\Support\PatientFollowUps::ensureTables();
        } catch (\Throwable) {
            // Follow-up tables may not be creatable until migrate on a fresh install.
        }

        try {
            \App\Support\StaffNoteAlerts::ensureTables();
        } catch (\Throwable) {
            // Message inbox table may not be creatable until migrate on a fresh install.
        }
    }
}
