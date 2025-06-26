<?php

namespace App\Filament\Resources\CurrencyMasterResource\Pages;

use App\Filament\Resources\CurrencyMasterResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCurrencyMasters extends ManageRecords
{
    protected static string $resource = CurrencyMasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
