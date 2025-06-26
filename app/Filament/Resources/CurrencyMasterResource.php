<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurrencyMasterResource\Pages;
use App\Filament\Resources\CurrencyMasterResource\RelationManagers;
use App\Models\CurrencyMaster;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CurrencyMasterResource extends Resource
{
    protected static ?string $model = CurrencyMaster::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('symbol')
                    ->required()
                    ->maxLength(10),
                Forms\Components\TextInput::make('exchange_rate_to_inr')
                    ->required()
                    ->numeric()
                    ->step('0.000001'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('symbol')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('exchange_rate_to_inr')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCurrencyMasters::route('/'),
        ];
    }
}
