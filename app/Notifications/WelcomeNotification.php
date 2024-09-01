<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $password;

    /**
     * Create a new notification instance.
     */
    public function __construct($password)
    {
        $this->password = $password;
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
        $appName = config('app.name');
        $user = User::where('email', $notifiable->email)->first();
        return (new MailMessage)
                    ->subject("Welcome to {$appName}!")
                    ->greeting("Hello {$user->name},")
                    ->line("Your account has been created on {$appName} platform.")
                    ->line("Here are your login details:")
                    ->line(new HtmlString("<strong>Email</strong> : {$notifiable->email}"))
                    ->line(new HtmlString("<strong>Temporary password</strong> : {$this->password}"))
                    ->line("You will be prompted to change this temporary password at your next login.")
                    ->action('Go to app', url('/'));
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
