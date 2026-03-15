<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Password;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function set(string $key, $value)
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    public static function getPasswordRules(): Password
    {
        $min = (int) static::where('key', 'min_password_length')->first()?->value ?? 8;
        
        $rule = Password::min($min);

        if ((bool) static::where('key', 'require_uppercase')->first()?->value && 
            (bool) static::where('key', 'require_lowercase')->first()?->value) {
            $rule->mixedCase();
        } elseif ((bool) static::where('key', 'require_uppercase')->first()?->value || 
                  (bool) static::where('key', 'require_lowercase')->first()?->value) {
            $rule->letters();
        }

        if ((bool) static::where('key', 'require_number')->first()?->value) {
            $rule->numbers();
        }

        if ((bool) static::where('key', 'require_special_characters')->first()?->value) {
            $list = static::where('key', 'special_characters_list')->first()?->value;
            
            if ($list) {
                // Use custom regex for specific special characters
                $rule->rules(['regex:/[' . preg_quote($list, '/') . ']/']);
            } else {
                $rule->symbols();
            }
        }

        return $rule;
    }
}
