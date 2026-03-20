<?php

namespace App\Filament\Admin\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\File;
use ShuvroRoy\FilamentSpatieLaravelBackup\Pages\Backups as BaseBackups;

class CustomBackups extends BaseBackups
{
    protected static string $view = 'filament.admin.pages.custom-backups';

    protected function getActions(): array
    {
        return [
            Action::make('Create Backup')
                ->button()
                ->label('Backup Now')
                ->color('warning')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->action('openOptionModal'),

            Action::make('Restore SQL file')
                ->button()
                ->label('Restore SQL file')
                ->color('warning')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('sql_file')
                        ->label('Backup File (SQL or ZIP)')
                        ->acceptedFileTypes(['application/sql', 'application/x-sql', 'text/plain', 'application/zip', 'application/x-zip-compressed', 'application/x-compressed'])
                        ->disk('local')
                        ->directory('manual-restores')
                        ->required()
                        ->helperText('Select a downloaded .zip backup or raw .sql file. WARNING: This will overwrite the entire active database!')
                        ->preserveFilenames()
                ])
                ->action(function (array $data) {
                    $filePath = Storage::disk('local')->path($data['sql_file']);
                    
                    try {
                        $host = config('database.connections.mysql.host');
                        $user = config('database.connections.mysql.username');
                        $password = config('database.connections.mysql.password');
                        $database = config('database.connections.mysql.database');
                        $artisanPath = base_path('artisan');

                        if (preg_match('/\.zip$/i', $filePath)) {
                            $extractPath = storage_path('app/temp_restore_' . time());
                            $zip = new \ZipArchive;
                            if ($zip->open($filePath) === TRUE) {
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

                                if (empty($password)) {
                                    $mysqlCmd = "mysql --skip-ssl -h {$host} -u {$user} {$database} < {$sqlFile}";
                                } else {
                                    $mysqlCmd = "mysql --skip-ssl -h {$host} -u {$user} -p'{$password}' {$database} < {$sqlFile}";
                                }
                                
                                $command = "nohup bash -c 'sleep 1 && php {$artisanPath} down && sleep 1 && {$mysqlCmd} && rm -rf \"{$extractPath}\" && rm -f \"{$filePath}\" && php {$artisanPath} up' > /dev/null 2>&1 &";
                            } else {
                                throw new \Exception("Failed to open backup zip archive.");
                            }
                        } else {
                            if (empty($password)) {
                                $mysqlCmd = "mysql --skip-ssl -h {$host} -u {$user} {$database} < {$filePath}";
                            } else {
                                $mysqlCmd = "mysql --skip-ssl -h {$host} -u {$user} -p'{$password}' {$database} < {$filePath}";
                            }
                            
                            $command = "nohup bash -c 'sleep 1 && php {$artisanPath} down && sleep 1 && {$mysqlCmd} && rm -f \"{$filePath}\" && php {$artisanPath} up' > /dev/null 2>&1 &";
                        }
                        
                        Process::fromShellCommandline($command)->run();

                        $redirectUrl = \App\Filament\Admin\Pages\CustomBackups::getUrl();
                        return redirect('/restore-progress.html?redirect=' . urlencode($redirectUrl));

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Restore Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('Backup Schedule')
                ->button()
                ->label('Backup Schedule')
                ->color('info')
                ->icon('heroicon-o-calendar')
                ->action(function () {
                    Notification::make()
                        ->title('Backup Schedule')
                        ->body('This feature is coming soon or managed elsewhere.')
                        ->info()
                        ->send();
                }),
        ];
    }
}
