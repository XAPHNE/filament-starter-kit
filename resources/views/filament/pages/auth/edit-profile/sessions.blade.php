<div>
    <div class="space-y-6">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            {{ __('If necessary, you may log out of all of your other browser sessions across all of your devices. Some of your recent sessions are listed below; however, this list may not be exhaustive. If you feel your account has been compromised, you should also update your password.') }}
        </div>

        @php
            $sessions = \App\Models\UserSession::where('user_id', auth()->id())
                ->latest('last_activity')
                ->get();
        @endphp

        @if (count($sessions) > 0)
            <div class="mt-5 space-y-6">
                @foreach ($sessions as $session)
                    <div class="flex items-center">
                        <div class="p-2 bg-gray-100 dark:bg-gray-800 rounded-lg">
                            @if (str_contains($session->user_agent, 'Windows'))
                                <x-heroicon-o-computer-desktop class="h-8 w-8 text-gray-500" />
                            @elseif (str_contains($session->user_agent, 'iPhone') || str_contains($session->user_agent, 'Android'))
                                <x-heroicon-o-device-phone-mobile class="h-8 w-8 text-gray-500" />
                            @else
                                <x-heroicon-o-globe-alt class="h-8 w-8 text-gray-500" />
                            @endif
                        </div>

                        <div class="ms-3 flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $session->ip_address }}
                                @if ($session->session_id === session()->getId())
                                    <span class="text-emerald-500 font-semibold text-xs ml-2">{{ __('This device') }}</span>
                                @endif
                            </div>

                            <div class="mt-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-md">
                                    {{ $session->user_agent }}
                                </div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                    {{ __('Last active') }} {{ $session->last_activity->diffForHumans() }}
                                </div>
                            </div>
                        </div>

                        @if ($session->session_id !== session()->getId())
                            <x-filament::button
                                color="danger"
                                size="sm"
                                variant="ghost"
                                wire:click="logoutSession('{{ $session->id }}')"
                                wire:confirm="Are you sure you want to terminate this session?"
                            >
                                {{ __('Log out') }}
                            </x-filament::button>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-sm text-gray-500 italic">
                No active sessions found.
            </div>
        @endif

        <div class="flex items-center gap-4 mt-8 pt-6 border-t border-gray-100 dark:border-gray-800">
            <x-filament::button
                color="danger"
                variant="outline"
                wire:click="logoutOtherBrowserSessions"
                wire:confirm="Are you sure you want to log out of all other browser sessions?"
            >
                {{ __('Log out other browser sessions') }}
            </x-filament::button>
        </div>
    </div>
</div>
