<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $message;
    public $comment; 
    public $cc;

    /**
     * Create a new notification instance.
     */
    public function __construct($message, ?string $comment = null, ?array $cc = null)
    {
        $this->message = $message;
        $this->comment = $comment;
        $this->cc = $cc;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        if(empty($this->cc)) {
            return (new MailMessage)
                    ->subject($this->message['subject'])
                    ->greeting($this->message['greeting'])
                    ->line($this->message['body'])
                    ->line($this->comment)
                    ->action($this->message['action']['name'], $this->message['action']['url'])
                    ->line(__('mail.thanks'));
        }

        return (new MailMessage)
                    ->cc($this->cc)
                    ->subject($this->message['subject'])
                    ->greeting($this->message['greeting'])
                    ->line($this->message['body'])
                    ->line($this->comment)
                    ->action($this->message['action']['name'], $this->message['action']['url'])
                    ->line(__('mail.thanks'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
