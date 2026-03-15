<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\UserSession;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class EnforceConcurrentSessions
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // 1. Fetch relevant settings
            $settings = Setting::whereIn('key', [
                'session_timeout',
                'logout_on_browser_close',
                'enable_concurrent_sessions',
                'default_concurrent_sessions',
                'enable_tier_based_concurrency'
            ])->pluck('value', 'key');

            // 2. Enforce "Logout on Browser Close": If enabled, we must not allow "Remember Me" sessions to persist.
            if (Auth::viaRemember() && filter_var($settings->get('logout_on_browser_close', 'false'), FILTER_VALIDATE_BOOLEAN)) {
                Auth::logout();
                session()->invalidate();
                session()->regenerateToken();
                return redirect()->route('filament.admin.auth.login')->with('status', 'Persistent login is disabled when "Logout on Browser Close" is active.');
            }

            // 3. Session Tracking & Concurrency Enforcement
            if (!app()->runningUnitTests() && !app()->environment('testing')) {
                $sid = session()->getId();

                // Load current session record from our tracking table
                $curr = UserSession::where('session_id', $sid)->where('user_id', $user->id)->first();

                // Self-Healing: If record is missing, try to register it
                if (!$curr) {
                    // Calculate allowed sessions
                    $allow = $this->computeAllowedSessions($user, $settings);

                    // Get all active sessions for this user from tracking table
                    $sessions = UserSession::where('user_id', $user->id)
                        ->orderBy('last_activity', 'desc')
                        ->get();

                    // If at/over limit, we must evict some to make room for this new session
                    if ($sessions->count() >= $allow) {
                        // Keep only $allow - 1 most recent sessions
                        $toKeep = $sessions->take($allow - 1)->pluck('session_id')->toArray();

                        // Add current SID to the "keep" list just in case
                        $toKeep[] = $sid;

                        // Purge from custom tracking table
                        UserSession::where('user_id', $user->id)
                            ->whereNotIn('session_id', $toKeep)
                            ->delete();

                        // Purge from Laravel CORE sessions table if database driver is in use
                        if (config('session.driver') === 'database') {
                            DB::table(config('session.table', 'sessions'))
                                ->where('user_id', $user->id)
                                ->whereNotIn('id', $toKeep)
                                ->delete();
                        }
                    }

                    // Register the current session in tracking table
                    $curr = UserSession::create([
                        'session_id' => $sid,
                        'user_id' => $user->id,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->header('User-Agent'),
                        'last_activity' => now(),
                    ]);
                }

                // 4. Inactivity Enforcement (Custom Timeout)
                $timeoutMinutes = (int)$settings->get('session_timeout', 120);
                if ($curr->last_activity) {
                    $cutoff = now()->subMinutes($timeoutMinutes);
                    if ($curr->last_activity->lessThan($cutoff)) {
                        Auth::logout();
                        session()->invalidate();
                        session()->regenerateToken();
                        $curr->delete(); // Remove from tracking
                        return redirect()->route('filament.admin.auth.login')->with('status', 'Your session has been terminated due to inactivity.');
                    }
                }

                // 5. Update last activity in tracking table
                $curr->update([
                    'last_activity' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ]);
            }
        }

        return $next($request);
    }

    protected function computeAllowedSessions($user, $settings): int
    {
        $enabled = filter_var($settings->get('enable_concurrent_sessions', 'true'), FILTER_VALIDATE_BOOLEAN);
        if (!$enabled) {
            return 1;
        }

        $tierBased = filter_var($settings->get('enable_tier_based_concurrency', 'false'), FILTER_VALIDATE_BOOLEAN);
        if ($tierBased) {
            // Find the highest session limit from all tiers assigned to the user
            $max = $user->tiers()->max('concurrent_sessions');

            // If the user has assigned tiers with limits, use the maximum one
            if ($max !== null) {
                return (int)$max;
            }
        }

        // Standard global default
        return (int)$settings->get('default_concurrent_sessions', 1);
    }
}
