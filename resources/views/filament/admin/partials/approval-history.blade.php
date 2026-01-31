<div class="space-y-2">
    {{-- Created By --}}
    <div
        class="flex items-center justify-between p-3 border rounded-lg bg-gray-50 dark:bg-gray-800 dark:border-gray-700 border-l-4 border-l-blue-500">
        <div class="flex flex-col">
            <span class="font-medium text-sm text-gray-900 dark:text-gray-100">
                Request Created
            </span>
            <span class="text-sm text-gray-600 dark:text-gray-400">
                by {{ $record->user->name ?? 'Unknown' }}
            </span>
        </div>
        <div class="flex flex-col items-end gap-1">
            <x-filament::badge color="gray">
                Created
            </x-filament::badge>
            <span class="text-xs text-gray-400" title="{{ $record->created_at }}">
                {{ $record->created_at->format('d M Y H:i') }}
            </span>
        </div>
    </div>

    @forelse($record->approvalSteps->sortBy('level') as $step)
        <div
            class="flex items-center justify-between p-3 border rounded-lg bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
            <div class="flex flex-col">
                <span class="font-medium text-sm text-gray-900 dark:text-gray-100">
                    {{ str($step->role->name ?? 'Approver')->replace('_', ' ')->title() }}
                    <span class="text-xs text-gray-500 ml-1">(Level {{ $step->level }})</span>
                </span>
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $step->approver->name ?? 'Unassigned' }}
                </span>
            </div>

            <div class="flex flex-col items-end gap-1">
                @php
                    $statusColor = match ($step->status->value) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'pending' => 'warning',
                        'waiting' => 'gray',
                        'discarded' => 'danger',
                        default => 'primary',
                    };
                @endphp

                <x-filament::badge :color="$statusColor">
                    {{ str($step->status->value)->headline() }}
                </x-filament::badge>

                @if($step->status->value !== 'waiting' && $step->status->value !== 'pending')
                    <span class="text-xs text-gray-400" title="{{ $step->updated_at }}">
                        {{ $step->updated_at->format('d M Y H:i') }}
                    </span>
                @endif
            </div>
        </div>
    @empty
        <div class="text-center text-gray-500 py-4">
            No approval steps found.
        </div>
    @endforelse
</div>