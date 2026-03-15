<?php

namespace App\Filament\Resources;

use Tapp\FilamentAuthenticationLog\Resources\AuthenticationLogResource as BaseResource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;
use Tapp\FilamentAuthenticationLog\FilamentAuthenticationLogPlugin;

class AuthenticationLogResource extends BaseResource
{
    protected static \UnitEnum | string | null $navigationGroup = 'Audit Hub';

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->columns([
                TextColumn::make('authenticatable')
                    ->label(trans('filament-authentication-log::filament-authentication-log.column.authenticatable'))
                    ->formatStateUsing(function (?string $state, Model $record) {
                        $authenticatableFieldToDisplay = config('filament-authentication-log.authenticatable.field-to-display');

                        $authenticatableDisplay = $authenticatableFieldToDisplay !== null ? $record->authenticatable->{$authenticatableFieldToDisplay} : class_basename($record->authenticatable::class);

                        if (! $record->authenticatable_id) {
                            return new HtmlString('&mdash;');
                        }

                        $authenticableEditRoute = '#';

                        /** @var FilamentAuthenticationLogPlugin $plugin */
                        $plugin = FilamentAuthenticationLogPlugin::get();
                        $routeName = 'filament.'.$plugin->getPanelName().'.resources.'.Str::plural((Str::lower(class_basename($record->authenticatable::class)))).'.edit';

                        if (Route::has($routeName)) {
                            $authenticableEditRoute = route($routeName, ['record' => $record->authenticatable_id]);
                        } elseif (config('filament-authentication-log.user-resource')) {
                            $authenticableEditRoute = self::getCustomUserRoute($record);
                        }

                        return new HtmlString('<a href="'.$authenticableEditRoute.'" class="inline-flex items-center justify-center text-sm font-medium hover:underline focus:outline-none focus:underline filament-tables-link text-primary-600 hover:text-primary-500 filament-tables-link-action">'.$authenticatableDisplay.'</a>');
                    })
                    ->sortable(['authenticatable_id']),
                TextColumn::make('ip_address')
                    ->label(trans('filament-authentication-log::filament-authentication-log.column.ip_address'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user_agent')
                    ->label(trans('filament-authentication-log::filament-authentication-log.column.user_agent'))
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(fn ($state) => $state),
                TextColumn::make('login_at')
                    ->label(trans('filament-authentication-log::filament-authentication-log.column.login_at'))
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('login_successful')
                    ->label(trans('filament-authentication-log::filament-authentication-log.column.login_successful'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('logout_at')
                    ->label(trans('filament-authentication-log::filament-authentication-log.column.logout_at'))
                    ->dateTime()
                    ->sortable(),
                // "Cleared by user" column removed as there is no option to clear logs manually
            ]);
    }
}
