<?php

namespace App\Filament\Admin\Resources\ProcessApprovalRuleResource\Pages;

use App\Filament\Admin\Resources\ProcessApprovalRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProcessApprovalRules extends ListRecords
{
    use ListRecords\Concerns\Translatable;

    protected static string $resource = ProcessApprovalRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('btn.label.new', ['label' => __('model.process_approval_rule')]))
                ->color('primary')
                ->icon('heroicon-o-plus'),
            Actions\LocaleSwitcher::make(),
        ];
    }
}
