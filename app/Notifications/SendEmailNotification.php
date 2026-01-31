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
        $mail = (new MailMessage)
            ->subject($this->message['subject'])
            ->greeting($this->message['greeting'])
            ->line($this->message['body']);

        // Add specific details if available
        if (!empty($this->message['details']) && is_array($this->message['details'])) {
            foreach ($this->message['details'] as $label => $value) {
                $mail->line(new \Illuminate\Support\HtmlString("<strong>{$label}:</strong> {$value}"));
            }
        }

        if ($this->comment) {
            $mail->line(new \Illuminate\Support\HtmlString("<strong>" . __('field.note') . ":</strong> {$this->comment}"));
        }

        $mail->action($this->message['action']['name'], $this->message['action']['url'])
            ->line(__('mail.thanks'));

        if (!empty($this->cc)) {
            $mail->cc($this->cc);
        }

        return $mail;
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
