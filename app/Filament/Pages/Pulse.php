<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use BackedEnum;
use UnitEnum;

class Pulse extends Page
{
    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'System Pulse';

    protected static ?string $title = 'System Pulse';

    protected static UnitEnum | string | null $navigationGroup = 'Settings';

    protected string $view = 'filament.pages.pulse';

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    public static function canAccess(): bool
    {
        return \Illuminate\Support\Facades\Gate::allows('viewPulse');
    }
}
