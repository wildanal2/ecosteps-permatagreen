<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class CustomVerifyEmail extends BaseVerifyEmail
{
    protected function buildMailMessage($url)
    {
        return (new MailMessage)
            ->subject('Verifikasi Email Anda - EcoSteps PermataGreen')
            ->greeting('Halo!')
            ->line('Terima kasih telah mendaftar di EcoSteps PermataGreen.')
            ->line('Silakan klik tombol di bawah ini untuk memverifikasi alamat email Anda.')
            ->action('Verifikasi Email', $url)
            ->line('Link verifikasi ini akan kedaluwarsa dalam 60 menit.')
            ->line('Jika Anda tidak membuat akun, abaikan email ini.')
            ->salutation('Salam,<br>Tim EcoSteps PermataGreen')
            ->with([
                'footerText' => '© ' . date('Y') . ' EcoSteps PermataGreen - Permata Bank. All rights reserved.',
            ]);
    }

    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
