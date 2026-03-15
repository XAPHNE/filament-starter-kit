<?php

namespace App\Filament\Resources\ActivityLogs\Schemas;

use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;

class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('log_name')
                                    ->label('Log Type'),
                                TextEntry::make('description')
                                    ->label('Description'),
                                TextEntry::make('created_at')
                                    ->label('Logged At'),
                            ]),
                    ]),
                Section::make('Actor & Target')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('causer.name')
                                    ->label('Performed By'),
                                TextEntry::make('subject_type')
                                    ->label('Target'),
                            ]),
                    ]),
                Section::make('Changes')
                    ->schema([
                        TextEntry::make('properties')
                            ->label('Data Diffs')
                            ->formatStateUsing(fn ($state) => null)
                            ->belowContent(fn ($state) => empty($state) ? 'No changes recorded.' : json_encode($state, JSON_PRETTY_PRINT)),
                    ]),
            ]);
    }
}
