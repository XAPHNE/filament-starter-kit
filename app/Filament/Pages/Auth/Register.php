<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Register as BaseRegister;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Components\Component;

class Register extends BaseRegister
{
    protected function handleRegistration(array $data): Model
    {
        $user = parent::handleRegistration($data);

        $defaultRoles = json_decode(Setting::where('key', 'default_roles')->first()?->value ?? '[]', true);

        if (! empty($defaultRoles)) {
            $user->assignRole($defaultRoles);
        }

        $defaultTierId = Setting::where('key', 'default_tier')->first()?->value;
        if ($defaultTierId && \App\Models\Tier::find($defaultTierId)) {
            $user->tiers()->attach($defaultTierId);
        }

        return $user;
    }

    protected function getPasswordFormComponent(): Component
    {
        $component = parent::getPasswordFormComponent();

        $component->rule(Setting::getPasswordRules());
        $component->maxLength((int) Setting::where('key', 'max_password_length')->first()?->value ?? 32);

        return $component;
    }
}
