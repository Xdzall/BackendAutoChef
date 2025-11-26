use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailBase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class VerifyEmail extends VerifyEmailBase
{
    protected function verificationUrl($notifiable)
    {
        $verifyUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
        );

        // Ganti domain ke domain aplikasi atau deep link
        return str_replace(
            config('app.url'),
            env('FRONTEND_URL', 'https://autochef.site'),
            $verifyUrl
        );
    }
}
