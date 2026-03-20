<?php

namespace App\Livewire;

use Filament\Tables\Table;
use Filament\Tables;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\File;
use ShuvroRoy\FilamentSpatieLaravelBackup\Components\BackupDestinationListRecords;
use ShuvroRoy\FilamentSpatieLaravelBackup\Models\BackupDestination;

class CustomBackupDestinationListRecords extends BackupDestinationListRecords
{
    public function table(Table $table): Table
    {
        // First get the parent table configuration
        $table = parent::table($table);

        // Then override the actions to include our custom logic
        return $table->actions([
            Tables\Actions\Action::make('download')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => auth()->user()->hasRole('super_admin'))
                ->url(fn (BackupDestination $record) => route('admin.backups.download', ['path' => $record->path, 'disk' => $record->disk])),

            Tables\Actions\Action::make('restore')
                ->label('Restore')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Restore Backup')
                ->modalDescription('Are you absolutely sure you want to restore this backup? This will completely overwrite your current active database with the contents of this backup zip. This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, restore database')
                ->visible(fn () => auth()->user()->hasRole('super_admin'))
                ->action(function (BackupDestination $record) {
                    $zipPath = Storage::disk($record->disk)->path($record->path);
                    $extractPath = storage_path('app/temp_restore_' . time());
                    
                    try {
                        $zip = new \ZipArchive;
                        if ($zip->open($zipPath) === TRUE) {
                            $zip->extractTo($extractPath);
                            $zip->close();
                            
                            $files = File::allFiles($extractPath);
                            $sqlFile = null;
                            foreach($files as $file) {
                                if($file->getExtension() === 'sql') {
                                    $sqlFile = $file->getRealPath();
                                    break;
                                }
                            }

                            if (!$sqlFile) {
                                throw new \Exception("No SQL file found inside the backup archive.");
                            }

                            $host = config('database.connections.mysql.host');
                            $user = config('database.connections.mysql.username');
                            $password = config('database.connections.mysql.password');
                            $database = config('database.connections.mysql.database');

                            if (empty($password)) {
                                $mysqlCmd = "mysql --skip-ssl -h {$host} -u {$user} {$database} < {$sqlFile}";
                            } else {
                                $mysqlCmd = "mysql --skip-ssl -h {$host} -u {$user} -p'{$password}' {$database} < {$sqlFile}";
                            }
                            
                            $artisanPath = base_path('artisan');
                            $command = "nohup bash -c 'sleep 1 && php {$artisanPath} down && sleep 1 && {$mysqlCmd} && rm -rf \"{$extractPath}\" && php {$artisanPath} up' > /dev/null 2>&1 &";
                            
                            Process::fromShellCommandline($command)->run();

                            $redirectUrl = \App\Filament\Admin\Pages\CustomBackups::getUrl();
                            return redirect('/restore-progress.html?redirect=' . urlencode($redirectUrl));
                        } else {
                            throw new \Exception("Failed to open backup zip archive.");
                        }
                    } catch (\Exception $e) {
                        if (File::exists($extractPath)) {
                            File::deleteDirectory($extractPath);
                        }
                        Notification::make()
                            ->title('Restore Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Tables\Actions\Action::make('delete')
                ->label('Delete')
                ->icon('heroicon-o-trash')
                ->visible(fn () => auth()->user()->hasRole('super_admin'))
                ->requiresConfirmation()
                ->color('danger')
                ->modalIcon('heroicon-o-trash')
                ->action(function (BackupDestination $record) {
                    \Spatie\Backup\BackupDestination\BackupDestination::create($record->disk, config('backup.backup.name'))
                        ->backups()
                        ->first(function (\Spatie\Backup\BackupDestination\Backup $backup) use ($record) {
                            return $backup->path() === $record->path;
                        })
                        ->delete();

                    Notification::make()
                        ->title(__('filament-spatie-backup::backup.pages.backups.messages.backup_delete_success'))
                        ->success()
                        ->send();
                }),
        ]);
    }
}
