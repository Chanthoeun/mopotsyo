<?php

namespace App\Traits;

use App\Notifications\SendEmailNotification;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

trait SendNotification
{
    public function sendNotification($reciever, $message, $comment = null, $cc = null)
    {        
        Notification::make()
            ->success()
            ->icon('fas-user-clock')
            ->iconColor('success')
            ->title($message['subject'])
            ->body($message['body'])
            ->actions([               
                Action::make('view')
                    ->label($message['action']['name'])
                    ->button()
                    ->url($message['action']['url']) 
                    ->icon('fas-eye')                   
                    ->markAsRead(),
            ])
            ->sendToDatabase($reciever);
        
        $reciever->notify(new SendEmailNotification($message, $comment, $cc));
    }
}
