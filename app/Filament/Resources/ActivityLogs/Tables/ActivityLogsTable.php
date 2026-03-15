<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('log_name')
                    ->badge()
                    ->sortable(),
                TextColumn::make('description')
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label('Target')
                    ->formatStateUsing(fn ($state, $record) => class_basename($state) . ' (#' . $record->subject_id . ')')
                    ->searchable(),
                TextColumn::make('causer.name')
                    ->label('User')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                //
            ]);
    }
}
