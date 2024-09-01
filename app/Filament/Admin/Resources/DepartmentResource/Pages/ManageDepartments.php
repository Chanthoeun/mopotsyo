<?php

namespace App\Filament\Admin\Resources\DepartmentResource\Pages;

use App\Filament\Admin\Resources\DepartmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageDepartments extends ManageRecords
{
    use ManageRecords\Concerns\Translatable;

    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('btn.label.new', ['label' => __('model.department')]))
                ->color('primary')
                ->icon('heroicon-o-plus'),
            Actions\LocaleSwitcher::make(),
        ];
    }
}
