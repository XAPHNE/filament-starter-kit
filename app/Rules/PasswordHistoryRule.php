<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class PasswordHistoryRule implements ValidationRule
{
    public function __construct(protected ?\App\Models\User $user = null)
    {
        $this->user = $user ?? auth()->user();
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->user) {
            return;
        }

        $limit = (int) \App\Models\Setting::where('key', 'password_history_limit')->first()?->value ?? 0;

        if ($limit <= 0) {
            return;
        }

        $history = $this->user->passwordHistories()
            ->latest()
            ->take($limit)
            ->get();

        foreach ($history as $entry) {
            if (\Illuminate\Support\Facades\Hash::check($value, $entry->password)) {
                $fail("You cannot reuse any of your last {$limit} passwords.");
                return;
            }
        }
    }
}
