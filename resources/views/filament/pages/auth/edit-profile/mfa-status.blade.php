<div x-on:refresh-mfa-status.window="$wire.$refresh()">
    <div class="space-y-4">
        @php
            $user = auth()->user();
            $hasApp = filled($user->app_authentication_secret);
        @endphp

        <div class="flex items-center gap-3">
            @if ($hasApp)
                <x-filament::badge color="success" icon="heroicon-o-check-circle">
                    {{ __('App Enabled') }}
                </x-filament::badge>
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Your account is secured with an Authenticator App.') }}
                </span>
            @else
                <x-filament::badge color="gray" icon="heroicon-o-x-circle">
                    {{ __('App Not Configured') }}
                </x-filament::badge>
                <span class="text-sm text-gray-500 italic">
                    {{ __('You haven\'t set up an authenticator app yet.') }}
                </span>
            @endif
        </div>

        <div class="pt-2 flex gap-2">
            @if ($hasApp)
                <x-filament::button
                    color="gray"
                    variant="outline"
                    icon="heroicon-o-arrow-path"
                    tag="a"
                    href="{{ url('/admin/multi-factor-authentication/app/recovery-codes') }}"
                >
                    {{ __('View Recovery Codes') }}
                </x-filament::button>

                <x-filament::button
                    color="danger"
                    variant="ghost"
                    icon="heroicon-o-trash"
                    wire:click="disableAppMfa"
                    wire:confirm="Are you sure you want to disable your Authenticator App?"
                >
                    {{ __('Disable App MFA') }}
                </x-filament::button>
            @else
                <x-filament::button
                    color="primary"
                    icon="heroicon-o-device-phone-mobile"
                    tag="a"
                    href="{{ url('/admin/multi-factor-authentication/app/setup') }}"
                >
                    {{ __('Set Up Authenticator App') }}
                </x-filament::button>
            @endif
        </div>
    </div>
</div>
